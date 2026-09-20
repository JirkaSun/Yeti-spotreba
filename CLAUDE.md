# Yeti Spotřeba

Evidence jízd, tankování a servisu pro více aut.

## Stack
- Čisté PHP 8.3 bez frameworků
- MySQL / MariaDB 10.11 přes PDO
- Tailwind CSS přes CDN (žádný build pipeline)
- Lokální vývoj: Laravel Herd (Nginx), DBngin, http://yeti-spotreba.test
- Produkce: WebGlobe (Nginx, PHP 8.3)

## Struktura souborů
- `config.php` — konstant APP_ENV, DB_*, APP_NAME, APP_URL
- `db.php` — singleton `db(): PDO`, socket s fallback na TCP
- `public/index.php` — front controller: router + handlery + `renderPage()`

## Router (public/index.php)
- Pole `$routes`: `[method, uri, handler_function]`
- Handler je globální funkce, volá `renderPage(title, callable)`
- `renderPage()` vykreslí celý HTML layout včetně nav a footeru

## Databáze
- Žádný ORM, čisté prepared statements přes `db()`
- Všechny tabulky v DB `yeti_spotreba`
- Hlavní entita: `auta` (id, nazev, spz, ...)
- Záznamy: `jizdy`, `tankovani`, `servis`

## Konvence
- `declare(strict_types=1)` v každém souboru
- snake_case pro DB sloupce, camelCase pro PHP proměnné
- `htmlspecialchars()` na veškerý výstup z DB
- Prepared statements vždy — žádné raw queries s user inputem

## PHP binary (lokálně)
`/Users/jirka/Library/Application Support/Herd/bin/php83`

## Deployment na WebGlobe
- Nahrát: `public/`, `src/`, `db/`, `config.php`, `db.php`
- Document root nastavit na `public/`
- Upravit `config.php`: APP_ENV=production, DB_* hodnoty od WebGlobe, APP_URL na skutečnou doménu
- DB importovat přes phpMyAdmin ze SQL dumpu
