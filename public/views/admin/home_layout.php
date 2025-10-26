<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <h1>Startseite strukturieren</h1>
    <?php include __DIR__ . '/nav.php'; ?>
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-danger" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
    <article class="card" style="margin-top:1.5rem;">
        <header class="card-header">
            <h2 class="card-title">Sektionen per Drag &amp; Drop sortieren</h2>
            <p class="card-subtitle">Ziehe die Blöcke in die gewünschte Reihenfolge. Deaktiviere Bereiche, um sie temporär auszublenden.</p>
        </header>
        <form method="post" class="flex flex-col gap-5" data-sortable-form>
            <?= csrf_field() ?>
            <input type="hidden" name="layout" id="home-layout-input" value='<?= htmlspecialchars(json_encode($layout, JSON_UNESCAPED_UNICODE)) ?>' data-sortable-input>
            <ul class="sortable-list" data-sortable-list data-sortable-input="#home-layout-input">
                <?php foreach ($layout as $section): ?>
                    <?php $definition = $definitions[$section['key']] ?? ['label' => ucfirst($section['key']), 'description' => '']; ?>
                    <li class="sortable-item" data-section-key="<?= htmlspecialchars($section['key']) ?>" draggable="true">
                        <div class="sortable-handle" aria-hidden="true">⠿</div>
                        <div class="sortable-content">
                            <h3><?= htmlspecialchars($definition['label']) ?></h3>
                            <?php if (!empty($definition['description'])): ?>
                                <p><?= htmlspecialchars($definition['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <label class="sortable-toggle">
                            <input type="checkbox" data-section-enabled <?= !empty($section['enabled']) ? 'checked' : '' ?>>
                            <span>aktiv</span>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-primary">Layout speichern</button>
                <button type="button" class="btn" data-reset-layout>Standard wiederherstellen</button>
            </div>
        </form>
    </article>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
