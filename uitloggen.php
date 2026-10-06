<?php
// FE01 – Uitloggen. Alleen via een POST-formulier met CSRF-code.
require_once __DIR__ . '/includes/init.php';

if (!is_post()) {
    doorsturen('index.php');
}
controleer_csrf();

log_uit();
session_start(); // nieuwe, lege sessie voor de melding
zet_melding('succes', 'Je bent uitgelogd.');
doorsturen('index.php');
