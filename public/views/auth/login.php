<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 sm:px-6 lg:px-8">
    <div class="rounded-3xl border border-white/5 bg-night-900/70 p-8 shadow-lg shadow-black/30">
        <?php $wordmark = media_url($settings['logo_wordmark_path'] ?? null) ?? asset('logo-wordmark.svg'); ?>
        <img src="<?= htmlspecialchars($wordmark) ?>" alt="<?= htmlspecialchars(APP_NAME) ?>" width="240" height="72" class="mx-auto mb-6 w-48 sm:w-56" loading="lazy">
        <h2 class="text-2xl font-semibold text-white"><?= htmlspecialchars(content_value($settings, 'login_title')) ?></h2>
        <?php if (!empty($_SESSION['initial_admin_credentials'])): ?>
            <?php $initialCredentials = $_SESSION['initial_admin_credentials']; unset($_SESSION['initial_admin_credentials']); ?>
            <div class="mt-4 rounded-2xl border border-amber-400/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-100" role="alert" aria-live="polite">
                <p class="font-semibold">Erstinstallation abgeschlossen.</p>
                <p>Standardzugang: <strong><?= htmlspecialchars($initialCredentials['username']) ?></strong> / <strong><?= htmlspecialchars($initialCredentials['password']) ?></strong>. Bitte nach der Anmeldung sofort ändern.</p>
            </div>
        <?php endif; ?>
        <?php if ($error = flash('error')): ?>
            <div class="mt-4 rounded-2xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200" role="alert" aria-live="assertive"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php $inputClasses = 'mt-1 block w-full rounded-xl border border-white/10 bg-night-900/60 px-3 py-2 text-slate-100 shadow-inner shadow-black/40 focus:border-brand-400 focus:outline-none focus:ring focus:ring-brand-500/40'; ?>
        <form method="post" action="<?= BASE_URL ?>/index.php?route=login" class="mt-6 grid gap-4 text-sm text-slate-200">
            <?= csrf_field() ?>
            <label class="space-y-1">
                <span class="font-medium text-slate-200"><?= htmlspecialchars(content_value($settings, 'login_username_label')) ?></span>
                <input type="text" name="username" required autofocus class="<?= $inputClasses ?>">
            </label>
            <label class="space-y-1">
                <span class="font-medium text-slate-200"><?= htmlspecialchars(content_value($settings, 'login_password_label')) ?></span>
                <input type="password" name="password" required class="<?= $inputClasses ?>">
            </label>
            <button type="submit" class="mt-4 inline-flex items-center justify-center rounded-full border border-brand-400/60 bg-brand-500/20 px-4 py-2 text-sm font-semibold text-brand-100 transition hover:border-brand-300 hover:bg-brand-500/30"><?= htmlspecialchars(content_value($settings, 'login_submit')) ?></button>
        </form>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
