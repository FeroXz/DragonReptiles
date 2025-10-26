<?php

declare(strict_types=1);

function get_media_assets(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM media_assets ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function get_media_asset(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM media_assets WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $asset = $stmt->fetch();
    return $asset ?: null;
}

function find_media_asset_by_path(PDO $pdo, string $path): ?array
{
    $normalized = normalize_media_path($path);
    if ($normalized === null) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM media_assets WHERE file_path = :path LIMIT 1');
    $stmt->execute(['path' => $normalized]);
    $asset = $stmt->fetch();
    return $asset ?: null;
}

function find_media_asset_by_tag(PDO $pdo, string $tag): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM media_assets WHERE tags LIKE :tag ORDER BY created_at DESC LIMIT 1');
    $stmt->execute(['tag' => '%' . $tag . '%']);
    $asset = $stmt->fetch();
    return $asset ?: null;
}

function create_media_asset(PDO $pdo, array $data): int
{
    $filePath = normalize_media_path($data['file_path'] ?? null);
    if ($filePath === null) {
        return 0;
    }

    $stmt = $pdo->prepare('INSERT INTO media_assets (title, file_path, original_name, mime_type, file_size, width, height, alt_text, tags)'
        . ' VALUES (:title, :file_path, :original_name, :mime_type, :file_size, :width, :height, :alt_text, :tags)');
    $stmt->execute([
        'title' => $data['title'] ?? null,
        'file_path' => $filePath,
        'original_name' => $data['original_name'] ?? null,
        'mime_type' => $data['mime_type'] ?? null,
        'file_size' => $data['file_size'] ?? null,
        'width' => $data['width'] ?? null,
        'height' => $data['height'] ?? null,
        'alt_text' => $data['alt_text'] ?? null,
        'tags' => $data['tags'] ?? null,
    ]);

    return (int)$pdo->lastInsertId();
}

function update_media_asset(PDO $pdo, int $id, array $data): void
{
    $columns = ['title', 'file_path', 'original_name', 'mime_type', 'file_size', 'width', 'height', 'alt_text', 'tags'];
    $setParts = [];
    $params = ['id' => $id];

    foreach ($columns as $column) {
        if (!array_key_exists($column, $data)) {
            continue;
        }

        $value = $data[$column];
        if ($column === 'file_path') {
            $value = normalize_media_path($value);
            if ($value === null) {
                continue;
            }
        }

        $setParts[] = $column . ' = :' . $column;
        $params[$column] = $value;
    }

    if (empty($setParts)) {
        return;
    }

    $setParts[] = 'updated_at = CURRENT_TIMESTAMP';
    $sql = 'UPDATE media_assets SET ' . implode(', ', $setParts) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function delete_media_asset(PDO $pdo, int $id): void
{
    $asset = get_media_asset($pdo, $id);
    if ($asset) {
        $stmt = $pdo->prepare('DELETE FROM media_assets WHERE id = :id');
        $stmt->execute(['id' => $id]);
        remove_media_file($asset['file_path'] ?? null);
    }
}

function create_media_asset_from_upload(PDO $pdo, array $file, array $data = []): ?int
{
    $upload = handle_upload($file, true);
    if (!$upload || empty($upload['path'])) {
        return null;
    }

    $payload = array_merge($data, [
        'file_path' => $upload['path'],
        'original_name' => $upload['original_name'] ?? null,
        'mime_type' => $upload['mime_type'] ?? null,
        'file_size' => $upload['file_size'] ?? null,
        'width' => $upload['width'] ?? null,
        'height' => $upload['height'] ?? null,
    ]);

    $assetId = create_media_asset($pdo, $payload);
    return $assetId > 0 ? $assetId : null;
}

function search_media_assets(PDO $pdo, string $query): array
{
    $query = trim($query);
    if ($query === '') {
        return get_media_assets($pdo);
    }

    $stmt = $pdo->prepare('SELECT * FROM media_assets WHERE (
        title LIKE :like OR
        original_name LIKE :like OR
        tags LIKE :like OR
        alt_text LIKE :like
    ) ORDER BY created_at DESC');
    $stmt->execute(['like' => '%' . $query . '%']);
    return $stmt->fetchAll();
}

function remove_media_file(?string $relativePath): void
{
    $normalized = normalize_media_path($relativePath);
    if ($normalized === null) {
        return;
    }

    $absolute = realpath(__DIR__ . '/../public/' . $normalized);
    $uploadsRoot = realpath(UPLOAD_PATH);
    if ($absolute && $uploadsRoot && strncmp($absolute, $uploadsRoot, strlen($uploadsRoot)) === 0 && is_file($absolute)) {
        @unlink($absolute);
    }
}

function ensure_media_asset_for_path(PDO $pdo, string $path, array $attributes = []): ?array
{
    $normalized = normalize_media_path($path);
    if ($normalized === null) {
        return null;
    }

    $existing = find_media_asset_by_path($pdo, $normalized);
    if ($existing) {
        return $existing;
    }

    $publicRoot = realpath(__DIR__ . '/../public');
    if (!$publicRoot) {
        return null;
    }

    $absolute = $publicRoot . '/' . $normalized;
    if (!is_file($absolute)) {
        return null;
    }

    $mimeType = function_exists('mime_content_type') ? @mime_content_type($absolute) : null;
    $size = @filesize($absolute);
    $dimensions = @getimagesize($absolute) ?: null;

    $payload = array_merge([
        'title' => $attributes['title'] ?? null,
        'file_path' => $normalized,
        'original_name' => basename($normalized),
        'mime_type' => $mimeType ?: null,
        'file_size' => $size !== false ? (int)$size : null,
        'width' => $dimensions ? (int)$dimensions[0] : null,
        'height' => $dimensions ? (int)$dimensions[1] : null,
        'alt_text' => $attributes['alt_text'] ?? null,
        'tags' => $attributes['tags'] ?? null,
    ], $attributes);

    $assetId = create_media_asset($pdo, $payload);
    return $assetId > 0 ? get_media_asset($pdo, $assetId) : null;
}
