<?php
// FE06 / T17 – Toernooi aanmaken of wijzigen, met capaciteit en puntensysteem.
require_once __DIR__ . '/../includes/init.php';
vereis_rol('toernooileider');

// Met ?id=... wijzigen we een bestaand toernooi, zonder id maken we een nieuw toernooi.
$id       = get_getal('id');
$toernooi = $id !== null ? vereis_toernooi($id) : null;

// Standaardwaarden voor een nieuw toernooi
$waarden = $toernooi ?? [
    'naam' => '', 'spel' => '', 'datum' => '', 'capaciteit' => 8,
    'punten_winst' => 3, 'punten_gelijk' => 1, 'punten_verlies' => 0, 'status' => 'open',
];
$fouten = [];

if (is_post()) {
    controleer_csrf();

    $waarden = [
        'naam'           => post_tekst('naam'),
        'spel'           => post_tekst('spel'),
        'datum'          => post_tekst('datum'),
        'capaciteit'     => post_getal('capaciteit'),
        'punten_winst'   => post_getal('punten_winst'),
        'punten_gelijk'  => post_getal('punten_gelijk'),
        'punten_verlies' => post_getal('punten_verlies'),
        'status'         => post_tekst('status'),
    ];

    // Invoer controleren (TE-04)
    if (mb_strlen($waarden['naam']) < 3 || mb_strlen($waarden['naam']) > 100) {
        $fouten['naam'] = 'De naam moet 3 tot 100 tekens zijn.';
    }
    if (mb_strlen($waarden['spel']) < 2 || mb_strlen($waarden['spel']) > 100) {
        $fouten['spel'] = 'Het spel moet 2 tot 100 tekens zijn.';
    }
    if (!is_geldige_datum($waarden['datum'])) {
        $fouten['datum'] = 'Vul een geldige datum in.';
    } elseif ($toernooi === null && $waarden['datum'] < date('Y-m-d')) {
        $fouten['datum'] = 'Een nieuw toernooi kan niet in het verleden liggen.';
    }
    if ($waarden['capaciteit'] === null || $waarden['capaciteit'] < 2 || $waarden['capaciteit'] > 256) {
        $fouten['capaciteit'] = 'De capaciteit moet een getal van 2 tot en met 256 zijn.';
    } elseif ($toernooi !== null && $waarden['capaciteit'] < $toernooi['aantal_inschrijvingen']) {
        $fouten['capaciteit'] = 'De capaciteit kan niet lager zijn dan het aantal inschrijvingen (' . $toernooi['aantal_inschrijvingen'] . ').';
    }
    foreach (['punten_winst', 'punten_gelijk', 'punten_verlies'] as $veld) {
        if ($waarden[$veld] === null || $waarden[$veld] < 0 || $waarden[$veld] > 10) {
            $fouten['punten'] = 'De punten moeten getallen van 0 tot en met 10 zijn.';
        }
    }
    if (!isset($fouten['punten'])
        && !($waarden['punten_winst'] >= $waarden['punten_gelijk'] && $waarden['punten_gelijk'] >= $waarden['punten_verlies'])) {
        $fouten['punten'] = 'Winst moet minstens zoveel punten geven als gelijk, en gelijk minstens zoveel als verlies.';
    }
    if (!in_array($waarden['status'], ['open', 'gesloten'], true)) {
        $fouten['status'] = 'Kies een geldige status.';
    }

    if (!$fouten) {
        $parameters = [
            $waarden['naam'], $waarden['spel'], $waarden['datum'], $waarden['capaciteit'],
            $waarden['punten_winst'], $waarden['punten_gelijk'], $waarden['punten_verlies'], $waarden['status'],
        ];

        if ($toernooi === null) {
            $parameters[] = huidige_gebruiker()['id'];
            $stmt = db()->prepare(
                'INSERT INTO toernooien (naam, spel, datum, capaciteit, punten_winst, punten_gelijk, punten_verlies, status, aangemaakt_door)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute($parameters);
            zet_melding('succes', 'Het toernooi is aangemaakt.');
        } else {
            $parameters[] = $toernooi['id'];
            $stmt = db()->prepare(
                'UPDATE toernooien
                 SET naam = ?, spel = ?, datum = ?, capaciteit = ?, punten_winst = ?, punten_gelijk = ?, punten_verlies = ?, status = ?
                 WHERE id = ?'
            );
            $stmt->execute($parameters);
            zet_melding('succes', 'Het toernooi is gewijzigd.');
        }
        doorsturen('beheer/toernooien.php');
    }
}

$paginatitel = $toernooi ? 'Toernooi wijzigen' : 'Nieuw toernooi';
require __DIR__ . '/../includes/header.php';
?>
<h1><?= e($paginatitel) ?></h1>

<?php if ($fouten): ?>
    <div class="melding melding-fout" role="alert">Het toernooi is niet opgeslagen. Verbeter de velden hieronder.</div>
<?php endif; ?>

<form method="post" class="formulier">
    <?= csrf_veld() ?>

    <label for="naam">Naam toernooi</label>
    <input type="text" id="naam" name="naam" maxlength="100" required value="<?= e($waarden['naam']) ?>">
    <?php toon_veldfout($fouten, 'naam'); ?>

    <label for="spel">Spel</label>
    <input type="text" id="spel" name="spel" maxlength="100" required value="<?= e($waarden['spel']) ?>">
    <?php toon_veldfout($fouten, 'spel'); ?>

    <label for="datum">Datum</label>
    <input type="date" id="datum" name="datum" required value="<?= e($waarden['datum']) ?>">
    <?php toon_veldfout($fouten, 'datum'); ?>

    <label for="capaciteit">Capaciteit (maximaal aantal deelnemers)</label>
    <input type="number" id="capaciteit" name="capaciteit" min="2" max="256" required value="<?= e($waarden['capaciteit']) ?>">
    <?php toon_veldfout($fouten, 'capaciteit'); ?>

    <fieldset>
        <legend>Puntensysteem</legend>
        <div class="punten-velden">
            <div>
                <label for="punten_winst">Winst</label>
                <input type="number" id="punten_winst" name="punten_winst" min="0" max="10" required value="<?= e($waarden['punten_winst']) ?>">
            </div>
            <div>
                <label for="punten_gelijk">Gelijk</label>
                <input type="number" id="punten_gelijk" name="punten_gelijk" min="0" max="10" required value="<?= e($waarden['punten_gelijk']) ?>">
            </div>
            <div>
                <label for="punten_verlies">Verlies</label>
                <input type="number" id="punten_verlies" name="punten_verlies" min="0" max="10" required value="<?= e($waarden['punten_verlies']) ?>">
            </div>
        </div>
        <?php toon_veldfout($fouten, 'punten'); ?>
    </fieldset>

    <label for="status">Status inschrijving</label>
    <select id="status" name="status">
        <option value="open" <?= $waarden['status'] === 'open' ? 'selected' : '' ?>>Open</option>
        <option value="gesloten" <?= $waarden['status'] === 'gesloten' ? 'selected' : '' ?>>Gesloten</option>
    </select>
    <?php toon_veldfout($fouten, 'status'); ?>

    <div class="knoppen">
        <button type="submit" class="knop">Opslaan</button>
        <a class="knop knop-licht" href="<?= e(url('beheer/toernooien.php')) ?>">Annuleren</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
