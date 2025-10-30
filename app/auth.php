<?php declare(strict_types=1);

/**
 * Ensures a default admin user exists.
 *
 * @param PDO $pdo Database connection
 * @return void
 */
function ensure_default_admin(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
    $count = (int)$stmt->fetchColumn();
    if ($count > 0) {
        return;
    }

    $plainPassword = bin2hex(random_bytes(12));
    $password = password_hash($plainPassword, PASSWORD_ALGO);
    $stmt = $pdo->prepare('INSERT INTO users(username, password_hash, role, can_manage_animals, can_manage_settings, can_manage_adoptions) VALUES (:username, :hash, :role, 1, 1, 1)');
    $stmt->execute([
        'username' => 'admin',
        'hash' => $password,
        'role' => 'admin'
    ]);

    $_SESSION['initial_admin_credentials'] = [
        'username' => 'admin',
        'password' => $plainPassword,
    ];

    $credentialsPath = __DIR__ . '/../storage/initial_admin_credentials.json';
    $payload = json_encode([
        'username' => 'admin',
        'password' => $plainPassword,
        'generated_at' => date('c'),
        'note' => 'Bitte melden Sie sich an und ändern Sie das Passwort umgehend.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if ($payload !== false) {
        if (@file_put_contents($credentialsPath, $payload) === false) {
            error_log(sprintf('Initiale Admin-Zugangsdaten konnten nicht nach %s geschrieben werden.', $credentialsPath));
        } else {
            @chmod($credentialsPath, 0600);
            $realPath = realpath($credentialsPath) ?: $credentialsPath;
            error_log(sprintf('Initiale Admin-Zugangsdaten wurden unter %s abgelegt.', $realPath));
        }
    }
}

/**
 * Checks if an IP address is rate-limited for login attempts.
 *
 * @param string $ip IP address to check
 * @return bool True if rate-limited, false otherwise
 */
function is_login_rate_limited(string $ip): bool
{
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    $now = time();

    // Clean up old attempts
    foreach ($_SESSION['login_attempts'] as $attempt_ip => $data) {
        if ($data['lockout_until'] < $now) {
            unset($_SESSION['login_attempts'][$attempt_ip]);
        }
    }

    if (!isset($_SESSION['login_attempts'][$ip])) {
        return false;
    }

    $attempts = $_SESSION['login_attempts'][$ip];
    return $attempts['count'] >= LOGIN_MAX_ATTEMPTS && $attempts['lockout_until'] > $now;
}

/**
 * Records a failed login attempt.
 *
 * @param string $ip IP address
 * @return void
 */
function record_login_attempt(string $ip): void
{
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    if (!isset($_SESSION['login_attempts'][$ip])) {
        $_SESSION['login_attempts'][$ip] = [
            'count' => 0,
            'lockout_until' => 0
        ];
    }

    $_SESSION['login_attempts'][$ip]['count']++;

    if ($_SESSION['login_attempts'][$ip]['count'] >= LOGIN_MAX_ATTEMPTS) {
        $_SESSION['login_attempts'][$ip]['lockout_until'] = time() + LOGIN_LOCKOUT_TIME;
    }
}

/**
 * Resets login attempts for an IP address.
 *
 * @param string $ip IP address
 * @return void
 */
function reset_login_attempts(string $ip): void
{
    if (isset($_SESSION['login_attempts'][$ip])) {
        unset($_SESSION['login_attempts'][$ip]);
    }
}

/**
 * Authenticates a user with brute-force protection.
 *
 * @param PDO $pdo Database connection
 * @param string $username Username
 * @param string $password Password
 * @return bool True on successful authentication
 */
function authenticate(PDO $pdo, string $username, string $password): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Check rate limiting
    if (is_login_rate_limited($ip)) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        record_login_attempt($ip);
        return false;
    }

    // Successful login - regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user'] = $user;
    $_SESSION['login_time'] = time();

    // Reset login attempts on successful login
    reset_login_attempts($ip);

    return true;
}

/**
 * Logs out the current user.
 *
 * @return void
 */
function logout(): void
{
    unset($_SESSION['user']);
    unset($_SESSION['login_time']);
    session_regenerate_id(true);
}

/**
 * Validates an email address.
 *
 * @param string|null $email Email to validate
 * @return bool True if valid
 */
function validate_email(?string $email): bool
{
    if ($email === null || $email === '') {
        return false;
    }

    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
