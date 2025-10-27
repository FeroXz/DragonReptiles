<?php

declare(strict_types=1);

function migrate_legacy_uploads(): void
{
    $legacyPath = __DIR__ . '/../uploads';
    if (!is_dir($legacyPath)) {
        return;
    }

    ensure_directory(UPLOAD_PATH);

    $files = glob($legacyPath . '/*');
    if (!is_array($files)) {
        return;
    }

    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }

        $target = rtrim(UPLOAD_PATH, '/\\') . '/' . basename($file);
        if (!is_file($target)) {
            @rename($file, $target);
        }
    }
}

function ensure_existing_media_registered(PDO $pdo): void
{
    $targets = [
        ['table' => 'animals', 'title_column' => 'name', 'path_column' => 'image_path', 'tags' => 'animals,import'],
        ['table' => 'adoption_listings', 'title_column' => 'title', 'path_column' => 'image_path', 'tags' => 'adoption,import'],
        ['table' => 'gallery_items', 'title_column' => 'title', 'path_column' => 'image_path', 'tags' => 'gallery,import'],
    ];

    foreach ($targets as $target) {
        $sql = sprintf('SELECT id, %s AS title, %s AS path FROM %s WHERE %s IS NOT NULL AND %s != ""',
            $target['title_column'],
            $target['path_column'],
            $target['table'],
            $target['path_column'],
            $target['path_column']
        );
        $rows = $pdo->query($sql)->fetchAll();
        foreach ($rows as $row) {
            $asset = ensure_media_asset_for_path($pdo, $row['path'], [
                'title' => $row['title'] ?? null,
                'tags' => $target['tags'],
            ]);
            if ($asset && empty($asset['alt_text']) && !empty($row['title'])) {
                update_media_asset($pdo, (int)$asset['id'], ['alt_text' => $row['title'] . ' Vorschau']);
            }
        }
    }
}

function ensure_media_library_seed(PDO $pdo): array
{
    ensure_directory(UPLOAD_PATH);

    $definitions = [
        'emerald-hatchling' => [
            'title' => 'Smaragdschlüpfling',
            'file_path' => 'uploads/emerald-hatchling.svg',
            'alt_text' => 'Junger Smaragddrache ruht auf einer mit Farn bedeckten Wurzel.',
            'tags' => 'seed:emerald-hatchling,animals,showcase',
        ],
        'sunset-gecko' => [
            'title' => 'Sonnenuntergangs-Gecko',
            'file_path' => 'uploads/sunset-gecko.svg',
            'alt_text' => 'Gecko mit warmen Abendfarben auf einem futuristischen Ast.',
            'tags' => 'seed:sunset-gecko,adoption,highlight',
        ],
        'nebula-terrarium' => [
            'title' => 'Nebula Terrarium',
            'file_path' => 'uploads/nebula-terrarium.svg',
            'alt_text' => 'Terrarium mit holografischen Pflanzen und weichen Neonlichtern.',
            'tags' => 'seed:nebula-terrarium,gallery,hero',
        ],
    ];

    $resolved = [];

    foreach ($definitions as $key => $definition) {
        $existing = find_media_asset_by_tag($pdo, 'seed:' . $key);
        if ($existing) {
            $resolved[$key] = $existing;
            continue;
        }

        $absolute = __DIR__ . '/../public/' . $definition['file_path'];
        if (!is_file($absolute)) {
            continue;
        }

        $payload = [
            'title' => $definition['title'],
            'file_path' => $definition['file_path'],
            'original_name' => basename($absolute),
            'mime_type' => 'image/svg+xml',
            'file_size' => @filesize($absolute) ?: null,
            'alt_text' => $definition['alt_text'],
            'tags' => $definition['tags'],
        ];

        $assetId = create_media_asset($pdo, $payload);
        $resolved[$key] = get_media_asset($pdo, $assetId);
    }

    return $resolved;
}

function ensure_sample_content(PDO $pdo): void
{
    migrate_legacy_uploads();
    ensure_existing_media_registered($pdo);
    $seedAssets = ensure_media_library_seed($pdo);
    ensure_sample_animals($pdo, $seedAssets);
    ensure_sample_gallery($pdo, $seedAssets);
    ensure_sample_adoption($pdo, $seedAssets);
    $releaseHighlights = [
        '3.5.0' => [
            'Öffentliche Seiten und Wiki nutzen jetzt komplett Nuxt UI Panels, Karten und Pills.',
            'Hero-, Highlight- und Adoption-Abschnitte erhielten ein HorizonUI 3.0-inspiriertes Facelift.',
            'Neue Medienverwaltung erlaubt Upload, Metadatenpflege und Suche nach Assets.',
        ],
        '3.6.0' => [
            'Uploads landen unter public/uploads und funktionieren sofort im Frontend.',
            'Tier-, Adoption- und Galerie-Formulare bieten eine Medienbibliothek zur Wiederverwendung.',
            'Vorinstallierter Beispielcontent zeigt Tiere, Adoption und Galerie mit funktionierenden Bildern.',
        ],
        APP_VERSION => [
            'Startseiten-Studio mit konfigurierbaren Standardsektionen inklusive Mengensteuerung pro Bereich.',
            'Eigene Nuxt UI Custom-Sektionen lassen sich erstellen, bearbeiten und im Layout aktivieren.',
            'Genetikbibliothek ergänzt Swiss-Chocolate-Linie samt Alias und Referenzkarte.',
        ],
    ];
    foreach ($releaseHighlights as $version => $highlights) {
        ensure_release_news($pdo, $version, $highlights);
    }
}

function ensure_sample_animals(PDO $pdo, array $assets): void
{
    $existing = (int)$pdo->query('SELECT COUNT(*) FROM animals')->fetchColumn();
    if ($existing > 0) {
        return;
    }

    $mediaPath = $assets['emerald-hatchling']['file_path'] ?? null;

    create_animal($pdo, [
        'name' => 'Aurora',
        'species' => 'Heterodon nasicus',
        'species_slug' => 'heterodon-nasicus',
        'age' => '2021-05-01',
        'genetics' => 'Super Arctic, Anaconda',
        'genetics_profile' => json_encode(['arctic' => 'homozygous', 'anaconda' => 'heterozygous'], JSON_UNESCAPED_UNICODE),
        'origin' => 'Eigenes Zuchtprojekt 2021',
        'special_notes' => 'Zeigt bereits stark ausgeprägtes „Snow“-Pattern und frisst zuverlässig Frostmäuse.',
        'description' => '<p>Aurora ist unsere Referenz für die Horizon Nightfall Linie. Sie zeigt kräftige Kontraste und eignet sich perfekt für Präsentationen im Dashboard.</p>',
        'image_path' => $mediaPath,
        'owner_id' => null,
        'is_private' => false,
        'is_showcased' => true,
        'is_piebald' => false,
    ]);
}

function ensure_sample_gallery(PDO $pdo, array $assets): void
{
    $existing = (int)$pdo->query('SELECT COUNT(*) FROM gallery_items')->fetchColumn();
    if ($existing > 0) {
        return;
    }

    $entries = [
        [
            'title' => 'Neon Terrarium Setup',
            'description' => 'Neues Terrarium mit Nebula-Beleuchtung und digitalem Klima-Overlay.',
            'image_path' => $assets['nebula-terrarium']['file_path'] ?? null,
            'tags' => 'terrarium, horizon, setup',
            'is_featured' => 1,
        ],
        [
            'title' => 'Aurora Showcase',
            'description' => 'Smaragdschlüpfling Aurora in der Präsentationsbox mit spektralen Highlights.',
            'image_path' => $assets['emerald-hatchling']['file_path'] ?? null,
            'tags' => 'aurora, showcase, animals',
            'is_featured' => 1,
        ],
        [
            'title' => 'Sonnenuntergangs-Gecko',
            'description' => 'Warme Farbtöne treffen auf moderne Linien – ideal für Social Media Posts.',
            'image_path' => $assets['sunset-gecko']['file_path'] ?? null,
            'tags' => 'gecko, social, adoption',
            'is_featured' => 0,
        ],
    ];

    foreach ($entries as $entry) {
        if (empty($entry['image_path'])) {
            continue;
        }
        create_gallery_item($pdo, $entry);
    }
}

function ensure_sample_adoption(PDO $pdo, array $assets): void
{
    $existing = (int)$pdo->query('SELECT COUNT(*) FROM adoption_listings')->fetchColumn();
    if ($existing > 0) {
        return;
    }

    $mediaPath = $assets['sunset-gecko']['file_path'] ?? null;

    create_listing($pdo, [
        'animal_id' => null,
        'title' => 'Sonnenuntergangs-Gecko sucht Neon-Heimat',
        'species' => 'Heterodon nasicus',
        'species_slug' => 'heterodon-nasicus',
        'genetics' => 'Albino, Extreme Red',
        'genetics_profile' => json_encode(['albino' => 'homozygous', 'extreme-red-purple-line' => 'heterozygous'], JSON_UNESCAPED_UNICODE),
        'price' => '350 €',
        'description' => '<p>Dieser Gecko ist auf die HorizonUI Terrarien optimiert und kommt mit vollständiger Fütterungshistorie.</p>',
        'image_path' => $mediaPath,
        'status' => 'available',
        'contact_email' => 'info@example.com',
    ]);
}

function ensure_release_news(PDO $pdo, string $version, array $highlights): void
{
    $slug = 'release-' . str_replace('.', '-', $version);
    $existing = get_news_by_slug($pdo, $slug);
    if ($existing) {
        return;
    }

    $listItems = array_map(static fn($item) => '- ' . $item, $highlights);
    $content = '## Changelog ' . $version . "\n\n" . implode("\n", $listItems) . "\n";

    create_news($pdo, [
        'title' => 'Release ' . $version,
        'slug' => $slug,
        'excerpt' => 'Wichtigste Neuerungen der Version ' . $version . ' im Überblick.',
        'content' => $content,
        'is_published' => 1,
        'published_at' => date('c'),
    ]);
}
