<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-5xl px-4 sm:px-6 lg:px-8">
<h1>Einstellungen</h1>
<?php include __DIR__ . '/nav.php'; ?>
<?php if ($flashSuccess): ?>
    <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<div class="card">
    <form method="post">
        <?= csrf_field() ?>
        <?php $themes = get_available_themes(); ?>
        <label>Seitentitel
            <input type="text" name="site_title" value="<?= htmlspecialchars($settings['site_title'] ?? '') ?>">
        </label>
        <label>Untertitel
            <input type="text" name="site_tagline" value="<?= htmlspecialchars($settings['site_tagline'] ?? '') ?>">
        </label>
        <label>Hero-Einleitung
            <textarea name="hero_intro" class="rich-text"><?= htmlspecialchars($settings['hero_intro'] ?? '') ?></textarea>
        </label>
        <label>Abgabe Intro
            <textarea name="adoption_intro" class="rich-text"><?= htmlspecialchars($settings['adoption_intro'] ?? '') ?></textarea>
        </label>
        <label>Footer Text
            <textarea name="footer_text" class="rich-text"><?= htmlspecialchars($settings['footer_text'] ?? '') ?></textarea>
        </label>
        <label>Kontakt E-Mail
            <input type="email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
        </label>
        <label>Logo (Icon, 1:1)
            <input type="text" name="logo_icon_path" value="<?= htmlspecialchars($settings['logo_icon_path'] ?? '') ?>" placeholder="z. B. assets/logo-icon.svg oder uploads/logo.png">
            <span class="form-help">Pfad relativ zum Webroot oder vollständige URL. Standard: <code>assets/logo-icon.svg</code>.</span>
            <?php if ($iconPreview = media_url($settings['logo_icon_path'] ?? null)): ?>
                <img src="<?= htmlspecialchars($iconPreview) ?>" alt="Aktuelles Logo-Icon" class="mt-2 h-12 w-12 rounded-xl border border-white/10 bg-night-900/40 p-2" loading="lazy">
            <?php endif; ?>
        </label>
        <label>Logo (Wortmarke)
            <input type="text" name="logo_wordmark_path" value="<?= htmlspecialchars($settings['logo_wordmark_path'] ?? '') ?>" placeholder="z. B. assets/logo-wordmark.svg oder uploads/logo.svg">
            <span class="form-help">Pfad relativ zum Webroot oder vollständige URL. Standard: <code>assets/logo-wordmark.svg</code>.</span>
            <?php if ($wordmarkPreview = media_url($settings['logo_wordmark_path'] ?? null)): ?>
                <img src="<?= htmlspecialchars($wordmarkPreview) ?>" alt="Aktuelle Wortmarke" class="mt-2 max-h-16 w-full rounded-xl border border-white/10 bg-night-900/40 object-contain p-3" loading="lazy">
            <?php endif; ?>
        </label>
        <?php $defaultBranch = defined('APP_REPOSITORY_BRANCH') ? APP_REPOSITORY_BRANCH : 'main'; ?>
        <label>Repository-Branch für Updates
            <input type="text" name="repository_branch" value="<?= htmlspecialchars($settings['repository_branch'] ?? $defaultBranch) ?>" placeholder="z. B. <?= htmlspecialchars($defaultBranch) ?>">
            <span class="form-help">Der Update-Manager lädt ZIP-Archive direkt aus diesem Git-Branch.</span>
        </label>
        <label>Design
            <select name="active_theme">
                <?php foreach ($themes as $key => $theme): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= (($settings['active_theme'] ?? 'aurora') === $key) ? 'selected' : '' ?>><?= htmlspecialchars($theme['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Speichern</button>
    </form>
</div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
