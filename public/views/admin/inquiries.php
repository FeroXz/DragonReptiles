<?php include __DIR__ . '/../partials/header.php'; ?>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
<h1>Anfragen</h1>
<?php include __DIR__ . '/nav.php'; ?>
<div class="card">
    <?php if (empty($inquiries)): ?>
        Keine Nachrichten vorhanden.
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Inserat</th>
                    <th>Name</th>
                    <th>E-Mail</th>
                    <th>Nachricht</th>
                    <th>Antwort</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inquiries as $inquiry): ?>
                    <tr>
                        <td><?= htmlspecialchars($inquiry['created_at']) ?></td>
                        <td><?= htmlspecialchars($inquiry['listing_title']) ?></td>
                        <td><?= htmlspecialchars($inquiry['sender_name']) ?></td>
                        <td><a href="mailto:<?= htmlspecialchars($inquiry['sender_email']) ?>">Mail</a></td>
                        <td><?= nl2br(htmlspecialchars($inquiry['message'])) ?></td>
                        <td><a class="btn btn-secondary" href="mailto:<?= htmlspecialchars($inquiry['sender_email']) ?>?subject=Re:%20<?= urlencode($inquiry['listing_title']) ?>">Antworten</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
