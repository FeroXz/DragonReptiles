<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
<h1>Benutzerverwaltung</h1>
<?php include __DIR__ . '/nav.php'; ?>
<?php if ($flashSuccess): ?>
    <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<div class="admin-two-column">
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Benutzername</th>
                    <th>Rolle</th>
                    <th>Rechte</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <?php if ($user['can_manage_animals']): ?>Tiere<?php endif; ?>
                            <?php if ($user['can_manage_settings']): ?>, Einstellungen<?php endif; ?>
                            <?php if ($user['can_manage_adoptions']): ?>, Adoption<?php endif; ?>
                        </td>
                        <td>
                            <a class="btn btn-secondary" href="<?= BASE_URL ?>/index.php?route=admin/users&edit=<?= (int)$user['id'] ?>">Bearbeiten</a>
                            <?php if ($user['id'] !== current_user()['id']): ?>
                                <form method="post" style="display:inline" onsubmit="return confirm('Benutzer löschen?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                    <button type="submit" class="btn btn-secondary">Löschen</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2><?= $editUser ? 'Benutzer bearbeiten' : 'Benutzer anlegen' ?></h2>
        <form method="post">
            <?= csrf_field() ?>
            <?php if ($editUser): ?>
                <input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>">
            <?php endif; ?>
            <label>Benutzername
                <input type="text" name="username" value="<?= htmlspecialchars($editUser['username'] ?? '') ?>" <?= $editUser ? 'readonly' : 'required' ?>>
            </label>
            <label>Passwort <?= $editUser ? '(leer lassen für unverändert)' : '' ?>
                <input type="password" name="password" <?= $editUser ? '' : 'required' ?>>
            </label>
            <label>Rolle
                <select name="role">
                    <option value="admin" <?= (($editUser['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="staff" <?= (($editUser['role'] ?? '') === 'staff') ? 'selected' : '' ?>>Staff</option>
                </select>
            </label>
            <label><input type="checkbox" name="can_manage_animals" value="1" <?= !empty($editUser['can_manage_animals']) ? 'checked' : '' ?>> Tiere verwalten</label>
            <label><input type="checkbox" name="can_manage_settings" value="1" <?= !empty($editUser['can_manage_settings']) ? 'checked' : '' ?>> Einstellungen bearbeiten</label>
            <label><input type="checkbox" name="can_manage_adoptions" value="1" <?= !empty($editUser['can_manage_adoptions']) ? 'checked' : '' ?>> Adoption verwalten</label>
            <button type="submit">Speichern</button>
        </form>
    </div>
</div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
