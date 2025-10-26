<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <h1>Galerie verwalten</h1>
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
                <h2 class="card-title"><?= $editingItem ? 'Galerie-Eintrag bearbeiten' : 'Neuen Eintrag anlegen' ?></h2>
                <?php if ($editingItem): ?>
                    <p class="card-subtitle">Aktualisiere Informationen, lade ein neues Foto hoch oder markiere das Motiv als Highlight.</p>
                <?php else: ?>
                    <p class="card-subtitle">Lade hochwertige Bilder hoch und ergänze optionale Beschreibungstexte sowie Schlagworte.</p>
                <?php endif; ?>
            </header>
            <form method="post" enctype="multipart/form-data" class="flex flex-col gap-4">
                <?= csrf_field() ?>
                <?php if ($editingItem): ?>
                    <input type="hidden" name="id" value="<?= (int)$editingItem['id'] ?>">
                <?php endif; ?>
                <label class="form-label">
                    <span>Titel</span>
                    <input type="text" name="title" required value="<?= htmlspecialchars($editingItem['title'] ?? '') ?>">
                </label>
                <label class="form-label">
                    <span>Beschreibung</span>
                    <textarea name="description" class="rich-text" rows="4"><?= htmlspecialchars($editingItem['description'] ?? '') ?></textarea>
                </label>
                <label class="form-label">
                    <span>Schlagworte (durch Komma getrennt)</span>
                    <input type="text" name="tags" value="<?= htmlspecialchars($editingItem['tags'] ?? '') ?>" placeholder="z.B. Bartagame, Terrarium, UVB">
                </label>
                <label class="form-label">
                    <span>Bilddatei <?= $editingItem ? '(optional für Austausch)' : '(erforderlich)' ?></span>
                    <input type="file" name="image" accept="image/*" <?= $editingItem ? '' : 'required' ?>>
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_featured" value="1" <?= !empty($editingItem['is_featured']) ? 'checked' : '' ?>>
                    <span>Als Highlight auf der Startseite hervorheben</span>
                </label>
                <?php if ($editingItem && !empty($editingItem['image_path'])): ?>
                    <div class="rounded-2xl border border-white/10 bg-night-900/60 p-3 text-xs text-slate-400">
                        <p class="mb-2 font-semibold text-slate-200">Aktuelles Bild</p>
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($editingItem['image_path']) ?>" alt="<?= htmlspecialchars($editingItem['title']) ?>" class="h-40 w-full rounded-xl object-cover">
                    </div>
                <?php endif; ?>
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?= $editingItem ? 'Änderungen speichern' : 'Eintrag speichern' ?>
                    </button>
                    <?php if ($editingItem): ?>
                        <a class="btn" href="<?= BASE_URL ?>/index.php?route=admin/gallery">Abbrechen</a>
                    <?php endif; ?>
                </div>
            </form>
        </article>
        <article class="card">
            <header class="card-header">
                <h2 class="card-title">Bestehende Einträge</h2>
                <p class="card-subtitle">Reihenfolge nach Erstellungsdatum. Highlights erscheinen zusätzlich auf der Startseite.</p>
            </header>
            <?php if ($galleryItems): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                        <tr>
                            <th>Titel</th>
                            <th>Erstellt</th>
                            <th>Highlight</th>
                            <th class="text-right">Aktionen</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($galleryItems as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($item['title']) ?></strong>
                                    <?php if (!empty($item['tags'])): ?>
                                        <div class="text-xs text-slate-400"><?= htmlspecialchars($item['tags']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></td>
                                <td><?= !empty($item['is_featured']) ? 'Ja' : 'Nein' ?></td>
                                <td class="text-right">
                                    <a class="btn btn-link" href="<?= BASE_URL ?>/index.php?route=admin/gallery&amp;edit=<?= (int)$item['id'] ?>">Bearbeiten</a>
                                    <form method="post" action="<?= BASE_URL ?>/index.php?route=admin/gallery/delete" class="inline-flex" onsubmit="return confirm('Eintrag wirklich löschen?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                        <button type="submit" class="btn btn-danger">Löschen</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-sm text-slate-400">Noch keine Bilder hinterlegt.</p>
            <?php endif; ?>
        </article>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
