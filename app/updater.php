<?php

function get_preserved_update_paths(): array
{
    return [
        'uploads',
        'storage/database.sqlite',
        'storage/initial_admin_credentials.json',
        'storage/updates',
    ];
}

function should_preserve_path(string $relativePath, array $preserved): bool
{
    foreach ($preserved as $path) {
        if ($relativePath === $path || str_starts_with($relativePath, rtrim($path, '/') . '/')) {
            return true;
        }
    }
    return false;
}

function detect_version_from_config(string $configPath): ?string
{
    if (!is_file($configPath)) {
        return null;
    }
    $contents = file_get_contents($configPath);
    if ($contents === false) {
        return null;
    }
    if (preg_match("/const\\s+APP_VERSION\\s*=\\s*'([^']+)'/", $contents, $matches)) {
        return $matches[1];
    }
    return null;
}

function find_update_root(string $extractedPath): string
{
    $items = array_values(array_filter(scandir($extractedPath), static function ($item) {
        return !in_array($item, ['.', '..'], true);
    }));
    if (count($items) === 1) {
        $candidate = $extractedPath . DIRECTORY_SEPARATOR . $items[0];
        if (is_dir($candidate)) {
            return $candidate;
        }
    }
    return $extractedPath;
}

function sanitize_update_label(string $label): string
{
    $normalized = strtolower(preg_replace('/[^a-z0-9\-]+/i', '-', $label));
    $trimmed = trim($normalized ?? '', '-');
    return $trimmed !== '' ? '-' . $trimmed : '';
}

function resolve_repository_branch(PDO $pdo): string
{
    $stored = get_setting($pdo, 'repository_branch') ?? '';
    $normalized = normalize_branch_name($stored);
    if ($normalized) {
        return $normalized;
    }

    if (defined('APP_REPOSITORY_BRANCH') && APP_REPOSITORY_BRANCH !== '') {
        return APP_REPOSITORY_BRANCH;
    }

    return 'main';
}

function repository_archive_url(PDO $pdo): string
{
    $branch = resolve_repository_branch($pdo);
    $encodedBranch = implode('/', array_map('rawurlencode', explode('/', $branch)));

    if (defined('APP_REPOSITORY_ZIP_TEMPLATE') && str_contains(APP_REPOSITORY_ZIP_TEMPLATE, '%s')) {
        return sprintf(APP_REPOSITORY_ZIP_TEMPLATE, $encodedBranch);
    }

    if (defined('APP_REPOSITORY_ZIP')) {
        $candidate = preg_replace('#/refs/heads/[^/]+$#', '/refs/heads/' . $encodedBranch, APP_REPOSITORY_ZIP);
        if (is_string($candidate) && $candidate !== '') {
            return $candidate;
        }
    }

    throw new RuntimeException('Es wurde keine Update-Quelle für das Repository konfiguriert.');
}

function apply_update_from_path(PDO $pdo, string $zipPath, string $label = 'update'): array
{
    if (!is_file($zipPath)) {
        throw new RuntimeException('Update-Datei wurde nicht gefunden.');
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new RuntimeException('Das Update-Paket konnte nicht geöffnet werden.');
    }

    $updatesPath = __DIR__ . '/../storage/updates';
    ensure_directory($updatesPath);
    $timestamp = date('YmdHis');
    $extractPath = $updatesPath . '/' . $timestamp . sanitize_update_label($label);
    ensure_directory($extractPath);

    if (!$zip->extractTo($extractPath)) {
        $zip->close();
        throw new RuntimeException('Das Update-Paket konnte nicht entpackt werden.');
    }
    $zip->close();

    $archiveCopy = $extractPath . '/source.zip';
    if (!@copy($zipPath, $archiveCopy)) {
        @file_put_contents($archiveCopy . '.log', 'Quelle konnte nicht gesichert: ' . $zipPath);
    }

    $root = find_update_root($extractPath);
    $basePath = realpath(__DIR__ . '/..');
    if ($basePath === false) {
        throw new RuntimeException('Basisverzeichnis konnte nicht ermittelt werden.');
    }

    $preserved = get_preserved_update_paths();
    $filesUpdated = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relativePath = ltrim(str_replace($root, '', $item->getPathname()), DIRECTORY_SEPARATOR);
        if ($relativePath === '') {
            continue;
        }
        if (should_preserve_path($relativePath, $preserved)) {
            continue;
        }

        $targetPath = $basePath . DIRECTORY_SEPARATOR . $relativePath;

        if ($item->isDir()) {
            ensure_directory($targetPath);
            continue;
        }

        ensure_directory(dirname($targetPath));
        if (!copy($item->getPathname(), $targetPath)) {
            throw new RuntimeException(sprintf('Datei "%s" konnte nicht aktualisiert werden.', $relativePath));
        }
        $filesUpdated++;
    }

    $detectedVersion = detect_version_from_config($basePath . '/app/config.php');
    if (!$detectedVersion && defined('APP_VERSION')) {
        $detectedVersion = APP_VERSION;
    }
    if ($detectedVersion) {
        set_setting($pdo, 'app_version', $detectedVersion);
        set_setting($pdo, 'footer_text', '© ' . date('Y') . ' ' . APP_NAME . ' — Version ' . $detectedVersion);
    }

    return [
        'files' => $filesUpdated,
        'version' => $detectedVersion,
        'extracted' => $extractPath,
    ];
}

function apply_update_package(PDO $pdo, array $uploadedFile): array
{
    if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($uploadedFile['tmp_name'])) {
        throw new RuntimeException('Upload fehlgeschlagen. Bitte erneut versuchen.');
    }

    if (!is_uploaded_file($uploadedFile['tmp_name'])) {
        throw new RuntimeException('Die hochgeladene Datei ist ungültig.');
    }

    return apply_update_from_path($pdo, $uploadedFile['tmp_name'], 'upload');
}

function download_repository_update(PDO $pdo): array
{
    $archiveUrl = repository_archive_url($pdo);

    $context = stream_context_create([
        'http' => [
            'timeout' => 60,
            'follow_location' => 1,
            'header' => "User-Agent: " . APP_NAME . " Updater\r\n",
        ],
        'https' => [
            'timeout' => 60,
        ],
    ]);

    $resource = @fopen($archiveUrl, 'rb', false, $context);
    if (!$resource) {
        throw new RuntimeException('Repository-Download konnte nicht gestartet werden.');
    }

    $updatesPath = __DIR__ . '/../storage/updates';
    ensure_directory($updatesPath);
    $timestamp = date('YmdHis');
    $zipPath = $updatesPath . '/repository-' . $timestamp . '.zip';

    $target = @fopen($zipPath, 'wb');
    if (!$target) {
        fclose($resource);
        throw new RuntimeException('Heruntergeladene Update-Datei konnte nicht gespeichert werden.');
    }

    stream_copy_to_stream($resource, $target);
    fclose($resource);
    fclose($target);

    if (!is_file($zipPath) || filesize($zipPath) === 0) {
        @unlink($zipPath);
        throw new RuntimeException('Das heruntergeladene Update-Paket ist leer.');
    }

    $result = apply_update_from_path($pdo, $zipPath, 'repository');
    $result['branch'] = resolve_repository_branch($pdo);
    $result['source'] = $archiveUrl;

    return $result;
}
