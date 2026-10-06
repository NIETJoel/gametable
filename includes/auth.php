<?php
// Inloggen, uitloggen en rechten controleren (FE01, TE-01).

// Geeft de ingelogde gebruiker uit de database terug, of null als niemand is ingelogd.
function huidige_gebruiker(): ?array
{
    static $gebruiker = false;

    if ($gebruiker === false) {
        $gebruiker = null;
        if (isset($_SESSION['gebruiker_id'])) {
            $stmt = db()->prepare('SELECT id, spelersnaam, email, rol FROM gebruikers WHERE id = ?');
            $stmt->execute([$_SESSION['gebruiker_id']]);
            $gebruiker = $stmt->fetch() ?: null;
        }
    }

    return $gebruiker;
}

function is_ingelogd(): bool
{
    return huidige_gebruiker() !== null;
}

function heeft_rol(string $rol): bool
{
    return is_ingelogd() && huidige_gebruiker()['rol'] === $rol;
}

// Gebruik bovenaan een pagina die alleen voor ingelogde gebruikers is.
function vereis_login(): void
{
    if (!is_ingelogd()) {
        zet_melding('fout', 'Log eerst in om deze pagina te bekijken.');
        doorsturen('inloggen.php');
    }
}

// Gebruik bovenaan een pagina die alleen voor een bepaalde rol is. Controle gebeurt op de server.
function vereis_rol(string $rol): void
{
    vereis_login();
    if (!heeft_rol($rol)) {
        stop_met_fout(403, 'Je hebt geen toegang tot deze pagina.');
    }
}

function log_in(int $gebruikerId): void
{
    session_regenerate_id(true); // nieuwe sessie-id tegen sessie-overname
    $_SESSION['gebruiker_id'] = $gebruikerId;
}

function log_uit(): void
{
    $_SESSION = [];
    session_destroy();
}
