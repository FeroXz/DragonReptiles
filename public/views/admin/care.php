<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
<h1>Pflegeleitfaden</h1>
<?php include __DIR__ . '/nav.php'; ?>
<?php if ($flashSuccess): ?>
    <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>
<div class="admin-two-column">
    <div class="card">
        <h2>Artikelübersicht</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($careArticles as $article): ?>
                    <tr>
                        <td><?= htmlspecialchars($article['title']) ?></td>
                        <td><?= htmlspecialchars($article['slug']) ?></td>
                        <td>
                            <?php if ($article['is_published']): ?>
                                <span class="badge">veröffentlicht</span>
                            <?php else: ?>
                                <span class="badge">Entwurf</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn btn-secondary" href="<?= BASE_URL ?>/index.php?route=admin/care&edit=<?= (int)$article['id'] ?>">Bearbeiten</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Artikel wirklich löschen?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_care_article">
                                <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
                                <button type="submit" class="btn btn-secondary">Löschen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2><?= $editArticle ? 'Artikel bearbeiten' : 'Neuer Artikel' ?></h2>
        <form method="post">
            <?= csrf_field() ?>
            <?php if ($editArticle): ?>
                <input type="hidden" name="id" value="<?= (int)$editArticle['id'] ?>">
            <?php endif; ?>
            <label>Titel
                <input type="text" name="title" value="<?= htmlspecialchars($editArticle['title'] ?? '') ?>" required>
            </label>
            <label>Slug (optional)
                <input type="text" name="slug" value="<?= htmlspecialchars($editArticle['slug'] ?? '') ?>">
            </label>
            <label>Kurzbeschreibung
                <textarea name="summary" class="rich-text" rows="4"><?= htmlspecialchars($editArticle['summary'] ?? '') ?></textarea>
            </label>
            <label>Inhalt
                <textarea name="content" class="rich-text" required><?= htmlspecialchars($editArticle['content'] ?? '') ?></textarea>
            </label>
            <label style="display:flex;align-items:center;gap:0.5rem;">
                <input type="checkbox" name="is_published" value="1" <?= !empty($editArticle['is_published']) ? 'checked' : '' ?>> Veröffentlichen
            </label>
            <button type="submit">Speichern</button>
        </form>
    </div>
</div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>

