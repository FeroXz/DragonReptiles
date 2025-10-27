<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
<h1 class="text-3xl font-semibold text-white sm:text-4xl"><?= htmlspecialchars(content_value($settings, 'genetics_title')) ?></h1>
<p class="mt-2 text-sm text-slate-300"><?= htmlspecialchars(content_value($settings, 'genetics_intro')) ?></p>

<?php if (empty($speciesList)): ?>
    <div class="card">
        <p><?= htmlspecialchars(content_value($settings, 'genetics_empty_notice')) ?></p>
    </div>
<?php else: ?>
    <div class="card" style="margin-bottom:2rem;">
        <form method="get" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
            <input type="hidden" name="route" value="genetics">
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
            <p style="margin-top:0.5rem;">Aktuelle Art: <strong><?= htmlspecialchars($selectedSpecies['name']) ?></strong><?php if (!empty($selectedSpecies['scientific_name'])): ?> (<em><?= htmlspecialchars($selectedSpecies['scientific_name']) ?></em>)<?php endif; ?></p>
            <?php if (!empty($selectedSpecies['description'])): ?>
                <div class="rich-text-content" style="margin-top:0.75rem;">
                    <?= render_rich_text($selectedSpecies['description']) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if ($selectedSpecies && !empty($genes)): ?>
        <?php
            $toLower = static function (string $value): string {
                return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
            };
            $modeLabels = [
                'recessive' => 'rezessiv',
                'dominant' => 'dominant',
                'incomplete_dominant' => 'inkomplett dominant',
            ];
            $geneStatePayload = [];
            foreach ($genes as $gene) {
                $geneId = (int)$gene['id'];
                $stateEntries = [];
                foreach (['heterozygous', 'homozygous'] as $stateKey) {
                    $label = gene_state_label($gene, $stateKey);
                    $tokens = [$toLower($label), $toLower($gene['name'])];
                    if (!empty($gene['shorthand'])) {
                        $tokens[] = $toLower($gene['shorthand']);
                    }
                    if ($stateKey === 'heterozygous') {
                        $tokens[] = 'het ' . $toLower($gene['name']);
                        $tokens[] = 'träger ' . $toLower($gene['name']);
                    } else {
                        $tokens[] = 'visual ' . $toLower($gene['name']);
                    }
                    $stateEntries[] = [
                        'key' => $stateKey,
                        'label' => $label,
                        'searchTokens' => array_values(array_unique($tokens)),
                    ];
                }
                $geneStatePayload[] = [
                    'id' => $geneId,
                    'name' => $gene['name'],
                    'shorthand' => $gene['shorthand'],
                    'inheritance' => $modeLabels[$gene['inheritance_mode']] ?? $gene['inheritance_mode'],
                    'description' => $gene['description'],
                    'states' => $stateEntries,
                ];
            }
            $comboPayload = [];
            foreach ($combinationAliases as $alias) {
                $comboPayload[] = [
                    'key' => $alias['key'],
                    'name' => $alias['name'],
                    'display' => $alias['display'],
                    'synonyms' => $alias['synonyms'],
                    'components' => array_map(static function ($component) {
                        return [
                            'geneId' => $component['gene_id'],
                            'geneSlug' => $component['gene_slug'],
                            'geneName' => $component['gene_name'],
                            'stateKey' => $component['state'],
                            'label' => $component['label'],
                        ];
                    }, $alias['components']),
                ];
            }
        ?>
        <form method="post" class="card gene-selector" data-genetic-selector>
            <input type="hidden" name="species_slug" value="<?= htmlspecialchars($selectedSpecies['slug']) ?>">
            <div class="gene-selector__intro">
                <p><strong>Eingabehilfe:</strong> Tippen Sie einen Gen-Namen oder Trägerstatus (z.&nbsp;B. „Albino“, „het Toffee“, „Super Anaconda“). Bestätigen Sie den Vorschlag mit Enter oder einem Klick. Nicht ausgewählte Gene bleiben als Basisform erhalten.</p>
            </div>
            <div class="alert alert-error" data-form-error hidden role="alert" aria-live="assertive"></div>
            <div class="gene-selector__parents">
                <section class="gene-parent" data-parent="parent1">
                    <h2>Elter 1</h2>
                    <p class="text-muted">Fügen Sie alle sichtbaren Morphe sowie Trägereigenschaften hinzu.</p>
                    <div class="gene-parent__tags" data-tag-container></div>
                    <div class="gene-parent__input">
                        <input type="text" placeholder="Gen oder Bezeichnung eingeben …" data-input>
                        <button type="button" class="btn btn-secondary" data-clear>Zurücksetzen</button>
                    </div>
                    <div class="gene-parent__suggestions" data-suggestions hidden></div>
                    <div data-hidden-inputs></div>
                </section>
                <section class="gene-parent" data-parent="parent2">
                    <h2>Elter 2</h2>
                    <p class="text-muted">Bestimmen Sie visuelle Merkmale oder Heterozygotie wie „het Albino“.</p>
                    <div class="gene-parent__tags" data-tag-container></div>
                    <div class="gene-parent__input">
                        <input type="text" placeholder="Gen oder Bezeichnung eingeben …" data-input>
                        <button type="button" class="btn btn-secondary" data-clear>Zurücksetzen</button>
                    </div>
                    <div class="gene-parent__suggestions" data-suggestions hidden></div>
                    <div data-hidden-inputs></div>
                </section>
            </div>
            <button type="submit" class="btn" style="margin-top:1.5rem;align-self:flex-start;"><?= htmlspecialchars(content_value($settings, 'genetics_submit')) ?></button>
        </form>
        <?php if (!empty($results)): ?>
            <section class="gene-results">
                <div class="card gene-results__summary">
                    <h2>Auswertung</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Wahrscheinlichkeit</th>
                                <th>Ausprägung</th>
                                <th>Genotyp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['combined'] as $entry): ?>
                                <tr>
                                    <td><?= number_format($entry['probability'] * 100, 1, ',', '.') ?>%</td>
                                    <td>
                                        <?php if (!empty($entry['combination_labels'])): ?>
                                            <div class="result-tags">
                                                <?php foreach ($entry['combination_labels'] as $comboLabel): ?>
                                                    <span class="tag tag-combo"><?= htmlspecialchars($comboLabel) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div><?= htmlspecialchars($entry['phenotype'] ?: '–') ?></div>
                                    </td>
                                    <td>
                                        <?php foreach ($entry['labels'] as $label): ?>
                                            <div><?= htmlspecialchars($label) ?></div>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="gene-results__per-gene">
                    <?php foreach ($results['genes'] as $geneResult): ?>
                        <?php $gene = $geneResult['gene']; ?>
                        <article class="card gene-results__card">
                            <h3><?= htmlspecialchars($gene['name']) ?></h3>
                            <p class="text-muted" style="font-size:0.9rem;">Elter 1: <?= htmlspecialchars(gene_state_label($gene, $geneResult['parent_states']['parent_one'])) ?> · Elter 2: <?= htmlspecialchars(gene_state_label($gene, $geneResult['parent_states']['parent_two'])) ?></p>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Genotyp</th>
                                        <th>Wahrscheinlichkeit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($geneResult['states'] as $state): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($state['label']) ?>
                                                <?php if ($state['is_visual']): ?>
                                                    <span class="tag tag-visual">visuell</span>
                                                <?php elseif ($state['is_carrier']): ?>
                                                    <span class="tag tag-carrier">Träger</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= number_format($state['probability'] * 100, 1, ',', '.') ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedSpecies): ?>
            <div class="card" style="margin-top:2rem;">
                <p>Bitte wählen Sie mindestens ein Gen mit Träger- oder visueller Ausprägung aus, um eine Auswertung zu erhalten.</p>
            </div>
        <?php endif; ?>
        <section class="gene-reference">
            <h2>Verfügbare Gene</h2>
            <div class="grid cards">
                <?php foreach ($genes as $gene): ?>
                    <article class="card gene-reference__card">
                        <header class="gene-reference__header">
                            <div>
                                <h3><?= htmlspecialchars($gene['name']) ?></h3>
                                <?php if (!empty($gene['shorthand'])): ?>
                                    <span class="badge">Kürzel: <?= htmlspecialchars($gene['shorthand']) ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="badge"><?= htmlspecialchars($modeLabels[$gene['inheritance_mode']] ?? $gene['inheritance_mode']) ?></span>
                        </header>
                        <?php if ($imageUrl = media_url($gene['image_path'] ?? null)): ?>
                            <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($gene['name']) ?>" class="gene-reference__image">
                        <?php endif; ?>
                        <dl class="gene-reference__states">
                            <div><dt>Träger</dt><dd><?= htmlspecialchars(gene_state_label($gene, 'heterozygous')) ?></dd></div>
                            <div><dt>Visuell</dt><dd><?= htmlspecialchars(gene_state_label($gene, 'homozygous')) ?></dd></div>
                        </dl>
                        <?php if (!empty($gene['description'])): ?>
                            <p class="text-muted" style="line-height:1.5;"><?= htmlspecialchars($gene['description']) ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php if (!empty($referenceGenes ?? [])): ?>
            <section class="gene-reference" style="margin-top:2.5rem;">
                <h2>Kombinations-Referenzen</h2>
                <p class="text-muted" style="margin-bottom:1rem;line-height:1.5;">
                    Diese Einträge dokumentieren bestätigte Morph-Kombinationen. Für Berechnungen im Genetik-Rechner
                    bitte die jeweiligen Basismorphe auswählen – die Karten dienen ausschließlich als Nachschlagewerk.
                </p>
                <div class="grid cards">
                    <?php foreach ($referenceGenes as $gene): ?>
                        <article class="card gene-reference__card">
                            <header class="gene-reference__header">
                                <div>
                                    <h3><?= htmlspecialchars($gene['name']) ?></h3>
                                    <?php if (!empty($gene['shorthand'])): ?>
                                        <span class="badge">Kürzel: <?= htmlspecialchars($gene['shorthand']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge badge-pattern">Referenz</span>
                            </header>
                            <dl class="gene-reference__states">
                                <div><dt>Basis</dt><dd><?= htmlspecialchars($gene['normal_label']) ?></dd></div>
                                <div><dt>Teil-Kombi</dt><dd><?= htmlspecialchars($gene['heterozygous_label']) ?></dd></div>
                                <div><dt>Komplett</dt><dd><?= htmlspecialchars($gene['homozygous_label']) ?></dd></div>
                            </dl>
                            <?php if (!empty($gene['description'])): ?>
                                <p class="text-muted" style="line-height:1.5;">
                                    <?= htmlspecialchars($gene['description']) ?>
                                </p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        <script>
            window.GENETIC_GENE_DATA = <?= json_encode($geneStatePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            window.GENETIC_PARENT_SELECTIONS = <?= json_encode($parentSelections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            window.GENETIC_COMBINATIONS = <?= json_encode($comboPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        </script>
    <?php elseif ($selectedSpecies): ?>
        <div class="card" style="margin-bottom:2rem;">
            <p>Für diese Art wurden bislang keine Gene hinterlegt.</p>
        </div>
    <?php endif; ?>

<?php endif; ?>

</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
