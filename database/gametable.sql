-- GameTable database
-- Importeer dit bestand in een LEGE database (bijv. via phpMyAdmin > Importeren).
-- Let op: bestaande GameTable-tabellen worden eerst verwijderd.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS ronde_spelers, wedstrijden, rondes, inschrijvingen, toernooien, gebruikers;
SET FOREIGN_KEY_CHECKS = 1;

-- Gebruikers: spelers en toernooileiders (TE-01: wachtwoord alleen als hash)
CREATE TABLE gebruikers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    spelersnaam     VARCHAR(30)  NOT NULL,
    email           VARCHAR(255) NOT NULL,
    wachtwoord_hash VARCHAR(255) NOT NULL,
    rol             ENUM('speler', 'toernooileider') NOT NULL DEFAULT 'speler',
    aangemaakt_op   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_gebruikers_email (email),
    UNIQUE KEY uq_gebruikers_spelersnaam (spelersnaam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Toernooien met capaciteit en puntensysteem (FE06)
CREATE TABLE toernooien (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    naam            VARCHAR(100) NOT NULL,
    spel            VARCHAR(100) NOT NULL,
    datum           DATE NOT NULL,
    capaciteit      SMALLINT UNSIGNED NOT NULL,
    punten_winst    TINYINT UNSIGNED NOT NULL DEFAULT 3,
    punten_gelijk   TINYINT UNSIGNED NOT NULL DEFAULT 1,
    punten_verlies  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status          ENUM('open', 'gesloten') NOT NULL DEFAULT 'open',
    aangemaakt_door INT UNSIGNED NOT NULL,
    aangemaakt_op   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_toernooien_gebruiker FOREIGN KEY (aangemaakt_door) REFERENCES gebruikers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inschrijvingen: een speler kan zich maar 1 keer per toernooi inschrijven (FE04, FE07, FE08)
CREATE TABLE inschrijvingen (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    toernooi_id     INT UNSIGNED NOT NULL,
    gebruiker_id    INT UNSIGNED NOT NULL,
    status          ENUM('aangemeld', 'goedgekeurd') NOT NULL DEFAULT 'aangemeld',
    startpositie    SMALLINT UNSIGNED NULL,
    ingeschreven_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inschrijving_speler (toernooi_id, gebruiker_id),
    UNIQUE KEY uq_inschrijving_startpositie (toernooi_id, startpositie),
    CONSTRAINT fk_inschrijvingen_toernooi FOREIGN KEY (toernooi_id) REFERENCES toernooien (id) ON DELETE CASCADE,
    CONSTRAINT fk_inschrijvingen_gebruiker FOREIGN KEY (gebruiker_id) REFERENCES gebruikers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rondes per toernooi (FE09, FE11, FE12)
CREATE TABLE rondes (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    toernooi_id  INT UNSIGNED NOT NULL,
    nummer       SMALLINT UNSIGNED NOT NULL,
    gepubliceerd TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_ronde_nummer (toernooi_id, nummer),
    CONSTRAINT fk_rondes_toernooi FOREIGN KEY (toernooi_id) REFERENCES toernooien (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wedstrijden: 2 spelers aan 1 tafel binnen een ronde, met uitslag (FE09, FE13)
CREATE TABLE wedstrijden (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ronde_id    INT UNSIGNED NOT NULL,
    tafelnummer SMALLINT UNSIGNED NOT NULL,
    speler1_id  INT UNSIGNED NOT NULL,
    speler2_id  INT UNSIGNED NOT NULL,
    uitslag     ENUM('speler1', 'speler2', 'gelijk') NULL,
    UNIQUE KEY uq_wedstrijd_tafel (ronde_id, tafelnummer),
    CONSTRAINT chk_wedstrijd_andere_spelers CHECK (speler1_id <> speler2_id),
    CONSTRAINT fk_wedstrijden_ronde FOREIGN KEY (ronde_id) REFERENCES rondes (id) ON DELETE CASCADE,
    CONSTRAINT fk_wedstrijden_speler1 FOREIGN KEY (speler1_id) REFERENCES gebruikers (id),
    CONSTRAINT fk_wedstrijden_speler2 FOREIGN KEY (speler2_id) REFERENCES gebruikers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ronde_spelers: de primary key (ronde_id, gebruiker_id) zorgt dat een speler
-- maar 1 keer per ronde ingedeeld kan worden (FE10, TE-03)
CREATE TABLE ronde_spelers (
    ronde_id     INT UNSIGNED NOT NULL,
    gebruiker_id INT UNSIGNED NOT NULL,
    wedstrijd_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (ronde_id, gebruiker_id),
    CONSTRAINT fk_ronde_spelers_ronde FOREIGN KEY (ronde_id) REFERENCES rondes (id) ON DELETE CASCADE,
    CONSTRAINT fk_ronde_spelers_gebruiker FOREIGN KEY (gebruiker_id) REFERENCES gebruikers (id) ON DELETE CASCADE,
    CONSTRAINT fk_ronde_spelers_wedstrijd FOREIGN KEY (wedstrijd_id) REFERENCES wedstrijden (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Testgegevens (verzonnen, geen echte persoonsgegevens)
-- Toernooileider: leider@gametable.nl  / Leider123!
-- Spelers:        speler1..6@gametable.nl / Speler123!
-- ---------------------------------------------------------------
INSERT INTO gebruikers (id, spelersnaam, email, wachtwoord_hash, rol) VALUES
(1, 'Toernooileider', 'leider@gametable.nl',  '$2y$10$QKAOmr6GkJsX.Z2hD77x2.Fd2hVV7RFnt9tWp5RA4z3mP8afcLBhK', 'toernooileider'),
(2, 'DiceQueen',      'speler1@gametable.nl', '$2y$10$770RxxrDUYzvaXrE8zFFxerIlg9x2alYZaRsowjjWFXIKpIwnNHHa', 'speler'),
(3, 'MeepleMike',     'speler2@gametable.nl', '$2y$10$770RxxrDUYzvaXrE8zFFxerIlg9x2alYZaRsowjjWFXIKpIwnNHHa', 'speler'),
(4, 'CardShark',      'speler3@gametable.nl', '$2y$10$770RxxrDUYzvaXrE8zFFxerIlg9x2alYZaRsowjjWFXIKpIwnNHHa', 'speler'),
(5, 'RollMaster',     'speler4@gametable.nl', '$2y$10$770RxxrDUYzvaXrE8zFFxerIlg9x2alYZaRsowjjWFXIKpIwnNHHa', 'speler'),
(6, 'TileTom',        'speler5@gametable.nl', '$2y$10$770RxxrDUYzvaXrE8zFFxerIlg9x2alYZaRsowjjWFXIKpIwnNHHa', 'speler'),
(7, 'PawnPia',        'speler6@gametable.nl', '$2y$10$770RxxrDUYzvaXrE8zFFxerIlg9x2alYZaRsowjjWFXIKpIwnNHHa', 'speler');

INSERT INTO toernooien (id, naam, spel, datum, capaciteit, punten_winst, punten_gelijk, punten_verlies, status, aangemaakt_door) VALUES
(1, 'Catan Clash',      'Catan',             '2026-12-12', 8, 3, 1, 0, 'open', 1),
(2, 'Magic Mini Cup',   'Magic: The Gathering', '2026-12-19', 2, 3, 1, 0, 'open', 1),
(3, 'Carcassonne Cup',  'Carcassonne',       '2027-01-16', 16, 2, 1, 0, 'open', 1);

INSERT INTO inschrijvingen (toernooi_id, gebruiker_id, status, startpositie) VALUES
(1, 2, 'goedgekeurd', 1),
(1, 3, 'goedgekeurd', 2),
(1, 4, 'goedgekeurd', 3),
(1, 5, 'aangemeld',   NULL),
(2, 6, 'goedgekeurd', 1),
(2, 7, 'goedgekeurd', 2);
