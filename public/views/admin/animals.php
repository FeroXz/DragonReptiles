<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
<h1>Tiere verwalten</h1>
<?php include __DIR__ . '/nav.php'; ?>
<?php if ($flashSuccess): ?>
    <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>
<div class="admin-two-column">
    <div class="card">
        <h2>Bestand</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Species</th>
                    <th>Eigentümer</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($animals as $animal): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($animal['name']) ?>
                            <?php if (!empty($animal['is_piebald'])): ?>
                                <span class="animal-marker" title="Geschecktes Tier" aria-label="Geschecktes Tier">⬟</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($animal['species']) ?></td>
                        <td><?= htmlspecialchars($animal['owner_name'] ?? '–') ?></td>
                        <td>
                            <?php if ($animal['is_private']): ?>
                                <span class="badge">Privat</span>
                            <?php endif; ?>
                            <?php if ($animal['is_showcased']): ?>
                                <span class="badge">Highlight</span>
                            <?php endif; ?>
                            <?php if (!empty($animal['is_piebald'])): ?>
                                <span class="badge badge-pattern">Gescheckt</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn btn-secondary" href="<?= BASE_URL ?>/index.php?route=admin/animals&edit=<?= (int)$animal['id'] ?>">Bearbeiten</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Tier wirklich löschen?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_animal">
                                <input type="hidden" name="animal_id" value="<?= (int)$animal['id'] ?>">
                                <button type="submit" class="btn btn-secondary">Löschen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card">
        <h2><?= $editAnimal ? 'Tier bearbeiten' : 'Neues Tier' ?></h2>
        <?php
            $currentSpeciesSlug = $editAnimal['species_slug'] ?? ($speciesList[0]['slug'] ?? null);
            $ageParts = $editAnimal['age_parts'] ?? parse_partial_date($editAnimal['age'] ?? null);
            $currentGeneStates = $editAnimal['gene_states'] ?? [];
            $currentYear = (int)date('Y');
        ?>
        <form method="post" enctype="multipart/form-data" class="admin-animal-form">
            <?= csrf_field() ?>
            <?php if ($editAnimal): ?>
                <input type="hidden" name="id" value="<?= (int)$editAnimal['id'] ?>">
            <?php endif; ?>
            <label>Name
                <input type="text" name="name" value="<?= htmlspecialchars($editAnimal['name'] ?? '') ?>" required>
            </label>
            <div class="form-field">
                <label for="species-select">Art</label>
                <?php if (empty($speciesList)): ?>
                    <p class="form-hint">Bitte lege zuerst unter <a href="<?= BASE_URL ?>/index.php?route=admin/genetics">Genetik</a> mindestens eine Art an.</p>
                <?php endif; ?>
                <select id="species-select" name="species_slug" <?= empty($speciesList) ? 'disabled' : '' ?> data-species-select>
                    <option value="">— Art auswählen —</option>
                    <?php foreach ($speciesList as $species): ?>
                        <option value="<?= htmlspecialchars($species['slug']) ?>" <?= ($currentSpeciesSlug === $species['slug']) ? 'selected' : '' ?>><?= htmlspecialchars($species['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-hint">Arten lassen sich im Bereich <a href="<?= BASE_URL ?>/index.php?route=admin/genetics">Genetikverwaltung</a> ergänzen oder bearbeiten.</p>
            </div>
            <fieldset class="form-field">
                <legend>Schlupfdatum / Alter</legend>
                <div class="age-picker">
                    <label>
                        <span class="sr-only">Jahr</span>
                        <select name="age_year">
                            <option value="">Jahr auswählen</option>
                            <?php for ($year = $currentYear + 1; $year >= 1950; $year--): ?>
                                <option value="<?= $year ?>" <?= ((string)$year === ($ageParts['year'] ?? '')) ? 'selected' : '' ?>><?= $year ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label>
                        <span class="sr-only">Monat</span>
                        <select name="age_month">
                            <option value="">Monat</option>
                            <?php
                                $monthNames = [1=>'Januar',2=>'Februar',3=>'März',4=>'April',5=>'Mai',6=>'Juni',7=>'Juli',8=>'August',9=>'September',10=>'Oktober',11=>'November',12=>'Dezember'];
                                foreach ($monthNames as $number => $label):
                            ?>
                                <option value="<?= $number ?>" <?= ((int)($ageParts['month'] ?? 0) === $number) ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span class="sr-only">Tag</span>
                        <select name="age_day">
                            <option value="">Tag</option>
                            <?php for ($day = 1; $day <= 31; $day++): ?>
                                <option value="<?= $day ?>" <?= ((int)($ageParts['day'] ?? 0) === $day) ? 'selected' : '' ?>><?= $day ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                </div>
                <p class="form-hint">Es genügt, nur Jahr oder Jahr + Monat auszuwählen, falls das genaue Datum nicht bekannt ist.</p>
            </fieldset>
            <fieldset class="form-field">
                <legend>Genetik</legend>
                <?php if (empty($speciesList)): ?>
                    <p class="form-hint">Gene werden verfügbar, sobald eine Art ausgewählt werden kann.</p>
                <?php endif; ?>
                <?php if (!empty($speciesList)): ?>
                    <?php foreach ($speciesList as $species): ?>
                        <?php
                            $isActive = $currentSpeciesSlug === $species['slug'];
                            $genes = $speciesGenes[$species['slug']] ?? [];
                        ?>
                        <div class="gene-group" data-species-genes="<?= htmlspecialchars($species['slug']) ?>" <?= $isActive ? '' : 'hidden' ?>>
                            <?php if (empty($genes)): ?>
                                <p class="form-hint">Für diese Art wurden noch keine Gene gepflegt. <a href="<?= BASE_URL ?>/index.php?route=admin/genetics&amp;species=<?= urlencode($species['slug']) ?>">Jetzt anlegen</a>.</p>
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
            <label>Herkunft
                <input type="text" name="origin" value="<?= htmlspecialchars($editAnimal['origin'] ?? '') ?>">
            </label>
            <label>Besonderheiten
                <textarea name="special_notes" class="rich-text"><?= htmlspecialchars($editAnimal['special_notes'] ?? '') ?></textarea>
            </label>
            <label>Beschreibung
                <textarea name="description" class="rich-text"><?= htmlspecialchars($editAnimal['description'] ?? '') ?></textarea>
            </label>
            <label>Bild
                <input type="file" name="image" accept="image/*">
                <?php if (!empty($editAnimal['image_path'])): ?>
                    <input type="hidden" name="image_path" value="<?= htmlspecialchars($editAnimal['image_path']) ?>">
                    <p><a href="<?= BASE_URL . '/' . htmlspecialchars($editAnimal['image_path']) ?>" target="_blank">Aktuelles Bild anzeigen</a></p>
                <?php endif; ?>
            </label>
            <label>Besitzer
                <select name="owner_id">
                    <option value="">— keiner —</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= (int)$user['id'] ?>" <?= (($editAnimal['owner_id'] ?? '') == $user['id']) ? 'selected' : '' ?>><?= htmlspecialchars($user['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="display:flex;align-items:center;gap:0.5rem;">
                <input type="checkbox" name="is_private" value="1" <?= !empty($editAnimal['is_private']) ? 'checked' : '' ?>> Privat
            </label>
            <label style="display:flex;align-items:center;gap:0.5rem;">
                <input type="checkbox" name="is_showcased" value="1" <?= !empty($editAnimal['is_showcased']) ? 'checked' : '' ?>> In Highlights anzeigen
            </label>
            <label style="display:flex;align-items:center;gap:0.5rem;">
                <input type="checkbox" name="is_piebald" value="1" <?= !empty($editAnimal['is_piebald']) ? 'checked' : '' ?>> Als gescheckt markieren
            </label>
            <button type="submit" <?= empty($speciesList) ? 'disabled' : '' ?>>Speichern</button>
        </form>
    </div>
</div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const speciesSelect = document.querySelector('[data-species-select]');
        if (!speciesSelect) {
            return;
        }
        const groups = document.querySelectorAll('[data-species-genes]');
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
    });
</script>
<?php include __DIR__ . '/../partials/footer.php'; ?>
