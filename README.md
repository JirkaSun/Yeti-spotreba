# Yeti Spotřeba

Evidence jízd, tankování a servisu pro více aut. Čisté PHP 8.3, MySQL/MariaDB (PDO), Tailwind přes CDN.

## Lokální spuštění
1. Zkopíruj `config.example.php` na `config.php` a vyplň údaje k DB (`APP_ENV` = `development`).
2. Vytvoř databázi `yeti_spotreba` a postupně spusť všechny soubory z `db/migrations/` (001 → 011).
3. Document root nastav na `public/` (Laravel Herd: http://yeti-spotreba.test).

## Role
- `admin` – spravuje uživatele a auta, upravuje všechny záznamy.
- `ridic` – zakládá a upravuje jen vlastní jízdy, tankování a servis.

## Bezpečnost
- CSRF token se automaticky vkládá do všech POST formulářů (`public/index.php`) a ověřuje v routeru.
- Přihlášení je omezeno na 5 neúspěšných pokusů za 15 minut (tabulka `login_pokusy`).
- Session cookie má `HttpOnly`, `SameSite=Lax` a `Secure` na HTTPS.

## Nasazení (WebGlobe)
Viz `CLAUDE.md`. V `config.php` nastav `APP_ENV=production` a před spuštěním importuj všechny migrace.
