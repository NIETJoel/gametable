<?php
// Algemene hulpfuncties die op bijna elke pagina gebruikt worden.

// Maakt tekst veilig om in HTML te tonen (tegen XSS, TE-04).
function e($tekst): string
{
    return htmlspecialchars((string) $tekst, ENT_QUOTES, 'UTF-8');
}

// Maakt een link binnen de app, bijvoorbeeld url('inloggen.php').
function url(string $pad = ''): string
{
    return BASIS_URL . '/' . ltrim($pad, '/');
}

// Stuurt de gebruiker door naar een andere pagina en stopt het script.
function doorsturen(string $pad): void
{
    header('Location: ' . url($pad));
    exit;
}

// ---------- Meldingen (FE15) ----------

// Bewaart een melding in de sessie. Type: 'succes', 'fout' of 'info'.
function zet_melding(string $type, string $tekst): void
{
    $_SESSION['meldingen'][] = ['type' => $type, 'tekst' => $tekst];
}

// Toont alle bewaarde meldingen en haalt ze daarna weg.
function toon_meldingen(): void
{
    foreach ($_SESSION['meldingen'] ?? [] as $melding) {
        echo '<div class="melding melding-' . e($melding['type']) . '" role="alert">' . e($melding['tekst']) . '</div>';
    }
    unset($_SESSION['meldingen']);
}

// Toont een pagina met alleen een foutmelding en stopt (bijv. 403 of 404).
function stop_met_fout(int $httpCode, string $tekst): void
{
    http_response_code($httpCode);
    $paginatitel = 'Melding';
    require __DIR__ . '/header.php';
    echo '<div class="melding melding-fout" role="alert">' . e($tekst) . '</div>';
    echo '<p><a href="' . e(url('index.php')) . '">Terug naar de toernooien</a></p>';
    require __DIR__ . '/footer.php';
    exit;
}

// ---------- Formulieren en invoer ----------

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Toont de foutmelding onder een formulierveld, als die er is.
function toon_veldfout(array $fouten, string $veld): void
{
    if (isset($fouten[$veld])) {
        echo '<p class="veldfout">' . e($fouten[$veld]) . '</p>';
    }
}

// Haalt een tekstveld uit het formulier, zonder spaties aan begin en eind.
function post_tekst(string $naam): string
{
    return trim((string) ($_POST[$naam] ?? ''));
}

// Haalt een heel getal uit het formulier. Geeft null als het geen geldig getal is.
function post_getal(string $naam): ?int
{
    $waarde = filter_var($_POST[$naam] ?? null, FILTER_VALIDATE_INT);
    return $waarde === false ? null : $waarde;
}

// Haalt een heel getal uit de URL (bijv. ?id=3). Geeft null als het ontbreekt of ongeldig is.
function get_getal(string $naam): ?int
{
    $waarde = filter_var($_GET[$naam] ?? null, FILTER_VALIDATE_INT);
    return $waarde === false ? null : $waarde;
}

// Controleert een spelersnaam. Geeft een foutmelding terug, of null als de naam goed is.
function controleer_spelersnaam(string $spelersnaam): ?string
{
    if (!preg_match('/^[\p{L}0-9 _-]{3,30}$/u', $spelersnaam)) {
        return 'Spelersnaam moet 3 tot 30 tekens zijn (letters, cijfers, spatie, - of _).';
    }
    return null;
}

// Controleert of een datum echt bestaat en de vorm jjjj-mm-dd heeft.
function is_geldige_datum(string $datum): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $datum);
    return $d !== false && $d->format('Y-m-d') === $datum;
}

// Zet een datum uit de database (2026-12-12) om naar 12-12-2026.
function toon_datum(string $datum): string
{
    return date('d-m-Y', strtotime($datum));
}

// ---------- CSRF-beveiliging ----------
// Elk formulier krijgt een geheime code. Zo kan een andere website geen acties uitvoeren namens de gebruiker.

function csrf_veld(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

function controleer_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        stop_met_fout(400, 'Het formulier is verlopen. Ga terug, ververs de pagina en probeer het opnieuw.');
    }
}
