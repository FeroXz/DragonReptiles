<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <div class="horizon-shell">
        <div class="horizon-shell__header">
            <div class="horizon-shell__intro">
                <span class="badge">ArminDashboard · HorizonUI 3.0</span>
                <h1>Kontrollzentrum</h1>
                <p>
                    Willkommen im neuen Horizon-Dashboard. Steuere Tierbestand, Adoptionen und Inhalte jetzt in einem
                    dunklen, klaren Interface – inspiriert von Nuxt UI 4.1 Komponenten und optimiert für fokussiertes Arbeiten.
                </p>
                <div class="stack" style="margin-top: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                    <a href="<?= BASE_URL ?>/index.php?route=admin/animals" class="btn">Tier hinzufügen</a>
                    <a href="<?= BASE_URL ?>/index.php?route=admin/adoption" class="btn-secondary">Neue Abgabe planen</a>
                </div>
            </div>
            <div class="horizon-shell__spotlight">
                <h3>Aktueller Überblick</h3>
                <strong><?= count($animals) + count($listings) + count($pages) ?></strong>
                <span>Gesamtobjekte aus Tierbestand, Abgabe-Board und Inhaltsseiten.</span>
                <div class="badge" style="justify-self: start;">Version <?= htmlspecialchars(APP_VERSION) ?></div>
            </div>
        </div>

        <?php include __DIR__ . '/nav.php'; ?>

        <div class="horizon-stat-grid">
            <article class="horizon-stat-card">
                <div class="horizon-stat-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 9.75l7.5-6 7.5 6V18a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 18V9.75z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 22.5V12h6v10.5" />
                    </svg>
                </div>
                <div>
                    <p class="horizon-stat-card__label">Aktive Tiere</p>
                    <p class="horizon-stat-card__value"><?= count($animals) ?></p>
                    <p class="horizon-stat-card__hint">Tiere im Bestand</p>
                </div>
            </article>
            <article class="horizon-stat-card">
                <div class="horizon-stat-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.25l9-6 9 6v9a3 3 0 01-3 3H6a3 3 0 01-3-3v-9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 21.75V12h6v9.75" />
                    </svg>
                </div>
                <div>
                    <p class="horizon-stat-card__label">Abgabe-Einträge</p>
                    <p class="horizon-stat-card__value"><?= count($listings) ?></p>
                    <p class="horizon-stat-card__hint">Inserate aktiv</p>
                </div>
            </article>
            <article class="horizon-stat-card">
                <div class="horizon-stat-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-9-9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35" />
                    </svg>
                </div>
                <div>
                    <p class="horizon-stat-card__label">Neue Anfragen</p>
                    <p class="horizon-stat-card__value"><?= count($inquiries) ?></p>
                    <p class="horizon-stat-card__hint">offene Kontakte</p>
                </div>
            </article>
            <article class="horizon-stat-card">
                <div class="horizon-stat-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25A2.25 2.25 0 016 3h8.25L20.25 9v9.75A2.25 2.25 0 0118 21H6a2.25 2.25 0 01-2.25-2.25V5.25z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v6h6" />
                    </svg>
                </div>
                <div>
                    <p class="horizon-stat-card__label">Seiten</p>
                    <p class="horizon-stat-card__value"><?= count($pages) ?></p>
                    <p class="horizon-stat-card__hint">CMS-Inhalte</p>
                </div>
            </article>
            <article class="horizon-stat-card">
                <div class="horizon-stat-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h12" />
                        <circle cx="12" cy="12" r="9" />
                    </svg>
                </div>
                <div>
                    <p class="horizon-stat-card__label">News</p>
                    <p class="horizon-stat-card__value"><?= count($newsPosts) ?></p>
                    <p class="horizon-stat-card__hint">Story-Updates</p>
                </div>
            </article>
            <article class="horizon-stat-card">
                <div class="horizon-stat-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a3.75 3.75 0 013.75-3.75h7.5A3.75 3.75 0 0119.5 12v3.75A3.75 3.75 0 0115.75 19.5h-7.5A3.75 3.75 0 014.5 15.75V12z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6A3.75 3.75 0 0112 2.25 3.75 3.75 0 0115.75 6v1.5" />
                    </svg>
                </div>
                <div>
                    <p class="horizon-stat-card__label">Zuchtpläne</p>
                    <p class="horizon-stat-card__value"><?= count($breedingPlans) ?></p>
                    <p class="horizon-stat-card__hint">laufende Projekte</p>
                </div>
            </article>
            <article class="horizon-stat-card">
                <div class="horizon-stat-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 6.75h9a3.75 3.75 0 010 7.5h-9a3.75 3.75 0 010-7.5z" />
                    </svg>
                </div>
                <div>
                    <p class="horizon-stat-card__label">Genetik Arten</p>
                    <p class="horizon-stat-card__value"><?= isset($geneticSpecies) ? count($geneticSpecies) : 0 ?></p>
                    <p class="horizon-stat-card__hint">Taxonomien</p>
                </div>
            </article>
            <article class="horizon-stat-card">
                <div class="horizon-stat-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 21a7.5 7.5 0 0115 0" />
                    </svg>
                </div>
                <div>
                    <p class="horizon-stat-card__label">Genetik Gene</p>
                    <p class="horizon-stat-card__value"><?= isset($geneticGenes) ? count($geneticGenes) : 0 ?></p>
                    <p class="horizon-stat-card__hint">kombinierbare Marker</p>
                </div>
            </article>
        </div>

        <div class="horizon-panel">
            <h2>Letzte Anfragen</h2>
            <?php if (empty($inquiries)): ?>
                <div class="horizon-empty">Noch keine Kontaktanfragen eingegangen. Teile deine Inserate, um Interesse zu wecken!</div>
            <?php else: ?>
                <div class="table-responsive">
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
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($inquiry['sender_email']) ?>">
                                            <?= htmlspecialchars($inquiry['sender_email']) ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
