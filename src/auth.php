<?php

declare(strict_types=1);

function sessionStart(): void
{
    if (session_status() === PHP_SESSION_NONE) {
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
        return false;
    }

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
