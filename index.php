<?php
// T09 – Basisproject: controleert of de databaseverbinding werkt.
// Deze pagina wordt in stap 3 vervangen door het toernooioverzicht.
require_once __DIR__ . '/includes/init.php';

$aantalToernooien = (int) db()->query('SELECT COUNT(*) FROM toernooien')->fetchColumn();

$paginatitel = 'Home';
require __DIR__ . '/includes/header.php';
?>
<h1>GameTable</h1>
<div class="melding melding-succes">
    De databaseverbinding werkt. Er staan <?= $aantalToernooien ?> toernooien in de database.
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
