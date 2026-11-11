CREATE TABLE login_pokusy (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    ip              VARCHAR(45)     NOT NULL,
    identifikator   VARCHAR(255)    NOT NULL,
    cas             TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_ip_cas (ip, cas),
    INDEX idx_ident_cas (identifikator, cas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
