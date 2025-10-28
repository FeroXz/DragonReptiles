<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="admin-shell__header">
        <div>
            <h1>Navigation verwalten</h1>
            <p class="admin-shell__subtitle">Ordne öffentliche und Admin-Menüs, passe Labels an und steuere Sichtbarkeiten zentral.</p>
        </div>
        <p class="admin-shell__hint">Einträge per Drag &amp; Drop neu sortieren. Aktionen werden direkt gespeichert.</p>
    </div>
    <?php include __DIR__ . '/nav.php'; ?>
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
    <?php
        $frontendItems = [];
        $adminItems = [];
        foreach ($menuItems as $item) {
            if (($item['location'] ?? 'frontend') === 'admin') {
                $adminItems[] = $item;
            } else {
                $frontendItems[] = $item;
            }
        }
        $apiEndpoint = BASE_URL . '/admin/api/menu.php';
    ?>
    <div class="card menu-manager" data-menu-manager data-menu-endpoint="<?= htmlspecialchars($apiEndpoint) ?>" data-menu-csrf="<?= htmlspecialchars($csrfToken) ?>">
        <div class="menu-manager__toolbar">
            <button type="button" class="btn btn-primary" data-menu-new>Neuer Eintrag</button>
        </div>
        <div class="menu-manager__table">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:3rem;" aria-label="Position"></th>
                        <th>Label &amp; Link</th>
                        <th>Icon</th>
                        <th>Ziel</th>
                        <th>Status</th>
                        <th style="width:10rem;" class="text-right">Aktionen</th>
                    </tr>
                </thead>
                <tbody data-menu-location="frontend">
                    <tr class="menu-manager__section">
                        <td colspan="6">Öffentliche Navigation</td>
                    </tr>
                    <?php if (empty($frontendItems)): ?>
                        <tr class="menu-manager__empty" data-menu-empty="frontend">
                            <td colspan="6">Keine Einträge vorhanden.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($frontendItems as $item): ?>
                            <tr class="menu-manager__row" data-menu-row data-id="<?= (int)$item['id'] ?>" data-location="frontend" data-label="<?= htmlspecialchars($item['label'], ENT_QUOTES) ?>" data-path="<?= htmlspecialchars($item['path'], ENT_QUOTES) ?>" data-icon="<?= htmlspecialchars((string)($item['icon'] ?? ''), ENT_QUOTES) ?>" data-target="<?= htmlspecialchars($item['target'] ?? '_self', ENT_QUOTES) ?>" data-visible="<?= (int)$item['visible'] ?>" data-position="<?= (int)$item['position'] ?>">
                                <td class="menu-manager__handle" data-menu-handle draggable="true" title="Zum Sortieren ziehen">⋮⋮</td>
                                <td>
                                    <strong><?= htmlspecialchars($item['label']) ?></strong>
                                    <div class="menu-manager__path"><?= htmlspecialchars($item['path']) ?></div>
                                </td>
                                <td><?= htmlspecialchars((string)($item['icon'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($item['target'] ?? '_self') ?></td>
                                <td>
                                    <?php if (!empty($item['visible'])): ?>
                                        <span class="badge">sichtbar</span>
                                    <?php else: ?>
                                        <span class="badge badge-muted">ausgeblendet</span>
                                    <?php endif; ?>
                                </td>
                                <td class="menu-manager__actions">
                                    <button type="button" class="btn btn-tertiary" data-menu-toggle><?= !empty($item['visible']) ? 'Verbergen' : 'Anzeigen' ?></button>
                                    <button type="button" class="btn btn-secondary" data-menu-edit>Bearbeiten</button>
                                    <button type="button" class="btn btn-secondary" data-menu-delete>Entfernen</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tbody data-menu-location="admin">
                    <tr class="menu-manager__section">
                        <td colspan="6">Admin-Navigation</td>
                    </tr>
                    <?php if (empty($adminItems)): ?>
                        <tr class="menu-manager__empty" data-menu-empty="admin">
                            <td colspan="6">Keine Einträge vorhanden.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($adminItems as $item): ?>
                            <tr class="menu-manager__row" data-menu-row data-id="<?= (int)$item['id'] ?>" data-location="admin" data-label="<?= htmlspecialchars($item['label'], ENT_QUOTES) ?>" data-path="<?= htmlspecialchars($item['path'], ENT_QUOTES) ?>" data-icon="<?= htmlspecialchars((string)($item['icon'] ?? ''), ENT_QUOTES) ?>" data-target="<?= htmlspecialchars($item['target'] ?? '_self', ENT_QUOTES) ?>" data-visible="<?= (int)$item['visible'] ?>" data-position="<?= (int)$item['position'] ?>">
                                <td class="menu-manager__handle" data-menu-handle draggable="true" title="Zum Sortieren ziehen">⋮⋮</td>
                                <td>
                                    <strong><?= htmlspecialchars($item['label']) ?></strong>
                                    <div class="menu-manager__path"><?= htmlspecialchars($item['path']) ?></div>
                                </td>
                                <td><?= htmlspecialchars((string)($item['icon'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($item['target'] ?? '_self') ?></td>
                                <td>
                                    <?php if (!empty($item['visible'])): ?>
                                        <span class="badge">sichtbar</span>
                                    <?php else: ?>
                                        <span class="badge badge-muted">ausgeblendet</span>
                                    <?php endif; ?>
                                </td>
                                <td class="menu-manager__actions">
                                    <button type="button" class="btn btn-tertiary" data-menu-toggle><?= !empty($item['visible']) ? 'Verbergen' : 'Anzeigen' ?></button>
                                    <button type="button" class="btn btn-secondary" data-menu-edit>Bearbeiten</button>
                                    <button type="button" class="btn btn-secondary" data-menu-delete>Entfernen</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="menu-manager__modal" data-menu-modal hidden>
        <div class="menu-manager__dialog" role="dialog" aria-modal="true" aria-labelledby="menu-modal-title">
            <div class="menu-manager__dialog-header">
                <h2 id="menu-modal-title">Menüeintrag bearbeiten</h2>
                <button type="button" class="menu-manager__close" data-menu-close aria-label="Schließen">×</button>
            </div>
            <form data-menu-form>
                <input type="hidden" name="id" data-menu-field="id">
                <label>Label
                    <input type="text" name="label" data-menu-field="label" required>
                </label>
                <label>Pfad / URL
                    <input type="text" name="path" data-menu-field="path" placeholder="/index.php?route=..." required>
                </label>
                <label>Icon (Schlüssel oder Emoji)
                    <input type="text" name="icon" data-menu-field="icon" placeholder="z. B. home, animals, ⚡">
                </label>
                <label>Ziel
                    <select name="target" data-menu-field="target">
                        <option value="_self">Selbes Fenster</option>
                        <option value="_blank">Neuer Tab</option>
                    </select>
                </label>
                <label>Bereich
                    <select name="location" data-menu-field="location">
                        <option value="frontend">Öffentlich</option>
                        <option value="admin">Admin</option>
                    </select>
                </label>
                <label class="menu-manager__checkbox">
                    <input type="checkbox" name="visible" value="1" data-menu-field="visible"> Sichtbar
                </label>
                <div class="menu-manager__dialog-actions">
                    <button type="button" class="btn btn-secondary" data-menu-close>Abbrechen</button>
                    <button type="submit" class="btn btn-primary" data-menu-submit>Speichern</button>
                </div>
                <p class="menu-manager__error" data-menu-error hidden></p>
            </form>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
