<?php
// FE01 / T11 – Een speler maakt een account aan.
require_once __DIR__ . '/includes/init.php';

if (is_ingelogd()) {
    doorsturen('index.php');
}

$fouten = [];

if (is_post()) {
    controleer_csrf();

    $spelersnaam = post_tekst('spelersnaam');
    $email       = post_tekst('email');
    $wachtwoord  = $_POST['wachtwoord'] ?? '';
    $herhaling   = $_POST['wachtwoord_herhalen'] ?? '';

    // Invoer controleren (TE-04)
    $naamFout = controleer_spelersnaam($spelersnaam);
    if ($naamFout !== null) {
        $fouten['spelersnaam'] = $naamFout;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $fouten['email'] = 'Vul een geldig e-mailadres in.';
    }
    if (strlen($wachtwoord) < 8) {
        $fouten['wachtwoord'] = 'Het wachtwoord moet minimaal 8 tekens zijn.';
    } elseif ($wachtwoord !== $herhaling) {
        $fouten['wachtwoord_herhalen'] = 'De wachtwoorden zijn niet hetzelfde.';
    }

    if (!$fouten) {
        try {
            $stmt = db()->prepare(
                "INSERT INTO gebruikers (spelersnaam, email, wachtwoord_hash, rol) VALUES (?, ?, ?, 'speler')"
            );
            // TE-01: alleen de hash van het wachtwoord wordt opgeslagen
            $stmt->execute([$spelersnaam, $email, password_hash($wachtwoord, PASSWORD_DEFAULT)]);

            zet_melding('succes', 'Je account is aangemaakt. Je kunt nu inloggen.');
            doorsturen('inloggen.php');
        } catch (PDOException $fout) {
            if (!is_dubbele_waarde($fout)) {
                throw $fout;
            }
            $fouten['algemeen'] = 'Dit e-mailadres of deze spelersnaam is al in gebruik.';
        }
    }
}

$paginatitel = 'Registreren';
require __DIR__ . '/includes/header.php';
?>
<h1>Account aanmaken</h1>

<?php if (isset($fouten['algemeen'])): ?>
    <div class="melding melding-fout" role="alert"><?= e($fouten['algemeen']) ?></div>
<?php endif; ?>

<form method="post" class="formulier" novalidate>
    <?= csrf_veld() ?>

    <label for="spelersnaam">Spelersnaam</label>
    <input type="text" id="spelersnaam" name="spelersnaam" maxlength="30" required
           value="<?= e($_POST['spelersnaam'] ?? '') ?>">
    <?php toon_veldfout($fouten, 'spelersnaam'); ?>

    <label for="email">E-mailadres</label>
    <input type="email" id="email" name="email" maxlength="255" required
           value="<?= e($_POST['email'] ?? '') ?>">
    <?php toon_veldfout($fouten, 'email'); ?>

    <label for="wachtwoord">Wachtwoord (minimaal 8 tekens)</label>
    <input type="password" id="wachtwoord" name="wachtwoord" minlength="8" required>
    <?php toon_veldfout($fouten, 'wachtwoord'); ?>

    <label for="wachtwoord_herhalen">Wachtwoord herhalen</label>
    <input type="password" id="wachtwoord_herhalen" name="wachtwoord_herhalen" minlength="8" required>
    <?php toon_veldfout($fouten, 'wachtwoord_herhalen'); ?>

    <button type="submit" class="knop">Account aanmaken</button>
</form>

<p>Heb je al een account? <a href="<?= e(url('inloggen.php')) ?>">Log hier in</a>.</p>

<?php require __DIR__ . '/includes/footer.php'; ?>
