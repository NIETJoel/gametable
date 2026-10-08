<?php
// FE07 / FE08 / T18 – Deelnemers bekijken, goedkeuren en een startpositie geven.
require_once __DIR__ . '/../includes/init.php';
vereis_rol('toernooileider');

$toernooi = vereis_toernooi(get_getal('toernooi_id'));
$pad      = 'beheer/deelnemers.php?toernooi_id=' . $toernooi['id'];

if (is_post()) {
    controleer_csrf();
    $inschrijvingId = post_getal('inschrijving_id');
    $actie          = post_tekst('actie');

    // De inschrijving moet bij DIT toernooi horen, anders kan iemand een ander id meesturen.
    $stmt = db()->prepare('SELECT id, status FROM inschrijvingen WHERE id = ? AND toernooi_id = ?');
    $stmt->execute([$inschrijvingId, $toernooi['id']]);
    $inschrijving = $stmt->fetch();

    if (!$inschrijving) {
        zet_melding('fout', 'Deze inschrijving hoort niet bij dit toernooi.');
    } elseif ($actie === 'goedkeuren') {
        $stmt = db()->prepare("UPDATE inschrijvingen SET status = 'goedgekeurd' WHERE id = ?");
        $stmt->execute([$inschrijving['id']]);
        zet_melding('succes', 'De deelnemer is goedgekeurd.');
    } elseif ($actie === 'startpositie') {
        $startpositie = post_getal('startpositie');

        if ($inschrijving['status'] !== 'goedgekeurd') {
            zet_melding('fout', 'Keur de deelnemer eerst goed voordat je een startpositie instelt.');
        } elseif ($startpositie === null || $startpositie < 1 || $startpositie > $toernooi['capaciteit']) {
            zet_melding('fout', 'De startpositie moet een getal van 1 tot en met ' . $toernooi['capaciteit'] . ' zijn.');
        } else {
            try {
                $stmt = db()->prepare('UPDATE inschrijvingen SET startpositie = ? WHERE id = ?');
                $stmt->execute([$startpositie, $inschrijving['id']]);
                zet_melding('succes', 'De startpositie is opgeslagen.');
            } catch (PDOException $fout) {
                if (!is_dubbele_waarde($fout)) {
                    throw $fout;
                }
                // UNIQUE (toernooi_id, startpositie)
                zet_melding('fout', 'Startpositie ' . $startpositie . ' is al aan een andere deelnemer gegeven.');
            }
        }
    } else {
        zet_melding('fout', 'Onbekende actie.');
    }

    doorsturen($pad); // na een POST altijd doorsturen, zodat verversen de actie niet herhaalt
}

$stmt = db()->prepare(
    'SELECT i.id, i.status, i.startpositie, i.ingeschreven_op, g.spelersnaam
     FROM inschrijvingen i
     JOIN gebruikers g ON g.id = i.gebruiker_id
     WHERE i.toernooi_id = ?
     ORDER BY i.status, i.startpositie IS NULL, i.startpositie, i.ingeschreven_op'
);
$stmt->execute([$toernooi['id']]);
$inschrijvingen = $stmt->fetchAll();

$paginatitel = 'Deelnemers - ' . $toernooi['naam'];
$actieveTab  = 'deelnemers';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/beheer_menu.php';
?>
<h2>Deelnemers (<?= count($inschrijvingen) ?> / <?= (int) $toernooi['capaciteit'] ?>)</h2>

<?php if (!$inschrijvingen): ?>
    <div class="melding melding-info">Er zijn nog geen inschrijvingen voor dit toernooi.</div>
<?php else: ?>
    <div class="tabel-wrapper">
        <table>
            <thead>
            <tr><th>Spelersnaam</th><th>Ingeschreven op</th><th>Status</th><th>Startpositie</th></tr>
            </thead>
            <tbody>
            <?php foreach ($inschrijvingen as $inschrijving): ?>
                <tr>
                    <td><?= e($inschrijving['spelersnaam']) ?></td>
                    <td><?= e(date('d-m-Y H:i', strtotime($inschrijving['ingeschreven_op']))) ?></td>
                    <td>
                        <span class="label label-<?= e($inschrijving['status']) ?>"><?= e($inschrijving['status']) ?></span>
                        <?php if ($inschrijving['status'] === 'aangemeld'): ?>
                            <form method="post" class="inline-form">
                                <?= csrf_veld() ?>
                                <input type="hidden" name="inschrijving_id" value="<?= (int) $inschrijving['id'] ?>">
                                <input type="hidden" name="actie" value="goedkeuren">
                                <button type="submit" class="knop knop-klein">Goedkeuren</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($inschrijving['status'] === 'goedgekeurd'): ?>
                            <form method="post" class="inline-form">
                                <?= csrf_veld() ?>
                                <input type="hidden" name="inschrijving_id" value="<?= (int) $inschrijving['id'] ?>">
                                <input type="hidden" name="actie" value="startpositie">
                                <label class="verborgen" for="startpositie-<?= (int) $inschrijving['id'] ?>">Startpositie</label>
                                <input type="number" id="startpositie-<?= (int) $inschrijving['id'] ?>" name="startpositie"
                                       class="klein-veld" min="1" max="<?= (int) $toernooi['capaciteit'] ?>"
                                       value="<?= e($inschrijving['startpositie']) ?>">
                                <button type="submit" class="knop knop-klein">Opslaan</button>
                            </form>
                        <?php else: ?>
                            <span class="leeg">Eerst goedkeuren</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
