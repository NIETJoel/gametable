<?php
// Beheer: toernooi verwijderen (ontwerp F06). Inschrijvingen, rondes en wedstrijden
// worden door ON DELETE CASCADE in de database automatisch mee verwijderd.
require_once __DIR__ . '/../includes/init.php';
vereis_rol('toernooileider');

if (!is_post()) {
    doorsturen('beheer/toernooien.php');
}
controleer_csrf();

$toernooi = vereis_toernooi(post_getal('id'));

$stmt = db()->prepare('DELETE FROM toernooien WHERE id = ?');
$stmt->execute([$toernooi['id']]);

zet_melding('succes', 'Het toernooi "' . $toernooi['naam'] . '" is verwijderd.');
doorsturen('beheer/toernooien.php');
