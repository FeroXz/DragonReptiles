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
        <nav class="app-nav" data-desktop-nav>
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
            ?>
            <?php foreach ($menuItems as $menuItem): ?>
                <?php
                    $url = $buildMenuUrl($menuItem);
                    $target = ($menuItem['target'] ?? '_self') === '_blank' ? '_blank' : '_self';
                    $isActive = $isMenuItemActive($menuItem);
                    $normalizedPath = $normalizeMenuPath($menuItem['path'] ?? '');
                    $renderDropdown = $normalizedPath === '/index.php?route=care-guide' && !empty($navCareArticles);
                ?>
                <?php if ($renderDropdown): ?>
                    <div class="app-nav__group" data-nav-group>
                        <a href="<?= htmlspecialchars($url) ?>" class="app-nav__link <?= $isActive ? 'is-active' : '' ?>" data-nav-trigger target="<?= htmlspecialchars($target) ?>">
                            <?php if (!empty($menuItem['icon'])): ?>
                                <span class="app-nav__icon"><?= htmlspecialchars($menuItem['icon']) ?></span>
                            <?php endif; ?>
                            <?= htmlspecialchars($menuItem['label']) ?>
                            <svg class="h-4 w-4" data-chevron fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                            </svg>
                        </a>
                        <div class="app-nav__dropdown" role="menu">
                            <a href="<?= BASE_URL ?>/index.php?route=care-guide" class="<?= ($currentRoute === 'care-guide') ? 'is-active' : '' ?>">Übersicht</a>
                            <?php foreach (($navCareArticles ?? []) as $careNav): ?>
                                <a href="<?= BASE_URL ?>/index.php?route=care-article&amp;slug=<?= urlencode($careNav['slug']) ?>" class="<?= ($currentRoute === 'care-article' && ($activeCareSlug ?? '') === $careNav['slug']) ? 'is-active' : '' ?>"><?= htmlspecialchars($careNav['title']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($url) ?>" class="app-nav__link <?= $isActive ? 'is-active' : '' ?>" target="<?= htmlspecialchars($target) ?>">
                        <?php
                            $iconKey = trim((string)($menuItem['icon'] ?? ''));
                            if ($iconKey !== '') {
                                if (isset($iconLibrary[$iconKey])) {
                                    echo '<span class="app-nav__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' . $iconLibrary[$iconKey] . '</svg></span>';
                                } else {
                                    echo '<span class="app-nav__icon" aria-hidden="true">' . htmlspecialchars($iconKey) . '</span>';
                                }
                            }
                        ?>
                        <?= htmlspecialchars($menuItem['label']) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (current_user()): ?>
                <a href="<?= BASE_URL ?>/index.php?route=my-animals" class="app-nav__link <?= ($currentRoute === 'my-animals') ? 'is-active' : '' ?>">Meine Tiere</a>
                <a href="<?= BASE_URL ?>/index.php?route=breeding" class="app-nav__link <?= ($currentRoute === 'breeding') ? 'is-active' : '' ?>">Zuchtplanung</a>
                <a href="<?= BASE_URL ?>/index.php?route=admin/dashboard" class="app-nav__link <?= str_starts_with($currentRoute, 'admin/') ? 'is-active' : '' ?>">Admin</a>
                <a href="<?= BASE_URL ?>/index.php?route=logout" class="app-nav__cta">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/index.php?route=login" class="app-nav__link <?= ($currentRoute === 'login') ? 'is-active' : '' ?>">Login</a>
            <?php endif; ?>
        </nav>
        <button type="button" class="app-nav__toggle lg:hidden" data-mobile-nav-toggle aria-expanded="false">
            <span class="sr-only">Navigation umschalten</span>
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>
    <div class="app-nav-mobile hidden" data-mobile-nav-panel>
        <nav>
            <?php foreach ($menuItems as $menuItem): ?>
                <?php
                    $url = $buildMenuUrl($menuItem);
                    $target = ($menuItem['target'] ?? '_self') === '_blank' ? '_blank' : '_self';
                    $normalizedPath = $normalizeMenuPath($menuItem['path'] ?? '');
                    $isActive = $isMenuItemActive($menuItem);
                    $renderDropdown = $normalizedPath === '/index.php?route=care-guide' && !empty($navCareArticles);
                ?>
                <?php if ($renderDropdown): ?>
                    <details class="group" <?= $isCareActive ? 'open' : '' ?>>
                        <summary class="app-nav__link">
                            <?= htmlspecialchars($menuItem['label']) ?>
                            <svg class="h-4 w-4 group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" /></svg>
                        </summary>
                        <div class="mt-2 space-y-1 pl-3 text-sm">
                            <a href="<?= BASE_URL ?>/index.php?route=care-guide" class="<?= ($currentRoute === 'care-guide') ? 'app-nav__link is-active' : 'app-nav__link' ?>">Übersicht</a>
                            <?php foreach (($navCareArticles ?? []) as $careNav): ?>
                                <a href="<?= BASE_URL ?>/index.php?route=care-article&amp;slug=<?= urlencode($careNav['slug']) ?>" class="app-nav__link <?= ($currentRoute === 'care-article' && ($activeCareSlug ?? '') === $careNav['slug']) ? 'is-active' : '' ?>"><?= htmlspecialchars($careNav['title']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($url) ?>" class="<?= $isActive ? 'app-nav__link is-active' : 'app-nav__link' ?>" target="<?= htmlspecialchars($target) ?>">
                        <?= htmlspecialchars($menuItem['label']) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (current_user()): ?>
                <a href="<?= BASE_URL ?>/index.php?route=my-animals" class="<?= ($currentRoute === 'my-animals') ? 'app-nav__link is-active' : 'app-nav__link' ?>">Meine Tiere</a>
                <a href="<?= BASE_URL ?>/index.php?route=breeding" class="<?= ($currentRoute === 'breeding') ? 'app-nav__link is-active' : 'app-nav__link' ?>">Zuchtplanung</a>
                <a href="<?= BASE_URL ?>/index.php?route=admin/dashboard" class="<?= str_starts_with($currentRoute, 'admin/') ? 'app-nav__link is-active' : 'app-nav__link' ?>">Admin</a>
                <a href="<?= BASE_URL ?>/index.php?route=logout" class="app-nav__link">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/index.php?route=login" class="<?= ($currentRoute === 'login') ? 'app-nav__link is-active' : 'app-nav__link' ?>">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="app-main">
