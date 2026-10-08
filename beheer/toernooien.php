<?php
// Beheer: overzicht van alle toernooien (FE06 / T17). Alleen voor de toernooileider.
require_once __DIR__ . '/../includes/init.php';
vereis_rol('toernooileider');

$toernooien = db()->query(
    'SELECT t.*,
            (SELECT COUNT(*) FROM inschrijvingen i WHERE i.toernooi_id = t.id) AS aantal_inschrijvingen,
            (SELECT COUNT(*) FROM inschrijvingen i WHERE i.toernooi_id = t.id AND i.status = \'aangemeld\') AS aantal_wachtend
     FROM toernooien t
     ORDER BY t.datum DESC'
)->fetchAll();

$paginatitel = 'Beheer toernooien';
require __DIR__ . '/../includes/header.php';
?>
<div class="titel-balk">
    <h1>Toernooien beheren</h1>
    <a class="knop" href="<?= e(url('beheer/toernooi_formulier.php')) ?>">+ Nieuw toernooi</a>
</div>

<?php if (!$toernooien): ?>
    <div class="melding melding-info">Er zijn nog geen toernooien. Maak een nieuw toernooi aan.</div>
<?php else: ?>
    <div class="tabel-wrapper">
        <table>
            <thead>
            <tr><th>Naam</th><th>Spel</th><th>Datum</th><th>Inschrijvingen</th><th>Status</th><th>Acties</th></tr>
            </thead>
            <tbody>
            <?php foreach ($toernooien as $toernooi): ?>
                <tr>
                    <td><?= e($toernooi['naam']) ?></td>
                    <td><?= e($toernooi['spel']) ?></td>
                    <td><?= e(toon_datum($toernooi['datum'])) ?></td>
                    <td>
                        <?= (int) $toernooi['aantal_inschrijvingen'] ?> / <?= (int) $toernooi['capaciteit'] ?>
                        <?php if ($toernooi['aantal_wachtend'] > 0): ?>
                            <span class="label label-aangemeld"><?= (int) $toernooi['aantal_wachtend'] ?> wachtend</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($toernooi['status']) ?></td>
                    <td class="acties">
                        <a href="<?= e(url('beheer/deelnemers.php?toernooi_id=' . $toernooi['id'])) ?>">Deelnemers</a>
                        <a href="<?= e(url('beheer/rondes.php?toernooi_id=' . $toernooi['id'])) ?>">Rondes</a>
                        <a href="<?= e(url('beheer/toernooi_formulier.php?id=' . $toernooi['id'])) ?>">Wijzigen</a>
                        <form method="post" action="<?= e(url('beheer/toernooi_verwijderen.php')) ?>" class="inline-form"
                              onsubmit="return confirm('Weet je zeker dat je dit toernooi met alle inschrijvingen, rondes en uitslagen wilt verwijderen?');">
                            <?= csrf_veld() ?>
                            <input type="hidden" name="id" value="<?= (int) $toernooi['id'] ?>">
                            <button type="submit" class="link-knop link-gevaar">Verwijderen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
