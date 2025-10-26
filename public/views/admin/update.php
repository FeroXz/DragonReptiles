<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <h1>Update-Manager</h1>
    <?php include __DIR__ . '/nav.php'; ?>
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-danger" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
    <div class="grid gap-6 lg:grid-cols-2" style="margin-top:1.5rem;">
        <article class="card">
            <header class="card-header">
                <h2 class="card-title">Aktuelle Version</h2>
                <p class="card-subtitle">Dein System läuft aktuell mit Version <strong><?= htmlspecialchars($currentVersion) ?></strong>.</p>
            </header>
            <div class="prose prose-invert max-w-none text-sm text-slate-300">
                <ol class="list-decimal space-y-3 pl-5">
                    <li>Downloade das gewünschte Update als ZIP-Datei (z.&nbsp;B. vom GitHub-Release oder Pull Request).</li>
                    <li>Klicke auf „Paket hochladen“ und wähle das ZIP aus. Der Inhalt wird ohne Nutzer-Uploads oder Datenbank zu überschreiben eingespielt.</li>
                    <li>Nach erfolgreicher Aktualisierung wird die Versionsnummer automatisch angehoben.</li>
                </ol>
                <p class="mt-4 text-xs text-slate-400">Hinweis: Benutzerinhalte in <code>uploads/</code> sowie die SQLite-Datenbank bleiben unverändert. Ein Backup vor größeren Updates ist dennoch empfohlen.</p>
            </div>
        </article>
        <article class="card">
            <header class="card-header">
                <h2 class="card-title">Paket hochladen</h2>
                <p class="card-subtitle">Akzeptiert werden ZIP-Archive mit der kompletten CMS-Struktur.</p>
            </header>
            <form method="post" enctype="multipart/form-data" class="flex flex-col gap-4">
                <?= csrf_field() ?>
                <label class="form-label">
                    <span>Update-Paket (ZIP)</span>
                    <input type="file" name="package" required accept="application/zip,.zip">
                </label>
                <button type="submit" class="btn btn-primary">Paket hochladen</button>
            </form>
        </article>
    </div>
    <article class="card" style="margin-top:1.5rem;">
        <header class="card-header">
            <h2 class="card-title">Protokollierte Update-Sitzungen</h2>
            <p class="card-subtitle">Zu jeder Installation wird der entpackte Inhalt im Verzeichnis <code>storage/updates</code> abgelegt.</p>
        </header>
        <?php if (!empty($availablePackages)): ?>
            <ul class="divide-y divide-white/5 text-sm text-slate-300">
                <?php foreach ($availablePackages as $path): ?>
                    <li class="flex items-center justify-between py-3">
                        <span><?= htmlspecialchars(basename($path)) ?></span>
                        <code class="text-xs text-slate-500"><?= htmlspecialchars($path) ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-sm text-slate-400">Noch keine Update-Sitzungen gespeichert.</p>
        <?php endif; ?>
    </article>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
