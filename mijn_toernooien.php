<?php
// Mijn toernooien: de toernooien waarvoor de ingelogde speler is ingeschreven.
require_once __DIR__ . '/includes/init.php';
vereis_rol('speler');

$stmt = db()->prepare(
    'SELECT t.id, t.naam, t.spel, t.datum, i.status, i.startpositie
     FROM inschrijvingen i
     JOIN toernooien t ON t.id = i.toernooi_id
     WHERE i.gebruiker_id = ?
     ORDER BY t.datum'
);
$stmt->execute([huidige_gebruiker()['id']]);
$inschrijvingen = $stmt->fetchAll();

$paginatitel = 'Mijn toernooien';
require __DIR__ . '/includes/header.php';
?>
<h1>Mijn toernooien</h1>

<?php if (!$inschrijvingen): ?>
    <div class="melding melding-info">
        Je bent nog niet ingeschreven voor een toernooi.
        <a href="<?= e(url('index.php')) ?>">Bekijk de open toernooien</a>.
    </div>
<?php else: ?>
    <div class="tabel-wrapper">
        <table>
            <thead>
            <tr><th>Toernooi</th><th>Spel</th><th>Datum</th><th>Status</th><th>Startpositie</th></tr>
            </thead>
            <tbody>
            <?php foreach ($inschrijvingen as $inschrijving): ?>
                <tr>
                    <td><a href="<?= e(url('toernooi.php?id=' . $inschrijving['id'])) ?>"><?= e($inschrijving['naam']) ?></a></td>
                    <td><?= e($inschrijving['spel']) ?></td>
                    <td><?= e(toon_datum($inschrijving['datum'])) ?></td>
                    <td><span class="label label-<?= e($inschrijving['status']) ?>"><?= e($inschrijving['status']) ?></span></td>
                    <td><?= $inschrijving['startpositie'] === null ? '-' : (int) $inschrijving['startpositie'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
