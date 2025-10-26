<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
<h1>Zuchtplanung</h1>
<?php include __DIR__ . '/nav.php'; ?>
<?php if ($flashSuccess): ?>
    <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError) ?></div>
<?php endif; ?>
<?php
    $currentUser = current_user();
    $ownedAnimals = [];
    $otherAnimals = [];
    foreach ($animals as $animalOption) {
        if ($currentUser && (int)($animalOption['owner_id'] ?? 0) === (int)$currentUser['id']) {
            $ownedAnimals[] = $animalOption;
        } else {
            $otherAnimals[] = $animalOption;
        }
    }
?>
<div class="admin-two-column">
    <div class="card">
        <h2>Aktive Pläne</h2>
        <?php if (empty($breedingPlans)): ?>
            <p>Es wurden bislang keine Zuchtpläne angelegt.</p>
        <?php else: ?>
            <div class="stack">
                <?php foreach ($breedingPlans as $plan): ?>
                    <article class="plan-entry">
                        <header class="plan-entry__header">
                            <div>
                                <h3><?= htmlspecialchars($plan['title']) ?></h3>
                                <p class="text-muted">
                                    <?= htmlspecialchars($plan['season'] ?: 'Saison offen') ?>
                                </p>
                            </div>
                            <div class="plan-entry__actions">
                                <a class="btn btn-secondary" href="<?= BASE_URL ?>/index.php?route=admin/breeding&edit_plan=<?= (int)$plan['id'] ?>">Bearbeiten</a>
                                <form method="post" style="display:inline" onsubmit="return confirm('Zuchtplan wirklich löschen?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="form" value="delete_plan">
                                    <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                                    <button type="submit" class="btn btn-secondary">Löschen</button>
                                </form>
                            </div>
                        </header>
                        <?php if (!empty($plan['expected_genetics'])): ?>
                            <div class="plan-entry__section">
                                <strong>Erwartete Genetik:</strong>
                                <div class="rich-text-content"><?= render_rich_text($plan['expected_genetics']) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($plan['incubation_notes'])): ?>
                            <div class="plan-entry__section">
                                <strong>Inkubation:</strong>
                                <div class="rich-text-content"><?= render_rich_text($plan['incubation_notes']) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($plan['notes'])): ?>
                            <div class="rich-text-content"><?= render_rich_text($plan['notes']) ?></div>
                        <?php endif; ?>
                        <h4>Elterntiere</h4>
                        <?php if (empty($plan['parents'])): ?>
                            <p class="text-muted">Derzeit sind keine Eltern hinterlegt.</p>
                        <?php else: ?>
                            <ul class="plan-parents">
                                <?php foreach ($plan['parents'] as $parent): ?>
                                    <li>
                                        <div class="plan-parent__title">
                                            <strong><?= htmlspecialchars($parent['parent_type'] === 'virtual' ? ($parent['name'] ?: 'Virtuell') : ($parent['animal_name'] ?? $parent['name'] ?? 'Unbenannt')) ?></strong>
                                            <?php if ($parent['sex']): ?>
                                                <span class="badge"><?= htmlspecialchars(strtoupper($parent['sex'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted">
                                            <?= htmlspecialchars($parent['parent_type'] === 'virtual' ? 'Virtuelles Tier' : 'Bestandstier') ?>
                                        </div>
                                        <?php if ($parent['parent_type'] === 'animal' && $parent['animal_species']): ?>
                                            <div><?= htmlspecialchars($parent['animal_species']) ?></div>
                                        <?php elseif (!empty($parent['species'])): ?>
                                            <div><?= htmlspecialchars($parent['species']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($parent['animal_genetics']) || !empty($parent['genetics'])): ?>
                                            <div><strong>Genetik:</strong> <?= htmlspecialchars($parent['animal_genetics'] ?? $parent['genetics']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($parent['notes'])): ?>
                                            <div><?= nl2br(htmlspecialchars($parent['notes'])) ?></div>
                                        <?php endif; ?>
                                        <form method="post" style="display:inline" onsubmit="return confirm('Elternteil entfernen?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="form" value="delete_parent">
                                            <input type="hidden" name="parent_id" value="<?= (int)$parent['id'] ?>">
                                            <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                                            <button type="submit" class="btn btn-secondary">Entfernen</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="card">
        <h2><?= $editPlan ? 'Zuchtplan bearbeiten' : 'Neuer Zuchtplan' ?></h2>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="plan">
            <?php if ($editPlan): ?>
                <input type="hidden" name="id" value="<?= (int)$editPlan['id'] ?>">
            <?php endif; ?>
            <label>Titel
                <input type="text" name="title" value="<?= htmlspecialchars($editPlan['title'] ?? '') ?>" required>
            </label>
            <label>Saison / Jahr
                <input type="text" name="season" value="<?= htmlspecialchars($editPlan['season'] ?? '') ?>" placeholder="z.B. 2024/2025">
            </label>
            <label>Erwartete Genetik
                <textarea name="expected_genetics" class="rich-text"><?= htmlspecialchars($editPlan['expected_genetics'] ?? '') ?></textarea>
            </label>
            <label>Inkubationsnotizen
                <textarea name="incubation_notes" class="rich-text"><?= htmlspecialchars($editPlan['incubation_notes'] ?? '') ?></textarea>
            </label>
            <label>Allgemeine Notizen
                <textarea name="notes" class="rich-text"><?= htmlspecialchars($editPlan['notes'] ?? '') ?></textarea>
            </label>
            <button type="submit">Zuchtplan speichern</button>
        </form>
        <?php if (!empty($breedingPlans)): ?>
            <hr style="margin:2rem 0;opacity:0.3;">
            <h3>Elterntier hinzufügen</h3>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form" value="parent">
                <label>Zuchtplan
                    <select name="plan_id" required>
                        <?php foreach ($breedingPlans as $planOption): ?>
                            <option value="<?= (int)$planOption['id'] ?>" <?= (($editPlan['id'] ?? 0) == $planOption['id']) ? 'selected' : '' ?>><?= htmlspecialchars($planOption['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Eltern-Typ
                    <select name="parent_type">
                        <option value="animal">Tier aus Bestand</option>
                        <option value="virtual">Virtuelles Tier</option>
                    </select>
                </label>
            <label>Tier aus Bestand
                <select name="animal_id">
                    <option value="">— auswählen —</option>
                    <?php if (!empty($ownedAnimals)): ?>
                        <optgroup label="Eigene Tiere">
                            <?php foreach ($ownedAnimals as $animal): ?>
                                <option value="<?= (int)$animal['id'] ?>"><?= htmlspecialchars($animal['name']) ?> (<?= htmlspecialchars($animal['species']) ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php if (!empty($otherAnimals)): ?>
                        <optgroup label="Bestand (Team)">
                            <?php foreach ($otherAnimals as $animal): ?>
                                <option value="<?= (int)$animal['id'] ?>"><?= htmlspecialchars($animal['name']) ?> (<?= htmlspecialchars($animal['species']) ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </label>
                <label>Name (für virtuelle Eltern)
                    <input type="text" name="name" value="">
                </label>
                <label>Geschlecht (m/w)
                    <input type="text" name="sex" value="">
                </label>
                <label>Art / Lokalität
                    <input type="text" name="species" value="">
                </label>
            <label>Genetik
                    <textarea name="genetics" class="rich-text"></textarea>
            </label>
            <label>Notizen
                    <textarea name="notes" class="rich-text"></textarea>
            </label>
                <button type="submit">Elternteil speichern</button>
            </form>
            <hr style="margin:2rem 0;opacity:0.3;">
            <h3>Verpaarung anlegen</h3>
            <form method="post" data-breeding-pair-form>
                <?= csrf_field() ?>
                <input type="hidden" name="form" value="pair">
                <label>Zuchtplan
                    <select name="pair_plan_id" required>
                        <?php foreach ($breedingPlans as $planOption): ?>
                            <option value="<?= (int)$planOption['id'] ?>" <?= (($editPlan['id'] ?? 0) == $planOption['id']) ? 'selected' : '' ?>><?= htmlspecialchars($planOption['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="pair-grid">
                    <fieldset class="parent-block">
                        <legend>Elternteil A</legend>
                        <label>Quelle
                            <select name="parent_a_type" data-parent-type="a">
                                <option value="animal">Tier aus dem Bestand</option>
                                <option value="virtual">Virtuelles Tier</option>
                            </select>
                        </label>
                        <div class="parent-block__group" data-parent-group="a" data-group-type="animal">
                            <label>Tier auswählen
                                <select name="parent_a_animal_id">
                                    <option value="">— auswählen —</option>
                                    <?php if (!empty($ownedAnimals)): ?>
                                        <optgroup label="Eigene Tiere">
                                            <?php foreach ($ownedAnimals as $animal): ?>
                                                <option value="<?= (int)$animal['id'] ?>"><?= htmlspecialchars($animal['name']) ?> (<?= htmlspecialchars($animal['species']) ?>)</option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    <?php if (!empty($otherAnimals)): ?>
                                        <optgroup label="Bestand (Team)">
                                            <?php foreach ($otherAnimals as $animal): ?>
                                                <option value="<?= (int)$animal['id'] ?>"><?= htmlspecialchars($animal['name']) ?> (<?= htmlspecialchars($animal['species']) ?>)</option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>
                            </label>
                        </div>
                        <div class="parent-block__group" data-parent-group="a" data-group-type="virtual" hidden>
                            <label>Name
                                <input type="text" name="parent_a_name">
                            </label>
                            <label>Geschlecht (m/w)
                                <input type="text" name="parent_a_sex">
                            </label>
                            <label>Art / Lokalität
                                <input type="text" name="parent_a_species">
                            </label>
                            <label>Genetik
                                <textarea name="parent_a_genetics" class="rich-text"></textarea>
                            </label>
                            <label>Notizen
                                <textarea name="parent_a_notes" class="rich-text"></textarea>
                            </label>
                        </div>
                    </fieldset>
                    <fieldset class="parent-block">
                        <legend>Elternteil B</legend>
                        <label>Quelle
                            <select name="parent_b_type" data-parent-type="b">
                                <option value="animal">Tier aus dem Bestand</option>
                                <option value="virtual">Virtuelles Tier</option>
                            </select>
                        </label>
                        <div class="parent-block__group" data-parent-group="b" data-group-type="animal">
                            <label>Tier auswählen
                                <select name="parent_b_animal_id">
                                    <option value="">— auswählen —</option>
                                    <?php if (!empty($ownedAnimals)): ?>
                                        <optgroup label="Eigene Tiere">
                                            <?php foreach ($ownedAnimals as $animal): ?>
                                                <option value="<?= (int)$animal['id'] ?>"><?= htmlspecialchars($animal['name']) ?> (<?= htmlspecialchars($animal['species']) ?>)</option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    <?php if (!empty($otherAnimals)): ?>
                                        <optgroup label="Bestand (Team)">
                                            <?php foreach ($otherAnimals as $animal): ?>
                                                <option value="<?= (int)$animal['id'] ?>"><?= htmlspecialchars($animal['name']) ?> (<?= htmlspecialchars($animal['species']) ?>)</option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>
                            </label>
                        </div>
                        <div class="parent-block__group" data-parent-group="b" data-group-type="virtual" hidden>
                            <label>Name
                                <input type="text" name="parent_b_name">
                            </label>
                            <label>Geschlecht (m/w)
                                <input type="text" name="parent_b_sex">
                            </label>
                            <label>Art / Lokalität
                                <input type="text" name="parent_b_species">
                            </label>
                            <label>Genetik
                                <textarea name="parent_b_genetics" class="rich-text"></textarea>
                            </label>
                            <label>Notizen
                                <textarea name="parent_b_notes" class="rich-text"></textarea>
                            </label>
                        </div>
                    </fieldset>
                </div>
                <button type="submit">Verpaarung speichern</button>
            </form>
        <?php endif; ?>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-breeding-pair-form]');
    if (!form) {
        return;
    }
    function toggleGroups(parentKey, type) {
        form.querySelectorAll('[data-parent-group="' + parentKey + '"]').forEach(function (group) {
            group.hidden = group.getAttribute('data-group-type') !== type;
        });
    }
    form.querySelectorAll('[data-parent-type]').forEach(function (select) {
        const parentKey = select.getAttribute('data-parent-type');
        toggleGroups(parentKey, select.value);
        select.addEventListener('change', function () {
            toggleGroups(parentKey, select.value);
        });
    });
});
</script>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>

