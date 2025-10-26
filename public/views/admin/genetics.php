<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
<h1>Genetikverwaltung</h1>
<?php include __DIR__ . '/nav.php'; ?>
<?php if ($flashSuccess): ?>
    <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>

<div class="admin-two-column">
    <div class="card">
        <h2>Genetische Arten</h2>
        <?php if (empty($speciesList)): ?>
            <p>Noch keine Arten hinterlegt.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Wissenschaftlich</th>
                        <th>Slug</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($speciesList as $species): ?>
                        <tr>
                            <td><?= htmlspecialchars($species['name']) ?></td>
                            <td><?= htmlspecialchars($species['scientific_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($species['slug']) ?></td>
                            <td style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                <a class="btn btn-secondary" href="<?= BASE_URL ?>/index.php?route=admin/genetics&amp;species=<?= urlencode($species['slug']) ?>">Anzeigen</a>
                                <a class="btn btn-secondary" href="<?= BASE_URL ?>/index.php?route=admin/genetics&amp;edit_species=<?= (int)$species['id'] ?>">Bearbeiten</a>
                                <form method="post" style="display:inline" onsubmit="return confirm('Art wirklich löschen? Alle zugehörigen Gene werden entfernt.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="form_type" value="delete_species">
                                    <input type="hidden" name="species_id" value="<?= (int)$species['id'] ?>">
                                    <button type="submit" class="btn btn-secondary">Löschen</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div class="card">
        <h2><?= $editSpecies ? 'Art bearbeiten' : 'Neue Art' ?></h2>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="form_type" value="species">
            <?php if ($editSpecies): ?>
                <input type="hidden" name="id" value="<?= (int)$editSpecies['id'] ?>">
            <?php endif; ?>
            <label>Name
                <input type="text" name="name" value="<?= htmlspecialchars($editSpecies['name'] ?? '') ?>" required>
            </label>
            <label>Slug (optional)
                <input type="text" name="slug" value="<?= htmlspecialchars($editSpecies['slug'] ?? '') ?>" placeholder="automatisch">
            </label>
            <label>Wissenschaftlicher Name
                <input type="text" name="scientific_name" value="<?= htmlspecialchars($editSpecies['scientific_name'] ?? '') ?>">
            </label>
            <label>Beschreibung
                <textarea name="description" rows="5"><?= htmlspecialchars($editSpecies['description'] ?? '') ?></textarea>
            </label>
            <button type="submit">Speichern</button>
        </form>
    </div>
</div>

<section style="margin-top:3rem;">
    <h2>Gene verwalten</h2>
    <?php if (empty($speciesList)): ?>
        <div class="card">
            <p>Lege zuerst eine Art an, um Gene zu verwalten.</p>
        </div>
    <?php else: ?>
        <div class="card" style="margin-bottom:1.5rem;">
            <form method="get" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
                <input type="hidden" name="route" value="admin/genetics">
                <label>Art auswählen
                    <select name="species" onchange="this.form.submit()">
                        <?php foreach ($speciesList as $species): ?>
                            <option value="<?= htmlspecialchars($species['slug']) ?>" <?= ($selectedSpeciesSlug === $species['slug']) ? 'selected' : '' ?>><?= htmlspecialchars($species['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <noscript>
                    <button type="submit">Wechseln</button>
                </noscript>
            </form>
            <?php if ($selectedSpecies): ?>
                <p class="text-muted">Aktiv: <?= htmlspecialchars($selectedSpecies['name']) ?><?= $selectedSpecies['scientific_name'] ? ' (' . htmlspecialchars($selectedSpecies['scientific_name']) . ')' : '' ?></p>
                <?php if (!empty($selectedSpecies['description'])): ?>
                    <div class="rich-text-content" style="margin-top:0.75rem;">
                        <?= render_rich_text($selectedSpecies['description']) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="admin-two-column">
            <div class="card">
                <h3>Gene der Art</h3>
                <?php if (empty($genes)): ?>
                    <p>Noch keine Gene angelegt.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Kürzel</th>
                                <th>Vererbung</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($genes as $gene): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($gene['name']) ?></strong><br>
                                        <small class="text-muted">Slug: <?= htmlspecialchars($gene['slug']) ?></small>
                                        <?php if (!empty($gene['is_reference'])): ?>
                                            <span class="badge badge-pattern" style="margin-left:0.5rem;">Referenz</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($gene['shorthand'] ?? '') ?></td>
                                    <td>
                                        <?php
                                            $modeLabels = [
                                                'recessive' => 'rezessiv',
                                                'dominant' => 'dominant',
                                                'incomplete_dominant' => 'inkomplett dominant',
                                            ];
                                        ?>
                                        <?= htmlspecialchars($modeLabels[$gene['inheritance_mode']] ?? $gene['inheritance_mode']) ?>
                                    </td>
                                    <td style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                        <a class="btn btn-secondary" href="<?= BASE_URL ?>/index.php?route=admin/genetics&amp;edit_gene=<?= (int)$gene['id'] ?>">Bearbeiten</a>
                                        <form method="post" style="display:inline" onsubmit="return confirm('Gen wirklich löschen?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="form_type" value="delete_gene">
                                            <input type="hidden" name="gene_id" value="<?= (int)$gene['id'] ?>">
                                            <input type="hidden" name="species_slug" value="<?= htmlspecialchars($selectedSpeciesSlug ?? '') ?>">
                                            <button type="submit" class="btn btn-secondary">Löschen</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="card">
                <h3><?= $editGene ? 'Gen bearbeiten' : 'Neues Gen' ?></h3>
                <?php if (!$selectedSpecies): ?>
                    <p>Bitte zuerst eine Art auswählen.</p>
                <?php else: ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_type" value="gene">
                        <input type="hidden" name="species_id" value="<?= (int)$selectedSpecies['id'] ?>">
                        <input type="hidden" name="species_slug" value="<?= htmlspecialchars($selectedSpecies['slug']) ?>">
                        <?php if ($editGene): ?>
                            <input type="hidden" name="id" value="<?= (int)$editGene['id'] ?>">
                        <?php endif; ?>
                        <label>Name
                            <input type="text" name="name" value="<?= htmlspecialchars($editGene['name'] ?? '') ?>" required>
                        </label>
                        <label>Slug (optional)
                            <input type="text" name="slug" value="<?= htmlspecialchars($editGene['slug'] ?? '') ?>" placeholder="automatisch">
                        </label>
                        <label>Kürzel
                            <input type="text" name="shorthand" value="<?= htmlspecialchars($editGene['shorthand'] ?? '') ?>">
                        </label>
                        <label>Vererbung
                            <?php $currentMode = $editGene['inheritance_mode'] ?? 'recessive'; ?>
                            <select name="inheritance_mode">
                                <option value="recessive" <?= ($currentMode === 'recessive') ? 'selected' : '' ?>>rezessiv</option>
                                <option value="dominant" <?= ($currentMode === 'dominant') ? 'selected' : '' ?>>dominant</option>
                                <option value="incomplete_dominant" <?= ($currentMode === 'incomplete_dominant') ? 'selected' : '' ?>>inkomplett dominant</option>
                            </select>
                        </label>
                        <label>Label (Normalform)
                            <input type="text" name="normal_label" value="<?= htmlspecialchars($editGene['normal_label'] ?? '') ?>" placeholder="z.B. Wildtyp">
                        </label>
                        <label>Label (heterozygot)
                            <input type="text" name="heterozygous_label" value="<?= htmlspecialchars($editGene['heterozygous_label'] ?? '') ?>" placeholder="z.B. het Albino">
                        </label>
                        <label>Label (homozygot)
                            <input type="text" name="homozygous_label" value="<?= htmlspecialchars($editGene['homozygous_label'] ?? '') ?>" placeholder="z.B. Albino">
                        </label>
                        <label>Reihenfolge
                            <input type="number" name="display_order" value="<?= htmlspecialchars((string)($editGene['display_order'] ?? 0)) ?>">
                        </label>
                        <label>Beschreibung
                            <textarea name="description" rows="4"><?= htmlspecialchars($editGene['description'] ?? '') ?></textarea>
                        </label>
                        <button type="submit">Speichern</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
