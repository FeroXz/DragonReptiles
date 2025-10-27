<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="nui-container nui-section">
    <div class="space-y-10">
        <header class="flex flex-col gap-2">
            <h1 class="nui-heading text-3xl text-white">Startseite gestalten</h1>
            <p class="nui-muted max-w-3xl text-sm">Steuere Inhalte, Reihenfolge und eigene Nuxt UI Bereiche der öffentlichen Startseite an einem Ort.</p>
        </header>
        <?php include __DIR__ . '/nav.php'; ?>
        <?php if ($flashSuccess): ?>
            <div class="nui-panel nui-panel--muted border-brand-500/40 text-sm text-brand-100" role="status" aria-live="polite">
                <?= htmlspecialchars($flashSuccess) ?>
            </div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="nui-panel nui-panel--muted border-red-400/60 text-sm text-red-200" role="alert" aria-live="assertive">
                <?= htmlspecialchars($flashError) ?>
            </div>
        <?php endif; ?>

        <article class="nui-panel nui-panel--floating space-y-6">
            <header class="space-y-2">
                <h2 class="nui-heading text-2xl text-white">Standardbereiche personalisieren</h2>
                <p class="nui-muted text-sm">Passe Texte und Mengenangaben der vorinstallierten Sektionen an, ohne den Inhaltseditor wechseln zu müssen.</p>
            </header>
            <form method="post" class="space-y-5">
                <?= csrf_field() ?>
                <input type="hidden" name="intent" value="update-standard">
                <div class="grid gap-5 md:grid-cols-2">
                    <?php foreach ($standardContent as $key => $entry): ?>
                        <?php $definition = $entry['definition']; $value = $entry['value']; $type = $definition['type'] ?? 'text'; ?>
                        <label class="flex flex-col gap-2">
                            <span class="text-sm font-semibold text-slate-200 flex items-center justify-between gap-2">
                                <?= htmlspecialchars($definition['label']) ?>
                                <?php if (($definition['type'] ?? '') === 'number'): ?>
                                    <span class="text-[11px] uppercase tracking-widest text-slate-500">Anzahl</span>
                                <?php endif; ?>
                            </span>
                            <?php if ($type === 'richtext'): ?>
                                <textarea name="blocks[<?= htmlspecialchars($key) ?>]" class="rich-text rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-100" rows="4"><?= htmlspecialchars($value) ?></textarea>
                            <?php elseif ($type === 'number'): ?>
                                <input type="number" min="0" name="blocks[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($value) ?>" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-100">
                            <?php else: ?>
                                <input type="text" name="blocks[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($value) ?>" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-100">
                            <?php endif; ?>
                            <?php if (!empty($definition['help'])): ?>
                                <span class="text-xs text-slate-400"><?= htmlspecialchars($definition['help']) ?></span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="nui-pill nui-button--primary">Änderungen speichern</button>
                    <span class="text-xs text-slate-500">Die Texte werden sofort auf der Startseite übernommen.</span>
                </div>
            </form>
        </article>

        <article class="nui-panel nui-panel--floating space-y-6">
            <header class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="nui-heading text-2xl text-white">Eigene Bereiche</h2>
                    <p class="nui-muted text-sm">Erstelle zusätzliche Sektionen, die vollständig in das Nuxt UI Layout passen.</p>
                </div>
                <?php if ($editingSection): ?>
                    <a href="<?= BASE_URL ?>/index.php?route=admin/home-layout" class="nui-pill text-sm font-semibold text-slate-200">Neuen Bereich anlegen</a>
                <?php endif; ?>
            </header>
            <div class="grid gap-6 lg:grid-cols-2">
                <form method="post" class="space-y-4 rounded-3xl border border-white/8 bg-white/5 p-5 backdrop-blur">
                    <?= csrf_field() ?>
                    <?php if ($editingSection): ?>
                        <input type="hidden" name="intent" value="update-section">
                        <input type="hidden" name="section_id" value="<?= (int)$editingSection['id'] ?>">
                    <?php else: ?>
                        <input type="hidden" name="intent" value="create-section">
                    <?php endif; ?>
                    <h3 class="text-lg font-semibold text-white">
                        <?= $editingSection ? 'Bereich bearbeiten' : 'Neuen Bereich hinzufügen' ?>
                    </h3>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="font-medium text-slate-200">Titel</span>
                        <input type="text" name="title" required value="<?= htmlspecialchars($editingSection['title'] ?? '') ?>" class="rounded-2xl border border-white/10 bg-night-900/60 px-4 py-3 text-slate-100">
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="font-medium text-slate-200">Slug</span>
                        <input type="text" name="slug" value="<?= htmlspecialchars($editingSection['slug'] ?? '') ?>" class="rounded-2xl border border-white/10 bg-night-900/60 px-4 py-3 text-slate-100" placeholder="optional">
                        <span class="text-xs text-slate-500">Leer lassen für automatische Generierung.</span>
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="font-medium text-slate-200">Badge</span>
                        <input type="text" name="eyebrow" value="<?= htmlspecialchars($editingSection['eyebrow'] ?? '') ?>" class="rounded-2xl border border-white/10 bg-night-900/60 px-4 py-3 text-slate-100" placeholder="z. B. Fokus oder Neu">
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="font-medium text-slate-200">Unterzeile</span>
                        <input type="text" name="subtitle" value="<?= htmlspecialchars($editingSection['subtitle'] ?? '') ?>" class="rounded-2xl border border-white/10 bg-night-900/60 px-4 py-3 text-slate-100">
                    </label>
                    <label class="flex flex-col gap-2 text-sm">
                        <span class="font-medium text-slate-200">Inhalt</span>
                        <textarea name="body" class="rich-text rounded-2xl border border-white/10 bg-night-900/60 px-4 py-3 text-slate-100" rows="4"><?= htmlspecialchars($editingSection['body'] ?? '') ?></textarea>
                    </label>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="flex flex-col gap-2 text-sm">
                            <span class="font-medium text-slate-200">CTA Label</span>
                            <input type="text" name="cta_label" value="<?= htmlspecialchars($editingSection['cta_label'] ?? '') ?>" class="rounded-2xl border border-white/10 bg-night-900/60 px-4 py-3 text-slate-100" placeholder="z. B. Mehr erfahren">
                        </label>
                        <label class="flex flex-col gap-2 text-sm">
                            <span class="font-medium text-slate-200">CTA Link</span>
                            <input type="url" name="cta_url" value="<?= htmlspecialchars($editingSection['cta_url'] ?? '') ?>" class="rounded-2xl border border-white/10 bg-night-900/60 px-4 py-3 text-slate-100" placeholder="https://...">
                        </label>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="nui-pill nui-button--primary">
                            <?= $editingSection ? 'Bereich speichern' : 'Bereich erstellen' ?>
                        </button>
                        <?php if ($editingSection): ?>
                            <a href="<?= BASE_URL ?>/index.php?route=admin/home-layout" class="text-xs font-semibold text-slate-300 hover:text-white">Abbrechen</a>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="space-y-4">
                    <?php if ($customSections): ?>
                        <?php foreach ($customSections as $section): ?>
                            <article class="nui-card flex flex-col gap-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                            <?= htmlspecialchars($section['title']) ?>
                                            <?php if (!empty($section['eyebrow'])): ?>
                                                <span class="rounded-full border border-brand-400/40 bg-brand-500/10 px-2 py-0.5 text-[11px] uppercase tracking-widest text-brand-100"><?= htmlspecialchars($section['eyebrow']) ?></span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="text-xs text-slate-500">Slug: <?= htmlspecialchars($section['slug']) ?></p>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="<?= BASE_URL ?>/index.php?route=admin/home-layout&amp;edit=<?= (int)$section['id'] ?>" class="nui-pill text-xs font-semibold text-slate-200">Bearbeiten</a>
                                        <form method="post" onsubmit="return confirm('Bereich wirklich löschen?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="intent" value="delete-section">
                                            <input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">
                                            <button type="submit" class="nui-pill text-xs font-semibold text-red-200">Löschen</button>
                                        </form>
                                    </div>
                                </div>
                                <?php if (!empty($section['subtitle'])): ?>
                                    <p class="text-sm text-slate-300"><?= htmlspecialchars($section['subtitle']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($section['body'])): ?>
                                    <div class="prose prose-invert max-w-none text-sm text-slate-200">
                                        <?= render_rich_text($section['body']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($section['cta_label']) && !empty($section['cta_url'])): ?>
                                    <a href="<?= htmlspecialchars($section['cta_url']) ?>" class="nui-pill inline-flex w-max items-center gap-2 text-xs font-semibold text-brand-100" target="_blank" rel="noopener">
                                        <?= htmlspecialchars($section['cta_label']) ?>
                                        <span aria-hidden="true">↗</span>
                                    </a>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="nui-panel nui-panel--muted text-sm text-slate-400">Noch keine eigenen Bereiche angelegt.</div>
                    <?php endif; ?>
                </div>
            </div>
        </article>

        <article class="nui-panel nui-panel--floating space-y-6">
            <header class="space-y-2">
                <h2 class="nui-heading text-2xl text-white">Reihenfolge &amp; Sichtbarkeit</h2>
                <p class="nui-muted text-sm">Ziehe die Karten, um die Reihenfolge zu ändern, oder deaktiviere Bereiche temporär.</p>
            </header>
            <form method="post" class="flex flex-col gap-5" data-sortable-form>
                <?= csrf_field() ?>
                <input type="hidden" name="intent" value="layout">
                <input type="hidden" name="layout" id="home-layout-input" value='<?= htmlspecialchars(json_encode($layout, JSON_UNESCAPED_UNICODE)) ?>' data-sortable-input>
                <ul class="sortable-list grid gap-3" data-sortable-list data-sortable-input="#home-layout-input">
                    <?php foreach ($layout as $section): ?>
                        <?php $definition = $definitions[$section['key']] ?? ['label' => ucfirst($section['key']), 'description' => '']; ?>
                        <li class="sortable-item nui-card flex items-center gap-4" data-section-key="<?= htmlspecialchars($section['key']) ?>" draggable="true">
                            <div class="sortable-handle text-slate-500" aria-hidden="true">⠿</div>
                            <div class="flex-1">
                                <h3 class="text-base font-semibold text-white flex items-center gap-2">
                                    <?= htmlspecialchars($definition['label']) ?>
                                    <?php if (!empty($definition['custom'])): ?>
                                        <span class="rounded-full border border-brand-400/40 bg-brand-500/10 px-2 py-0.5 text-[11px] uppercase tracking-widest text-brand-100">Custom</span>
                                    <?php endif; ?>
                                </h3>
                                <?php if (!empty($definition['description'])): ?>
                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($definition['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <label class="sortable-toggle flex items-center gap-2 text-xs text-slate-300">
                                <input type="checkbox" data-section-enabled <?= !empty($section['enabled']) ? 'checked' : '' ?> class="h-4 w-4 rounded border-white/20 bg-white/10">
                                <span>aktiv</span>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="flex items-center gap-3">
                    <button type="submit" class="nui-pill nui-button--primary">Layout speichern</button>
                    <button type="button" class="nui-pill text-sm font-semibold text-slate-200" data-reset-layout>Standard wiederherstellen</button>
                </div>
            </form>
        </article>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
