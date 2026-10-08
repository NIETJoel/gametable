<?php
// Eén ronde beheren:
// - spelers aan elkaar en aan een tafel koppelen (FE09 / T20)
// - dubbele indeling in dezelfde ronde blokkeren (FE10 / TE-03 / T21)
// - ronde publiceren (FE11 / T22)
// - uitslagen invoeren en wijzigen (FE13 / T23)
require_once __DIR__ . '/../includes/init.php';
vereis_rol('toernooileider');

$stmt = db()->prepare('SELECT * FROM rondes WHERE id = ?');
$stmt->execute([get_getal('id')]);
$ronde = $stmt->fetch();
if (!$ronde) {
    stop_met_fout(404, 'Deze ronde bestaat niet (meer).');
}
$toernooi = vereis_toernooi((int) $ronde['toernooi_id']);
$pad      = 'beheer/ronde.php?id=' . $ronde['id'];

if (is_post()) {
    controleer_csrf();
    $actie = post_tekst('actie');

    if ($actie === 'koppelen') {
        koppel_spelers($ronde, $toernooi);
    } elseif ($actie === 'verwijderen') {
        verwijder_wedstrijd($ronde);
    } elseif ($actie === 'publiceren') {
        publiceer_ronde($ronde);
    } elseif ($actie === 'uitslag') {
        sla_uitslag_op($ronde);
    } else {
        zet_melding('fout', 'Onbekende actie.');
    }

    doorsturen($pad);
}

// ---------- Acties ----------

function koppel_spelers(array $ronde, array $toernooi): void
{
    if ($ronde['gepubliceerd']) {
        zet_melding('fout', 'Deze ronde is al gepubliceerd. De indeling kan niet meer worden aangepast.');
        return;
    }

    $tafelnummer = post_getal('tafelnummer');
    $speler1     = post_getal('speler1_id');
    $speler2     = post_getal('speler2_id');

    if ($tafelnummer === null || $tafelnummer < 1 || $tafelnummer > 999) {
        zet_melding('fout', 'Vul een geldig tafelnummer in (1 tot en met 999).');
        return;
    }
    if ($speler1 === null || $speler2 === null) {
        zet_melding('fout', 'Kies twee spelers.');
        return;
    }
    if ($speler1 === $speler2) {
        zet_melding('fout', 'Een speler kan niet tegen zichzelf spelen. Kies twee verschillende spelers.');
        return;
    }

    // Beide spelers moeten goedgekeurde deelnemers van dit toernooi zijn
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM inschrijvingen
         WHERE toernooi_id = ? AND status = 'goedgekeurd' AND gebruiker_id IN (?, ?)"
    );
    $stmt->execute([$toernooi['id'], $speler1, $speler2]);
    if ((int) $stmt->fetchColumn() !== 2) {
        zet_melding('fout', 'Je kunt alleen goedgekeurde deelnemers van dit toernooi koppelen.');
        return;
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO wedstrijden (ronde_id, tafelnummer, speler1_id, speler2_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$ronde['id'], $tafelnummer, $speler1, $speler2]);
        $wedstrijdId = $pdo->lastInsertId();

        // TE-03: de primary key (ronde_id, gebruiker_id) in ronde_spelers weigert
        // een speler die al in deze ronde is ingedeeld.
        $stmt = $pdo->prepare('INSERT INTO ronde_spelers (ronde_id, gebruiker_id, wedstrijd_id) VALUES (?, ?, ?)');
        $stmt->execute([$ronde['id'], $speler1, $wedstrijdId]);
        $stmt->execute([$ronde['id'], $speler2, $wedstrijdId]);

        $pdo->commit();
        zet_melding('succes', 'De spelers zijn gekoppeld aan tafel ' . $tafelnummer . '.');
    } catch (PDOException $fout) {
        $pdo->rollBack(); // niets van deze koppeling wordt opgeslagen
        if (!is_dubbele_waarde($fout)) {
            throw $fout;
        }
        if (str_contains($fout->getMessage(), 'uq_wedstrijd_tafel')) {
            zet_melding('fout', 'Tafel ' . $tafelnummer . ' is in deze ronde al bezet. Kies een andere tafel.');
        } else {
            zet_melding('fout', 'Koppeling geweigerd: een van deze spelers is al ingedeeld in deze ronde.');
        }
    }
}

function verwijder_wedstrijd(array $ronde): void
{
    if ($ronde['gepubliceerd']) {
        zet_melding('fout', 'Deze ronde is al gepubliceerd. Wedstrijden kunnen niet meer worden verwijderd.');
        return;
    }
    // Alleen een wedstrijd uit DEZE ronde. ronde_spelers wordt via ON DELETE CASCADE mee verwijderd.
    $stmt = db()->prepare('DELETE FROM wedstrijden WHERE id = ? AND ronde_id = ?');
    $stmt->execute([post_getal('wedstrijd_id'), $ronde['id']]);

    if ($stmt->rowCount() === 1) {
        zet_melding('succes', 'De wedstrijd is verwijderd.');
    } else {
        zet_melding('fout', 'Deze wedstrijd hoort niet bij deze ronde.');
    }
}

function publiceer_ronde(array $ronde): void
{
    if ($ronde['gepubliceerd']) {
        zet_melding('info', 'Deze ronde is al gepubliceerd.');
        return;
    }
    if (!haal_wedstrijden((int) $ronde['id'])) {
        zet_melding('fout', 'Koppel eerst minimaal één wedstrijd voordat je de ronde publiceert.');
        return;
    }
    $stmt = db()->prepare('UPDATE rondes SET gepubliceerd = 1 WHERE id = ?');
    $stmt->execute([$ronde['id']]);
    zet_melding('succes', 'Ronde ' . $ronde['nummer'] . ' is gepubliceerd. Spelers kunnen de indeling nu zien.');
}

function sla_uitslag_op(array $ronde): void
{
    if (!$ronde['gepubliceerd']) {
        zet_melding('fout', 'Publiceer de ronde eerst. Daarna kun je uitslagen invoeren.');
        return;
    }

    $uitslag = post_tekst('uitslag');
    if (!in_array($uitslag, ['speler1', 'speler2', 'gelijk', ''], true)) {
        zet_melding('fout', 'Kies een geldige uitslag.');
        return;
    }

    // Lege keuze = "nog niet gespeeld" (NULL). Alleen een wedstrijd uit DEZE ronde.
    $stmt = db()->prepare('UPDATE wedstrijden SET uitslag = ? WHERE id = ? AND ronde_id = ?');
    $stmt->execute([$uitslag === '' ? null : $uitslag, post_getal('wedstrijd_id'), $ronde['id']]);

    zet_melding('succes', 'De uitslag is opgeslagen. De stand is bijgewerkt.');
}

// ---------- Gegevens voor het scherm ----------

$wedstrijden = haal_wedstrijden((int) $ronde['id']);
$deelnemers  = haal_goedgekeurde_deelnemers((int) $toernooi['id']);

// Welke spelers zitten al in deze ronde? (alleen voor de weergave in de keuzelijst)
$stmt = db()->prepare('SELECT gebruiker_id FROM ronde_spelers WHERE ronde_id = ?');
$stmt->execute([$ronde['id']]);
$alIngedeeld = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Voorstel voor het volgende vrije tafelnummer
$volgendeTafel = $wedstrijden ? max(array_column($wedstrijden, 'tafelnummer')) + 1 : 1;

$paginatitel = 'Ronde ' . $ronde['nummer'] . ' - ' . $toernooi['naam'];
$actieveTab  = 'rondes';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/beheer_menu.php';
?>
<div class="titel-balk">
    <h2>
        Ronde <?= (int) $ronde['nummer'] ?>
        <?php if ($ronde['gepubliceerd']): ?>
            <span class="label label-goedgekeurd">gepubliceerd</span>
        <?php else: ?>
            <span class="label label-aangemeld">concept &ndash; nog niet zichtbaar voor spelers</span>
        <?php endif; ?>
    </h2>
    <?php if (!$ronde['gepubliceerd']): ?>
        <form method="post" class="inline-form"
              onsubmit="return confirm('Na publiceren kun je de indeling niet meer wijzigen. Doorgaan?');">
            <?= csrf_veld() ?>
            <input type="hidden" name="actie" value="publiceren">
            <button type="submit" class="knop">Ronde publiceren</button>
        </form>
    <?php endif; ?>
</div>

<?php if (!$ronde['gepubliceerd']): ?>
    <section class="blok">
        <h3>Spelers koppelen</h3>
        <?php if (count($deelnemers) < 2): ?>
            <div class="melding melding-info">Er zijn minimaal 2 goedgekeurde deelnemers nodig.</div>
        <?php else: ?>
            <form method="post" class="koppel-formulier">
                <?= csrf_veld() ?>
                <input type="hidden" name="actie" value="koppelen">
                <div>
                    <label for="tafelnummer">Tafel</label>
                    <input type="number" id="tafelnummer" name="tafelnummer" min="1" max="999" required value="<?= (int) $volgendeTafel ?>">
                </div>
                <?php foreach (['speler1_id' => 'Speler 1', 'speler2_id' => 'Speler 2'] as $veld => $label): ?>
                    <div>
                        <label for="<?= $veld ?>"><?= $label ?></label>
                        <select id="<?= $veld ?>" name="<?= $veld ?>" required>
                            <option value="">Kies een speler</option>
                            <?php foreach ($deelnemers as $deelnemer): ?>
                                <option value="<?= (int) $deelnemer['id'] ?>">
                                    <?= $deelnemer['startpositie'] !== null ? '#' . (int) $deelnemer['startpositie'] . ' ' : '' ?><?= e($deelnemer['spelersnaam']) ?>
                                    <?= in_array($deelnemer['id'], $alIngedeeld) ? '(al ingedeeld)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
                <div>
                    <button type="submit" class="knop">Koppelen</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="blok">
    <h3>Wedstrijden</h3>
    <?php if (!$wedstrijden): ?>
        <div class="melding melding-info">Er zijn nog geen wedstrijden in deze ronde.</div>
    <?php else: ?>
        <div class="tabel-wrapper">
            <table>
                <thead>
                <tr><th>Tafel</th><th>Speler 1</th><th>Speler 2</th><th><?= $ronde['gepubliceerd'] ? 'Uitslag' : 'Actie' ?></th></tr>
                </thead>
                <tbody>
                <?php foreach ($wedstrijden as $wedstrijd): ?>
                    <tr>
                        <td><?= (int) $wedstrijd['tafelnummer'] ?></td>
                        <td><?= e($wedstrijd['speler1_naam']) ?></td>
                        <td><?= e($wedstrijd['speler2_naam']) ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <?= csrf_veld() ?>
                                <input type="hidden" name="wedstrijd_id" value="<?= (int) $wedstrijd['id'] ?>">
                                <?php if ($ronde['gepubliceerd']): ?>
                                    <input type="hidden" name="actie" value="uitslag">
                                    <label class="verborgen" for="uitslag-<?= (int) $wedstrijd['id'] ?>">Uitslag</label>
                                    <select id="uitslag-<?= (int) $wedstrijd['id'] ?>" name="uitslag">
                                        <?php
                                        $keuzes = [
                                            ''        => 'Nog niet gespeeld',
                                            'speler1' => $wedstrijd['speler1_naam'] . ' wint',
                                            'gelijk'  => 'Gelijkspel',
                                            'speler2' => $wedstrijd['speler2_naam'] . ' wint',
                                        ];
                                        foreach ($keuzes as $waarde => $tekst): ?>
                                            <option value="<?= e($waarde) ?>" <?= (string) $wedstrijd['uitslag'] === $waarde ? 'selected' : '' ?>><?= e($tekst) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="knop knop-klein">Opslaan</button>
                                <?php else: ?>
                                    <input type="hidden" name="actie" value="verwijderen">
                                    <button type="submit" class="link-knop link-gevaar">Verwijderen</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<p><a href="<?= e(url('beheer/rondes.php?toernooi_id=' . $toernooi['id'])) ?>">&larr; Terug naar rondes en stand</a></p>

<?php require __DIR__ . '/../includes/footer.php'; ?>
