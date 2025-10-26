<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <header class="max-w-3xl">
        <h1 class="text-3xl font-semibold text-white sm:text-4xl"><?= htmlspecialchars(content_value($settings, 'care_title')) ?></h1>
        <p class="mt-2 text-sm text-slate-300"><?= htmlspecialchars(content_value($settings, 'care_intro')) ?></p>
    </header>
    <div class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($careArticles as $article): ?>
            <article class="flex h-full flex-col rounded-3xl border border-white/5 bg-night-900/70 p-6 shadow-lg shadow-black/30 transition hover:border-brand-400/60 hover:shadow-glow">
                <h2 class="text-xl font-semibold text-white"><?= htmlspecialchars($article['title']) ?></h2>
                <?php if (!empty($article['summary'])): ?>
                    <p class="mt-3 text-sm text-slate-200"><?= nl2br(htmlspecialchars($article['summary'])) ?></p>
                <?php endif; ?>
                <a class="mt-auto inline-flex items-center gap-2 rounded-full border border-brand-400/60 bg-brand-500/10 px-4 py-2 text-sm font-semibold text-brand-100 transition hover:border-brand-300 hover:bg-brand-500/20" href="<?= BASE_URL ?>/index.php?route=care-article&amp;slug=<?= urlencode($article['slug']) ?>"><?= htmlspecialchars(content_value($settings, 'care_read_more')) ?></a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>

