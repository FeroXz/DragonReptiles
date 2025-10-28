<?php
$menuItems = $adminMenuItems ?? [];
$iconLibrary = function_exists('menu_icon_library') ? menu_icon_library() : [];
$requiresForPath = static function (string $path): ?string {
    $map = [
        'admin/breeding' => 'can_manage_animals',
        'admin/pages' => 'can_manage_settings',
        'admin/news' => 'can_manage_settings',
        'admin/care' => 'can_manage_settings',
        'admin/media' => 'can_manage_settings',
        'admin/genetics' => 'can_manage_settings',
        'admin/gallery' => 'can_manage_settings',
        'admin/home-layout' => 'can_manage_settings',
        'admin/settings' => 'can_manage_settings',
        'admin/content' => 'can_manage_settings',
        'admin/update' => 'can_manage_settings',
        'admin/menu' => 'can_manage_settings',
        'admin/users' => 'role:admin',
    ];
    foreach ($map as $needle => $requirement) {
        if (str_contains($path, $needle)) {
            return $requirement;
        }
    }
    return null;
};
$buildUrl = static function (array $item): string {
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
$normalizePath = static function (array $item): string {
    $path = trim($item['path'] ?? '');
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        $parsedPath = parse_url($path, PHP_URL_PATH) ?? '';
        $parsedQuery = parse_url($path, PHP_URL_QUERY);
        $path = $parsedPath . ($parsedQuery ? '?' . $parsedQuery : '');
    }
    return $path;
};
?>
<nav class="admin-nav nui-panel nui-panel--floating" aria-label="Admin-Navigation">
    <?php foreach ($menuItems as $item): ?>
        <?php
            $rawPath = $normalizePath($item);
            $requirement = $requiresForPath($rawPath);
            if ($requirement === 'role:admin' && (current_user()['role'] ?? '') !== 'admin') {
                continue;
            }
            if ($requirement && $requirement !== 'role:admin' && !is_authorized($requirement)) {
                continue;
            }
            $url = $buildUrl($item);
            $target = ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self';
            $isActive = false;
            $query = parse_url($rawPath, PHP_URL_QUERY);
            $params = [];
            if ($query) {
                parse_str($query, $params);
            }
            $route = $params['route'] ?? null;
            if ($route) {
                if ($route === 'admin/menu') {
                    $isActive = $currentRoute === 'admin/menu';
                } else {
                    $isActive = $currentRoute === $route;
                }
            } elseif ($rawPath === '/index.php?route=admin/dashboard' || $rawPath === '/admin/dashboard') {
                $isActive = $currentRoute === 'admin/dashboard';
            }
        ?>
        <a href="<?= htmlspecialchars($url) ?>" class="admin-nav__link nui-pill <?= $isActive ? 'is-active' : '' ?>" target="<?= htmlspecialchars($target) ?>">
            <span class="admin-nav__icon" aria-hidden="true">
                <?php
                    $iconKey = trim((string)($item['icon'] ?? ''));
                    if ($iconKey !== '' && isset($iconLibrary[$iconKey])) {
                        echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' . $iconLibrary[$iconKey] . '</svg>';
                    } elseif ($iconKey !== '') {
                        echo htmlspecialchars($iconKey);
                    } else {
                        echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2" /></svg>';
                    }
                ?>
            </span>
            <span class="admin-nav__label"><?= htmlspecialchars($item['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>
