<?php
// FE01 / T11 – Inloggen met e-mailadres en wachtwoord.
require_once __DIR__ . '/includes/init.php';

if (is_ingelogd()) {
    doorsturen('index.php');
}

$foutmelding = '';

if (is_post()) {
    controleer_csrf();

    $email      = post_tekst('email');
    $wachtwoord = $_POST['wachtwoord'] ?? '';

    if ($email === '' || $wachtwoord === '') {
        $foutmelding = 'Vul je e-mailadres en wachtwoord in.';
    } else {
        $stmt = db()->prepare('SELECT id, rol, wachtwoord_hash FROM gebruikers WHERE email = ?');
        $stmt->execute([$email]);
        $gebruiker = $stmt->fetch();

        // TE-01: wachtwoord controleren tegen de opgeslagen hash
        if ($gebruiker && password_verify($wachtwoord, $gebruiker['wachtwoord_hash'])) {
            log_in((int) $gebruiker['id']);
            zet_melding('succes', 'Je bent ingelogd.');

            // Na inloggen komt de gebruiker in zijn eigen omgeving terecht
            doorsturen('profiel.php');
        }

        // Zelfde melding bij onbekend e-mailadres en fout wachtwoord, zodat niemand kan raden welke accounts bestaan
        $foutmelding = 'Het e-mailadres of wachtwoord klopt niet.';
    }
}

$paginatitel = 'Inloggen';
require __DIR__ . '/includes/header.php';
?>
<h1>Inloggen</h1>

<?php if ($foutmelding !== ''): ?>
    <div class="melding melding-fout" role="alert"><?= e($foutmelding) ?></div>
<?php endif; ?>

<form method="post" class="formulier">
    <?= csrf_veld() ?>

    <label for="email">E-mailadres</label>
    <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">

    <label for="wachtwoord">Wachtwoord</label>
    <input type="password" id="wachtwoord" name="wachtwoord" required>

    <button type="submit" class="knop">Inloggen</button>
</form>

<p>Nog geen account? <a href="<?= e(url('registreren.php')) ?>">Registreer je hier</a>.</p>

<?php require __DIR__ . '/includes/footer.php'; ?>
