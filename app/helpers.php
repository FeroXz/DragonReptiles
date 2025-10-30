<?php declare(strict_types=1);

/**
 * Initializes a secure session.
 *
 * @return void
 */
function init_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Set secure session parameters
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');

    // Only set secure flag if HTTPS is enabled
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }

    // Set session lifetime
    ini_set('session.gc_maxlifetime', (string)SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', (string)SESSION_LIFETIME);

    session_start();

    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Renders a view template with the provided data.
 *
 * @param string $template Template name (without .php extension)
 * @param array $data Data to pass to the template
 * @return void
 */
function view(string $template, array $data = []): void
{
    if (!isset($data['currentRoute']) && isset($GLOBALS['currentRoute'])) {
        $data['currentRoute'] = $GLOBALS['currentRoute'];
    }

    global $pdo;
    if (isset($pdo)) {
        if (!isset($data['navPages']) && function_exists('get_navigation_pages')) {
            $data['navPages'] = get_navigation_pages($pdo);
        }
        if (!isset($data['navCareArticles']) && function_exists('get_published_care_articles')) {
            $data['navCareArticles'] = get_published_care_articles($pdo);
        }
        if (!isset($data['menuItems']) && function_exists('get_visible_menu_items')) {
            $data['menuItems'] = get_visible_menu_items($pdo, 'frontend');
        }
        $routeForContext = $data['currentRoute'] ?? ($GLOBALS['currentRoute'] ?? '');
        if (!isset($data['adminMenuItems']) && function_exists('get_visible_menu_items') && is_string($routeForContext) && str_starts_with($routeForContext, 'admin/')) {
            $data['adminMenuItems'] = get_visible_menu_items($pdo, 'admin');
        }
    }

    extract($data);
    include __DIR__ . '/../public/views/' . $template . '.php';
}

function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

function normalize_media_path(?string $path): ?string
{
    if ($path === null) {
        return null;
    }

    $path = trim($path);
    if ($path === '') {
        return null;
    }

    $path = str_replace('\\', '/', $path);

    if (preg_match('#^https?://#i', $path)) {
        $parsed = parse_url($path, PHP_URL_PATH);
        if (is_string($parsed) && $parsed !== '') {
            $path = $parsed;
        }
    }

    if (BASE_URL !== '' && str_starts_with($path, BASE_URL)) {
        $path = substr($path, strlen(BASE_URL));
    }

    $path = preg_replace('#^public/#', '', $path);
    $path = ltrim($path, '/');

    return $path !== '' ? $path : null;
}

function media_url(?string $path): ?string
{
    $normalized = normalize_media_path($path);
    if ($normalized === null) {
        return null;
    }

    $base = rtrim(BASE_URL, '/');
    return ($base !== '' ? $base : '') . '/' . $normalized;
}

function redirect(string $route, array $params = []): void
{
    $query = http_build_query(array_merge(['route' => $route], $params));
    header('Location: ' . BASE_URL . '/index.php?' . $query);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('login');
    }
}

function is_authorized(string $capability): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    if ($user['role'] === 'admin') {
        return true;
    }

    return !empty($user[$capability]);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message === null) {
        if (isset($_SESSION['flash'][$key])) {
            $value = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $value;
        }
        return null;
    }

    $_SESSION['flash'][$key] = $message;
    return null;
}

/**
 * Generates a new CSRF token.
 *
 * @return string The generated token
 */
function csrf_token(): string
{
    $now = time();
    if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }

    foreach ($_SESSION['csrf_tokens'] as $storedToken => $expiry) {
        if (!is_int($expiry) || $expiry < $now) {
            unset($_SESSION['csrf_tokens'][$storedToken]);
        }
    }

    if (count($_SESSION['csrf_tokens']) > CSRF_TOKEN_LIMIT) {
        $_SESSION['csrf_tokens'] = array_slice($_SESSION['csrf_tokens'], -CSRF_TOKEN_LIMIT, null, true);
    }

    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_tokens'][$token] = $now + CSRF_TOKEN_LIFETIME;

    return $token;
}

function verify_csrf_token(?string $token): bool
{
    if (!$token || !isset($_SESSION['csrf_tokens'][$token])) {
        return false;
    }

    $expiry = $_SESSION['csrf_tokens'][$token];
    unset($_SESSION['csrf_tokens'][$token]);

    return is_int($expiry) && $expiry >= time();
}

function require_csrf_token(string $route, array $params = []): void
{
    $token = $_POST['_token']
        ?? $_GET['_token']
        ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    if (verify_csrf_token($token)) {
        return;
    }

    // Wenn kein Token überprüft werden konnte, erzeugen wir ein frisches Token,
    // damit das nächste Absenden nicht erneut an einem leeren Feld scheitert.
    csrf_token();

    flash('error', 'Sicherheitsüberprüfung fehlgeschlagen. Bitte Formular erneut absenden.');
    redirect($route, $params);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
}

function ensure_directory(string $dir): void
{
    if (is_dir($dir)) {
        return;
    }

    if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException(sprintf('Verzeichnis "%s" konnte nicht erstellt werden.', $dir));
    }
}

/**
 * Securely handles file uploads with validation.
 *
 * @param array $file The uploaded file from $_FILES
 * @param bool $withDetails Whether to return detailed information
 * @return string|array|null The file path or detailed info, or null on failure
 */
function handle_upload(array $file, bool $withDetails = false)
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
        return null;
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    // Check file size
    $fileSize = $file['size'] ?? 0;
    if ($fileSize > MAX_UPLOAD_SIZE || $fileSize === 0) {
        return null;
    }

    ensure_directory(UPLOAD_PATH);

    // MIME type validation
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
    $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!$mimeType || !in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
        return null;
    }

    // Extension validation
    $originalName = $file['name'] ?? 'upload';
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_FILE_EXTENSIONS, true)) {
        return null;
    }

    // Check for double extensions (e.g., shell.php.jpg)
    $basename = pathinfo($originalName, PATHINFO_FILENAME);
    if (strpos($basename, '.') !== false) {
        return null;
    }

    // Generate safe filename with whitelisted extension
    $safeFilename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = UPLOAD_PATH . '/' . $safeFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    $relativePath = 'uploads/' . $safeFilename;

    if (!$withDetails) {
        return $relativePath;
    }

    $size = @filesize($destination);
    $dimensions = @getimagesize($destination) ?: null;

    return [
        'path' => $relativePath,
        'original_name' => $originalName,
        'mime_type' => $mimeType,
        'file_size' => $size !== false ? (int)$size : null,
        'width' => $dimensions ? (int)$dimensions[0] : null,
        'height' => $dimensions ? (int)$dimensions[1] : null,
    ];
}

function normalize_nullable_id($value): ?int
{
    if ($value === null) {
        return null;
    }

    if (is_string($value)) {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return null;
        }
    }

    if (!is_numeric($value)) {
        return null;
    }

    $intValue = (int)$value;
    return $intValue > 0 ? $intValue : null;
}

function normalize_flag($value): int
{
    $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $normalized ? 1 : 0;
}

function parse_partial_date(?string $value): array
{
    if (!$value) {
        return ['year' => '', 'month' => '', 'day' => ''];
    }

    $parts = explode('-', $value);
    $year = $parts[0] ?? '';
    $month = $parts[1] ?? '';
    $day = $parts[2] ?? '';

    return [
        'year' => ctype_digit($year) && strlen($year) === 4 ? $year : '',
        'month' => ctype_digit($month ?? '') && strlen($month) === 2 ? $month : '',
        'day' => ctype_digit($day ?? '') && strlen($day) === 2 ? $day : '',
    ];
}

/**
 * Normalizes partial date input (year, month, day).
 *
 * @param array $input Input array with 'year', 'month', 'day' keys
 * @return array [normalized_date|null, error_message|null]
 */
function normalize_partial_date_input(array $input): array
{
    $year = trim((string)($input['year'] ?? ''));
    $month = trim((string)($input['month'] ?? ''));
    $day = trim((string)($input['day'] ?? ''));

    if ($year === '' && ($month !== '' || $day !== '')) {
        return [null, 'Bitte zunächst ein Jahr auswählen, bevor Monat oder Tag gesetzt werden.'];
    }

    if ($year === '') {
        return [null, null];
    }

    if ($month === '' && $day !== '') {
        return [null, 'Bitte wählen Sie einen Monat, bevor Sie einen Tag festlegen.'];
    }

    $yearInt = (int)$year;
    $maxYear = (int)date('Y') + MAX_YEAR_OFFSET;
    if ($yearInt < MIN_YEAR || $yearInt > $maxYear) {
        return [null, 'Das ausgewählte Jahr liegt außerhalb des zulässigen Bereichs.'];
    }

    $parts = [$year];

    if ($month !== '') {
        $monthInt = (int)$month;
        if ($monthInt < 1 || $monthInt > 12) {
            return [null, 'Der ausgewählte Monat ist ungültig.'];
        }
        $parts[] = str_pad((string)$monthInt, 2, '0', STR_PAD_LEFT);

        if ($day !== '') {
            $dayInt = (int)$day;
            $maxDay = (int)date('t', strtotime(sprintf('%04d-%02d-01', $yearInt, $monthInt)));
            if ($dayInt < 1 || $dayInt > $maxDay) {
                return [null, 'Der ausgewählte Tag passt nicht zum gewählten Monat.'];
            }
            $parts[] = str_pad((string)$dayInt, 2, '0', STR_PAD_LEFT);
        }
    }

    return [implode('-', $parts), null];
}

function format_partial_date(?string $value): ?string
{
    if (!$value) {
        return null;
    }

    $parts = parse_partial_date($value);
    if ($parts['year'] === '') {
        return null;
    }

    $year = $parts['year'];
    if ($parts['month'] === '') {
        return $year;
    }

    $monthNames = [
        1 => 'Januar',
        2 => 'Februar',
        3 => 'März',
        4 => 'April',
        5 => 'Mai',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'August',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Dezember',
    ];
    $monthName = $monthNames[(int)$parts['month']] ?? null;
    if ($parts['day'] === '') {
        return $monthName ? sprintf('%s %s', $monthName, $year) : $year;
    }

    return sprintf('%02d. %s %s', (int)$parts['day'], $monthName ?: '', $year);
}

function build_gene_state_label(array $gene, string $state): ?string
{
    $state = trim($state);
    if ($state === '' || !in_array($state, ['normal', 'heterozygous', 'homozygous'], true)) {
        return null;
    }

    $name = $gene['name'] ?? '';
    if ($state === 'heterozygous') {
        return $gene['heterozygous_label'] ?: ($name ? $name . ' (het)' : null);
    }

    if ($state === 'homozygous') {
        return $gene['homozygous_label'] ?: ($name ? $name . ' (hom)' : null);
    }

    return $gene['normal_label'] ?: ($name ?: null);
}

function get_setting(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE key = :key');
    $stmt->execute(['key' => $key]);
    $row = $stmt->fetch();
    return $row['value'] ?? $default;
}

function set_setting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare('REPLACE INTO settings(key, value) VALUES (:key, :value)');
    $stmt->execute(['key' => $key, 'value' => $value]);
}

function slugify(string $value): string
{
    $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($transliterated !== false) {
        $value = $transliterated;
    }
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
    $value = trim($value, '-');
    return $value ?: bin2hex(random_bytes(4));
}

/**
 * Ensures a unique slug for a given table.
 *
 * @param PDO $pdo Database connection
 * @param string $table Table name (must be in ALLOWED_SLUG_TABLES)
 * @param string $slug The desired slug
 * @param int|null $ignoreId Optional ID to ignore (for updates)
 * @return string The unique slug
 * @throws InvalidArgumentException If table is not allowed
 */
function ensure_unique_slug(PDO $pdo, string $table, string $slug, ?int $ignoreId = null): string
{
    // SQL Injection prevention: Whitelist allowed tables
    if (!in_array($table, ALLOWED_SLUG_TABLES, true)) {
        throw new InvalidArgumentException("Invalid table for slug generation: {$table}");
    }

    $base = $slug ?: bin2hex(random_bytes(4));
    $candidate = $base;
    $counter = 1;

    while (true) {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE slug = :slug";
        $params = ['slug' => $candidate];
        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $ignoreId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn() == 0) {
            return $candidate;
        }
        $candidate = $base . '-' . (++$counter);
    }
}

/**
 * Renders rich text content with XSS protection.
 * Strips all tags except those in ALLOWED_HTML_TAGS.
 *
 * @param string|null $value The text to render
 * @return string The sanitized HTML
 */
function render_rich_text(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    if (strpos($value, '<') === false) {
        return nl2br(htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    // XSS Protection: Strip all tags except allowed ones
    $sanitized = strip_tags($value, ALLOWED_HTML_TAGS);

    // Additional protection: Remove potentially dangerous attributes
    // This is a simple implementation; for production, consider using HTML Purifier
    $sanitized = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $sanitized);
    $sanitized = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $sanitized);
    $sanitized = preg_replace('/javascript:/i', '', $sanitized);
    $sanitized = preg_replace('/data:/i', '', $sanitized);

    return $sanitized;
}

function format_bytes(int $bytes, int $precision = 1): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    $units = ['KB', 'MB', 'GB', 'TB'];
    $value = $bytes;
    $unitIndex = -1;
    while ($value >= 1024 && $unitIndex < count($units) - 1) {
        $value /= 1024;
        $unitIndex++;
    }

    return round($value, $precision) . ' ' . $units[$unitIndex];
}
