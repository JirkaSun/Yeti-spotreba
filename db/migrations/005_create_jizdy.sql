CREATE TABLE jizdy (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    auto_id         INT UNSIGNED    NOT NULL,
    uzivatel_id     INT UNSIGNED    NOT NULL,
    datum_odjezdu   DATETIME        NOT NULL,
    datum_prijezdu  DATETIME        NULL,
    km_start        INT UNSIGNED    NOT NULL,
    km_konec        INT UNSIGNED    NULL,
    odkud           VARCHAR(200)    NOT NULL,
    kam             VARCHAR(200)    NOT NULL,
    ucel            ENUM('sluzebni', 'soukroma') NOT NULL DEFAULT 'sluzebni',
    poznamka        TEXT            NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_jizdy_auto     FOREIGN KEY (auto_id)     REFERENCES auta(id),
    CONSTRAINT fk_jizdy_uzivatel FOREIGN KEY (uzivatel_id) REFERENCES uzivatele(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
