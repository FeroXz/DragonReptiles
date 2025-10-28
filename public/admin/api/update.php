<?php
declare(strict_types=1);

session_start();

$projectRoot = dirname(__DIR__, 3);
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', $projectRoot);
}

require_once PROJECT_ROOT . '/app/config.php';
require_once PROJECT_ROOT . '/app/helpers.php';

header('Cache-Control: no-store, no-cache, must-revalidate');

function respond(array $payload, int $status = 200): void
{
    if (!isset($payload['csrf'])) {
        $payload['csrf'] = csrf_token();
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function require_admin(): void
{
    $user = current_user();
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        respond(['error' => 'Nicht autorisiert.'], 403);
    }
}

function require_csrf_header(): void
{
    $header = $_SERVER['HTTP_X_CSRF']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? null;

    if (!verify_csrf_token($header)) {
        respond(['error' => 'CSRF-Überprüfung fehlgeschlagen.'], 419);
    }
}

function update_storage_path(): string
{
    $path = PROJECT_ROOT . '/storage/update';
    ensure_directory($path);
    return $path;
}

function status_file(): string
{
    return update_storage_path() . '/job.status.json';
}

function pid_file(): string
{
    return update_storage_path() . '/job.pid';
}

function log_file(): string
{
    return update_storage_path() . '/job.log';
}

function read_status(): array
{
    $file = status_file();
    if (!is_file($file)) {
        return [];
    }

    $content = file_get_contents($file);
    if ($content === false) {
        return [];
    }

    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
}

function write_status(array $status): void
{
    $status['updated_at'] = date('c');
    $json = json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return;
    }

    file_put_contents(status_file(), $json);
}

function current_pid(): ?int
{
    $file = pid_file();
    if (!is_file($file)) {
        return null;
    }
    $content = trim((string) file_get_contents($file));
    if ($content === '') {
        return null;
    }
    $pid = (int) $content;
    return $pid > 0 ? $pid : null;
}

function is_process_running(?int $pid): bool
{
    if (!$pid) {
        return false;
    }

    if (function_exists('posix_kill')) {
        return @posix_kill($pid, 0);
    }

    $result = [];
    $exit = 1;
    exec('ps -p ' . escapeshellarg((string) $pid) . ' -o pid=', $result, $exit);
    return $exit === 0 && !empty($result);
}

function tail_lines(string $file, int $lines = 60): array
{
    if (!is_file($file) || $lines <= 0) {
        return [];
    }

    $fp = fopen($file, 'rb');
    if (!$fp) {
        return [];
    }

    $chunkSize = 4096;
    $buffer = '';
    $lineCount = 0;

    fseek($fp, 0, SEEK_END);
    $position = ftell($fp);

    while ($position > 0 && $lineCount <= $lines) {
        $seek = max($position - $chunkSize, 0);
        $read = $position - $seek;
        fseek($fp, $seek);
        $chunk = fread($fp, $read);
        if ($chunk === false) {
            break;
        }
        $buffer = $chunk . $buffer;
        $lineCount += substr_count($chunk, "\n");
        $position = $seek;
    }

    fclose($fp);

    $buffer = str_replace(["\r\n", "\r"], "\n", $buffer);
    $allLines = explode("\n", $buffer);
    if (end($allLines) === '') {
        array_pop($allLines);
    }

    return array_values(array_slice($allLines, -$lines));
}

function detect_exit_code(string $file): ?int
{
    $tail = tail_lines($file, 120);
    for ($i = count($tail) - 1; $i >= 0; $i--) {
        if (preg_match('/__EXIT_CODE:(-?\d+)/', $tail[$i], $matches)) {
            return (int) $matches[1];
        }
    }
    return null;
}

function stop_process(int $pid, int $signal): void
{
    if (function_exists('posix_kill')) {
        @posix_kill($pid, $signal);
        return;
    }
    exec(sprintf('kill -%d %d 2>/dev/null', $signal, $pid));
}

function start_process(array $env): int
{
    $logFile = log_file();
    $script = PROJECT_ROOT . '/scripts/deploy_latest_pr.sh';
    $commandEnv = [];
    $path = getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
    $env['PATH'] = $path;

    foreach ($env as $key => $value) {
        if ($value === null) {
            continue;
        }
        $commandEnv[] = escapeshellarg($key . '=' . (string) $value);
    }

    $envPrefix = 'env -i ' . implode(' ', $commandEnv);
    $command = sprintf(
        'nohup %s bash %s >> %s 2>&1 & echo $!',
        $envPrefix,
        escapeshellarg($script),
        escapeshellarg($logFile)
    );

    if (function_exists('proc_open')) {
        $descriptor = [
            0 => ['pipe', 'w'],
            1 => ['pipe', 'r'],
            2 => ['pipe', 'r'],
        ];
        $process = proc_open('bash -lc ' . escapeshellarg($command), $descriptor, $pipes, PROJECT_ROOT, null);
        if (is_resource($process)) {
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    stream_set_blocking($pipe, true);
                }
            }
            $output = trim(stream_get_contents($pipes[1]));
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_close($process);
            $pid = (int) $output;
            if ($pid > 0) {
                return $pid;
            }
        }
    }

    $output = [];
    $exitCode = 0;
    exec('bash -lc ' . escapeshellarg($command), $output, $exitCode);
    $pid = (int) trim($output[0] ?? '0');
    if ($exitCode !== 0 || $pid <= 0) {
        throw new RuntimeException('Deploy-Skript konnte nicht gestartet werden.');
    }

    return $pid;
}

function load_deploy_config(): array
{
    $path = PROJECT_ROOT . '/config/deploy.php';
    if (!is_file($path)) {
        return [];
    }
    $config = include $path;
    return is_array($config) ? $config : [];
}

require_admin();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';
$logFile = log_file();
$pidFile = pid_file();
$status = read_status();
$pid = current_pid();
$isRunning = is_process_running($pid);

if (!$isRunning && $pid) {
    @unlink($pidFile);
}

switch ($action) {
    case 'start':
        if ($method !== 'POST') {
            respond(['error' => 'Methode nicht erlaubt.'], 405);
        }
        require_csrf_header();

        if ($isRunning) {
            respond([
                'error' => 'Es läuft bereits ein Update.',
                'status' => $status,
            ], 409);
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw ?: '[]', true) ?: [];
        $dryRun = $payload['dry_run'] ?? null;
        $prNumber = $payload['pr'] ?? null;
        $dryRun = $dryRun === null ? null : (bool) $dryRun;
        if ($prNumber !== null) {
            if (!is_numeric($prNumber)) {
                respond(['error' => 'Ungültige Pull-Request-Nummer.'], 422);
            }
            $prNumber = (int) $prNumber;
        }

        $config = load_deploy_config();
        $defaultDryRun = (bool) ($config['default_dry_run'] ?? false);
        $dryRunValue = $dryRun === null ? $defaultDryRun : $dryRun;

        ensure_directory(update_storage_path());
        file_put_contents($logFile, "\n==== Deploy gestartet am " . date('c') . " ====\n", FILE_APPEND);

        $env = [
            'GITHUB_OWNER' => (string) ($config['github_owner'] ?? ''),
            'GITHUB_REPO' => (string) ($config['github_repo'] ?? ''),
            'SFTP_HOST' => (string) ($config['sftp_host'] ?? ''),
            'SFTP_PORT' => (string) ($config['sftp_port'] ?? 22),
            'SFTP_USER' => (string) ($config['sftp_user'] ?? ''),
            'SFTP_PASS' => (string) ($config['sftp_pass'] ?? ''),
            'SFTP_KEY_BASE64' => (string) ($config['sftp_key_b64'] ?? ''),
            'DEPLOY_TARGET_DIR' => (string) ($config['target_dir'] ?? '/cms'),
            'DRY_RUN' => $dryRunValue ? '1' : '0',
        ];

        if ($prNumber !== null) {
            $env['PR_NUMBER'] = (string) $prNumber;
        }

        $pid = start_process($env);
        file_put_contents($pidFile, (string) $pid);

        $status = [
            'state' => 'running',
            'started_at' => date('c'),
            'dry_run' => $dryRunValue,
            'pr' => $prNumber,
            'pid' => $pid,
        ];
        write_status($status);

        respond([
            'status' => $status,
            'message' => 'Update gestartet.',
        ], 202);

    case 'status':
        if ($method !== 'GET') {
            respond(['error' => 'Methode nicht erlaubt.'], 405);
        }

        $status = read_status();
        $pid = current_pid();
        $running = is_process_running($pid);

        if (!$running && ($status['state'] ?? '') === 'running') {
            $exitCode = detect_exit_code($logFile);
            if ($exitCode !== null) {
                $status['exit_code'] = $exitCode;
                $status['state'] = $exitCode === 0 ? 'success' : 'failed';
                $status['finished_at'] = date('c');
                write_status($status);
            }
        }

        $tail = tail_lines($logFile, 60);
        respond([
            'running' => $running,
            'pid' => $running ? $pid : null,
            'status' => $status,
            'tail' => $tail,
        ]);

    case 'cancel':
        if ($method !== 'POST') {
            respond(['error' => 'Methode nicht erlaubt.'], 405);
        }
        require_csrf_header();

        $pid = current_pid();
        if (!$pid || !is_process_running($pid)) {
            respond(['error' => 'Kein laufender Job.'], 409);
        }

        stop_process($pid, SIGTERM);
        $waited = 0;
        while ($waited < 5) {
            if (!is_process_running($pid)) {
                break;
            }
            usleep(500000);
            $waited += 0.5;
        }
        if (is_process_running($pid)) {
            stop_process($pid, SIGKILL);
        }

        @unlink($pidFile);
        $status = read_status();
        $status['state'] = 'canceled';
        $status['finished_at'] = date('c');
        write_status($status);

        file_put_contents($logFile, "\n[WARN] Deploy wurde manuell abgebrochen.\n", FILE_APPEND);

        respond([
            'message' => 'Deploy abgebrochen.',
            'status' => $status,
        ]);

    case 'log':
        if ($method !== 'GET') {
            respond(['error' => 'Methode nicht erlaubt.'], 405);
        }
        if (!is_file($logFile)) {
            http_response_code(200);
            header('Content-Type: text/plain; charset=utf-8');
            echo '';
            exit;
        }
        header('Content-Type: text/plain; charset=utf-8');
        readfile($logFile);
        exit;

    default:
        respond(['error' => 'Unbekannte Aktion.'], 400);
}
