<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="wiki-article">
        <aside class="wiki-article__sidebar" aria-label="Artikelnavigation">
            <?php if (!empty($articleHeadings)): ?>
                <nav class="wiki-toc">
                    <h2>Inhaltsverzeichnis</h2>
                    <ol>
                        <?php foreach ($articleHeadings as $heading): ?>
                            <li class="level-<?= (int)$heading['level'] ?>">
                                <a href="#<?= htmlspecialchars($heading['id']) ?>"><?= htmlspecialchars($heading['text']) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            <?php endif; ?>
            <?php if (!empty($article['topics'])): ?>
                <div class="wiki-topics-card">
                    <h2>Themen</h2>
                    <ul>
                        <?php foreach ($article['topics'] as $topic): ?>
                            <li><a href="<?= BASE_URL ?>/index.php?route=care-guide&amp;topic=<?= urlencode($topic['slug']) ?>"><?= htmlspecialchars($topic['title']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </aside>
        <article class="wiki-article__content">
            <header class="wiki-article__header">
                <h1><?= htmlspecialchars($article['title']) ?></h1>
                <?php if (!empty($article['summary'])): ?>
                    <p class="wiki-article__summary"><?= nl2br(htmlspecialchars($article['summary'])) ?></p>
                <?php endif; ?>
                <p class="wiki-article__meta">Aktualisiert am <?= date('d.m.Y', strtotime($article['updated_at'] ?? $article['created_at'])) ?></p>
            </header>
            <div class="rich-text-content wiki-article__body">
                <?= $articleContent ?>
            </div>
            <?php if (!empty($relatedArticles)): ?>
                <section class="wiki-related" aria-label="Verwandte Artikel">
                    <h2>Verwandte Artikel</h2>
                    <ul>
                        <?php foreach ($relatedArticles as $related): ?>
                            <li>
                                <a href="<?= BASE_URL ?>/index.php?route=care-article&amp;slug=<?= urlencode($related['slug']) ?>"><?= htmlspecialchars($related['title']) ?></a>
                                <span><?= date('d.m.Y', strtotime($related['updated_at'] ?? $related['created_at'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </article>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
