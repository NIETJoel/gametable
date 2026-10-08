<?php
// FE09 / FE14 / T19 – Rondes van een toernooi bekijken en een nieuwe ronde starten. Toont ook de stand.
require_once __DIR__ . '/../includes/init.php';
vereis_rol('toernooileider');

$toernooi = vereis_toernooi(get_getal('toernooi_id'));

if (is_post()) {
    controleer_csrf();

    if (count(haal_goedgekeurde_deelnemers((int) $toernooi['id'])) < 2) {
        zet_melding('fout', 'Er zijn minimaal 2 goedgekeurde deelnemers nodig om een ronde te starten.');
        doorsturen('beheer/rondes.php?toernooi_id=' . $toernooi['id']);
    }

    // Nieuwe ronde krijgt het volgende nummer
    $stmt = db()->prepare('SELECT COALESCE(MAX(nummer), 0) + 1 FROM rondes WHERE toernooi_id = ?');
    $stmt->execute([$toernooi['id']]);
    $nummer = (int) $stmt->fetchColumn();

    $stmt = db()->prepare('INSERT INTO rondes (toernooi_id, nummer) VALUES (?, ?)');
    $stmt->execute([$toernooi['id'], $nummer]);

    zet_melding('succes', 'Ronde ' . $nummer . ' is aangemaakt. Koppel nu de spelers aan tafels.');
    doorsturen('beheer/ronde.php?id=' . db()->lastInsertId());
}

$rondes = haal_rondes((int) $toernooi['id'], false);
$stand  = bereken_stand($toernooi);

$paginatitel = 'Rondes - ' . $toernooi['naam'];
$actieveTab  = 'rondes';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/beheer_menu.php';
?>
<div class="titel-balk">
    <h2>Rondes</h2>
    <form method="post" class="inline-form">
        <?= csrf_veld() ?>
        <button type="submit" class="knop">+ Nieuwe ronde starten</button>
    </form>
</div>

<?php if (!$rondes): ?>
    <div class="melding melding-info">Er zijn nog geen rondes. Start de eerste ronde.</div>
<?php else: ?>
    <div class="tabel-wrapper">
        <table>
            <thead>
            <tr><th>Ronde</th><th>Wedstrijden</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rondes as $ronde): ?>
                <tr>
                    <td>Ronde <?= (int) $ronde['nummer'] ?></td>
                    <td><?= (int) $ronde['aantal_wedstrijden'] ?></td>
                    <td>
                        <?php if ($ronde['gepubliceerd']): ?>
                            <span class="label label-goedgekeurd">gepubliceerd</span>
                        <?php else: ?>
                            <span class="label label-aangemeld">concept</span>
                        <?php endif; ?>
                    </td>
                    <td><a href="<?= e(url('beheer/ronde.php?id=' . $ronde['id'])) ?>">Openen</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h2>Stand</h2>
<p>Puntensysteem: winst <?= (int) $toernooi['punten_winst'] ?>, gelijk <?= (int) $toernooi['punten_gelijk'] ?>, verlies <?= (int) $toernooi['punten_verlies'] ?>.</p>
<?php require __DIR__ . '/../includes/stand_tabel.php'; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
