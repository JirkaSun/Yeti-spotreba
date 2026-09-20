CREATE TABLE auta (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    vyrobce         VARCHAR(100)    NOT NULL,
    model           VARCHAR(100)    NOT NULL,
    rok_vyroby      YEAR            NOT NULL,
    obsah_motoru    SMALLINT UNSIGNED NULL COMMENT 'v cm3',
    vin             VARCHAR(17)     NULL UNIQUE,
    palivo          ENUM('benzin', 'nafta', 'lpg', 'elektro', 'hybrid') NOT NULL DEFAULT 'benzin',
    barva           VARCHAR(50)     NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
