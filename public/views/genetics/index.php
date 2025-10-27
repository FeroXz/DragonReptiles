<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 genetics-page">
    <header class="genetics-page__header">
        <h1 class="text-3xl font-semibold text-white sm:text-4xl"><?= htmlspecialchars(content_value($settings, 'genetics_title')) ?></h1>
        <p class="mt-2 text-sm text-slate-300"><?= htmlspecialchars(content_value($settings, 'genetics_intro')) ?></p>
    </header>
    <div data-genetics-root class="genetics-app-root">
        <noscript>Bitte aktiviere JavaScript, um den Genetik-Rechner zu verwenden.</noscript>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
