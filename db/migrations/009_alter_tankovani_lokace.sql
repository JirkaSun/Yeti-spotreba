ALTER TABLE tankovani
    ADD COLUMN pumpa_nazev VARCHAR(200) NULL AFTER poznamka,
    ADD COLUMN pumpa_lat   DECIMAL(10, 7) NULL AFTER pumpa_nazev,
    ADD COLUMN pumpa_lng   DECIMAL(11, 7) NULL AFTER pumpa_lat;
