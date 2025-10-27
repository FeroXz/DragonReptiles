<?php
function ensure_default_settings(PDO $pdo): void
{
    $defaults = [
        'site_title' => 'Dragon Reptiles',
        'site_tagline' => 'Spezialisierte Pflege und Haltungsempfehlungen für Drachen- und Schlangenfreunde.',
        'hero_intro' => 'Entdecke unsere Leidenschaft für verantwortungsvolle Haltung und Zucht.',
        'adoption_intro' => 'Diese Tiere suchen ein liebevolles Zuhause. Kontaktiere uns für mehr Informationen.',
        'footer_text' => '© ' . date('Y') . ' ' . APP_NAME . ' — Version ' . APP_VERSION,
        'contact_email' => 'info@example.com',
        'active_theme' => 'aurora',
        'logo_icon_path' => 'assets/logo-icon.svg',
        'logo_wordmark_path' => 'assets/logo-wordmark.svg',
        'repository_branch' => defined('APP_REPOSITORY_BRANCH') ? APP_REPOSITORY_BRANCH : 'main',
        'home_sections_layout' => json_encode(default_home_sections_layout($pdo), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        'app_version' => APP_VERSION,
    ];

    foreach (get_content_definitions() as $key => $definition) {
        $defaults[$key] = $definition['default'] ?? '';
    }

    foreach ($defaults as $key => $value) {
        $stmt = $pdo->prepare('INSERT OR IGNORE INTO settings(key, value) VALUES (:key, :value)');
        $stmt->execute(['key' => $key, 'value' => $value]);
    }

    $existingTitle = get_setting($pdo, 'site_title');
    if ($existingTitle === 'FeroxZ Reptile Center') {
        set_setting($pdo, 'site_title', 'Dragon Reptiles');
    }

    $existingTagline = get_setting($pdo, 'site_tagline');
    if ($existingTagline === 'Spezialisierte Pflege für Bartagamen und Hakennasennattern') {
        set_setting($pdo, 'site_tagline', 'Spezialisierte Pflege und Haltungsempfehlungen für Drachen- und Schlangenfreunde.');
    }

    $currentVersion = get_setting($pdo, 'app_version');
    if ($currentVersion !== APP_VERSION) {
        set_setting($pdo, 'app_version', APP_VERSION);
        $footer = '© ' . date('Y') . ' ' . APP_NAME . ' — Version ' . APP_VERSION;
        set_setting($pdo, 'footer_text', $footer);
    }

    foreach ([
        'logo_icon_path' => 'assets/logo-icon.svg',
        'logo_wordmark_path' => 'assets/logo-wordmark.svg',
        'repository_branch' => defined('APP_REPOSITORY_BRANCH') ? APP_REPOSITORY_BRANCH : 'main',
    ] as $key => $default) {
        $current = get_setting($pdo, $key);
        if ($current === null || trim((string)$current) === '') {
            set_setting($pdo, $key, $default);
        }
    }
}

function update_settings(PDO $pdo, array $values): void
{
    foreach ($values as $key => $value) {
        set_setting($pdo, $key, $value);
    }
}

function get_all_settings(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT key, value FROM settings');
    $settings = [];
    foreach ($stmt as $row) {
        $settings[$row['key']] = $row['value'];
    }
    return $settings;
}
