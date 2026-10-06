# GameTable

Webapplicatie voor spellenwinkel **Critical Corner** om bord- en kaarttoernooien te organiseren.
Spelers schrijven zich in voor toernooien, toernooileiders beheren deelnemers, rondes, tafels, uitslagen en de stand.

Gemaakt door Joel Tesfagherghis (klas 24A) voor KT1-W3 Realisatie.

## Techniek

- PHP 8 (zonder framework) met PDO
- MySQL / MariaDB
- HTML en CSS (responsive, zonder externe libraries)

## Installeren (lokaal met XAMPP)

1. Zet de map `gametable` in `C:\xampp\htdocs\`.
2. Start **Apache** en **MySQL** in het XAMPP Control Panel.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`) en maak een lege database `gametable` aan (utf8mb4_unicode_ci).
4. Kies de database > **Importeren** > kies `database/gametable.sql` > Starten.
5. Kopieer `includes/config.example.php` naar `includes/config.php` en controleer de gegevens.
6. Open `http://localhost/gametable/`.

## Testaccounts

| Rol | E-mailadres | Wachtwoord |
|---|---|---|
| Toernooileider | leider@gametable.nl | Leider123! |
| Speler | speler1@gametable.nl t/m speler6@gametable.nl | Speler123! |

## Mappenstructuur

```
gametable/
├── index.php              Open toernooien + filter (FE03)
├── registreren.php        Account aanmaken (FE01)
├── inloggen.php           Inloggen (FE01)
├── uitloggen.php          Uitloggen
├── profiel.php            Spelersnaam bekijken/wijzigen (FE02)
├── toernooi.php           Toernooipagina: info, inschrijven, rondes, stand (FE04, FE05, FE12, FE14)
├── inschrijven.php        Verwerkt een inschrijving (FE04, FE05)
├── mijn_toernooien.php    Toernooien van de ingelogde speler
├── beheer/                Alleen voor de toernooileider
│   ├── toernooien.php           Overzicht toernooien
│   ├── toernooi_formulier.php   Toernooi aanmaken/wijzigen (FE06)
│   ├── toernooi_verwijderen.php Toernooi verwijderen
│   ├── deelnemers.php           Goedkeuren + startposities (FE07, FE08)
│   ├── rondes.php               Rondes + stand (FE09, FE14)
│   └── ronde.php                Koppelen, publiceren, uitslagen (FE09–FE11, FE13)
├── includes/              Gedeelde code (niet direct opvraagbaar via de browser)
│   ├── init.php                 Wordt op elke pagina geladen
│   ├── config.example.php       Voorbeeld-instellingen
│   ├── db.php                   Databaseverbinding (PDO)
│   ├── functies.php             Hulpfuncties: meldingen, invoer, CSRF
│   ├── auth.php                 Inloggen en rechten (TE-01)
│   ├── toernooi_functies.php    Queries voor toernooien, rondes en wedstrijden
│   ├── stand.php                Standberekening (FE14)
│   ├── stand_tabel.php          Weergave van de stand
│   ├── beheer_menu.php          Submenu beheerpagina's
│   └── header.php / footer.php  Opmaak boven- en onderkant
├── css/style.css          Opmaak (responsive)
└── database/gametable.sql Tabellen + testgegevens
```
