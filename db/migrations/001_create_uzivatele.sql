CREATE TABLE uzivatele (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    jmeno       VARCHAR(100)    NOT NULL,
    prijmeni    VARCHAR(100)    NOT NULL,
    prezdivka   VARCHAR(50)     NULL,
    telefon     VARCHAR(20)     NULL,
    email       VARCHAR(255)    NOT NULL UNIQUE,
    heslo       VARCHAR(255)    NOT NULL,
    role        ENUM('admin', 'ridic') NOT NULL DEFAULT 'ridic',
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
