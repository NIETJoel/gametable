<?php
// Kopieer dit bestand naar config.php en vul je eigen gegevens in.
// config.php staat in .gitignore, zodat wachtwoorden niet op GitHub komen.

define('DB_HOST', 'localhost');
define('DB_NAAM', 'gametable');
define('DB_GEBRUIKER', 'root');
define('DB_WACHTWOORD', '');

// Map waarin de app staat. Lokaal (XAMPP): '/gametable'. Op Plesk in de hoofdmap: ''
define('BASIS_URL', '/gametable');

// Alleen lokaal op true zetten. Online altijd false, zodat bezoekers geen technische fouten zien.
define('TOON_FOUTEN', true);
