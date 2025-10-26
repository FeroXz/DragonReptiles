<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="nui-container nui-section">
    <header class="nui-section__header">
        <div>
            <h1 class="nui-heading text-white sm:text-4xl"><?= htmlspecialchars(content_value($settings, 'care_title')) ?></h1>
            <p class="nui-muted mt-2 text-sm"><?= htmlspecialchars(content_value($settings, 'care_intro')) ?></p>
        </div>
    </header>
    <div class="wiki-shell">
        <aside class="wiki-sidebar" aria-label="Wissensnavigation">
            <form method="get" class="nui-panel nui-panel--muted wiki-search" role="search">
                <input type="hidden" name="route" value="care-guide">
                <label class="sr-only" for="wiki-search-input">Wissensartikel durchsuchen</label>
                <input id="wiki-search-input" type="search" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Stichwort oder Thema suchen">
                <?php if (!empty($activeTopic['slug'])): ?>
                    <input type="hidden" name="topic" value="<?= htmlspecialchars($activeTopic['slug']) ?>">
                <?php endif; ?>
                <button type="submit">Suchen</button>
            </form>
            <?php if (!empty($topicsTree)): ?>
                <nav class="nui-panel nui-panel--muted wiki-topics" aria-label="Themenübersicht">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-200">Themen</h2>
                    <ul>
                        <?php
                        $renderTopic = function (array $topic) use (&$renderTopic, $activeTopic) {
                            $isActive = $activeTopic && (int)$activeTopic['id'] === (int)$topic['id'];
                            $url = BASE_URL . '/index.php?route=care-guide&amp;topic=' . urlencode($topic['slug']);
                            echo '<li class="' . ($isActive ? 'is-active' : '') . '">';
                            echo '<a href="' . $url . '">' . htmlspecialchars($topic['title']) . '<span>' . (int)$topic['article_count'] . '</span></a>';
                            if (!empty($topic['children'])) {
                                echo '<ul>';
                                foreach ($topic['children'] as $child) {
                                    $renderTopic($child);
                                }
                                echo '</ul>';
                            }
                            echo '</li>';
                        };
                        foreach ($topicsTree as $topic) {
                            $renderTopic($topic);
                        }
                        ?>
                    </ul>
                </nav>
            <?php endif; ?>
            <?php if (!empty($allArticles)): ?>
                <div class="nui-panel nui-panel--muted wiki-alphabet">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-200">Alle Artikel</h2>
                    <ul>
                        <?php foreach ($allArticles as $article): ?>
                            <li>
                                <a href="<?= BASE_URL ?>/index.php?route=care-article&amp;slug=<?= urlencode($article['slug']) ?>"><?= htmlspecialchars($article['title']) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </aside>
        <div class="wiki-content">
            <?php if ($activeTopic): ?>
                <div class="nui-panel nui-panel--muted wiki-context">
                    <h2><?= htmlspecialchars($activeTopic['title']) ?></h2>
                    <?php if (!empty($activeTopic['description'])): ?>
                        <p><?= nl2br(htmlspecialchars($activeTopic['description'])) ?></p>
                    <?php else: ?>
                        <p>Artikel in diesem Themenbereich.</p>
                    <?php endif; ?>
                </div>
            <?php elseif ($searchQuery !== ''): ?>
                <div class="nui-panel nui-panel--muted wiki-context">
                    <h2>Suchergebnisse</h2>
                    <p><?= count($careArticles) ?> Artikel zu „<?= htmlspecialchars($searchQuery) ?>“.</p>
                </div>
            <?php endif; ?>

            <?php if (empty($careArticles)): ?>
                <p class="wiki-empty">Keine Artikel gefunden. Passe die Suche oder das Thema an.</p>
            <?php else: ?>
                <div class="wiki-grid">
                    <?php foreach ($careArticles as $article): ?>
                        <article class="nui-card wiki-card">
                            <header>
                                <h2><a href="<?= BASE_URL ?>/index.php?route=care-article&amp;slug=<?= urlencode($article['slug']) ?>"><?= htmlspecialchars($article['title']) ?></a></h2>
                                <?php if (!empty($article['summary'])): ?>
                                    <p><?= nl2br(htmlspecialchars($article['summary'])) ?></p>
                                <?php endif; ?>
                            </header>
                            <?php if (!empty($article['topics'])): ?>
                                <ul class="wiki-card__topics" aria-label="Zugeordnete Themen">
                                    <?php foreach ($article['topics'] as $topic): ?>
                                        <li><a href="<?= BASE_URL ?>/index.php?route=care-guide&amp;topic=<?= urlencode($topic['slug']) ?>"><?= htmlspecialchars($topic['title']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <footer class="mt-auto flex items-center justify-between gap-3 text-sm text-slate-400">
                                <span class="wiki-card__meta">Aktualisiert am <?= date('d.m.Y', strtotime($article['updated_at'] ?? $article['created_at'])) ?></span>
                                <a class="nui-pill wiki-card__cta justify-between text-xs font-semibold text-slate-100" href="<?= BASE_URL ?>/index.php?route=care-article&amp;slug=<?= urlencode($article['slug']) ?>">
                                    <?= htmlspecialchars(content_value($settings, 'care_read_more')) ?>
                                    <span aria-hidden="true">→</span>
                                </a>
                            </footer>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
