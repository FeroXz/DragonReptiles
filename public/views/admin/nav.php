<?php
    $navItems = [
        [
            'route' => 'admin/dashboard',
            'href' => BASE_URL . '/index.php?route=admin/dashboard',
            'label' => 'Übersicht',
            'description' => 'KPIs & Trends',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5l6-6 4.5 4.5L21 4.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M3 20.25h18" /></svg>',
            'visible' => true,
        ],
        [
            'route' => 'admin/animals',
            'href' => BASE_URL . '/index.php?route=admin/animals',
            'label' => 'Tiere',
            'description' => 'Bestand & Pflege',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 9.75l7.5-6 7.5 6V18a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 18V9.75z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 22.5V12h6v10.5" /></svg>',
            'visible' => true,
        ],
        [
            'route' => 'admin/breeding',
            'href' => BASE_URL . '/index.php?route=admin/breeding',
            'label' => 'Zuchtplanung',
            'description' => 'Paare & Projekte',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a3.75 3.75 0 013.75-3.75h7.5A3.75 3.75 0 0119.5 12v3.75A3.75 3.75 0 0115.75 19.5h-7.5A3.75 3.75 0 014.5 15.75V12z" /><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6A3.75 3.75 0 0112 2.25 3.75 3.75 0 0115.75 6v1.5" /></svg>',
            'visible' => is_authorized('can_manage_animals'),
        ],
        [
            'route' => 'admin/adoption',
            'href' => BASE_URL . '/index.php?route=admin/adoption',
            'label' => 'Tierabgabe',
            'description' => 'Inserate & Matches',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8.25l9-6 9 6v9a3 3 0 01-3 3H6a3 3 0 01-3-3v-9z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 21.75V12h6v9.75" /></svg>',
            'visible' => true,
        ],
        [
            'route' => 'admin/inquiries',
            'href' => BASE_URL . '/index.php?route=admin/inquiries',
            'label' => 'Anfragen',
            'description' => 'Interessenten & Verlauf',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-9-9" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35" /></svg>',
            'visible' => true,
        ],
        [
            'route' => 'admin/pages',
            'href' => BASE_URL . '/index.php?route=admin/pages',
            'label' => 'Seiten',
            'description' => 'Inhalte strukturieren',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25A2.25 2.25 0 016 3h8.25L20.25 9v9.75A2.25 2.25 0 0118 21H6a2.25 2.25 0 01-2.25-2.25V5.25z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v6h6" /></svg>',
            'visible' => is_authorized('can_manage_settings'),
        ],
        [
            'route' => 'admin/news',
            'href' => BASE_URL . '/index.php?route=admin/news',
            'label' => 'Neuigkeiten',
            'description' => 'Updates & Stories',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 12h12" /><circle cx="12" cy="12" r="9" /></svg>',
            'visible' => is_authorized('can_manage_settings'),
        ],
        [
            'route' => 'admin/care',
            'href' => BASE_URL . '/index.php?route=admin/care',
            'label' => 'Pflegeleitfaden',
            'description' => 'Anleitungen & Tipps',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6l7.5 4.5-7.5 4.5L4.5 10.5 12 6z" /><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15l7.5 4.5 7.5-4.5" /></svg>',
            'visible' => is_authorized('can_manage_settings'),
        ],
        [
            'route' => 'admin/genetics',
            'href' => BASE_URL . '/index.php?route=admin/genetics',
            'label' => 'Genetik',
            'description' => 'Arten & Gene',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18" /><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 6.75h9a3.75 3.75 0 010 7.5h-9a3.75 3.75 0 010-7.5z" /></svg>',
            'visible' => is_authorized('can_manage_settings'),
        ],
        [
            'route' => 'admin/gallery',
            'href' => BASE_URL . '/index.php?route=admin/gallery',
            'label' => 'Galerie',
            'description' => 'Visuals & Uploads',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25A2.25 2.25 0 016 3h12a2.25 2.25 0 012.25 2.25v12A2.25 2.25 0 0118 19.5H6a2.25 2.25 0 01-2.25-2.25v-12z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 9.75l2.25 2.25L15 9" /><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 16.5L10.5 12l3 3.75L15.75 12l2.25 4.5" /></svg>',
            'visible' => is_authorized('can_manage_settings'),
        ],
        [
            'route' => 'admin/home-layout',
            'href' => BASE_URL . '/index.php?route=admin/home-layout',
            'label' => 'Startseite',
            'description' => 'Abschnitte & Reihenfolge',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6h15" /><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15" /><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 18h9" /></svg>',
            'visible' => is_authorized('can_manage_settings'),
        ],
        [
            'route' => 'admin/settings',
            'href' => BASE_URL . '/index.php?route=admin/settings',
            'label' => 'Einstellungen',
            'description' => 'System & Branding',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h3M5.25 9h13.5M4.5 12h15M5.25 15h13.5M10.5 18h3" /></svg>',
            'visible' => is_authorized('can_manage_settings'),
        ],
        [
            'route' => 'admin/content',
            'href' => BASE_URL . '/index.php?route=admin/content',
            'label' => 'Texte',
            'description' => 'Hero & Kopien',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5h15" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15" /><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 9h15" /><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 13.5h7.5" /></svg>',
            'visible' => is_authorized('can_manage_settings'),
        ],
        [
            'route' => 'admin/update',
            'href' => BASE_URL . '/index.php?route=admin/update',
            'label' => 'Updates',
            'description' => 'Version & Pakete',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v3" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12a7.5 7.5 0 11-7.5-7.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 12l3 3" /></svg>',
            'visible' => is_authorized('can_manage_settings'),
        ],
        [
            'route' => 'admin/users',
            'href' => BASE_URL . '/index.php?route=admin/users',
            'label' => 'Benutzer',
            'description' => 'Rechte & Rollen',
            'icon' => '<svg class="horizon-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 21a7.5 7.5 0 0115 0" /></svg>',
            'visible' => current_user()['role'] === 'admin',
        ],
    ];
?>
<nav class="horizon-nav" data-admin-nav>
    <?php foreach ($navItems as $item): ?>
        <?php if (!$item['visible']) { continue; } ?>
        <?php $isActive = ($currentRoute === $item['route']); ?>
        <a href="<?= $item['href'] ?>" class="horizon-nav__link<?= $isActive ? ' is-active' : '' ?>" aria-current="<?= $isActive ? 'page' : 'false' ?>">
            <?= $item['icon'] ?>
            <span>
                <span class="horizon-nav__label"><?= htmlspecialchars($item['label']) ?></span>
                <?php if (!empty($item['description'])): ?>
                    <span class="horizon-nav__meta"><?= htmlspecialchars($item['description']) ?></span>
                <?php endif; ?>
            </span>
        </a>
    <?php endforeach; ?>
</nav>
