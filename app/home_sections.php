<?php

declare(strict_types=1);

function home_section_key_for_custom(int $id): string
{
    return 'custom:' . $id;
}

function parse_custom_section_key(string $key): ?int
{
    if (str_starts_with($key, 'custom:')) {
        $id = (int)substr($key, strlen('custom:'));
        return $id > 0 ? $id : null;
    }

    return null;
}

function get_custom_home_sections(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM home_sections ORDER BY title COLLATE NOCASE ASC');
    return $stmt->fetchAll();
}

function get_custom_home_section(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM home_sections WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function ensure_unique_home_section_slug(PDO $pdo, string $slug, ?int $ignoreId = null): string
{
    return ensure_unique_slug($pdo, 'home_sections', $slug, $ignoreId);
}

function normalize_home_section_payload(array $data): array
{
    $title = trim((string)($data['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('Titel wird benötigt.');
    }

    $slugInput = trim((string)($data['slug'] ?? ''));
    $slug = $slugInput !== '' ? $slugInput : slugify($title);

    return [
        'title' => $title,
        'slug' => $slug,
        'subtitle' => trim((string)($data['subtitle'] ?? '')) ?: null,
        'eyebrow' => trim((string)($data['eyebrow'] ?? '')) ?: null,
        'body' => trim((string)($data['body'] ?? '')) ?: null,
        'cta_label' => trim((string)($data['cta_label'] ?? '')) ?: null,
        'cta_url' => trim((string)($data['cta_url'] ?? '')) ?: null,
    ];
}

function create_custom_home_section(PDO $pdo, array $data): int
{
    $payload = normalize_home_section_payload($data);
    $payload['slug'] = ensure_unique_home_section_slug($pdo, $payload['slug']);

    $stmt = $pdo->prepare('INSERT INTO home_sections (title, slug, subtitle, eyebrow, body, cta_label, cta_url) VALUES (:title, :slug, :subtitle, :eyebrow, :body, :cta_label, :cta_url)');
    $stmt->execute([
        'title' => $payload['title'],
        'slug' => $payload['slug'],
        'subtitle' => $payload['subtitle'],
        'eyebrow' => $payload['eyebrow'],
        'body' => $payload['body'],
        'cta_label' => $payload['cta_label'],
        'cta_url' => $payload['cta_url'],
    ]);

    return (int)$pdo->lastInsertId();
}

function update_custom_home_section(PDO $pdo, int $id, array $data): void
{
    if (!get_custom_home_section($pdo, $id)) {
        throw new InvalidArgumentException('Bereich wurde nicht gefunden.');
    }

    $payload = normalize_home_section_payload($data);
    $payload['slug'] = ensure_unique_home_section_slug($pdo, $payload['slug'], $id);

    $stmt = $pdo->prepare('UPDATE home_sections SET title = :title, slug = :slug, subtitle = :subtitle, eyebrow = :eyebrow, body = :body, cta_label = :cta_label, cta_url = :cta_url, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->execute([
        'title' => $payload['title'],
        'slug' => $payload['slug'],
        'subtitle' => $payload['subtitle'],
        'eyebrow' => $payload['eyebrow'],
        'body' => $payload['body'],
        'cta_label' => $payload['cta_label'],
        'cta_url' => $payload['cta_url'],
        'id' => $id,
    ]);
}

function delete_custom_home_section(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM home_sections WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

