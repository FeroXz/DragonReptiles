<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
<h1>Texte &amp; Inhalte</h1>
<?php include __DIR__ . '/nav.php'; ?>
<?php if ($flashSuccess): ?>
    <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
<?php endif; ?>
<form method="post" class="grid cards" style="margin-top:1.5rem;">
    <?= csrf_field() ?>
    <?php foreach ($contentGroups as $groupName => $entries): ?>
        <fieldset class="card" style="display:flex;flex-direction:column;gap:1rem;">
            <legend style="font-size:1.25rem;font-weight:600;margin-bottom:0.5rem;">
                <?= htmlspecialchars($groupName) ?>
            </legend>
            <?php foreach ($entries as $key => $definition): ?>
                <?php $value = content_value($settings, $key); ?>
                <?php $type = $definition['type'] ?? 'text'; ?>
                <label style="display:flex;flex-direction:column;gap:0.5rem;">
                    <span style="font-weight:600;"><?= htmlspecialchars($definition['label']) ?></span>
                    <?php if ($type === 'richtext'): ?>
                        <textarea name="blocks[<?= htmlspecialchars($key) ?>]" class="rich-text" rows="4"><?= htmlspecialchars($value) ?></textarea>
                    <?php elseif ($type === 'number'): ?>
                        <input type="number" min="0" name="blocks[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($value) ?>">
                    <?php else: ?>
                        <input type="text" name="blocks[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($value) ?>">
                    <?php endif; ?>
                    <?php if (!empty($definition['help'])): ?>
                        <span style="font-size:0.85rem;color:var(--text-muted);"><?= htmlspecialchars($definition['help']) ?></span>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
    <?php endforeach; ?>
    <div style="grid-column:1 / -1;">
        <button type="submit">Texte speichern</button>
    </div>
</form>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
