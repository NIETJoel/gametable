<?php
// Dit bestand wordt bovenaan elke pagina geladen.
// Het start de sessie en laadt de instellingen, database en hulpfuncties.

if (!file_exists(__DIR__ . '/config.php')) {
    exit('config.php ontbreekt. Kopieer includes/config.example.php naar includes/config.php.');
}
require_once __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', TOON_FOUTEN ? '1' : '0');

session_set_cookie_params([
    'httponly' => true,                     // JavaScript kan de sessiecookie niet lezen
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']), // alleen via https als de site https gebruikt
]);
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functies.php';
require_once __DIR__ . '/auth.php';

// Output eerst bufferen, zodat bij een fout een nette foutpagina getoond kan worden.
ob_start();

// Onverwachte fouten (bijv. database niet bereikbaar): geen technische details tonen,
// maar wel opslaan in het foutlogboek van de server.
set_exception_handler(function (Throwable $fout) {
    error_log('GameTable fout: ' . $fout->getMessage() . ' in ' . $fout->getFile() . ':' . $fout->getLine());
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><title>Fout - GameTable</title>'
        . '<link rel="stylesheet" href="' . e(url('css/style.css')) . '"></head><body><main class="container">'
        . '<div class="melding melding-fout" role="alert">Er ging iets mis. De actie is niet uitgevoerd. Probeer het later opnieuw.</div>'
        . (TOON_FOUTEN ? '<pre>' . e($fout->getMessage()) . '</pre>' : '')
        . '<p><a href="' . e(url('index.php')) . '">Terug naar de toernooien</a></p></main></body></html>';
});
