CREATE TABLE servis (
    id                      INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    auto_id                 INT UNSIGNED    NOT NULL,
    uzivatel_id             INT UNSIGNED    NOT NULL,
    datum                   DATE            NOT NULL,
    km_stav                 INT UNSIGNED    NOT NULL COMMENT 'stav tachometru při servisu',
    typ                     VARCHAR(100)    NOT NULL COMMENT 'např. STK, výměna oleje, pneu, brzdy…',
    popis                   TEXT            NULL,
    servisni_misto          VARCHAR(200)    NULL COMMENT 'název servisu nebo místo',
    cena                    DECIMAL(10,2)   NULL,
    dalsi_servis_km         INT UNSIGNED    NULL COMMENT 'doporučený km pro příští servis',
    dalsi_servis_datum      DATE            NULL COMMENT 'doporučené datum příštího servisu',
    created_at              TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_servis_auto     FOREIGN KEY (auto_id)     REFERENCES auta(id),
    CONSTRAINT fk_servis_uzivatel FOREIGN KEY (uzivatel_id) REFERENCES uzivatele(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
