<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <header class="max-w-3xl">
        <h1 class="text-3xl font-semibold text-white sm:text-4xl"><?= htmlspecialchars(content_value($settings, 'breeding_title')) ?></h1>
        <p class="mt-2 text-sm text-slate-300"><?= htmlspecialchars(content_value($settings, 'breeding_intro')) ?></p>
    </header>
    <div class="mt-10 grid gap-8">
        <?php foreach ($breedingPlans as $plan): ?>
            <article class="rounded-3xl border border-white/5 bg-night-900/70 p-6 shadow-lg shadow-black/30">
                <header class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold text-white"><?= htmlspecialchars($plan['title']) ?></h2>
                        <p class="text-xs uppercase tracking-wide text-slate-400">Saison: <?= htmlspecialchars($plan['season'] ?: 'offen') ?></p>
                    </div>
                </header>
                <?php if (!empty($plan['expected_genetics'])): ?>
                    <div class="mt-6 rounded-2xl border border-brand-400/40 bg-brand-500/10 p-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-100">Erwartete Genetik</h3>
                        <div class="rich-text-content prose prose-invert mt-3 max-w-none text-sm text-slate-100">
                            <?= render_rich_text($plan['expected_genetics']) ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($plan['incubation_notes'])): ?>
                    <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-200">Inkubationsnotizen</h3>
                        <div class="rich-text-content prose prose-invert mt-3 max-w-none text-sm text-slate-100">
                            <?= render_rich_text($plan['incubation_notes']) ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($plan['notes'])): ?>
                    <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-200">Notizen</h3>
                        <div class="rich-text-content prose prose-invert mt-3 max-w-none text-sm text-slate-100">
                            <?= render_rich_text($plan['notes']) ?>
                        </div>
                    </div>
                <?php endif; ?>
                <h3 class="mt-8 text-lg font-semibold text-white">Elterntiere</h3>
                <?php if (empty($plan['parents'])): ?>
                    <p class="mt-2 text-sm text-slate-400"><?= htmlspecialchars(content_value($settings, 'breeding_no_parents')) ?></p>
                <?php else: ?>
                    <ul class="mt-4 grid gap-4 sm:grid-cols-2">
                        <?php foreach ($plan['parents'] as $parent): ?>
                            <li class="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-200">
                                <div class="flex items-center justify-between gap-3">
                                    <strong class="text-base text-white"><?= htmlspecialchars($parent['parent_type'] === 'virtual' ? ($parent['name'] ?: 'Virtuell') : ($parent['animal_name'] ?? $parent['name'] ?? 'Unbenannt')) ?></strong>
                                    <?php if ($parent['sex']): ?>
                                        <span class="inline-flex items-center justify-center rounded-full border border-brand-400 bg-brand-500/20 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-brand-100"><?= htmlspecialchars(strtoupper($parent['sex'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">
                                    <?= htmlspecialchars($parent['parent_type'] === 'virtual' ? 'Virtuelles Tier' : 'Bestandstier') ?>
                                </p>
                                <?php if ($parent['parent_type'] === 'animal' && $parent['animal_species']): ?>
                                    <p class="mt-2 text-sm text-slate-200"><?= htmlspecialchars($parent['animal_species']) ?></p>
                                <?php elseif (!empty($parent['species'])): ?>
                                    <p class="mt-2 text-sm text-slate-200"><?= htmlspecialchars($parent['species']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($parent['animal_genetics']) || !empty($parent['genetics'])): ?>
                                    <p class="mt-2 text-sm text-slate-200"><strong>Genetik:</strong> <?= htmlspecialchars($parent['animal_genetics'] ?? $parent['genetics']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($parent['notes'])): ?>
                                    <p class="mt-2 whitespace-pre-line text-sm text-slate-200"><?= htmlspecialchars($parent['notes']) ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>

