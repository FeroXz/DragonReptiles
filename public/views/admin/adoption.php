<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
<h1>Tierabgabe verwalten</h1>
<?php include __DIR__ . '/nav.php'; ?>
<?php if ($flashSuccess): ?>
    <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>
<div class="admin-two-column">
    <div class="card">
        <h2>Inserate</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Status</th>
                    <th>Preis</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $listing): ?>
                    <tr>
                        <td><?= htmlspecialchars($listing['title']) ?></td>
                        <td><?= htmlspecialchars($listing['status']) ?></td>
                        <td><?= htmlspecialchars($listing['price'] ?? 'n/a') ?></td>
                        <td>
                            <a class="btn btn-secondary" href="<?= BASE_URL ?>/index.php?route=admin/adoption&edit=<?= (int)$listing['id'] ?>">Bearbeiten</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Eintrag löschen?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_listing">
                                <input type="hidden" name="listing_id" value="<?= (int)$listing['id'] ?>">
                                <button type="submit" class="btn btn-secondary">Löschen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2><?= $editListing ? 'Inserat bearbeiten' : 'Neues Inserat' ?></h2>
        <?php
            $currentSpeciesSlug = $editListing['species_slug'] ?? ($speciesList[0]['slug'] ?? null);
            $currentGeneStates = $editListing['gene_states'] ?? [];
        ?>
        <form method="post" enctype="multipart/form-data" class="admin-animal-form">
            <?= csrf_field() ?>
            <?php if ($editListing): ?>
                <input type="hidden" name="id" value="<?= (int)$editListing['id'] ?>">
            <?php endif; ?>
            <label>Titel
                <input type="text" name="title" value="<?= htmlspecialchars($editListing['title'] ?? '') ?>" required>
            </label>
            <label>Tier aus Bestand
                <select name="animal_id">
                    <option value="">— unabhängig —</option>
                    <?php foreach ($animals as $animal): ?>
                        <option value="<?= (int)$animal['id'] ?>" <?= (($editListing['animal_id'] ?? '') == $animal['id']) ? 'selected' : '' ?>><?= htmlspecialchars($animal['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="form-field">
                <label for="adoption-species">Art</label>
                <?php if (empty($speciesList)): ?>
                    <p class="form-hint">Bitte lege unter <a href="<?= BASE_URL ?>/index.php?route=admin/genetics">Genetik</a> zunächst mindestens eine Art an.</p>
                <?php endif; ?>
                <select id="adoption-species" name="species_slug" <?= empty($speciesList) ? 'disabled' : '' ?> data-species-select>
                    <option value="">— Art auswählen —</option>
                    <?php foreach ($speciesList as $species): ?>
                        <option value="<?= htmlspecialchars($species['slug']) ?>" <?= ($currentSpeciesSlug === $species['slug']) ? 'selected' : '' ?>><?= htmlspecialchars($species['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label>Preis
                <input type="text" name="price" value="<?= htmlspecialchars($editListing['price'] ?? '') ?>">
            </label>
            <fieldset class="form-field">
                <legend>Genetik</legend>
                <?php if (empty($speciesList)): ?>
                    <p class="form-hint">Gene stehen zur Auswahl, sobald mindestens eine Art vorhanden ist.</p>
                <?php endif; ?>
                <?php if (!empty($speciesList)): ?>
                    <?php foreach ($speciesList as $species): ?>
                        <?php
                            $isActive = $currentSpeciesSlug === $species['slug'];
                            $genes = $speciesGenes[$species['slug']] ?? [];
                        ?>
                        <div class="gene-group" data-species-genes="<?= htmlspecialchars($species['slug']) ?>" <?= $isActive ? '' : 'hidden' ?>>
                            <?php if (empty($genes)): ?>
                                <p class="form-hint">Für diese Art sind noch keine Gene gepflegt. <a href="<?= BASE_URL ?>/index.php?route=admin/genetics&amp;species=<?= urlencode($species['slug']) ?>">Jetzt ergänzen</a>.</p>
                            <?php else: ?>
                                <?php foreach ($genes as $gene): ?>
                                    <?php
                                        $state = $currentGeneStates[$gene['slug']] ?? '';
                                        $normalLabel = $gene['normal_label'] ?: ($gene['name'] . ' (Wildtyp)');
                                        $heteroLabel = $gene['heterozygous_label'] ?: ($gene['name'] . ' (het)');
                                        $homoLabel = $gene['homozygous_label'] ?: ($gene['name'] . ' (hom)');
                                    ?>
                                    <label class="gene-select">
                                        <span><?= htmlspecialchars($gene['name']) ?></span>
                                        <select name="gene_states[<?= htmlspecialchars($gene['slug']) ?>]" <?= $isActive ? '' : 'disabled' ?>>
                                            <option value="">Nicht festgelegt</option>
                                            <option value="normal" <?= ($state === 'normal') ? 'selected' : '' ?>><?= htmlspecialchars($normalLabel) ?></option>
                                            <option value="heterozygous" <?= ($state === 'heterozygous') ? 'selected' : '' ?>><?= htmlspecialchars($heteroLabel) ?></option>
                                            <option value="homozygous" <?= ($state === 'homozygous') ? 'selected' : '' ?>><?= htmlspecialchars($homoLabel) ?></option>
                                        </select>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </fieldset>
            <label>Beschreibung
                <textarea name="description" class="rich-text"><?= htmlspecialchars($editListing['description'] ?? '') ?></textarea>
            </label>
            <label>Status
                <select name="status">
                    <?php foreach (['available' => 'verfügbar', 'reserved' => 'reserviert', 'adopted' => 'vermittelt'] as $key => $label): ?>
                        <option value="<?= $key ?>" <?= (($editListing['status'] ?? 'available') === $key) ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Kontakt E-Mail
                <input type="email" name="contact_email" value="<?= htmlspecialchars($editListing['contact_email'] ?? $settings['contact_email'] ?? '') ?>">
            </label>
            <label>Bild aus Medienbibliothek
                <select name="media_asset_id" data-media-select>
                    <option value="">— auswählen —</option>
                    <?php foreach ($mediaAssets as $asset): ?>
                        <option value="<?= (int)$asset['id'] ?>" <?= (($editListing['media_asset_id'] ?? null) == $asset['id']) ? 'selected' : '' ?> data-media-preview="<?= htmlspecialchars(media_url($asset['file_path'] ?? null) ?? '') ?>">
                            <?= htmlspecialchars($asset['title'] ?: ($asset['original_name'] ?: ('#' . $asset['id']))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="media-picker-preview" data-media-preview>
                <?php if ($src = media_url($editListing['image_path'] ?? null)): ?>
                    <img src="<?= htmlspecialchars($src) ?>" alt="Aktuelles Inseratsbild" class="mb-2 h-40 w-full rounded-2xl object-cover">
                <?php else: ?>
                    <p class="text-sm text-slate-400">Noch kein Bild ausgewählt.</p>
                <?php endif; ?>
            </div>
            <label>Neues Bild hochladen
                <input type="file" name="image" accept="image/*">
            </label>
            <input type="hidden" name="image_path" value="<?= htmlspecialchars(normalize_media_path($editListing['image_path'] ?? '')) ?>">
            <button type="submit" <?= empty($speciesList) ? 'disabled' : '' ?>>Speichern</button>
        </form>
    </div>
</div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const speciesSelect = document.querySelector('form [data-species-select]');
        if (speciesSelect) {
            const groups = document.querySelectorAll('form [data-species-genes]');
            const toggleGroups = () => {
                const activeSlug = speciesSelect.value;
                groups.forEach(group => {
                    const isActive = group.dataset.speciesGenes === activeSlug && activeSlug !== '';
                    group.hidden = !isActive;
                    group.querySelectorAll('select').forEach(select => {
                        select.disabled = !isActive;
                    });
                });
            };
            speciesSelect.addEventListener('change', toggleGroups);
            toggleGroups();
        }

        const mediaSelect = document.querySelector('[data-media-select]');
        const mediaPreview = document.querySelector('[data-media-preview]');
        if (mediaSelect && mediaPreview) {
            const renderPreview = () => {
                const option = mediaSelect.selectedOptions[0];
                const src = option ? option.getAttribute('data-media-preview') : '';
                if (src) {
                    mediaPreview.innerHTML = '<img src="' + src + '" alt="Ausgewählte Vorschau" class="mb-2 h-40 w-full rounded-2xl object-cover">';
                } else {
                    mediaPreview.innerHTML = '<p class="text-sm text-slate-400">Noch kein Bild ausgewählt.</p>';
                }
            };
            mediaSelect.addEventListener('change', renderPreview);
            renderPreview();
        }
    });
</script>
<?php include __DIR__ . '/../partials/footer.php'; ?>
