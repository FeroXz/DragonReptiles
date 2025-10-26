<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <header class="max-w-3xl">
        <h1 class="text-3xl font-semibold text-white sm:text-4xl"><?= htmlspecialchars(content_value($settings, 'adoption_title')) ?></h1>
        <p class="mt-2 text-sm text-slate-300"><?= htmlspecialchars(content_value($settings, 'adoption_intro_text')) ?></p>
    </header>
    <div class="rich-text-content prose prose-invert mt-6 max-w-none text-slate-200">
        <?= render_rich_text($settings['adoption_intro'] ?? '') ?>
    </div>
    <?php if ($flashSuccess): ?>
        <div class="mt-6 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200" role="status" aria-live="polite">
            <?= htmlspecialchars($flashSuccess) ?>
        </div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="mt-6 rounded-2xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200" role="alert" aria-live="assertive">
            <?= htmlspecialchars($flashError) ?>
        </div>
    <?php endif; ?>
    <?php $inputClasses = 'mt-1 block w-full rounded-xl border border-white/10 bg-night-900/60 px-3 py-2 text-slate-100 shadow-inner shadow-black/40 focus:border-brand-400 focus:outline-none focus:ring focus:ring-brand-500/40'; ?>
    <div class="mt-10 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($listings as $listing): ?>
            <article class="flex h-full flex-col rounded-3xl border border-white/5 bg-night-900/70 p-6 shadow-lg shadow-black/30 transition hover:border-brand-400/60 hover:shadow-glow">
                <?php if ($src = media_url($listing['image_path'] ?? null)): ?>
                    <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($listing['title']) ?>" class="mb-4 h-48 w-full rounded-2xl object-cover" loading="lazy">
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
                <form method="post" class="mt-6 grid gap-4 rounded-2xl border border-brand-400/30 bg-brand-500/5 p-4 text-sm text-slate-100 shadow-inner shadow-brand-600/10">
                    <input type="hidden" name="listing_id" value="<?= (int)$listing['id'] ?>">
                    <label class="space-y-1">
                        <span class="font-medium text-slate-200">Interessiert an</span>
                        <input type="text" name="interested_in" value="<?= htmlspecialchars($listing['title']) ?>" class="<?= $inputClasses ?>">
                    </label>
                    <label class="space-y-1">
                        <span class="font-medium text-slate-200">Name</span>
                        <input type="text" name="name" required class="<?= $inputClasses ?>">
                    </label>
                    <label class="space-y-1">
                        <span class="font-medium text-slate-200">E-Mail</span>
                        <input type="email" name="email" required class="<?= $inputClasses ?>">
                    </label>
                    <label class="space-y-1">
                        <span class="font-medium text-slate-200">Nachricht</span>
                        <textarea name="message" required placeholder="Beschreiben Sie Ihre Haltung, Erfahrung und konkreten Fragen." class="<?= $inputClasses ?> h-28"></textarea>
                    </label>
                    <button type="submit" class="inline-flex items-center justify-center rounded-full border border-brand-400/60 bg-brand-500/20 px-4 py-2 text-sm font-semibold text-brand-100 transition hover:border-brand-300 hover:bg-brand-500/30"><?= htmlspecialchars(content_value($settings, 'adoption_form_submit')) ?></button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
