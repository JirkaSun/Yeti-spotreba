<?php

declare(strict_types=1);

function sessionStart(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function auth(): ?array
{
    sessionStart();
    return $_SESSION['uzivatel'] ?? null;
}

function authRequired(): void
{
    if (auth() === null) {
        header('Location: /login');
        exit;
    }
}

const LOGIN_MAX_POKUSU = 5;
const LOGIN_OKNO_MIN   = 15;

/** Počet neúspěšných pokusů za poslední okno pro danou IP nebo identifikátor. */
function loginPokusy(string $identifikator): int
{
    try {
        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM login_pokusy
             WHERE cas > (NOW() - INTERVAL ' . LOGIN_OKNO_MIN . ' MINUTE)
               AND (ip = ? OR identifikator = ?)'
        );
        $stmt->execute([clientIp(), mb_strtolower(mb_substr($identifikator, 0, 255))]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException) {
        return 0; // tabulka chybí (nespuštěná migrace 011) — nezablokuj přihlášení
    }
}

function loginZaznamenejSelhani(string $identifikator): void
{
    try {
        db()->prepare('INSERT INTO login_pokusy (ip, identifikator) VALUES (?, ?)')
            ->execute([clientIp(), mb_strtolower(mb_substr($identifikator, 0, 255))]);
    } catch (PDOException) {
    }
}

function loginVymazPokusy(string $identifikator): void
{
    try {
        db()->prepare('DELETE FROM login_pokusy WHERE ip = ? OR identifikator = ?')
            ->execute([clientIp(), mb_strtolower(mb_substr($identifikator, 0, 255))]);
        db()->exec('DELETE FROM login_pokusy WHERE cas < (NOW() - INTERVAL 1 DAY)');
    } catch (PDOException) {
    }
}

function clientIp(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

// ── CSRF ──────────────────────────────────────────────────────────────────────
function csrfToken(): string
{
    sessionStart();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfOver(): bool
{
    $poslany = (string)($_POST['csrf'] ?? '');
    return $poslany !== '' && hash_equals(csrfToken(), $poslany);
}

function login(string $identifikator, string $password): bool
{
    $stmt = db()->prepare(
        'SELECT id, jmeno, prijmeni, prezdivka, heslo, role FROM uzivatele
         WHERE email = ?
            OR (prezdivka IS NOT NULL AND prezdivka = ?)
            OR (telefon   IS NOT NULL AND telefon   = ?)
         LIMIT 1'
    );
    $stmt->execute([$identifikator, $identifikator, $identifikator]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['heslo'])) {
        loginZaznamenejSelhani($identifikator);
        return false;
    }
    loginVymazPokusy($identifikator);

    sessionStart();
    session_regenerate_id(true);

    $_SESSION['uzivatel'] = [
        'id'        => $user['id'],
        'jmeno'     => $user['jmeno'],
        'prijmeni'  => $user['prijmeni'],
        'prezdivka' => $user['prezdivka'],
        'role'      => $user['role'],
        'cas'       => time(),
    ];

    // Zapiš čas posledního přihlášení
    db()->prepare('UPDATE uzivatele SET posledni_prihlaseni = NOW() WHERE id = ?')
        ->execute([$user['id']]);

    return true;
}

function logout(): void
{
    sessionStart();
    session_destroy();
    header('Location: /login');
    exit;
}

function flash(string $key, string $zprava): void
{
    sessionStart();
    $_SESSION['flash'][$key] = $zprava;
}

function flashGet(string $key): ?string
{
    sessionStart();
    $zprava = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $zprava;
}

function isAdmin(): bool
{
    $u = auth();
    return $u !== null && $u['role'] === 'admin';
}

function canEditRecord(int $ownerId): bool
{
    $u = auth();
    if ($u === null) return false;
    return $u['role'] === 'admin' || $u['id'] === $ownerId;
}
