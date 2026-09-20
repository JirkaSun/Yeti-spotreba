CREATE TABLE tankovani (
    id              INT UNSIGNED        AUTO_INCREMENT PRIMARY KEY,
    auto_id         INT UNSIGNED        NOT NULL,
    uzivatel_id     INT UNSIGNED        NOT NULL,
    datum_cas       DATETIME            NOT NULL,
    km_stav         INT UNSIGNED        NOT NULL COMMENT 'stav tachometru při tankování',
    litry           DECIMAL(6,2)        NOT NULL,
    cena_za_litr    DECIMAL(6,3)        NOT NULL,
    celkova_cena    DECIMAL(8,2)        NOT NULL,
    palivo          ENUM('benzin', 'nafta', 'lpg', 'elektro', 'hybrid') NOT NULL,
    plna_nadrz      TINYINT(1)          NOT NULL DEFAULT 1,
    poznamka        TEXT                NULL,
    created_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_tankovani_auto     FOREIGN KEY (auto_id)     REFERENCES auta(id),
    CONSTRAINT fk_tankovani_uzivatel FOREIGN KEY (uzivatel_id) REFERENCES uzivatele(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
