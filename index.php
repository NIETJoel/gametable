<?php
// FE03 / T14 – Open toernooien bekijken en filteren op spel en datum.
require_once __DIR__ . '/includes/init.php';

$gekozenSpel  = trim((string) ($_GET['spel'] ?? ''));
$gekozenDatum = trim((string) ($_GET['datum'] ?? ''));

// Een ongeldige datum in de URL negeren we met een melding
if ($gekozenDatum !== '' && !is_geldige_datum($gekozenDatum)) {
    zet_melding('fout', 'De gekozen datum is ongeldig. Het datumfilter is niet gebruikt.');
    $gekozenDatum = '';
}

// Open toernooien: status 'open' en de datum is vandaag of later
$sql = "SELECT t.*,
               (SELECT COUNT(*) FROM inschrijvingen i WHERE i.toernooi_id = t.id) AS aantal_inschrijvingen
        FROM toernooien t
        WHERE t.status = 'open' AND t.datum >= CURDATE()";
$parameters = [];

if ($gekozenSpel !== '') {
    $sql .= ' AND t.spel = ?';
    $parameters[] = $gekozenSpel;
}
if ($gekozenDatum !== '') {
    $sql .= ' AND t.datum = ?';
    $parameters[] = $gekozenDatum;
}
$sql .= ' ORDER BY t.datum, t.naam';

$stmt = db()->prepare($sql);
$stmt->execute($parameters);
$toernooien = $stmt->fetchAll();

// Lijst met spellen voor het filter
$spellen = db()->query(
    "SELECT DISTINCT spel FROM toernooien WHERE status = 'open' AND datum >= CURDATE() ORDER BY spel"
)->fetchAll(PDO::FETCH_COLUMN);

$paginatitel = 'Toernooien';
require __DIR__ . '/includes/header.php';
?>
<h1>Open toernooien</h1>

<form method="get" class="filter">
    <div>
        <label for="spel">Spel</label>
        <select id="spel" name="spel">
            <option value="">Alle spellen</option>
            <?php foreach ($spellen as $spel): ?>
                <option value="<?= e($spel) ?>" <?= $spel === $gekozenSpel ? 'selected' : '' ?>><?= e($spel) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="datum">Datum</label>
        <input type="date" id="datum" name="datum" value="<?= e($gekozenDatum) ?>">
    </div>
    <div class="filter-knoppen">
        <button type="submit" class="knop">Filteren</button>
        <a href="<?= e(url('index.php')) ?>" class="knop knop-licht">Wis filters</a>
    </div>
</form>

<?php if (!$toernooien): ?>
    <div class="melding melding-info">Er zijn geen open toernooien gevonden<?= ($gekozenSpel !== '' || $gekozenDatum !== '') ? ' met deze filters' : '' ?>.</div>
<?php else: ?>
    <div class="kaarten">
        <?php foreach ($toernooien as $toernooi): ?>
            <?php $vrij = vrije_plaatsen($toernooi); ?>
            <article class="kaart">
                <h2><?= e($toernooi['naam']) ?></h2>
                <p><strong>Spel:</strong> <?= e($toernooi['spel']) ?></p>
                <p><strong>Datum:</strong> <?= e(toon_datum($toernooi['datum'])) ?></p>
                <p>
                    <strong>Plaatsen:</strong>
                    <?php if ($vrij > 0): ?>
                        <?= $vrij ?> van <?= (int) $toernooi['capaciteit'] ?> vrij
                    <?php else: ?>
                        <span class="label label-vol">Vol</span>
                    <?php endif; ?>
                </p>
                <a class="knop" href="<?= e(url('toernooi.php?id=' . $toernooi['id'])) ?>">Bekijk toernooi</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
