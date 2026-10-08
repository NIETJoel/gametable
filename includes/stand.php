<?php
// Berekent de stand van een toernooi (FE14).
// De stand wordt niet opgeslagen, maar steeds opnieuw berekend uit de opgeslagen uitslagen.
// Zo kan de stand nooit verschillen van de uitslagen (betrouwbaarheid).

function bereken_stand(array $toernooi): array
{
    // 1. Elke goedgekeurde deelnemer begint met 0 punten.
    $stand = [];
    foreach (haal_goedgekeurde_deelnemers((int) $toernooi['id']) as $deelnemer) {
        $stand[$deelnemer['id']] = [
            'spelersnaam' => $deelnemer['spelersnaam'],
            'gespeeld'    => 0,
            'gewonnen'    => 0,
            'gelijk'      => 0,
            'verloren'    => 0,
            'punten'      => 0,
        ];
    }

    // 2. Alle wedstrijden met een uitslag uit dit toernooi ophalen.
    $stmt = db()->prepare(
        'SELECT w.speler1_id, w.speler2_id, w.uitslag
         FROM wedstrijden w
         JOIN rondes r ON r.id = w.ronde_id
         WHERE r.toernooi_id = ? AND w.uitslag IS NOT NULL'
    );
    $stmt->execute([$toernooi['id']]);

    // 3. Per wedstrijd de punten optellen volgens het puntensysteem van het toernooi.
    foreach ($stmt->fetchAll() as $wedstrijd) {
        $speler1 = $wedstrijd['speler1_id'];
        $speler2 = $wedstrijd['speler2_id'];

        if (!isset($stand[$speler1], $stand[$speler2])) {
            continue; // speler is geen goedgekeurde deelnemer meer
        }

        if ($wedstrijd['uitslag'] === 'gelijk') {
            voeg_resultaat_toe($stand[$speler1], 'gelijk', (int) $toernooi['punten_gelijk']);
            voeg_resultaat_toe($stand[$speler2], 'gelijk', (int) $toernooi['punten_gelijk']);
        } elseif ($wedstrijd['uitslag'] === 'speler1') {
            voeg_resultaat_toe($stand[$speler1], 'gewonnen', (int) $toernooi['punten_winst']);
            voeg_resultaat_toe($stand[$speler2], 'verloren', (int) $toernooi['punten_verlies']);
        } else {
            voeg_resultaat_toe($stand[$speler2], 'gewonnen', (int) $toernooi['punten_winst']);
            voeg_resultaat_toe($stand[$speler1], 'verloren', (int) $toernooi['punten_verlies']);
        }
    }

    // 4. Sorteren: meeste punten bovenaan, daarna meeste overwinningen, daarna op naam.
    usort($stand, function (array $a, array $b) {
        return [$b['punten'], $b['gewonnen'], $a['spelersnaam']]
           <=> [$a['punten'], $a['gewonnen'], $b['spelersnaam']];
    });

    return $stand;
}

function voeg_resultaat_toe(array &$regel, string $soort, int $punten): void
{
    $regel['gespeeld']++;
    $regel[$soort]++;
    $regel['punten'] += $punten;
}
