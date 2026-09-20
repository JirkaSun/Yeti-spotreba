CREATE TABLE uctenky (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    vazba_typ   ENUM('tankovani', 'servis', 'jine') NOT NULL,
    vazba_id    INT UNSIGNED    NOT NULL COMMENT 'ID záznamu v tabulce dle vazba_typ',
    soubor      VARCHAR(500)    NOT NULL COMMENT 'relativní cesta k souboru ve storage/',
    nazev       VARCHAR(255)    NULL COMMENT 'původní název souboru',
    castka      DECIMAL(10,2)   NULL,
    datum       DATE            NULL,
    poznamka    TEXT            NULL,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_vazba (vazba_typ, vazba_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
