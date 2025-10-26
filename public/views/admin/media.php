<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <h1>Medienverwaltung</h1>
    <?php include __DIR__ . '/nav.php'; ?>
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
    <div class="admin-two-column admin-media">
        <div class="card">
            <div class="admin-media__header">
                <h2>Medienbibliothek</h2>
                <form method="get" class="admin-media__search">
                    <input type="hidden" name="route" value="admin/media">
                    <label class="sr-only" for="media-search">Medien durchsuchen</label>
                    <input id="media-search" type="search" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Titel, Tags oder Alt-Text suchen">
                    <button type="submit">Suchen</button>
                </form>
            </div>
            <?php if (empty($assets)): ?>
                <p class="admin-empty">Noch keine Medien vorhanden.</p>
            <?php else: ?>
                <div class="media-grid">
                    <?php foreach ($assets as $asset): ?>
                        <article class="media-card">
                            <div class="media-card__thumb">
                                <?php if (!empty($asset['file_path'])): ?>
                                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($asset['file_path']) ?>" alt="<?= htmlspecialchars($asset['alt_text'] ?: ($asset['title'] ?: 'Medienvorschau')) ?>">
                                <?php else: ?>
                                    <div class="media-card__placeholder">Keine Vorschau</div>
                                <?php endif; ?>
                            </div>
                            <div class="media-card__meta">
                                <h3><?= htmlspecialchars($asset['title'] ?: 'Unbenanntes Medium') ?></h3>
                                <dl>
                                    <div>
                                        <dt>Datei</dt>
                                        <dd><a href="<?= BASE_URL ?>/<?= htmlspecialchars($asset['file_path']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($asset['file_path']) ?></a></dd>
                                    </div>
                                    <?php if (!empty($asset['file_size'])): ?>
                                        <div>
                                            <dt>Größe</dt>
                                            <dd><?= format_bytes((int)$asset['file_size']) ?></dd>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($asset['width']) && !empty($asset['height'])): ?>
                                        <div>
                                            <dt>Abmessungen</dt>
                                            <dd><?= (int)$asset['width'] ?> × <?= (int)$asset['height'] ?> px</dd>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($asset['tags'])): ?>
                                        <div>
                                            <dt>Tags</dt>
                                            <dd><?= htmlspecialchars($asset['tags']) ?></dd>
                                        </div>
                                    <?php endif; ?>
                                </dl>
                                <div class="media-card__actions">
                                    <a class="btn btn-secondary" href="<?= BASE_URL ?>/index.php?route=admin/media&amp;edit=<?= (int)$asset['id'] ?>">Bearbeiten</a>
                                    <form method="post" action="<?= BASE_URL ?>/index.php?route=admin/media" onsubmit="return confirm('Medienobjekt wirklich löschen?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_asset">
                                        <input type="hidden" name="id" value="<?= (int)$asset['id'] ?>">
                                        <button type="submit" class="btn btn-secondary">Löschen</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="admin-media__side">
            <div class="card">
                <h2>Neues Medium hochladen</h2>
                <form method="post" action="<?= BASE_URL ?>/index.php?route=admin/media" enctype="multipart/form-data" class="admin-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload_asset">
                    <label>Titel
                        <input type="text" name="title" placeholder="Optionaler Anzeigename">
                    </label>
                    <label>Alt-Text
                        <input type="text" name="alt_text" placeholder="Beschreibung für Screenreader">
                    </label>
                    <label>Tags
                        <input type="text" name="tags" placeholder="z.&nbsp;B. hero, profil, snake">
                    </label>
                    <label>Datei auswählen
                        <input type="file" name="media_file" accept="image/*" required>
                    </label>
                    <button type="submit">Upload starten</button>
                </form>
            </div>
            <div class="card">
                <h2><?= $editingAsset ? 'Medienobjekt bearbeiten' : 'Metadaten anpassen' ?></h2>
                <?php if ($editingAsset): ?>
                    <form method="post" action="<?= BASE_URL ?>/index.php?route=admin/media" enctype="multipart/form-data" class="admin-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_asset">
                        <input type="hidden" name="id" value="<?= (int)$editingAsset['id'] ?>">
                        <label>Titel
                            <input type="text" name="title" value="<?= htmlspecialchars($editingAsset['title'] ?? '') ?>" placeholder="Optionaler Anzeigename">
                        </label>
                        <label>Alt-Text
                            <input type="text" name="alt_text" value="<?= htmlspecialchars($editingAsset['alt_text'] ?? '') ?>" placeholder="Beschreibung für Screenreader">
                        </label>
                        <label>Tags
                            <input type="text" name="tags" value="<?= htmlspecialchars($editingAsset['tags'] ?? '') ?>" placeholder="z.&nbsp;B. hero, profil, snake">
                        </label>
                        <label>Bild ersetzen
                            <input type="file" name="replacement_file" accept="image/*">
                        </label>
                        <button type="submit">Änderungen speichern</button>
                    </form>
                <?php else: ?>
                    <p class="admin-empty">Wähle in der Liste links ein Medium aus, um Metadaten anzupassen oder das Bild auszutauschen.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
