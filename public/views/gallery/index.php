<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-white sm:text-4xl">Galerie</h1>
            <p class="text-sm text-slate-400">Einblicke in die Welt von Dragon Reptiles – besondere Momente aus Haltung, Pflege und Events.</p>
        </div>
        <?php if (current_user() && is_authorized('can_manage_settings')): ?>
            <a href="<?= BASE_URL ?>/index.php?route=admin/gallery" class="inline-flex items-center gap-2 rounded-full border border-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-300 transition hover:border-brand-400 hover:text-brand-100">
                Admin bearbeiten
            </a>
        <?php endif; ?>
    </div>
    <div class="mt-10 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
                <article class="group flex h-full flex-col overflow-hidden rounded-3xl border border-white/5 bg-night-900/70 shadow-xl shadow-black/40 transition hover:border-brand-400/60 hover:shadow-glow">
                    <?php if ($src = media_url($item['image_path'] ?? null)): ?>
                        <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="h-56 w-full object-cover" loading="lazy">
                    <?php endif; ?>
                    <div class="flex flex-1 flex-col gap-3 p-6">
                        <h2 class="text-xl font-semibold text-white"><?= htmlspecialchars($item['title']) ?></h2>
                        <?php if (!empty($item['description'])): ?>
                            <div class="rich-text-content prose prose-invert max-w-none text-sm text-slate-200">
                                <?= render_rich_text($item['description']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($item['tags'])): ?>
                            <div class="flex flex-wrap gap-2 text-xs text-brand-100">
                                <?php foreach (array_filter(array_map('trim', explode(',', $item['tags']))) as $tag): ?>
                                    <span class="rounded-full border border-brand-400/40 bg-brand-500/10 px-3 py-1 uppercase tracking-wide">#<?= htmlspecialchars($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <span class="mt-auto text-xs text-slate-500">
                            <?= date('d.m.Y', strtotime($item['created_at'])) ?><?php if (!empty($item['is_featured'])): ?> · <span class="text-brand-200">Highlight</span><?php endif; ?>
                        </span>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full rounded-3xl border border-dashed border-white/10 bg-night-900/50 p-12 text-center text-sm text-slate-400">
                Noch keine Galerie-Einträge vorhanden. Melde dich als Admin an, um neue Fotos hochzuladen.
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
