<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/themes.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/animals.php';
require_once __DIR__ . '/adoption.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/pages.php';
require_once __DIR__ . '/news.php';
require_once __DIR__ . '/breeding.php';
require_once __DIR__ . '/careguide.php';
require_once __DIR__ . '/genetics.php';
require_once __DIR__ . '/gallery.php';
require_once __DIR__ . '/updater.php';

$pdo = get_database_connection();
initialize_database($pdo);
ensure_default_admin($pdo);
ensure_default_settings($pdo);
ensure_default_care_articles($pdo);
ensure_default_genetics($pdo);
