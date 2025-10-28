<?php
const MENU_LOCATIONS = ['frontend', 'admin'];

function ensure_menu_schema(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS menu_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        label TEXT NOT NULL,
        path TEXT NOT NULL,
        icon TEXT NULL,
        visible INTEGER NOT NULL DEFAULT 1,
        position INTEGER NOT NULL DEFAULT 0,
        target TEXT NULL,
        location TEXT NOT NULL DEFAULT "frontend"
    )');

    $columns = $pdo->query('PRAGMA table_info(menu_items)')->fetchAll();
    $columnNames = array_column($columns, 'name');
    if (!in_array('location', $columnNames, true)) {
        $pdo->exec('ALTER TABLE menu_items ADD COLUMN location TEXT NOT NULL DEFAULT "frontend"');
    }

    // Ensure existing NULL targets become _self for consistency
    if (in_array('target', $columnNames, true)) {
        $pdo->exec('UPDATE menu_items SET target = "_self" WHERE target IS NULL');
    }
}

function ensure_default_menu_items(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $defaults = [
        'frontend' => [
            ['label' => 'Start', 'path' => '/index.php', 'icon' => 'home', 'target' => '_self'],
            ['label' => 'Tierübersicht', 'path' => '/index.php?route=animals', 'icon' => 'animals', 'target' => '_self'],
            ['label' => 'Neuigkeiten', 'path' => '/index.php?route=news', 'icon' => 'news', 'target' => '_self'],
            ['label' => 'Pflegeleitfaden', 'path' => '/index.php?route=care-guide', 'icon' => 'care', 'target' => '_self'],
            ['label' => 'Genetik', 'path' => '/index.php?route=genetics', 'icon' => 'genetics', 'target' => '_self'],
            ['label' => 'Tierabgabe', 'path' => '/index.php?route=adoption', 'icon' => 'adoption', 'target' => '_self'],
        ],
        'admin' => [
            ['label' => 'Übersicht', 'path' => '/index.php?route=admin/dashboard', 'icon' => 'dashboard', 'target' => '_self'],
            ['label' => 'Tiere', 'path' => '/index.php?route=admin/animals', 'icon' => 'animals', 'target' => '_self'],
            ['label' => 'Zuchtplanung', 'path' => '/index.php?route=admin/breeding', 'icon' => 'breeding', 'target' => '_self'],
            ['label' => 'Tierabgabe', 'path' => '/index.php?route=admin/adoption', 'icon' => 'adoption', 'target' => '_self'],
            ['label' => 'Anfragen', 'path' => '/index.php?route=admin/inquiries', 'icon' => 'inquiries', 'target' => '_self'],
            ['label' => 'Seiten', 'path' => '/index.php?route=admin/pages', 'icon' => 'pages', 'target' => '_self'],
            ['label' => 'Neuigkeiten', 'path' => '/index.php?route=admin/news', 'icon' => 'news', 'target' => '_self'],
            ['label' => 'Pflegeleitfaden', 'path' => '/index.php?route=admin/care', 'icon' => 'care', 'target' => '_self'],
            ['label' => 'Medien', 'path' => '/index.php?route=admin/media', 'icon' => 'media', 'target' => '_self'],
            ['label' => 'Genetik', 'path' => '/index.php?route=admin/genetics', 'icon' => 'genetics', 'target' => '_self'],
            ['label' => 'Galerie', 'path' => '/index.php?route=admin/gallery', 'icon' => 'gallery', 'target' => '_self'],
            ['label' => 'Startseite', 'path' => '/index.php?route=admin/home-layout', 'icon' => 'home', 'target' => '_self'],
            ['label' => 'Einstellungen', 'path' => '/index.php?route=admin/settings', 'icon' => 'settings', 'target' => '_self'],
            ['label' => 'Texte', 'path' => '/index.php?route=admin/content', 'icon' => 'content', 'target' => '_self'],
            ['label' => 'Navigation', 'path' => '/index.php?route=admin/menu', 'icon' => 'navigation', 'target' => '_self'],
            ['label' => 'Updates', 'path' => '/index.php?route=admin/update', 'icon' => 'update', 'target' => '_self'],
            ['label' => 'Benutzer', 'path' => '/index.php?route=admin/users', 'icon' => 'users', 'target' => '_self'],
        ],
    ];

    $stmt = $pdo->prepare('INSERT INTO menu_items(label, path, icon, visible, position, target, location) VALUES (:label, :path, :icon, :visible, :position, :target, :location)');
    foreach ($defaults as $location => $entries) {
        $position = 0;
        foreach ($entries as $entry) {
            $stmt->execute([
                'label' => $entry['label'],
                'path' => $entry['path'],
                'icon' => $entry['icon'],
                'visible' => 1,
                'position' => $position++,
                'target' => $entry['target'] ?? '_self',
                'location' => $location,
            ]);
        }
    }
}

function get_all_menu_items(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM menu_items ORDER BY location ASC, position ASC, id ASC');
    return $stmt->fetchAll();
}

function get_visible_menu_items(PDO $pdo, string $location = 'frontend'): array
{
    $stmt = $pdo->prepare('SELECT * FROM menu_items WHERE location = :location AND visible = 1 ORDER BY position ASC, id ASC');
    $stmt->execute(['location' => normalize_menu_location($location)]);
    return $stmt->fetchAll();
}

function normalize_menu_location(string $location): string
{
    $location = strtolower(trim($location));
    return in_array($location, MENU_LOCATIONS, true) ? $location : 'frontend';
}

function normalize_menu_target(?string $target): string
{
    $target = $target ? strtolower(trim($target)) : '_self';
    return $target === '_blank' ? '_blank' : '_self';
}

function menu_item_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM menu_items WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch();
    return $item ?: null;
}

function validate_menu_payload(array $data, bool $isUpdate = false): array
{
    $errors = [];
    $label = trim($data['label'] ?? '');
    $path = trim($data['path'] ?? '');
    $icon = trim($data['icon'] ?? '');
    $target = normalize_menu_target($data['target'] ?? '_self');
    $visible = !empty($data['visible']) ? 1 : 0;
    $location = normalize_menu_location($data['location'] ?? 'frontend');

    if ($label === '' || mb_strlen($label) < 1) {
        $errors['label'] = 'Label ist erforderlich.';
    }

    if ($path === '') {
        $errors['path'] = 'Pfad ist erforderlich.';
    } elseif (!preg_match('#^(https?://|/)#i', $path)) {
        $errors['path'] = 'Pfad muss mit "/" oder "http" beginnen.';
    }

    if (!empty($data['target']) && !in_array($target, ['_self', '_blank'], true)) {
        $errors['target'] = 'Ungültiges Ziel.';
    }

    if (!empty($data['position']) && !is_numeric($data['position'])) {
        $errors['position'] = 'Position muss numerisch sein.';
    }

    return [
        'errors' => $errors,
        'normalized' => [
            'label' => $label,
            'path' => $path,
            'icon' => $icon,
            'target' => $target,
            'visible' => $visible,
            'location' => $location,
            'position' => isset($data['position']) ? (int) $data['position'] : null,
        ],
    ];
}

function create_menu_item(PDO $pdo, array $data): array
{
    ['errors' => $errors, 'normalized' => $normalized] = validate_menu_payload($data);
    if (!empty($errors)) {
        throw new InvalidArgumentException(json_encode($errors));
    }

    $normalized['position'] = next_menu_position($pdo, $normalized['location']);

    $stmt = $pdo->prepare('INSERT INTO menu_items(label, path, icon, visible, position, target, location) VALUES (:label, :path, :icon, :visible, :position, :target, :location)');
    $stmt->execute([
        'label' => $normalized['label'],
        'path' => $normalized['path'],
        'icon' => $normalized['icon'],
        'visible' => $normalized['visible'],
        'position' => $normalized['position'],
        'target' => $normalized['target'],
        'location' => $normalized['location'],
    ]);

    return menu_item_by_id($pdo, (int) $pdo->lastInsertId());
}

function update_menu_item(PDO $pdo, int $id, array $data): array
{
    ['errors' => $errors, 'normalized' => $normalized] = validate_menu_payload($data, true);
    if (!empty($errors)) {
        throw new InvalidArgumentException(json_encode($errors));
    }

    $existing = menu_item_by_id($pdo, $id);
    if (!$existing) {
        throw new RuntimeException('Eintrag nicht gefunden.');
    }

    // When location changes we need to move to end of new location
    if ($existing['location'] !== $normalized['location']) {
        $normalized['position'] = next_menu_position($pdo, $normalized['location']);
    } elseif ($normalized['position'] === null) {
        $normalized['position'] = (int) $existing['position'];
    }

    $stmt = $pdo->prepare('UPDATE menu_items SET label = :label, path = :path, icon = :icon, visible = :visible, position = :position, target = :target, location = :location WHERE id = :id');
    $stmt->execute([
        'label' => $normalized['label'],
        'path' => $normalized['path'],
        'icon' => $normalized['icon'],
        'visible' => $normalized['visible'],
        'position' => $normalized['position'],
        'target' => $normalized['target'],
        'location' => $normalized['location'],
        'id' => $id,
    ]);

    normalize_menu_positions($pdo, $existing['location']);
    normalize_menu_positions($pdo, $normalized['location']);

    return menu_item_by_id($pdo, $id);
}

function delete_menu_item(PDO $pdo, int $id): void
{
    $item = menu_item_by_id($pdo, $id);
    if (!$item) {
        return;
    }
    $stmt = $pdo->prepare('DELETE FROM menu_items WHERE id = :id');
    $stmt->execute(['id' => $id]);
    normalize_menu_positions($pdo, $item['location']);
}

function toggle_menu_visibility(PDO $pdo, int $id): ?array
{
    $item = menu_item_by_id($pdo, $id);
    if (!$item) {
        return null;
    }
    $newVisible = (int) (!$item['visible']);
    $stmt = $pdo->prepare('UPDATE menu_items SET visible = :visible WHERE id = :id');
    $stmt->execute(['visible' => $newVisible, 'id' => $id]);
    $item['visible'] = $newVisible;
    return $item;
}

function reorder_menu_items(PDO $pdo, string $location, array $orderedPositions): void
{
    $location = normalize_menu_location($location);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE menu_items SET position = :position WHERE id = :id AND location = :location');
        foreach ($orderedPositions as $index => $id) {
            $stmt->execute([
                'position' => (int) $index,
                'id' => (int) $id,
                'location' => $location,
            ]);
        }
        normalize_menu_positions($pdo, $location);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function normalize_menu_positions(PDO $pdo, string $location): void
{
    $location = normalize_menu_location($location);
    $stmt = $pdo->prepare('SELECT id FROM menu_items WHERE location = :location ORDER BY position ASC, id ASC');
    $stmt->execute(['location' => $location]);
    $ids = array_column($stmt->fetchAll(), 'id');
    $update = $pdo->prepare('UPDATE menu_items SET position = :position WHERE id = :id');
    foreach ($ids as $index => $id) {
        $update->execute([
            'position' => $index,
            'id' => $id,
        ]);
    }
}

function next_menu_position(PDO $pdo, string $location): int
{
    $stmt = $pdo->prepare('SELECT MAX(position) FROM menu_items WHERE location = :location');
    $stmt->execute(['location' => $location]);
    $max = $stmt->fetchColumn();
    return $max === null ? 0 : ((int) $max + 1);
}

function menu_icon_library(): array
{
    return [
        'home' => '<path d="M4 6h16" /><path d="M4 12h10" /><path d="M4 18h16" />',
        'animals' => '<path d="M5 11c0-4.5 3-7 7-7s7 2.5 7 7" /><path d="M4 11h16v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" /><path d="M9 15h6" />',
        'dashboard' => '<path d="M4 12h4v8H4z" /><path d="M10 4h4v16h-4z" /><path d="M16 9h4v11h-4z" />',
        'breeding' => '<path d="M7 17a5 5 0 0 1 5-5" /><path d="M17 7a5 5 0 0 1-5 5" /><path d="M12 22v-9" /><path d="M12 2v5" />',
        'adoption' => '<path d="M12 21s-7-4.5-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 11c0 5.5-7 10-7 10z" />',
        'inquiries' => '<path d="M21 15a2 2 0 0 1-2 2H9l-4 4V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z" />',
        'pages' => '<path d="M5 5h10l4 4v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z" /><path d="M15 5v4h4" />',
        'news' => '<path d="M4 5h16" /><path d="M4 11h16" /><path d="M4 17h10" />',
        'care' => '<path d="M12 6c-1.5-3-5-3-6 0-1.5 4 2 7 6 10 4-3 7.5-6 6-10-1-3-4.5-3-6 0z" />',
        'media' => '<rect x="3" y="5" width="18" height="14" rx="2" /><circle cx="8.5" cy="10.5" r="1.5" /><path d="M21 15l-4-4-3 3-2-2-5 5" />',
        'genetics' => '<path d="M6 3c0 6 12 6 12 12" /><path d="M6 9c0 6 12 6 12 12" /><path d="M12 3v18" />',
        'gallery' => '<rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 15l4-4 3 3 5-5 6 6" />',
        'settings' => '<path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.09A1.65 1.65 0 0 0 11 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.09a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />',
        'content' => '<path d="M4 4h16v16H4z" /><path d="M4 9h16" /><path d="M9 4v16" />',
        'update' => '<path d="M4 4v6h6" /><path d="M20 20v-6h-6" /><path d="M20 4l-7 7a4 4 0 0 1-5.66 0L4 7" />',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />',
        'navigation' => '<path d="M4 6h16" /><path d="M4 12h16" /><path d="M4 18h16" />',
    ];
}
