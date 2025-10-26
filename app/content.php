<?php

function get_content_definitions(): array
{
    static $definitions;
    if ($definitions !== null) {
        return $definitions;
    }

    $definitions = [
        'home_hero_badge' => [
            'group' => 'Startseite',
            'label' => 'Hero-Badge',
            'type' => 'text',
            'default' => 'Pflegeleitfaden',
            'help' => 'Kurzes Label über dem Hero-Abschnitt.',
        ],
        'home_hero_secondary_intro' => [
            'group' => 'Startseite',
            'label' => 'Hero-Begleittext',
            'type' => 'richtext',
            'default' => 'Unsere Leitfäden decken Beleuchtung, Ernährung, Habitatgestaltung und Gesundheitsvorsorge für <strong>Pogona vitticeps</strong> und <strong>Heterodon nasicus</strong> ab. Registrierte Benutzer erhalten Zugriff auf individuelle Tierakten inklusive Genetik und Besonderheiten.',
        ],
        'home_care_primary_cta' => [
            'group' => 'Startseite',
            'label' => 'Hero-CTA 1',
            'type' => 'text',
            'default' => 'Pflegewissen entdecken',
        ],
        'home_care_secondary_cta' => [
            'group' => 'Startseite',
            'label' => 'Hero-CTA 2',
            'type' => 'text',
            'default' => 'Genetik-Rechner starten',
        ],
        'home_highlights_title' => [
            'group' => 'Startseite',
            'label' => 'Highlights Überschrift',
            'type' => 'text',
            'default' => 'Unsere Highlights',
        ],
        'home_highlights_subtitle' => [
            'group' => 'Startseite',
            'label' => 'Highlights Unterzeile',
            'type' => 'text',
            'default' => 'Ausgewählte Tiere aus dem Bestand',
        ],
        'home_adoption_title' => [
            'group' => 'Startseite',
            'label' => 'Vermittlung Überschrift',
            'type' => 'text',
            'default' => 'Tiervermittlung',
        ],
        'home_adoption_subtitle' => [
            'group' => 'Startseite',
            'label' => 'Vermittlung Unterzeile',
            'type' => 'text',
            'default' => 'Aktuelle Vermittlungstiere und Kontakte',
        ],
        'home_adoption_cta' => [
            'group' => 'Startseite',
            'label' => 'Vermittlung CTA',
            'type' => 'text',
            'default' => 'Kontakt aufnehmen',
        ],
        'home_news_title' => [
            'group' => 'Startseite',
            'label' => 'News Überschrift',
            'type' => 'text',
            'default' => 'Neuigkeiten',
        ],
        'home_news_subtitle' => [
            'group' => 'Startseite',
            'label' => 'News Unterzeile',
            'type' => 'text',
            'default' => 'Aktuelle Meldungen aus Verein und Bestand',
        ],
        'home_news_cta' => [
            'group' => 'Startseite',
            'label' => 'News CTA',
            'type' => 'text',
            'default' => 'Alle Meldungen anzeigen',
        ],
        'home_news_post_cta' => [
            'group' => 'Startseite',
            'label' => 'News Artikel-Link',
            'type' => 'text',
            'default' => 'Details ansehen',
        ],
        'home_care_title' => [
            'group' => 'Startseite',
            'label' => 'Pflegewissen Überschrift',
            'type' => 'text',
            'default' => 'Pflegewissen',
        ],
        'home_care_subtitle' => [
            'group' => 'Startseite',
            'label' => 'Pflegewissen Unterzeile',
            'type' => 'text',
            'default' => 'Vertiefende Artikel für verantwortungsvolle Haltung',
        ],
        'home_care_cta' => [
            'group' => 'Startseite',
            'label' => 'Pflegewissen CTA',
            'type' => 'text',
            'default' => 'Zur Wissenssammlung',
        ],
        'home_care_article_cta' => [
            'group' => 'Startseite',
            'label' => 'Pflegewissen Artikel-Link',
            'type' => 'text',
            'default' => 'Leitfaden öffnen',
        ],
        'animals_title' => [
            'group' => 'Tierübersicht',
            'label' => 'Seitenüberschrift',
            'type' => 'text',
            'default' => 'Tierübersicht',
        ],
        'animals_intro' => [
            'group' => 'Tierübersicht',
            'label' => 'Einleitung',
            'type' => 'text',
            'default' => 'Alle öffentlich sichtbaren Tiere aus unserem Bestand auf einen Blick.',
        ],
        'adoption_title' => [
            'group' => 'Tiervermittlung',
            'label' => 'Seitenüberschrift',
            'type' => 'text',
            'default' => 'Tierabgabe',
        ],
        'adoption_intro_text' => [
            'group' => 'Tiervermittlung',
            'label' => 'Unterzeile',
            'type' => 'text',
            'default' => 'Vermittlungstiere mit Kontaktformular für eine direkte Anfrage.',
        ],
        'adoption_form_submit' => [
            'group' => 'Tiervermittlung',
            'label' => 'Formular-Button',
            'type' => 'text',
            'default' => 'Anfrage senden',
        ],
        'breeding_title' => [
            'group' => 'Zuchtplanung',
            'label' => 'Seitenüberschrift',
            'type' => 'text',
            'default' => 'Zuchtplanung',
        ],
        'breeding_intro' => [
            'group' => 'Zuchtplanung',
            'label' => 'Einleitung',
            'type' => 'text',
            'default' => 'Interne Übersicht über geplante Verpaarungen und Inkubationsschritte.',
        ],
        'breeding_no_parents' => [
            'group' => 'Zuchtplanung',
            'label' => 'Hinweis ohne Eltern',
            'type' => 'text',
            'default' => 'Noch keine Eltern hinterlegt.',
        ],
        'genetics_title' => [
            'group' => 'Genetik',
            'label' => 'Seitenüberschrift',
            'type' => 'text',
            'default' => 'Genetikrechner',
        ],
        'genetics_intro' => [
            'group' => 'Genetik',
            'label' => 'Einleitung',
            'type' => 'text',
            'default' => 'Planen Sie Ihre Verpaarungen analog zu MorphMarket: Wählen Sie eine Art, hinterlegen Sie die Genetik beider Elternteile und erhalten Sie fundierte Wahrscheinlichkeiten für visuelle Nachzuchten sowie Trägertiere.',
        ],
        'genetics_empty_notice' => [
            'group' => 'Genetik',
            'label' => 'Hinweis ohne Daten',
            'type' => 'text',
            'default' => 'Aktuell sind keine genetischen Datensätze hinterlegt. Bitte melden Sie sich als Administrator an, um Arten und Gene zu pflegen.',
        ],
        'genetics_submit' => [
            'group' => 'Genetik',
            'label' => 'Berechnen Button',
            'type' => 'text',
            'default' => 'Kombination berechnen',
        ],
        'news_title' => [
            'group' => 'Neuigkeiten',
            'label' => 'Seitenüberschrift',
            'type' => 'text',
            'default' => 'Neuigkeiten',
        ],
        'news_intro' => [
            'group' => 'Neuigkeiten',
            'label' => 'Einleitung',
            'type' => 'text',
            'default' => 'Aktuelle Updates aus dem FeroxZ Center.',
        ],
        'news_read_more' => [
            'group' => 'Neuigkeiten',
            'label' => 'Weiterlesen-Button',
            'type' => 'text',
            'default' => 'Weiterlesen',
        ],
        'care_title' => [
            'group' => 'Pflegeleitfaden',
            'label' => 'Seitenüberschrift',
            'type' => 'text',
            'default' => 'Pflegeleitfaden',
        ],
        'care_intro' => [
            'group' => 'Pflegeleitfaden',
            'label' => 'Einleitung',
            'type' => 'text',
            'default' => 'Wissensdatenbank mit Pflegeprofilen, Technik- und Ernährungsrichtlinien.',
        ],
        'care_read_more' => [
            'group' => 'Pflegeleitfaden',
            'label' => 'Artikel-Link',
            'type' => 'text',
            'default' => 'Artikel lesen',
        ],
        'login_title' => [
            'group' => 'Login',
            'label' => 'Überschrift',
            'type' => 'text',
            'default' => 'Login',
        ],
        'login_username_label' => [
            'group' => 'Login',
            'label' => 'Benutzername Label',
            'type' => 'text',
            'default' => 'Benutzername',
        ],
        'login_password_label' => [
            'group' => 'Login',
            'label' => 'Passwort Label',
            'type' => 'text',
            'default' => 'Passwort',
        ],
        'login_submit' => [
            'group' => 'Login',
            'label' => 'Login Button',
            'type' => 'text',
            'default' => 'Anmelden',
        ],
        'footer_rights' => [
            'group' => 'Footer',
            'label' => 'Rechtsvermerk',
            'type' => 'text',
            'default' => 'Alle Rechte vorbehalten.',
        ],
    ];

    return $definitions;
}

function get_content_groups(): array
{
    $groups = [];
    foreach (get_content_definitions() as $key => $definition) {
        $group = $definition['group'] ?? 'Allgemein';
        $groups[$group][$key] = $definition;
    }
    ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($groups as &$entries) {
        uasort($entries, static function ($a, $b) {
            return strnatcasecmp($a['label'], $b['label']);
        });
    }
    return $groups;
}

function content_value(array $settings, string $key): string
{
    $definitions = get_content_definitions();
    $default = $definitions[$key]['default'] ?? '';
    return $settings[$key] ?? $default;
}

function get_home_section_definitions(): array
{
    return [
        'highlights' => [
            'label' => 'Tier-Highlights',
            'description' => 'Präsentiert ausgewählte Tiere mit Bildern und Genetik.'
        ],
        'adoption' => [
            'label' => 'Vermittlung',
            'description' => 'Zeigt aktuelle Vermittlungstiere inklusive Kontaktmöglichkeiten.'
        ],
        'news' => [
            'label' => 'News',
            'description' => 'Listet die neuesten veröffentlichten Neuigkeiten auf.'
        ],
        'care' => [
            'label' => 'Wiki & Pflegewissen',
            'description' => 'Hebt die wichtigsten Pflege- und Haltungsartikel hervor.'
        ],
        'gallery' => [
            'label' => 'Galerie',
            'description' => 'Zeigt ausgewählte Bilder aus der Fotogalerie.'
        ],
    ];
}

function default_home_sections_layout(): array
{
    return array_map(
        static fn($key) => ['key' => $key, 'enabled' => true],
        array_keys(get_home_section_definitions())
    );
}

function sanitize_home_sections_layout(array $layout): array
{
    $definitions = get_home_section_definitions();
    $sanitized = [];
    foreach ($layout as $entry) {
        if (!is_array($entry) || empty($entry['key'])) {
            continue;
        }
        $key = $entry['key'];
        if (!isset($definitions[$key])) {
            continue;
        }
        $sanitized[$key] = [
            'key' => $key,
            'enabled' => !empty($entry['enabled']),
        ];
    }

    foreach ($definitions as $key => $_definition) {
        if (!isset($sanitized[$key])) {
            $sanitized[$key] = ['key' => $key, 'enabled' => true];
        }
    }

    return array_values($sanitized);
}

function get_home_sections_layout(array $settings): array
{
    $raw = $settings['home_sections_layout'] ?? null;
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return sanitize_home_sections_layout($decoded);
        }
    }

    return default_home_sections_layout();
}

function serialize_home_sections_layout(array $layout): string
{
    return json_encode(sanitize_home_sections_layout($layout), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

