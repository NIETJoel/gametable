<?php
// Toernooipagina: informatie, inschrijven (FE04/FE05), deelnemers,
// gepubliceerde rondes (FE12) en de stand (FE14).
require_once __DIR__ . '/includes/init.php';

$toernooi  = vereis_toernooi(get_getal('id'));
$gebruiker = huidige_gebruiker();
$vrij      = vrije_plaatsen($toernooi);

// Is de ingelogde speler al ingeschreven?
$mijnInschrijving = null;
if ($gebruiker !== null) {
    $stmt = db()->prepare('SELECT status, startpositie FROM inschrijvingen WHERE toernooi_id = ? AND gebruiker_id = ?');
    $stmt->execute([$toernooi['id'], $gebruiker['id']]);
    $mijnInschrijving = $stmt->fetch() ?: null;
}

$deelnemers = haal_goedgekeurde_deelnemers((int) $toernooi['id']);
$rondes     = haal_rondes((int) $toernooi['id'], true); // FE12: alleen gepubliceerde rondes
$stand      = bereken_stand($toernooi);

$paginatitel = $toernooi['naam'];
require __DIR__ . '/includes/header.php';
?>
<h1><?= e($toernooi['naam']) ?></h1>

<dl class="gegevens">
    <dt>Spel</dt>
    <dd><?= e($toernooi['spel']) ?></dd>
    <dt>Datum</dt>
    <dd><?= e(toon_datum($toernooi['datum'])) ?></dd>
    <dt>Plaatsen</dt>
    <dd><?= (int) $toernooi['aantal_inschrijvingen'] ?> van <?= (int) $toernooi['capaciteit'] ?> bezet</dd>
    <dt>Puntensysteem</dt>
    <dd>Winst <?= (int) $toernooi['punten_winst'] ?>, gelijk <?= (int) $toernooi['punten_gelijk'] ?>, verlies <?= (int) $toernooi['punten_verlies'] ?></dd>
    <dt>Status</dt>
    <dd><?= e(ucfirst($toernooi['status'])) ?></dd>
</dl>

<section class="blok">
    <h2>Inschrijven</h2>
    <?php if ($gebruiker !== null && $gebruiker['rol'] === 'toernooileider'): ?>
        <p>Je bent toernooileider. <a href="<?= e(url('beheer/deelnemers.php?toernooi_id=' . $toernooi['id'])) ?>">Beheer dit toernooi</a>.</p>
    <?php elseif ($mijnInschrijving !== null): ?>
        <div class="melding melding-succes">
            Je bent ingeschreven. Status: <strong><?= e($mijnInschrijving['status']) ?></strong>
            <?php if ($mijnInschrijving['startpositie'] !== null): ?>
                &ndash; startpositie <?= (int) $mijnInschrijving['startpositie'] ?>
            <?php endif; ?>
        </div>
    <?php elseif (!is_inschrijving_open($toernooi)): ?>
        <div class="melding melding-info">De inschrijving voor dit toernooi is gesloten.</div>
    <?php elseif ($vrij === 0): ?>
        <div class="melding melding-fout">Dit toernooi is vol. Je kunt je niet meer inschrijven.</div>
        <button class="knop" disabled>Toernooi is vol</button>
    <?php elseif ($gebruiker === null): ?>
        <p>Nog <?= $vrij ?> plaats(en) vrij. <a href="<?= e(url('inloggen.php')) ?>">Log in</a> of
            <a href="<?= e(url('registreren.php')) ?>">maak een account</a> om je in te schrijven.</p>
    <?php else: ?>
        <p>Nog <?= $vrij ?> plaats(en) vrij.</p>
        <form method="post" action="<?= e(url('inschrijven.php')) ?>">
            <?= csrf_veld() ?>
            <input type="hidden" name="toernooi_id" value="<?= (int) $toernooi['id'] ?>">
            <button type="submit" class="knop">Inschrijven</button>
        </form>
    <?php endif; ?>
</section>

<section class="blok">
    <h2>Deelnemers</h2>
    <?php if (!$deelnemers): ?>
        <p class="leeg">Er zijn nog geen goedgekeurde deelnemers.</p>
    <?php else: ?>
        <ol class="deelnemers">
            <?php foreach ($deelnemers as $deelnemer): ?>
                <li><?= e($deelnemer['spelersnaam']) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>

<section class="blok">
    <h2>Rondes</h2>
    <?php if (!$rondes): ?>
        <p class="leeg">Er zijn nog geen rondes gepubliceerd.</p>
    <?php endif; ?>
    <?php foreach ($rondes as $ronde): ?>
        <h3>Ronde <?= (int) $ronde['nummer'] ?></h3>
        <div class="tabel-wrapper">
            <table>
                <thead>
                <tr><th>Tafel</th><th>Speler 1</th><th>Speler 2</th><th>Uitslag</th></tr>
                </thead>
                <tbody>
                <?php foreach (haal_wedstrijden((int) $ronde['id']) as $wedstrijd): ?>
                    <?php $isMijnWedstrijd = $gebruiker !== null && in_array($gebruiker['id'], [$wedstrijd['speler1_id'], $wedstrijd['speler2_id']]); ?>
                    <tr class="<?= $isMijnWedstrijd ? 'mijn-wedstrijd' : '' ?>">
                        <td><?= (int) $wedstrijd['tafelnummer'] ?></td>
                        <td><?= e($wedstrijd['speler1_naam']) ?></td>
                        <td><?= e($wedstrijd['speler2_naam']) ?></td>
                        <td><?= e(uitslag_tekst($wedstrijd)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</section>

<section class="blok">
    <h2>Stand</h2>
    <?php require __DIR__ . '/includes/stand_tabel.php'; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
