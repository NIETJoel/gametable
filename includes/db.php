<?php
// Maakt één PDO-verbinding met de database en geeft die steeds terug.

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAAM . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_GEBRUIKER, DB_WACHTWOORD, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // fouten worden exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,                  // echte prepared statements (TE-04)
        ]);
    }

    return $pdo;
}

// Geeft true als een databasefout komt door een UNIQUE- of PRIMARY KEY-regel (dubbele waarde).
function is_dubbele_waarde(PDOException $fout): bool
{
    return ($fout->errorInfo[1] ?? null) === 1062;
}
