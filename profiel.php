<?php
// FE02 / T13 – Een ingelogde gebruiker bekijkt en wijzigt zijn spelersnaam.
require_once __DIR__ . '/includes/init.php';
vereis_login();

$gebruiker = huidige_gebruiker();
$fouten = [];

if (is_post()) {
    controleer_csrf();
    $spelersnaam = post_tekst('spelersnaam');

    $naamFout = controleer_spelersnaam($spelersnaam);
    if ($naamFout !== null) {
        $fouten['spelersnaam'] = $naamFout;
    } else {
        try {
            // Alleen de eigen gegevens: het id komt uit de sessie, niet uit het formulier
            $stmt = db()->prepare('UPDATE gebruikers SET spelersnaam = ? WHERE id = ?');
            $stmt->execute([$spelersnaam, $gebruiker['id']]);

            zet_melding('succes', 'Je spelersnaam is opgeslagen.');
            doorsturen('profiel.php');
        } catch (PDOException $fout) {
            if (!is_dubbele_waarde($fout)) {
                throw $fout;
            }
            $fouten['spelersnaam'] = 'Deze spelersnaam is al in gebruik. Kies een andere naam.';
        }
    }
}

$paginatitel = 'Profiel';
require __DIR__ . '/includes/header.php';
?>
<h1>Mijn profiel</h1>

<dl class="gegevens">
    <dt>Spelersnaam</dt>
    <dd><?= e($gebruiker['spelersnaam']) ?></dd>
    <dt>E-mailadres</dt>
    <dd><?= e($gebruiker['email']) ?></dd>
    <dt>Rol</dt>
    <dd><?= e(ucfirst($gebruiker['rol'])) ?></dd>
</dl>

<h2>Spelersnaam wijzigen</h2>
<form method="post" class="formulier">
    <?= csrf_veld() ?>
    <label for="spelersnaam">Nieuwe spelersnaam</label>
    <input type="text" id="spelersnaam" name="spelersnaam" maxlength="30" required
           value="<?= e($_POST['spelersnaam'] ?? $gebruiker['spelersnaam']) ?>">
    <?php toon_veldfout($fouten, 'spelersnaam'); ?>
    <button type="submit" class="knop">Opslaan</button>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
