<?php
function create_news(PDO $pdo, array $data): int
{
    $title = trim($data['title'] ?? '');
    $slugInput = trim($data['slug'] ?? '');
    $slug = $slugInput !== '' ? $slugInput : slugify($title);
    $slug = ensure_unique_slug($pdo, 'news_posts', $slug);
    $publishedAtInput = trim($data['published_at'] ?? '');
    $publishedAt = !empty($data['is_published']) ? ($publishedAtInput !== '' ? $publishedAtInput : date('c')) : null;
    $stmt = $pdo->prepare('INSERT INTO news_posts(title, slug, excerpt, content, is_published, published_at) VALUES (:title, :slug, :excerpt, :content, :is_published, :published_at)');
    $stmt->execute([
        'title' => $title,
        'slug' => $slug,
        'excerpt' => $data['excerpt'] ?? null,
        'content' => $data['content'],
        'is_published' => !empty($data['is_published']) ? 1 : 0,
        'published_at' => $publishedAt,
    ]);
    return (int)$pdo->lastInsertId();
}

function update_news(PDO $pdo, int $id, array $data): void
{
    $title = trim($data['title'] ?? '');
    $slugInput = trim($data['slug'] ?? '');
    $slug = $slugInput !== '' ? $slugInput : slugify($title);
    $slug = ensure_unique_slug($pdo, 'news_posts', $slug, $id);
    $publishedAtInput = trim($data['published_at'] ?? '');
    $publishedAt = !empty($data['is_published']) ? ($publishedAtInput !== '' ? $publishedAtInput : date('c')) : null;
    $stmt = $pdo->prepare('UPDATE news_posts SET title = :title, slug = :slug, excerpt = :excerpt, content = :content, is_published = :is_published, published_at = :published_at, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute([
        'title' => $title,
        'slug' => $slug,
        'excerpt' => $data['excerpt'] ?? null,
        'content' => $data['content'],
        'is_published' => !empty($data['is_published']) ? 1 : 0,
        'published_at' => $publishedAt,
        'id' => $id,
    ]);
}

function delete_news(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM news_posts WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function get_news(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM news_posts ORDER BY COALESCE(published_at, created_at) DESC')->fetchAll();
}

function get_news_post(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM news_posts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $post = $stmt->fetch();
    return $post ?: null;
}

function get_news_by_slug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM news_posts WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    $post = $stmt->fetch();
    return $post ?: null;
}

function get_published_news(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM news_posts WHERE is_published = 1 ORDER BY COALESCE(published_at, created_at) DESC')->fetchAll();
}

function get_latest_published_news(PDO $pdo, int $limit = 3): array
{
    $stmt = $pdo->prepare('SELECT * FROM news_posts WHERE is_published = 1 ORDER BY COALESCE(published_at, created_at) DESC LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

