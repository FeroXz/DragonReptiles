<?php

function get_gallery_items(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM gallery_items ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function get_featured_gallery_items(PDO $pdo, int $limit = 6): array
{
    $stmt = $pdo->prepare('SELECT * FROM gallery_items WHERE is_featured = 1 ORDER BY created_at DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function find_gallery_item(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM gallery_items WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();
    return $item ?: null;
}

function create_gallery_item(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare('INSERT INTO gallery_items (title, description, image_path, tags, is_featured) VALUES (:title, :description, :image_path, :tags, :is_featured)');
    $stmt->execute([
        'title' => $data['title'],
        'description' => $data['description'] ?? null,
        'image_path' => normalize_media_path($data['image_path'] ?? null),
        'tags' => $data['tags'] ?? null,
        'is_featured' => !empty($data['is_featured']) ? 1 : 0,
    ]);
    return (int)$pdo->lastInsertId();
}

function update_gallery_item(PDO $pdo, int $id, array $data): void
{
    $fields = [
        'title' => $data['title'],
        'description' => $data['description'] ?? null,
        'tags' => $data['tags'] ?? null,
        'is_featured' => !empty($data['is_featured']) ? 1 : 0,
    ];
    $setParts = [];
    foreach ($fields as $column => $value) {
        $setParts[] = $column . ' = :' . $column;
    }
    if (!empty($data['image_path'])) {
        $setParts[] = 'image_path = :image_path';
        $fields['image_path'] = normalize_media_path($data['image_path']);
    }
    $fields['id'] = $id;

    $sql = 'UPDATE gallery_items SET ' . implode(', ', $setParts) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($fields);
}

function delete_gallery_item(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM gallery_items WHERE id = :id');
    $stmt->execute(['id' => $id]);
}
