<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="admin-shell__header">
        <h1>ArminDashboard</h1>
        <p class="admin-shell__subtitle">Modernes HorizonUI 3.0 für schnelle Entscheidungen und Nuxt UI-inspirierte Übersichtlichkeit.</p>
    </div>
    <?php include __DIR__ . '/nav.php'; ?>
    <?php
        $statCards = [
            [
                'value' => count($animals),
                'label' => 'Aktive Tiere',
                'trend' => 'Datensätze im Bestand',
                'icon' => '<path d="M5 11c0-4.5 3-7 7-7s7 2.5 7 7" /><path d="M4 11h16v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" />',
            ],
            [
                'value' => count($listings),
                'label' => 'Abgabe-Einträge',
                'trend' => 'Inserate live',
                'icon' => '<path d="M12 21s-7-4.5-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 11c0 5.5-7 10-7 10z" />',
            ],
            [
                'value' => count($inquiries),
                'label' => 'Neue Anfragen',
                'trend' => 'Nachrichten im Postfach',
                'icon' => '<path d="M21 15a2 2 0 0 1-2 2H9l-4 4V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z" />',
            ],
            [
                'value' => count($pages),
                'label' => 'Seiten',
                'trend' => 'Aktive Inhalte',
                'icon' => '<path d="M5 5h10l4 4v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z" /><path d="M15 5v4h4" />',
            ],
            [
                'value' => count($newsPosts),
                'label' => 'News',
                'trend' => 'Beiträge veröffentlicht',
                'icon' => '<path d="M4 5h16" /><path d="M4 11h16" /><path d="M4 17h10" />',
            ],
            [
                'value' => count($breedingPlans),
                'label' => 'Zuchtpläne',
                'trend' => 'Laufende Projekte',
                'icon' => '<path d="M7 17a5 5 0 0 1 5-5" /><path d="M17 7a5 5 0 0 1-5 5" /><path d="M12 22v-9" /><path d="M12 2v5" />',
            ],
            [
                'value' => count($careArticles),
                'label' => 'Pflegeartikel',
                'trend' => 'Guides im System',
                'icon' => '<path d="M12 6c-1.5-3-5-3-6 0-1.5 4 2 7 6 10 4-3 7.5-6 6-10-1-3-4.5-3-6 0z" />',
            ],
            [
                'value' => isset($geneticSpecies) ? count($geneticSpecies) : 0,
                'label' => 'Genetische Arten',
                'trend' => 'Datensätze verfügbar',
                'icon' => '<path d="M6 3c0 6 12 6 12 12" /><path d="M6 9c0 6 12 6 12 12" /><path d="M12 3v18" />',
            ],
            [
                'value' => isset($geneticGenes) ? count($geneticGenes) : 0,
                'label' => 'Gene',
                'trend' => 'Einträge gepflegt',
                'icon' => '<circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.09A1.65 1.65 0 0 0 11 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.09a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />',
            ],
        ];
    ?>
    <div class="stats-grid">
        <?php foreach ($statCards as $stat): ?>
            <article class="stat-card">
                <span class="stat-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <?= $stat['icon'] ?>
                    </svg>
                </span>
                <span class="stat-card__value"><?= number_format((int) $stat['value'], 0, ',', '.') ?></span>
                <span class="stat-card__label"><?= htmlspecialchars($stat['label']) ?></span>
                <span class="stat-card__trend"><?= htmlspecialchars($stat['trend']) ?></span>
            </article>
        <?php endforeach; ?>
    </div>

    <section style="margin-top:2.5rem;">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <h2>Letzte Anfragen</h2>
            <p class="text-sm text-slate-400">Direkter Zugriff auf die letzten fünf Kontakte.</p>
        </div>
        <div class="card data-card" style="margin-top:1.25rem;">
            <?php if (empty($inquiries)): ?>
                <p class="text-sm text-slate-300">Aktuell sind keine Anfragen eingegangen.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Tier</th>
                            <th>Name</th>
                            <th>E-Mail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($inquiries, 0, 5) as $inquiry): ?>
                            <tr>
                                <td><?= htmlspecialchars($inquiry['created_at']) ?></td>
                                <td><?= htmlspecialchars($inquiry['listing_title']) ?></td>
                                <td><?= htmlspecialchars($inquiry['sender_name']) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($inquiry['sender_email']) ?>">Kontakt aufnehmen</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
