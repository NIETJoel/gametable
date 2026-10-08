<?php
// Bovenkant van elke pagina met de navigatie. Zet $paginatitel vóór het laden van dit bestand.
$ingelogd = huidige_gebruiker();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($paginatitel ?? 'GameTable') ?> - GameTable</title>
    <link rel="stylesheet" href="<?= e(url('css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container header-inhoud">
        <a class="logo" href="<?= e(url('index.php')) ?>">GameTable</a>
        <nav class="menu" aria-label="Hoofdmenu">
            <a href="<?= e(url('index.php')) ?>">Toernooien</a>
            <?php if ($ingelogd === null): ?>
                <a href="<?= e(url('inloggen.php')) ?>">Inloggen</a>
                <a href="<?= e(url('registreren.php')) ?>">Registreren</a>
            <?php else: ?>
                <?php if ($ingelogd['rol'] === 'toernooileider'): ?>
                    <a href="<?= e(url('beheer/toernooien.php')) ?>">Beheer</a>
                <?php else: ?>
                    <a href="<?= e(url('mijn_toernooien.php')) ?>">Mijn toernooien</a>
                <?php endif; ?>
                <a href="<?= e(url('profiel.php')) ?>">Profiel (<?= e($ingelogd['spelersnaam']) ?>)</a>
                <form method="post" action="<?= e(url('uitloggen.php')) ?>" class="inline-form">
                    <?= csrf_veld() ?>
                    <button type="submit" class="link-knop">Uitloggen</button>
                </form>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
<?php toon_meldingen(); ?>
