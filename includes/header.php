<?php
// Bovenkant van elke pagina met de navigatie. Zet $paginatitel vóór het laden van dit bestand.
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
        </nav>
    </div>
</header>
<main class="container">
<?php toon_meldingen(); ?>
