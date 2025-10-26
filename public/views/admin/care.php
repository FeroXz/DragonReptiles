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
<?php $selectedTopicIds = array_map('intval', array_column($editArticle['topics'] ?? [], 'id')); ?>
<div class="admin-two-column admin-care">
    <div class="admin-care__column">
        <div class="card">
            <h2>Artikelübersicht</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Titel</th>
                        <th>Slug</th>
                        <th>Themen</th>
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
                                <?php if (!empty($article['topics'])): ?>
                                    <ul class="badge-list">
                                        <?php foreach ($article['topics'] as $topic): ?>
                                            <li><a href="<?= BASE_URL ?>/index.php?route=care-guide&amp;topic=<?= urlencode($topic['slug']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($topic['title']) ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="badge badge--muted">kein Thema</span>
                                <?php endif; ?>
                            </td>
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
            <h2>Themenstruktur</h2>
            <?php if (empty($topicsTree)): ?>
                <p>Noch keine Themen angelegt.</p>
            <?php else: ?>
                <ul class="care-topic-tree">
                    <?php
                    $renderTopicRow = function (array $topic) use (&$renderTopicRow) {
                        echo '<li>';
                        echo '<a href="' . BASE_URL . '/index.php?route=admin/care&edit_topic=' . (int)$topic['id'] . '">' . htmlspecialchars($topic['title']) . '</a>';
                        echo ' <span class="count">' . (int)$topic['article_count'] . '</span>';
                        if (!empty($topic['children'])) {
                            echo '<ul>';
                            foreach ($topic['children'] as $child) {
                                $renderTopicRow($child);
                            }
                            echo '</ul>';
                        }
                        echo '</li>';
                    };
                    foreach ($topicsTree as $topic) {
                        $renderTopicRow($topic);
                    }
                    ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="admin-care__column">
        <div class="card">
            <h2><?= $editArticle ? 'Artikel bearbeiten' : 'Neuer Artikel' ?></h2>
            <form method="post" class="admin-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_care_article">
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
                <label>Themen (Mehrfachauswahl)
                    <select name="topic_ids[]" multiple size="6">
                        <?php foreach ($flatTopics as $topicOption): ?>
                            <option value="<?= (int)$topicOption['id'] ?>" <?= in_array((int)$topicOption['id'], $selectedTopicIds, true) ? 'selected' : '' ?>><?= str_repeat('— ', (int)$topicOption['depth']) . htmlspecialchars($topicOption['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
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
        <div class="card">
            <h2><?= $topicToEdit ? 'Thema bearbeiten' : 'Neues Thema' ?></h2>
            <form method="post" class="admin-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_care_topic">
                <?php if ($topicToEdit): ?>
                    <input type="hidden" name="id" value="<?= (int)$topicToEdit['id'] ?>">
                <?php endif; ?>
                <label>Titel
                    <input type="text" name="topic_title" value="<?= htmlspecialchars($topicToEdit['title'] ?? '') ?>" required>
                </label>
                <label>Slug (optional)
                    <input type="text" name="topic_slug" value="<?= htmlspecialchars($topicToEdit['slug'] ?? '') ?>">
                </label>
                <label>Beschreibung
                    <textarea name="topic_description" rows="3"><?= htmlspecialchars($topicToEdit['description'] ?? '') ?></textarea>
                </label>
                <label>Übergeordnetes Thema
                    <select name="topic_parent_id">
                        <option value="">– Kein übergeordnetes Thema –</option>
                        <?php foreach ($flatTopics as $topicOption): ?>
                            <?php if ($topicToEdit && (int)$topicOption['id'] === (int)$topicToEdit['id']) { continue; } ?>
                            <option value="<?= (int)$topicOption['id'] ?>" <?= $topicToEdit && (int)$topicOption['id'] === (int)($topicToEdit['parent_id'] ?? 0) ? 'selected' : '' ?>><?= str_repeat('— ', (int)$topicOption['depth']) . htmlspecialchars($topicOption['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="admin-form__actions">
                    <button type="submit">Speichern</button>
                </div>
            </form>
            <?php if ($topicToEdit): ?>
                <form method="post" action="<?= BASE_URL ?>/index.php?route=admin/care" onsubmit="return confirm('Thema wirklich löschen?');" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_care_topic">
                    <input type="hidden" name="topic_id" value="<?= (int)$topicToEdit['id'] ?>">
                    <button type="submit" class="btn btn-secondary">Thema löschen</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
