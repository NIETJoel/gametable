<?php
// FE04 / FE05 / T15 / T16 – Een speler schrijft zich in voor een open toernooi met vrije plaatsen.
require_once __DIR__ . '/includes/init.php';
vereis_rol('speler');

if (!is_post()) {
    doorsturen('index.php');
}
controleer_csrf();

$toernooiId = post_getal('toernooi_id');
$gebruiker  = huidige_gebruiker();
$pdo        = db();

$pdo->beginTransaction();
try {
    // FOR UPDATE: het toernooi wordt even "vastgezet", zodat twee spelers tegelijk
    // niet samen de laatste plaats kunnen krijgen.
    $stmt = $pdo->prepare('SELECT id, status, datum, capaciteit FROM toernooien WHERE id = ? FOR UPDATE');
    $stmt->execute([$toernooiId]);
    $toernooi = $stmt->fetch();

    if (!$toernooi) {
        $pdo->rollBack();
        stop_met_fout(404, 'Dit toernooi bestaat niet (meer).');
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM inschrijvingen WHERE toernooi_id = ?');
    $stmt->execute([$toernooiId]);
    $aantal = (int) $stmt->fetchColumn();

    if (!is_inschrijving_open($toernooi)) {
        zet_melding('fout', 'Inschrijven is niet mogelijk: de inschrijving voor dit toernooi is gesloten.');
    } elseif ($aantal >= (int) $toernooi['capaciteit']) {
        // FE05: vol toernooi
        zet_melding('fout', 'Inschrijven is niet gelukt: het maximale aantal deelnemers is bereikt.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO inschrijvingen (toernooi_id, gebruiker_id) VALUES (?, ?)');
        $stmt->execute([$toernooiId, $gebruiker['id']]);
        zet_melding('succes', 'Je bent ingeschreven. De toernooileider moet je inschrijving nog goedkeuren.');
    }

    $pdo->commit();
} catch (PDOException $fout) {
    $pdo->rollBack();
    if (!is_dubbele_waarde($fout)) {
        throw $fout;
    }
    // UNIQUE (toernooi_id, gebruiker_id) voorkomt dubbel inschrijven
    zet_melding('fout', 'Je bent al ingeschreven voor dit toernooi.');
}

doorsturen('toernooi.php?id=' . $toernooiId);
