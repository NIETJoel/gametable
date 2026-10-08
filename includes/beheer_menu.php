<?php
// Submenu bovenaan de beheerpagina's van één toernooi.
// Verwacht $toernooi en $actieveTab ('deelnemers' of 'rondes').
$tabs = [
    'deelnemers' => ['Deelnemers', 'beheer/deelnemers.php?toernooi_id=' . $toernooi['id']],
    'rondes'     => ['Rondes', 'beheer/rondes.php?toernooi_id=' . $toernooi['id']],
];
?>
<p class="kruimelpad"><a href="<?= e(url('beheer/toernooien.php')) ?>">&larr; Alle toernooien</a></p>
<h1><?= e($toernooi['naam']) ?> <small>(<?= e(toon_datum($toernooi['datum'])) ?>)</small></h1>
<nav class="tabs" aria-label="Toernooi beheren">
    <?php foreach ($tabs as $sleutel => [$titel, $pad]): ?>
        <a href="<?= e(url($pad)) ?>" class="<?= $sleutel === $actieveTab ? 'actief' : '' ?>"><?= e($titel) ?></a>
    <?php endforeach; ?>
    <a href="<?= e(url('beheer/toernooi_formulier.php?id=' . $toernooi['id'])) ?>">Wijzigen</a>
    <a href="<?= e(url('toernooi.php?id=' . $toernooi['id'])) ?>">Bekijk als speler</a>
</nav>
