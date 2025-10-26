<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
<h1>Neuigkeiten</h1>
<?php include __DIR__ . '/nav.php'; ?>
<?php if ($flashSuccess): ?>
    <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>
<div class="admin-two-column">
    <div class="card">
        <h2>Veröffentlichte Beiträge</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Status</th>
                    <th>Veröffentlicht</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($newsPosts as $post): ?>
                    <tr>
                        <td><?= htmlspecialchars($post['title']) ?></td>
                        <td>
                            <?php if ($post['is_published']): ?>
                                <span class="badge">online</span>
                            <?php else: ?>
                                <span class="badge">Entwurf</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($post['published_at'] ?? '—') ?></td>
                        <td>
                            <a class="btn btn-secondary" href="<?= BASE_URL ?>/index.php?route=admin/news&edit=<?= (int)$post['id'] ?>">Bearbeiten</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Beitrag wirklich löschen?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_news">
                                <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                <button type="submit" class="btn btn-secondary">Löschen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2><?= $editPost ? 'Beitrag bearbeiten' : 'Neuer Beitrag' ?></h2>
        <form method="post">
            <?= csrf_field() ?>
            <?php if ($editPost): ?>
                <input type="hidden" name="id" value="<?= (int)$editPost['id'] ?>">
            <?php endif; ?>
            <label>Titel
                <input type="text" name="title" value="<?= htmlspecialchars($editPost['title'] ?? '') ?>" required>
            </label>
            <label>Slug (optional)
                <input type="text" name="slug" value="<?= htmlspecialchars($editPost['slug'] ?? '') ?>">
            </label>
            <label>Kurzfassung
                <textarea name="excerpt" rows="4"><?= htmlspecialchars($editPost['excerpt'] ?? '') ?></textarea>
            </label>
            <label>Inhalt
                <textarea name="content" class="rich-text" required><?= htmlspecialchars($editPost['content'] ?? '') ?></textarea>
            </label>
            <label>Veröffentlicht am
                <input type="datetime-local" name="published_at" value="<?= !empty($editPost['published_at']) ? date('Y-m-d\TH:i', strtotime($editPost['published_at'])) : '' ?>">
            </label>
            <label style="display:flex;align-items:center;gap:0.5rem;">
                <input type="checkbox" name="is_published" value="1" <?= !empty($editPost['is_published']) ? 'checked' : '' ?>> Sofort veröffentlichen
            </label>
            <button type="submit">Speichern</button>
        </form>
    </div>
</div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>

