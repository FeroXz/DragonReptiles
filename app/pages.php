<?php
function create_page(PDO $pdo, array $data): int
{
    $title = trim($data['title'] ?? '');
    $slugInput = trim($data['slug'] ?? '');
    $slug = $slugInput !== '' ? $slugInput : slugify($title);
    $slug = ensure_unique_slug($pdo, 'pages', $slug);
    $parentId = normalize_page_parent($pdo, $data['parent_id'] ?? null);
    $stmt = $pdo->prepare('INSERT INTO pages(title, slug, content, is_published, show_in_menu, parent_id, menu_order) VALUES (:title, :slug, :content, :is_published, :show_in_menu, :parent_id, :menu_order)');
    $stmt->execute([
        'title' => $title,
        'slug' => $slug,
        'content' => $data['content'],
        'is_published' => !empty($data['is_published']) ? 1 : 0,
        'show_in_menu' => !empty($data['show_in_menu']) ? 1 : 0,
        'parent_id' => $parentId,
        'menu_order' => isset($data['menu_order']) ? (int)$data['menu_order'] : 0,
    ]);
    return (int)$pdo->lastInsertId();
}

function update_page(PDO $pdo, int $id, array $data): void
{
    $title = trim($data['title'] ?? '');
    $slugInput = trim($data['slug'] ?? '');
    $slug = $slugInput !== '' ? $slugInput : slugify($title);
    $slug = ensure_unique_slug($pdo, 'pages', $slug, $id);
    $parentId = normalize_page_parent($pdo, $data['parent_id'] ?? null, $id);
    $stmt = $pdo->prepare('UPDATE pages SET title = :title, slug = :slug, content = :content, is_published = :is_published, show_in_menu = :show_in_menu, parent_id = :parent_id, menu_order = :menu_order, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute([
        'title' => $title,
        'slug' => $slug,
        'content' => $data['content'],
        'is_published' => !empty($data['is_published']) ? 1 : 0,
        'show_in_menu' => !empty($data['show_in_menu']) ? 1 : 0,
        'parent_id' => $parentId,
        'menu_order' => isset($data['menu_order']) ? (int)$data['menu_order'] : 0,
        'id' => $id,
    ]);
}

function delete_page(PDO $pdo, int $id): void
{
    $pdo->prepare('UPDATE pages SET parent_id = NULL WHERE parent_id = :id')->execute(['id' => $id]);
    $stmt = $pdo->prepare('DELETE FROM pages WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function get_page(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM pages WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $page = $stmt->fetch();
    return $page ?: null;
}

function get_page_by_slug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = :slug');
    $stmt->execute(['slug' => $slug]);
    $page = $stmt->fetch();
    return $page ?: null;
}

function get_pages(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM pages ORDER BY title COLLATE NOCASE ASC')->fetchAll();
}

function get_published_pages(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM pages WHERE is_published = 1 ORDER BY title COLLATE NOCASE ASC')->fetchAll();
}

function get_navigation_pages(PDO $pdo): array
{
    $pages = $pdo->query('SELECT * FROM pages WHERE is_published = 1 AND show_in_menu = 1 ORDER BY menu_order ASC, title COLLATE NOCASE ASC')->fetchAll();
    $pagesById = [];
    foreach ($pages as $page) {
        $pagesById[$page['id']] = $page;
    }

    $children = [];
    foreach ($pages as $page) {
        $parentId = (int)($page['parent_id'] ?? 0);
        if ($parentId && !isset($pagesById[$parentId])) {
            $parentId = 0;
        }
        $children[$parentId][] = $page;
    }

    return build_page_navigation_tree($children, 0);
}

function build_page_navigation_tree(array $children, int $parentId): array
{
    $branch = [];
    foreach ($children[$parentId] ?? [] as $page) {
        $page['children'] = build_page_navigation_tree($children, (int)$page['id']);
        $branch[] = $page;
    }
    return $branch;
}

function normalize_page_parent(PDO $pdo, $parentId, ?int $currentId = null): ?int
{
    if ($parentId === null || $parentId === '') {
        return null;
    }

    $parentId = (int)$parentId;
    if ($currentId !== null && $parentId === $currentId) {
        return null;
    }

    $parent = get_page($pdo, $parentId);
    if (!$parent) {
        return null;
    }

    return $parentId;
}

