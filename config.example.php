<?php

declare(strict_types=1);

// Zkopíruj tento soubor jako config.php a vyplň hodnoty
define('APP_ENV', 'production'); // 'development' nebo 'production'

if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Databáze
define('DB_HOST',   '127.0.0.1');
define('DB_PORT',   '3306');
define('DB_SOCKET', '');          // DBngin lokálně: '/tmp/mariadb_3306.sock'
define('DB_NAME',   'yeti_spotreba');
define('DB_USER',   '');
define('DB_PASS',   '');
define('DB_CHARSET','utf8mb4');

// Aplikace
define('APP_NAME', 'Yeti Spotřeba');
define('APP_URL',  'https://vasadomena.cz');
