<?php
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

function authenticate(PDO $pdo, string $username, string $password): bool
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    if (!$user) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user'] = $user;
    return true;
}

function logout(): void
{
    unset($_SESSION['user']);
}
