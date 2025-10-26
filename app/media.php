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

function create_media_asset(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO media_assets (title, file_path, original_name, mime_type, file_size, width, height, alt_text, tags)
        VALUES (:title, :file_path, :original_name, :mime_type, :file_size, :width, :height, :alt_text, :tags)');
    $stmt->execute([
        'title' => $data['title'] ?? null,
        'file_path' => $data['file_path'],
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
        if (array_key_exists($column, $data)) {
            $setParts[] = $column . ' = :' . $column;
            $params[$column] = $data[$column];
        }
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

    return create_media_asset($pdo, $payload);
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
    if (!$relativePath) {
        return;
    }

    $relativePath = ltrim($relativePath, '/');
    $absolute = realpath(__DIR__ . '/../' . $relativePath);
    $uploadsRoot = realpath(UPLOAD_PATH);
    if ($absolute && $uploadsRoot && strncmp($absolute, $uploadsRoot, strlen($uploadsRoot)) === 0 && is_file($absolute)) {
        @unlink($absolute);
    }
}
