ALTER TABLE auta
    ADD COLUMN spz          VARCHAR(20) NULL AFTER vin,
    ADD COLUMN spz_historie JSON        NULL AFTER spz;
