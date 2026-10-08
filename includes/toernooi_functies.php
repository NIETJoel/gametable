<?php
// Functies om toernooien, deelnemers, rondes en wedstrijden op te halen.
// Zo staan deze queries op één plek en niet dubbel in verschillende pagina's.

// Haalt een toernooi op, inclusief het aantal inschrijvingen.
function haal_toernooi(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT t.*,
                (SELECT COUNT(*) FROM inschrijvingen i WHERE i.toernooi_id = t.id) AS aantal_inschrijvingen
         FROM toernooien t
         WHERE t.id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// Haalt een toernooi op of toont een 404-melding als het niet bestaat.
function vereis_toernooi(?int $id): array
{
    $toernooi = $id === null ? null : haal_toernooi($id);
    if ($toernooi === null) {
        stop_met_fout(404, 'Dit toernooi bestaat niet (meer).');
    }
    return $toernooi;
}

function vrije_plaatsen(array $toernooi): int
{
    return max(0, (int) $toernooi['capaciteit'] - (int) $toernooi['aantal_inschrijvingen']);
}

// Inschrijven kan alleen als het toernooi open is en de datum nog niet voorbij is.
function is_inschrijving_open(array $toernooi): bool
{
    return $toernooi['status'] === 'open' && $toernooi['datum'] >= date('Y-m-d');
}

// Goedgekeurde deelnemers, gesorteerd op startpositie (deelnemers zonder startpositie achteraan).
function haal_goedgekeurde_deelnemers(int $toernooiId): array
{
    $stmt = db()->prepare(
        "SELECT g.id, g.spelersnaam, i.startpositie
         FROM inschrijvingen i
         JOIN gebruikers g ON g.id = i.gebruiker_id
         WHERE i.toernooi_id = ? AND i.status = 'goedgekeurd'
         ORDER BY i.startpositie IS NULL, i.startpositie, g.spelersnaam"
    );
    $stmt->execute([$toernooiId]);
    return $stmt->fetchAll();
}

// Rondes van een toernooi. Spelers zien alleen gepubliceerde rondes (FE12).
function haal_rondes(int $toernooiId, bool $alleenGepubliceerd): array
{
    $sql = 'SELECT r.*, (SELECT COUNT(*) FROM wedstrijden w WHERE w.ronde_id = r.id) AS aantal_wedstrijden
            FROM rondes r
            WHERE r.toernooi_id = ?';
    if ($alleenGepubliceerd) {
        $sql .= ' AND r.gepubliceerd = 1';
    }
    $sql .= ' ORDER BY r.nummer';

    $stmt = db()->prepare($sql);
    $stmt->execute([$toernooiId]);
    return $stmt->fetchAll();
}

// Wedstrijden van een ronde, met de spelersnamen erbij.
function haal_wedstrijden(int $rondeId): array
{
    $stmt = db()->prepare(
        'SELECT w.*, s1.spelersnaam AS speler1_naam, s2.spelersnaam AS speler2_naam
         FROM wedstrijden w
         JOIN gebruikers s1 ON s1.id = w.speler1_id
         JOIN gebruikers s2 ON s2.id = w.speler2_id
         WHERE w.ronde_id = ?
         ORDER BY w.tafelnummer'
    );
    $stmt->execute([$rondeId]);
    return $stmt->fetchAll();
}

// Zet de uitslag van een wedstrijd om naar leesbare tekst.
function uitslag_tekst(array $wedstrijd): string
{
    switch ($wedstrijd['uitslag']) {
        case 'speler1':
            return $wedstrijd['speler1_naam'] . ' wint';
        case 'speler2':
            return $wedstrijd['speler2_naam'] . ' wint';
        case 'gelijk':
            return 'Gelijkspel';
        default:
            return 'Nog niet gespeeld';
    }
}
