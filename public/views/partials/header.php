<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_title'] ?? APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@nuxt/ui@4.1.0/dist/runtime/index.css">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link rel="icon" type="image/svg+xml" href="<?= asset('logo-icon.svg') ?>">
    <link rel="apple-touch-icon" href="<?= asset('logo-icon.svg') ?>">
    <meta name="theme-color" content="#0b1120">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        night: {
                            950: '#030712',
                            900: '#0b1120',
                            800: '#111827',
                        },
                        brand: {
                            300: '#a78bfa',
                            400: '#7f5af0',
                            500: '#6246ea',
                            600: '#4c3ac1',
                        },
                        accent: {
                            400: '#38e3ff',
                            500: '#22d3ee',
                        },
                    },
                    boxShadow: {
                        glow: '0 28px 80px rgba(127, 90, 240, 0.26)',
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'ui-sans-serif', 'Segoe UI', 'sans-serif'],
                    },
                }
            }
        };
    </script>
    <?php
        $activeThemeKey = $settings['active_theme'] ?? 'horizon';
        $themeConfig = get_theme_config($activeThemeKey);
    ?>
    <link rel="stylesheet" href="<?= asset('style.css') ?>">
    <?php if (!empty($themeConfig['stylesheet'])): ?>
        <link rel="stylesheet" href="<?= asset($themeConfig['stylesheet']) ?>">
    <?php endif; ?>
</head>
<?php $isCareActive = ($currentRoute === 'care-guide' || $currentRoute === 'care-article'); ?>
<body class="min-h-screen font-sans text-slate-100 <?= htmlspecialchars($themeConfig['body_class'] ?? '') ?>">
<header class="app-header">
    <div class="app-header__inner">
        <a href="<?= BASE_URL ?>/index.php" class="app-brand">
            <span class="app-brand__logo" aria-hidden="true">
                <img src="<?= asset('logo-icon.svg') ?>" alt="" width="44" height="44" loading="lazy">
            </span>
            <span>
                <span class="app-brand__title"><?= htmlspecialchars($settings['site_title'] ?? APP_NAME) ?></span>
                <span class="app-brand__subtitle"><?= htmlspecialchars($settings['site_tagline'] ?? '') ?></span>
            </span>
        </a>
        <div class="app-menu-status">
            <?php
                $menuItems = array_values($menuItems ?? []);
                $iconLibrary = function_exists('menu_icon_library') ? menu_icon_library() : [];
                $normalizeMenuPath = static function (?string $path): string {
                    $path = trim($path ?? '');
                    if ($path === '') {
                        return '';
                    }
                    if (preg_match('#^https?://#i', $path)) {
                        $parsedPath = parse_url($path, PHP_URL_PATH) ?? '';
                        $parsedQuery = parse_url($path, PHP_URL_QUERY);
                        $path = $parsedPath . ($parsedQuery ? '?' . $parsedQuery : '');
                    }
                    if (BASE_URL !== '' && str_starts_with($path, BASE_URL)) {
                        $path = substr($path, strlen(BASE_URL));
                    }
                    if ($path === '') {
                        return '';
                    }
                    return str_starts_with($path, '/') ? $path : '/' . $path;
                };
                $menuPaths = array_map(static function ($item) use ($normalizeMenuPath) {
                    return $normalizeMenuPath($item['path'] ?? '');
                }, $menuItems);
                $additionalPages = [];
                foreach (($navPages ?? []) as $navPage) {
                    $pagePath = '/index.php?route=page&slug=' . urlencode($navPage['slug']);
                    if (!in_array($pagePath, $menuPaths, true)) {
                        $additionalPages[] = [
                            'label' => $navPage['title'],
                            'path' => $pagePath,
                            'icon' => '',
                            'target' => '_self',
                        ];
                    }
                }
                if ($additionalPages) {
                    $menuItems = array_merge($menuItems, $additionalPages);
                }
                $buildMenuUrl = static function (array $item): string {
                    $path = trim($item['path'] ?? '');
                    if ($path === '') {
                        return '#';
                    }
                    if (preg_match('#^https?://#i', $path)) {
                        return $path;
                    }
                    $base = rtrim(BASE_URL, '/');
                    return $base . $path;
                };
                $isMenuItemActive = static function (array $item) use ($normalizeMenuPath, $currentRoute, $activePageSlug, $activeCareSlug, $isCareActive): bool {
                    $normalized = $normalizeMenuPath($item['path'] ?? '');
                    if ($normalized === '/index.php' || $normalized === '/index.php?route=home' || $normalized === '/') {
                        return $currentRoute === 'home';
                    }
                    $query = parse_url($normalized, PHP_URL_QUERY);
                    $params = [];
                    if ($query) {
                        parse_str($query, $params);
                    }
                    $route = $params['route'] ?? null;
                    if ($route === null) {
                        return false;
                    }
                    if ($route === 'page') {
                        $slug = $params['slug'] ?? null;
                        return $currentRoute === 'page' && $slug && $slug === ($activePageSlug ?? '');
                    }
                    if ($route === 'care-guide') {
                        return $isCareActive;
                    }
                    if ($route === 'care-article') {
                        $slug = $params['slug'] ?? null;
                        return $currentRoute === 'care-article' && $slug && $slug === ($activeCareSlug ?? '');
                    }
                    return $currentRoute === $route;
                };
                $activeMenuLabel = $settings['site_title'] ?? 'Landing';
                foreach ($menuItems as $menuItem) {
                    if ($isMenuItemActive($menuItem)) {
                        $activeMenuLabel = $menuItem['label'];
                        break;
                    }
                }
            ?>
            <p class="app-menu-status__eyebrow">Aktueller Bereich</p>
            <p class="app-menu-status__title"><?= htmlspecialchars($activeMenuLabel) ?></p>
        </div>
        <button type="button" class="app-menu-trigger" data-menu-toggle aria-expanded="false" aria-controls="app-menu-overlay">
            <span>Menü</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 12h13M10 17h10" />
            </svg>
        </button>
    </div>
</header>
<div class="app-menu-overlay" id="app-menu-overlay" data-menu-overlay aria-hidden="true">
    <div class="app-menu-overlay__backdrop" data-menu-dismiss></div>
    <div class="app-menu-overlay__panel" role="dialog" aria-modal="true" aria-label="Navigation" tabindex="-1">
        <div class="app-menu-overlay__heading">
            <p class="app-menu-overlay__eyebrow">Dragon Reptiles</p>
            <h2><?= htmlspecialchars($settings['site_title'] ?? APP_NAME) ?></h2>
            <p class="app-menu-overlay__subline"><?= htmlspecialchars($settings['site_tagline'] ?? 'Landing Page inspiriert von HTML5UP') ?></p>
        </div>
        <nav class="app-menu-overlay__nav" aria-label="Hauptnavigation">
        <?php foreach ($menuItems as $menuItem): ?>
            <?php
                $url = $buildMenuUrl($menuItem);
                $target = ($menuItem['target'] ?? '_self') === '_blank' ? '_blank' : '_self';
                $isActive = $isMenuItemActive($menuItem);
                $normalizedPath = $normalizeMenuPath($menuItem['path'] ?? '');
                $renderDropdown = $normalizedPath === '/index.php?route=care-guide' && !empty($navCareArticles);
            ?>
            <?php if ($renderDropdown): ?>
                <div class="app-menu-overlay__group">
                    <a href="<?= htmlspecialchars($url) ?>" class="app-menu-overlay__link <?= $isActive ? 'is-active' : '' ?>" target="<?= htmlspecialchars($target) ?>">
                        <?= htmlspecialchars($menuItem['label']) ?>
                    </a>
                    <div class="app-menu-overlay__subnav">
                        <a href="<?= BASE_URL ?>/index.php?route=care-guide" class="<?= ($currentRoute === 'care-guide') ? 'is-active' : '' ?>">Übersicht</a>
                        <?php foreach (($navCareArticles ?? []) as $careNav): ?>
                            <a href="<?= BASE_URL ?>/index.php?route=care-article&amp;slug=<?= urlencode($careNav['slug']) ?>" class="<?= ($currentRoute === 'care-article' && ($activeCareSlug ?? '') === $careNav['slug']) ? 'is-active' : '' ?>"><?= htmlspecialchars($careNav['title']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= htmlspecialchars($url) ?>" class="app-menu-overlay__link <?= $isActive ? 'is-active' : '' ?>" target="<?= htmlspecialchars($target) ?>">
                    <?= htmlspecialchars($menuItem['label']) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
        </nav>
        <div class="app-menu-overlay__ctas">
        <?php if (current_user()): ?>
            <a href="<?= BASE_URL ?>/index.php?route=my-animals" class="app-menu-overlay__cta <?= ($currentRoute === 'my-animals') ? 'is-active' : '' ?>">Meine Tiere</a>
            <a href="<?= BASE_URL ?>/index.php?route=breeding" class="app-menu-overlay__cta <?= ($currentRoute === 'breeding') ? 'is-active' : '' ?>">Zuchtplanung</a>
            <a href="<?= BASE_URL ?>/index.php?route=admin/dashboard" class="app-menu-overlay__cta <?= str_starts_with($currentRoute, 'admin/') ? 'is-active' : '' ?>">Admin</a>
            <a href="<?= BASE_URL ?>/index.php?route=logout" class="app-menu-overlay__cta app-menu-overlay__cta--primary">Logout</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/index.php?route=login" class="app-menu-overlay__cta app-menu-overlay__cta--primary <?= ($currentRoute === 'login') ? 'is-active' : '' ?>">Login</a>
        <?php endif; ?>
        </div>
        <button type="button" class="app-menu-overlay__close" data-menu-toggle aria-label="Menü schließen">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12" />
            </svg>
        </button>
    </div>
</div>
<main class="app-main">
