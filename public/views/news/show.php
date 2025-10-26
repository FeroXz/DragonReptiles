<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-3xl px-4 sm:px-6 lg:px-8">
    <article class="rounded-3xl border border-white/5 bg-night-900/70 p-8 shadow-lg shadow-black/30">
        <h1 class="text-3xl font-semibold text-white sm:text-4xl"><?= htmlspecialchars($post['title']) ?></h1>
        <?php if (!empty($post['published_at'])): ?>
            <p class="mt-2 text-xs uppercase tracking-wide text-slate-400">Veröffentlicht am <?= date('d.m.Y H:i', strtotime($post['published_at'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($post['excerpt'])): ?>
            <p class="mt-4 text-sm italic text-slate-200"><?= nl2br(htmlspecialchars($post['excerpt'])) ?></p>
        <?php endif; ?>
        <div class="rich-text-content prose prose-invert mt-6 max-w-none text-slate-100">
            <?= render_rich_text($post['content']) ?>
        </div>
    </article>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>

