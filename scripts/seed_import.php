<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/database.php';

function ensure_seed_directory(string $path): void
{
    if (!is_dir($path)) {
        throw new RuntimeException(sprintf('Seed-Verzeichnis %s existiert nicht.', $path));
    }
}

function normalize_path(string $path): string
{
    $cleaned = str_replace('\\\\', '/', $path);
    $cleaned = trim($cleaned);
    $cleaned = preg_replace('#/{2,}#', '/', $cleaned);
    $cleaned = preg_replace('#^\./+#', '', $cleaned);

    $segments = [];
    foreach (explode('/', $cleaned) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }

        if ($segment === '..') {
            array_pop($segments);
            continue;
        }

        $segments[] = $segment;
    }

    return implode('/', $segments);
}

function collect_seed_files(string $directory, string $prefix = ''): array
{
    $files = [];
    $iterator = new DirectoryIterator($directory);
    foreach ($iterator as $entry) {
        if ($entry->isDot()) {
            continue;
        }

        if ($entry->isDir()) {
            $nested = collect_seed_files($entry->getPathname(), $prefix . $entry->getFilename() . '/');
            foreach ($nested as $file) {
                $files[] = $file;
            }
            continue;
        }

        if ($entry->isFile() && strtolower($entry->getExtension()) === 'sql') {
            $files[] = $prefix . $entry->getFilename();
        }
    }

    sort($files, SORT_STRING);

    return $files;
}

function load_manifest(string $manifestPath): ?array
{
    if (!is_file($manifestPath)) {
        return null;
    }

    $raw = file_get_contents($manifestPath);
    if ($raw === false) {
        throw new RuntimeException(sprintf('Manifest %s konnte nicht gelesen werden.', $manifestPath));
    }

    $data = json_decode($raw, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException(sprintf('Manifest %s enthält ungültiges JSON: %s', $manifestPath, json_last_error_msg()));
    }

    if (is_array($data) && array_is_list($data)) {
        $required = $data;
        $order = [];
    } elseif (is_array($data)) {
        $required = $data['required'] ?? [];
        $order = $data['order'] ?? [];
    } else {
        throw new RuntimeException('Manifest muss entweder ein Array oder ein Objekt mit den Feldern "required"/"order" sein.');
    }

    $required = array_map(static fn ($entry) => normalize_path((string) $entry), $required);
    $required = array_values(array_unique(array_filter($required, static fn ($value) => $value !== '')));

    $order = array_map(static fn ($entry) => normalize_path((string) $entry), $order);
    $order = array_values(array_unique(array_filter($order, static fn ($value) => $value !== '')));

    return [
        'required' => $required,
        'order' => $order,
    ];
}

function determine_import_order(array $available, ?array $manifest): array
{
    $availableSet = array_flip($available);
    $ordered = [];

    if ($manifest !== null) {
        foreach ($manifest['order'] as $entry) {
            if (isset($availableSet[$entry]) && !in_array($entry, $ordered, true)) {
                $ordered[] = $entry;
            }
        }
    }

    foreach ($available as $file) {
        if (!in_array($file, $ordered, true)) {
            $ordered[] = $file;
        }
    }

    return $ordered;
}

function ensure_required_files(array $available, ?array $manifest): void
{
    if ($manifest === null) {
        return;
    }

    $availableSet = array_flip($available);
    $missing = [];
    foreach ($manifest['required'] as $entry) {
        if (!isset($availableSet[$entry])) {
            $missing[] = $entry;
        }
    }

    if ($missing !== []) {
        throw new RuntimeException('Fehlende Seed-Dateien: ' . implode(', ', $missing));
    }
}

$pdo = get_database_connection();
initialize_database($pdo);

$seedDirectory = __DIR__ . '/../storage/seeds';
ensure_seed_directory($seedDirectory);

$available = collect_seed_files($seedDirectory);
if ($available === []) {
    throw new RuntimeException('Keine Seed-Dateien gefunden.');
}

$manifest = load_manifest($seedDirectory . '/manifest.json');
ensure_required_files($available, $manifest);

$importOrder = determine_import_order($available, $manifest);

$pdo->exec('PRAGMA foreign_keys = OFF');
$pdo->beginTransaction();

try {
    foreach ($importOrder as $relativeFile) {
        $path = $seedDirectory . '/' . $relativeFile;
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException(sprintf('Seed-Datei %s konnte nicht gelesen werden.', $relativeFile));
        }

        if (trim($sql) === '') {
            continue;
        }

        $pdo->exec($sql);
        printf("✅ %s importiert\n", $relativeFile);
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $pdo->exec('PRAGMA foreign_keys = ON');
    throw $exception;
}

$pdo->exec('PRAGMA foreign_keys = ON');
