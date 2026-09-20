ALTER TABLE uzivatele
    ADD UNIQUE KEY uq_prezdivka (prezdivka),
    ADD UNIQUE KEY uq_telefon   (telefon);
