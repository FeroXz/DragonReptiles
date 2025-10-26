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

function apply_update_package(PDO $pdo, array $uploadedFile): array
{
    if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($uploadedFile['tmp_name'])) {
        throw new RuntimeException('Upload fehlgeschlagen. Bitte erneut versuchen.');
    }

    if (!is_uploaded_file($uploadedFile['tmp_name'])) {
        throw new RuntimeException('Die hochgeladene Datei ist ungültig.');
    }

    $zip = new ZipArchive();
    if ($zip->open($uploadedFile['tmp_name']) !== true) {
        throw new RuntimeException('Das Update-Paket konnte nicht geöffnet werden.');
    }

    $updatesPath = __DIR__ . '/../storage/updates';
    ensure_directory($updatesPath);
    $timestamp = date('YmdHis');
    $extractPath = $updatesPath . '/' . $timestamp;
    ensure_directory($extractPath);

    if (!$zip->extractTo($extractPath)) {
        $zip->close();
        throw new RuntimeException('Das Update-Paket konnte nicht entpackt werden.');
    }
    $zip->close();

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
