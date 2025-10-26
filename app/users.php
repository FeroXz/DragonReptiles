<?php
function get_users(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
}

function get_user(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function create_user(PDO $pdo, array $data): void
{
    $stmt = $pdo->prepare('INSERT INTO users(username, password_hash, role, can_manage_animals, can_manage_settings, can_manage_adoptions) VALUES (:username, :password_hash, :role, :animals, :settings, :adoptions)');
    $stmt->execute([
        'username' => $data['username'],
        'password_hash' => password_hash($data['password'], PASSWORD_ALGO),
        'role' => $data['role'] ?? 'staff',
        'animals' => !empty($data['can_manage_animals']) ? 1 : 0,
        'settings' => !empty($data['can_manage_settings']) ? 1 : 0,
        'adoptions' => !empty($data['can_manage_adoptions']) ? 1 : 0,
    ]);
}

function update_user(PDO $pdo, int $id, array $data): void
{
    $fields = [
        'role' => $data['role'] ?? 'staff',
        'can_manage_animals' => !empty($data['can_manage_animals']) ? 1 : 0,
        'can_manage_settings' => !empty($data['can_manage_settings']) ? 1 : 0,
        'can_manage_adoptions' => !empty($data['can_manage_adoptions']) ? 1 : 0,
        'id' => $id,
    ];

    $sql = 'UPDATE users SET role = :role, can_manage_animals = :can_manage_animals, can_manage_settings = :can_manage_settings, can_manage_adoptions = :can_manage_adoptions';

    if (!empty($data['password'])) {
        $sql .= ', password_hash = :password_hash';
        $fields['password_hash'] = password_hash($data['password'], PASSWORD_ALGO);
    }

    $sql .= ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($fields);
}

function delete_user(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
}
