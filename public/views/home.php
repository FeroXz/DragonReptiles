<?php include __DIR__ . '/partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="grid gap-10 rounded-3xl border border-white/5 bg-night-900/70 p-8 shadow-glow shadow-brand-600/20 lg:grid-cols-2">
        <div class="space-y-6">
            <h1 class="text-3xl font-semibold text-white sm:text-4xl lg:text-5xl"><?= htmlspecialchars($settings['site_title'] ?? APP_NAME) ?></h1>
            <div class="rich-text-content prose prose-invert max-w-none">
                <?= render_rich_text($settings['hero_intro'] ?? '') ?>
            </div>
        </div>
        <div class="space-y-6">
            <span class="inline-flex items-center gap-2 rounded-full border border-brand-400/60 bg-brand-500/10 px-4 py-2 text-sm font-semibold uppercase tracking-wide text-brand-100"><?= htmlspecialchars(content_value($settings, 'home_hero_badge')) ?></span>
            <div class="rich-text-content prose prose-invert max-w-none text-base leading-relaxed text-slate-300">
                <?= render_rich_text(content_value($settings, 'home_hero_secondary_intro')) ?>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <a href="<?= BASE_URL ?>/index.php?route=care-guide" class="flex items-center justify-between rounded-2xl border border-brand-400/50 bg-brand-500/10 px-4 py-3 text-sm font-semibold text-brand-100 shadow-glow transition hover:border-brand-300 hover:bg-brand-500/20"><?= htmlspecialchars(content_value($settings, 'home_care_primary_cta')) ?> <span aria-hidden="true">→</span></a>
                <a href="<?= BASE_URL ?>/index.php?route=genetics" class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-slate-100 transition hover:border-brand-300 hover:text-brand-100"><?= htmlspecialchars(content_value($settings, 'home_care_secondary_cta')) ?> <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </div>
</section>

<?php foreach ($homeSections as $section): ?>
    <?php if (empty($section['enabled'])) {
        continue;
    }
    if ($section['key'] === 'highlights' && !empty($animals)) : ?>
        <section class="mx-auto mt-16 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-white sm:text-3xl"><?= htmlspecialchars(content_value($settings, 'home_highlights_title')) ?></h2>
                <span class="text-sm text-slate-400"><?= htmlspecialchars(content_value($settings, 'home_highlights_subtitle')) ?></span>
            </div>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($animals as $animal): ?>
                    <article class="group flex h-full flex-col rounded-3xl border border-white/5 bg-night-900/70 shadow-xl shadow-black/40 transition hover:border-brand-400/60 hover:shadow-glow">
                        <?php if (!empty($animal['image_path'])): ?>
                            <img src="<?= BASE_URL . '/' . htmlspecialchars($animal['image_path']) ?>" alt="<?= htmlspecialchars($animal['name']) ?>" class="h-52 w-full rounded-t-3xl object-cover" loading="lazy">
                        <?php endif; ?>
                        <div class="flex flex-1 flex-col gap-3 p-6">
                            <h3 class="text-xl font-semibold text-white">
                                <?= htmlspecialchars($animal['name']) ?>
                                <?php if (!empty($animal['is_piebald'])): ?>
                                    <span class="ml-2 inline-flex items-center justify-center rounded-full border border-brand-400 bg-brand-500/20 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-brand-100" title="Geschecktes Tier" aria-label="Geschecktes Tier">Gescheckt</span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-sm text-slate-300"><?= htmlspecialchars($animal['species']) ?></p>
                            <?php if (!empty($animal['genetics'])): ?>
                                <div class="rounded-2xl border border-brand-400/30 bg-brand-500/5 px-3 py-2 text-sm text-brand-100">
                                    <span class="font-semibold uppercase tracking-wide">Genetik:</span>
                                    <?= htmlspecialchars($animal['genetics']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($animal['special_notes'])): ?>
                                <div class="rich-text-content prose prose-invert max-w-none text-sm text-slate-200">
                                    <?= render_rich_text($animal['special_notes']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ($section['key'] === 'adoption'): ?>
        <section class="mx-auto mt-16 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-white sm:text-3xl"><?= htmlspecialchars(content_value($settings, 'home_adoption_title')) ?></h2>
                    <p class="text-sm text-slate-400"><?= htmlspecialchars(content_value($settings, 'home_adoption_subtitle')) ?></p>
                </div>
                <?php if (!empty($settings['contact_email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($settings['contact_email']) ?>" class="inline-flex items-center gap-2 rounded-full border border-brand-400/60 bg-brand-500/10 px-4 py-2 text-sm font-semibold text-brand-100 shadow-glow transition hover:border-brand-300 hover:bg-brand-500/20">
                        <?= htmlspecialchars(content_value($settings, 'home_adoption_cta')) ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="rich-text-content prose prose-invert mt-6 max-w-none text-slate-200">
                <?= render_rich_text($settings['adoption_intro'] ?? '') ?>
            </div>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                <?php if ($listings): ?>
                    <?php foreach ($listings as $listing): ?>
                        <article class="flex h-full flex-col rounded-3xl border border-white/5 bg-night-900/70 p-6 shadow-lg shadow-black/40 transition hover:border-brand-400/60 hover:shadow-glow">
                            <?php if (!empty($listing['image_path'])): ?>
                                <img src="<?= BASE_URL . '/' . htmlspecialchars($listing['image_path']) ?>" alt="<?= htmlspecialchars($listing['title']) ?>" class="mb-4 h-48 w-full rounded-2xl object-cover" loading="lazy">
                            <?php endif; ?>
                            <h3 class="text-xl font-semibold text-white"><?= htmlspecialchars($listing['title']) ?></h3>
                            <?php if (!empty($listing['species'])): ?>
                                <p class="text-sm text-slate-300"><?= htmlspecialchars($listing['species']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($listing['genetics'])): ?>
                                <p class="mt-2 rounded-xl border border-brand-400/40 bg-brand-500/10 px-3 py-2 text-sm text-brand-100">
                                    <span class="font-semibold uppercase tracking-wide">Genetik:</span>
                                    <?= htmlspecialchars($listing['genetics']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($listing['price'])): ?>
                                <p class="mt-2 text-sm text-slate-200"><strong>Preis:</strong> <?= htmlspecialchars($listing['price']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($listing['description'])): ?>
                                <div class="rich-text-content prose prose-invert mt-3 max-w-none text-sm text-slate-200">
                                    <?= render_rich_text($listing['description']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($settings['contact_email'])): ?>
                                <a class="mt-4 inline-flex items-center justify-center rounded-full border border-brand-400/60 bg-brand-500/10 px-4 py-2 text-sm font-semibold text-brand-100 shadow-glow transition hover:border-brand-300 hover:bg-brand-500/20" href="mailto:<?= htmlspecialchars($settings['contact_email']) ?>?subject=Anfrage%20<?= urlencode($listing['title']) ?>">Direkt anfragen</a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full rounded-3xl border border-dashed border-white/10 bg-night-900/50 p-12 text-center text-sm text-slate-400">
                        Aktuell keine Vermittlungstiere verfügbar.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php elseif ($section['key'] === 'news' && !empty($latestNews)): ?>
        <section class="mx-auto mt-16 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-white sm:text-3xl"><?= htmlspecialchars(content_value($settings, 'home_news_title')) ?></h2>
                    <p class="text-sm text-slate-400"><?= htmlspecialchars(content_value($settings, 'home_news_subtitle')) ?></p>
                </div>
                <a class="inline-flex items-center gap-2 rounded-full border border-white/10 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:border-brand-400 hover:text-brand-100" href="<?= BASE_URL ?>/index.php?route=news"><?= htmlspecialchars(content_value($settings, 'home_news_cta')) ?></a>
            </div>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <?php foreach ($latestNews as $post): ?>
                    <article class="flex h-full flex-col rounded-3xl border border-white/5 bg-night-900/70 p-6 shadow-lg shadow-black/40 transition hover:border-brand-400/60 hover:shadow-glow">
                        <h3 class="text-xl font-semibold text-white"><?= htmlspecialchars($post['title']) ?></h3>
                        <?php if (!empty($post['published_at'])): ?>
                            <p class="text-sm text-slate-400"><?= date('d.m.Y', strtotime($post['published_at'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($post['excerpt'])): ?>
                            <p class="mt-3 text-sm text-slate-200"><?= nl2br(htmlspecialchars($post['excerpt'])) ?></p>
                        <?php endif; ?>
                        <a class="mt-4 inline-flex items-center gap-2 rounded-full border border-brand-400/60 bg-brand-500/10 px-4 py-2 text-sm font-semibold text-brand-100 transition hover:border-brand-300 hover:bg-brand-500/20" href="<?= BASE_URL ?>/index.php?route=news&amp;slug=<?= urlencode($post['slug']) ?>"><?= htmlspecialchars(content_value($settings, 'home_news_post_cta')) ?></a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ($section['key'] === 'care' && !empty($careHighlights)): ?>
        <section class="mx-auto mt-16 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-white sm:text-3xl"><?= htmlspecialchars(content_value($settings, 'home_care_title')) ?></h2>
                    <p class="text-sm text-slate-400"><?= htmlspecialchars(content_value($settings, 'home_care_subtitle')) ?></p>
                </div>
                <a class="inline-flex items-center gap-2 rounded-full border border-brand-400/60 bg-brand-500/10 px-4 py-2 text-sm font-semibold text-brand-100 shadow-glow transition hover:border-brand-300 hover:bg-brand-500/20" href="<?= BASE_URL ?>/index.php?route=care-guide"><?= htmlspecialchars(content_value($settings, 'home_care_cta')) ?></a>
            </div>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <?php foreach ($careHighlights as $article): ?>
                    <article class="flex h-full flex-col rounded-3xl border border-white/5 bg-night-900/70 p-6 shadow-lg shadow-black/40 transition hover:border-brand-400/60 hover:shadow-glow">
                        <h3 class="text-xl font-semibold text-white"><?= htmlspecialchars($article['title']) ?></h3>
                        <?php if (!empty($article['summary'])): ?>
                            <p class="mt-3 text-sm text-slate-200"><?= nl2br(htmlspecialchars($article['summary'])) ?></p>
                        <?php endif; ?>
                        <a class="mt-auto inline-flex items-center gap-2 rounded-full border border-white/10 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:border-brand-400 hover:text-brand-100" href="<?= BASE_URL ?>/index.php?route=care-article&amp;slug=<?= urlencode($article['slug']) ?>"><?= htmlspecialchars(content_value($settings, 'home_care_article_cta')) ?></a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php elseif ($section['key'] === 'gallery' && !empty($galleryItems)): ?>
        <section class="mx-auto mt-16 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-white sm:text-3xl">Galerie</h2>
                    <p class="text-sm text-slate-400">Ausgewählte Impressionen aus Haltung, Zucht und Events.</p>
                </div>
                <a class="inline-flex items-center gap-2 rounded-full border border-white/10 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:border-brand-400 hover:text-brand-100" href="<?= BASE_URL ?>/index.php?route=gallery">Zur Galerie</a>
            </div>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($galleryItems as $item): ?>
                    <article class="group flex h-full flex-col overflow-hidden rounded-3xl border border-white/5 bg-night-900/70 shadow-xl shadow-black/40 transition hover:border-brand-400/60 hover:shadow-glow">
                        <?php if (!empty($item['image_path'])): ?>
                            <img src="<?= BASE_URL . '/' . htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="h-52 w-full object-cover" loading="lazy">
                        <?php endif; ?>
                        <div class="flex flex-1 flex-col gap-3 p-6">
                            <h3 class="text-xl font-semibold text-white"><?= htmlspecialchars($item['title']) ?></h3>
                            <?php if (!empty($item['description'])): ?>
                                <div class="rich-text-content prose prose-invert max-w-none text-sm text-slate-200">
                                    <?= render_rich_text($item['description']) ?>
                                </div>
                            <?php endif; ?>
                            <span class="mt-auto text-xs text-slate-500"><?= date('d.m.Y', strtotime($item['created_at'])) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endforeach; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
