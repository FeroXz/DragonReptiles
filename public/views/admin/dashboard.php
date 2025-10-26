<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
<h1>Admin-Dashboard</h1>
<?php include __DIR__ . '/nav.php'; ?>
<div class="grid cards">
    <div class="card">
        <h3>Aktive Tiere</h3>
        <p><?= count($animals) ?> Datensätze</p>
    </div>
    <div class="card">
        <h3>Abgabe-Einträge</h3>
        <p><?= count($listings) ?> Inserate</p>
    </div>
    <div class="card">
        <h3>Neue Anfragen</h3>
        <p><?= count($inquiries) ?> Nachrichten</p>
    </div>
    <div class="card">
        <h3>Seiten</h3>
        <p><?= count($pages) ?> Einträge</p>
    </div>
    <div class="card">
        <h3>News</h3>
        <p><?= count($newsPosts) ?> Beiträge</p>
    </div>
    <div class="card">
        <h3>Zuchtpläne</h3>
        <p><?= count($breedingPlans) ?> Projekte</p>
    </div>
    <div class="card">
        <h3>Pflegeartikel</h3>
        <p><?= count($careArticles) ?> Artikel</p>
    </div>
    <div class="card">
        <h3>Genetische Arten</h3>
        <p><?= isset($geneticSpecies) ? count($geneticSpecies) : 0 ?> Datensätze</p>
    </div>
    <div class="card">
        <h3>Gene</h3>
        <p><?= isset($geneticGenes) ? count($geneticGenes) : 0 ?> Einträge</p>
    </div>
</div>

<section style="margin-top:2rem;">
    <h2>Letzte Anfragen</h2>
    <div class="card">
        <?php if (empty($inquiries)): ?>
            Keine Anfragen vorhanden.
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
