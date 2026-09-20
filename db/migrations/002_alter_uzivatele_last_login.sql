ALTER TABLE uzivatele
    ADD COLUMN posledni_prihlaseni TIMESTAMP NULL DEFAULT NULL
        AFTER updated_at;
