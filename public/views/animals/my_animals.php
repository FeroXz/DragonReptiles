<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <header class="max-w-3xl">
        <h1 class="text-3xl font-semibold text-white sm:text-4xl">Meine Tiere</h1>
        <p class="mt-2 text-sm text-slate-300">Nur du kannst diese Tiere sehen. Bearbeitungen erfolgen im Adminbereich.</p>
    </header>
    <?php if (empty($animals)): ?>
        <div class="mt-8 rounded-3xl border border-white/5 bg-night-900/70 p-8 text-center text-sm text-slate-300">
            Es wurden bislang keine Tiere zugewiesen.
        </div>
    <?php else: ?>
        <div class="mt-10 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($animals as $animal): ?>
                <article class="flex h-full flex-col rounded-3xl border border-white/5 bg-night-900/70 p-6 shadow-lg shadow-black/30 transition hover:border-brand-400/60 hover:shadow-glow">
                    <?php if ($src = media_url($animal['image_path'] ?? null)): ?>
                        <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($animal['name']) ?>" class="mb-4 h-48 w-full rounded-2xl object-cover" loading="lazy">
                    <?php endif; ?>
                    <h3 class="text-xl font-semibold text-white">
                        <?= htmlspecialchars($animal['name']) ?>
                        <?php if (!empty($animal['is_piebald'])): ?>
                            <span class="ml-2 inline-flex items-center justify-center rounded-full border border-brand-400 bg-brand-500/20 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-brand-100" title="Geschecktes Tier" aria-label="Geschecktes Tier">Gescheckt</span>
                        <?php endif; ?>
                    </h3>
                    <p class="text-sm text-slate-300"><?= htmlspecialchars($animal['species']) ?></p>
                    <?php $displayAge = format_partial_date($animal['age'] ?? null) ?? ($animal['age'] ?? null); ?>
                    <?php if (!empty($displayAge)): ?>
                        <p class="mt-2 text-sm text-slate-200"><strong>Alter:</strong> <?= htmlspecialchars($displayAge) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($animal['genetics'])): ?>
                        <p class="mt-2 rounded-xl border border-brand-400/40 bg-brand-500/10 px-3 py-2 text-sm text-brand-100">
                            <span class="font-semibold uppercase tracking-wide">Genetik:</span>
                            <?= htmlspecialchars($animal['genetics']) ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($animal['origin'])): ?>
                        <p class="mt-2 text-sm text-slate-200"><strong>Herkunft:</strong> <?= htmlspecialchars($animal['origin']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($animal['special_notes'])): ?>
                        <div class="rich-text-content prose prose-invert mt-3 max-w-none text-sm text-slate-200">
                            <?= render_rich_text($animal['special_notes']) ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
