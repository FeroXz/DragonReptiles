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
            <span class="app-brand__logo">
                <?= strtoupper(substr($settings['site_title'] ?? APP_NAME, 0, 2)) ?>
            </span>
            <span>
                <span class="app-brand__title"><?= htmlspecialchars($settings['site_title'] ?? APP_NAME) ?></span>
                <span class="app-brand__subtitle"><?= htmlspecialchars($settings['site_tagline'] ?? '') ?></span>
            </span>
        </a>
        <nav class="app-nav" data-desktop-nav>
            <a href="<?= BASE_URL ?>/index.php" class="app-nav__link <?= ($currentRoute === 'home') ? 'is-active' : '' ?>">Start</a>
            <a href="<?= BASE_URL ?>/index.php?route=animals" class="app-nav__link <?= ($currentRoute === 'animals') ? 'is-active' : '' ?>">Tierübersicht</a>
            <a href="<?= BASE_URL ?>/index.php?route=news" class="app-nav__link <?= ($currentRoute === 'news') ? 'is-active' : '' ?>">Neuigkeiten</a>
            <div class="app-nav__group" data-nav-group>
                <a href="<?= BASE_URL ?>/index.php?route=care-guide" class="app-nav__link <?= $isCareActive ? 'is-active' : '' ?>" data-nav-trigger>
                    Pflegeleitfaden
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
            <a href="<?= BASE_URL ?>/index.php?route=genetics" class="app-nav__link <?= ($currentRoute === 'genetics') ? 'is-active' : '' ?>">Genetik</a>
            <?php foreach (($navPages ?? []) as $navPage): ?>
                <?php
                    $parentActive = ($currentRoute === 'page' && ($activePageSlug ?? '') === $navPage['slug']);
                    $childActive = false;
                    foreach ($navPage['children'] ?? [] as $childPage) {
                        if ($currentRoute === 'page' && ($activePageSlug ?? '') === $childPage['slug']) {
                            $childActive = true;
                            break;
                        }
                    }
                    $isActive = $parentActive || $childActive;
                ?>
                <div class="app-nav__group" data-nav-group>
                    <a href="<?= BASE_URL ?>/index.php?route=page&amp;slug=<?= urlencode($navPage['slug']) ?>" class="app-nav__link <?= $isActive ? 'is-active' : '' ?>" <?= !empty($navPage['children']) ? 'data-nav-trigger' : '' ?>>
                        <?= htmlspecialchars($navPage['title']) ?>
                        <?php if (!empty($navPage['children'])): ?>
                            <svg class="h-4 w-4" data-chevron fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                            </svg>
                        <?php endif; ?>
                    </a>
                    <?php if (!empty($navPage['children'])): ?>
                        <div class="app-nav__dropdown" role="menu">
                            <?php foreach ($navPage['children'] as $childPage): ?>
                                <a href="<?= BASE_URL ?>/index.php?route=page&amp;slug=<?= urlencode($childPage['slug']) ?>" class="<?= ($currentRoute === 'page' && ($activePageSlug ?? '') === $childPage['slug']) ? 'is-active' : '' ?>"><?= htmlspecialchars($childPage['title']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <a href="<?= BASE_URL ?>/index.php?route=adoption" class="app-nav__link <?= ($currentRoute === 'adoption') ? 'is-active' : '' ?>">Tierabgabe</a>
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
            <a href="<?= BASE_URL ?>/index.php" class="<?= ($currentRoute === 'home') ? 'app-nav__link is-active' : 'app-nav__link' ?>">Start</a>
            <a href="<?= BASE_URL ?>/index.php?route=animals" class="<?= ($currentRoute === 'animals') ? 'app-nav__link is-active' : 'app-nav__link' ?>">Tierübersicht</a>
            <a href="<?= BASE_URL ?>/index.php?route=news" class="<?= ($currentRoute === 'news') ? 'app-nav__link is-active' : 'app-nav__link' ?>">Neuigkeiten</a>
            <details class="group" <?= $isCareActive ? 'open' : '' ?>>
                <summary class="app-nav__link">Pflegeleitfaden<svg class="h-4 w-4 group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" /></svg></summary>
                <div class="mt-2 space-y-1 pl-3 text-sm">
                    <a href="<?= BASE_URL ?>/index.php?route=care-guide" class="<?= ($currentRoute === 'care-guide') ? 'app-nav__link is-active' : 'app-nav__link' ?>">Übersicht</a>
                    <?php foreach (($navCareArticles ?? []) as $careNav): ?>
                        <a href="<?= BASE_URL ?>/index.php?route=care-article&amp;slug=<?= urlencode($careNav['slug']) ?>" class="app-nav__link <?= ($currentRoute === 'care-article' && ($activeCareSlug ?? '') === $careNav['slug']) ? 'is-active' : '' ?>"><?= htmlspecialchars($careNav['title']) ?></a>
                    <?php endforeach; ?>
                </div>
            </details>
            <a href="<?= BASE_URL ?>/index.php?route=genetics" class="<?= ($currentRoute === 'genetics') ? 'app-nav__link is-active' : 'app-nav__link' ?>">Genetik</a>
            <?php foreach (($navPages ?? []) as $navPage): ?>
                <?php
                    $parentActive = ($currentRoute === 'page' && ($activePageSlug ?? '') === $navPage['slug']);
                    $childActive = false;
                    foreach ($navPage['children'] ?? [] as $childPage) {
                        if ($currentRoute === 'page' && ($activePageSlug ?? '') === $childPage['slug']) {
                            $childActive = true;
                            break;
                        }
                    }
                ?>
                <details class="group" <?= ($parentActive || $childActive) ? 'open' : '' ?>>
                    <summary class="app-nav__link">
                        <?= htmlspecialchars($navPage['title']) ?>
                        <?php if (!empty($navPage['children'])): ?>
                            <svg class="h-4 w-4 group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" /></svg>
                        <?php endif; ?>
                    </summary>
                    <?php if (!empty($navPage['children'])): ?>
                        <div class="mt-2 space-y-1 pl-3 text-sm">
                            <?php foreach ($navPage['children'] as $childPage): ?>
                                <a href="<?= BASE_URL ?>/index.php?route=page&amp;slug=<?= urlencode($childPage['slug']) ?>" class="app-nav__link <?= ($currentRoute === 'page' && ($activePageSlug ?? '') === $childPage['slug']) ? 'is-active' : '' ?>"><?= htmlspecialchars($childPage['title']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>
            <a href="<?= BASE_URL ?>/index.php?route=adoption" class="<?= ($currentRoute === 'adoption') ? 'app-nav__link is-active' : 'app-nav__link' ?>">Tierabgabe</a>
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
