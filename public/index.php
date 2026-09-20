<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/uzivatele.php';
require_once __DIR__ . '/../src/auta.php';
require_once __DIR__ . '/../src/dashboard.php';
require_once __DIR__ . '/../src/tankovani.php';
require_once __DIR__ . '/../src/jizdy.php';
require_once __DIR__ . '/../src/servis.php';

// ── Router ────────────────────────────────────────────────────────────────────
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Routovací tabulka: [method, regex pattern, handler]
$routes = [
    ['GET',  '/',                              'handleHome'],
    ['GET',  '/dashboard',                     'handleDashboard'],
    ['GET',  '/login',                         'handleLoginForm'],
    ['POST', '/login',                         'handleLoginPost'],
    ['GET',  '/logout',                        'handleLogout'],
    ['GET',  '/uzivatele',                     'handleUzivateleSeznam'],
    ['GET',  '/uzivatele/novy',                'handleUzivateleNovy'],
    ['POST', '/uzivatele/novy',                'handleUzivateleNovyPost'],
    ['GET',  '/uzivatele/(\d+)/upravit',       'handleUzivateleUpravit'],
    ['POST', '/uzivatele/(\d+)/upravit',       'handleUzivateleUpravitPost'],
    ['POST', '/uzivatele/(\d+)/smazat',        'handleUzivateleSmazat'],
    ['GET',  '/auta',                          'handleAutaSeznam'],
    ['GET',  '/auta/nove',                     'handleAutaNove'],
    ['POST', '/auta/nove',                     'handleAutaNovePost'],
    ['GET',  '/auta/(\d+)/upravit',            'handleAutaUpravit'],
    ['POST', '/auta/(\d+)/upravit',            'handleAutaUpravitPost'],
    ['POST', '/auta/(\d+)/smazat',             'handleAutaSmazat'],
    ['GET',  '/tankovani',                     'handleTankovaníSeznam'],
    ['GET',  '/tankovani/nove',                'handleTankovaníNove'],
    ['POST', '/tankovani/nove',                'handleTankovaníNovePost'],
    ['GET',  '/tankovani/(\d+)/upravit',       'handleTankovaníUpravit'],
    ['POST', '/tankovani/(\d+)/upravit',       'handleTankovaníUpravitPost'],
    ['POST', '/tankovani/(\d+)/smazat',        'handleTankovaníSmazat'],
    ['GET',  '/jizdy',                         'handleJizdySeznam'],
    ['GET',  '/jizdy/nove',                    'handleJizdyNove'],
    ['POST', '/jizdy/nove',                    'handleJizdyNovePost'],
    ['GET',  '/jizdy/(\d+)/upravit',           'handleJizdyUpravit'],
    ['POST', '/jizdy/(\d+)/upravit',           'handleJizdyUpravitPost'],
    ['POST', '/jizdy/(\d+)/smazat',            'handleJizdySmazat'],
    ['GET',  '/servis',                        'handleServisSeznam'],
    ['GET',  '/servis/nove',                   'handleServisNove'],
    ['POST', '/servis/nove',                   'handleServisNovePost'],
    ['GET',  '/servis/(\d+)/upravit',          'handleServisUpravit'],
    ['POST', '/servis/(\d+)/upravit',          'handleServisUpravitPost'],
    ['POST', '/servis/(\d+)/smazat',           'handleServisSmazat'],
];

$matched = false;
foreach ($routes as [$routeMethod, $pattern, $handler]) {
    if ($method !== $routeMethod) continue;
    if (preg_match('#^' . $pattern . '$#u', $uri, $matches)) {
        $matched = true;
        call_user_func($handler, ...array_slice($matches, 1));
        break;
    }
}

if (!$matched) {
    http_response_code(404);
    renderPage('404 — Stránka nenalezena', fn() => <<<HTML
        <div class="text-center py-24">
            <p class="text-8xl font-black text-indigo-200">404</p>
            <p class="mt-4 text-xl text-slate-500">Stránka nenalezena.</p>
            <a href="/" class="mt-8 inline-block btn-primary">← Zpět na úvod</a>
        </div>
    HTML);
}

// ── Handlery ──────────────────────────────────────────────────────────────────
function handleHome(): void
{
    renderPage('Úvod', function () {
        try {
            db()->query('SELECT 1');
            $dbStatus = '<span class="text-emerald-600 font-semibold">✓ Připojeno</span>';
        } catch (PDOException $e) {
            $dbStatus = '<span class="text-red-500 font-semibold">✗ ' . htmlspecialchars($e->getMessage()) . '</span>';
        }

        $env     = APP_ENV === 'development' ? '⚙ Vývoj' : '🚀 Produkce';
        $phpVer  = PHP_VERSION;

        echo <<<HTML
        <div class="text-center py-20">
            <div class="inline-flex items-center gap-3 mb-6">
                <span class="text-5xl">🧊</span>
                <h1 class="text-5xl font-black text-slate-800 tracking-tight">Yeti Spotřeba</h1>
            </div>
            <p class="mt-4 text-xl text-slate-500 max-w-xl mx-auto">
                Evidence jízd, tankování a servisu pro více aut na jednom místě.
            </p>
            <div class="mt-10 flex justify-center gap-4">
                <a href="/dashboard" class="btn-primary">Přejít do aplikace →</a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-3xl mx-auto mt-4">
            <div class="card">
                <p class="text-sm text-slate-400 uppercase tracking-wide">Databáze</p>
                <p class="mt-1">$dbStatus</p>
            </div>
            <div class="card">
                <p class="text-sm text-slate-400 uppercase tracking-wide">Prostředí</p>
                <p class="mt-1 font-semibold text-amber-600">$env</p>
            </div>
            <div class="card">
                <p class="text-sm text-slate-400 uppercase tracking-wide">PHP verze</p>
                <p class="mt-1 font-semibold text-slate-700">$phpVer</p>
            </div>
        </div>
        HTML;
    });
}


function handleLoginForm(): void
{
    if (auth() !== null) {
        header('Location: /');
        exit;
    }

    $chyba = $_SESSION['login_chyba'] ?? null;
    unset($_SESSION['login_chyba']);

    renderLoginPage(function () use ($chyba) {
        $chybaTpl = $chyba
            ? '<p class="text-sm text-red-500 text-center">' . htmlspecialchars($chyba) . '</p>'
            : '';
        echo <<<HTML
        <div class="min-h-screen flex items-center justify-center bg-slate-50">
            <div class="w-full max-w-sm">
                <div class="text-center mb-8">
                    <span class="text-5xl">🧊</span>
                    <h1 class="mt-3 text-2xl font-black text-slate-800">Yeti Spotřeba</h1>
                    <p class="text-sm text-slate-400 mt-1">Přihlaste se ke svému účtu</p>
                </div>
                <div class="card">
                    $chybaTpl
                    <form method="POST" action="/login" class="space-y-4 mt-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">E-mail, přezdívka nebo telefon</label>
                            <input type="text" name="identifikator" required autofocus autocomplete="username"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Heslo</label>
                            <input type="password" name="heslo" required
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        </div>
                        <button type="submit" class="btn-primary w-full text-center">
                            Přihlásit se →
                        </button>
                    </form>
                </div>
            </div>
        </div>
        HTML;
    });
}

function handleLoginPost(): void
{
    sessionStart();
    $identifikator = trim($_POST['identifikator'] ?? '');
    $heslo         = $_POST['heslo'] ?? '';

    if (login($identifikator, $heslo)) {
        header('Location: /');
        exit;
    }

    $_SESSION['login_chyba'] = 'Nesprávné přihlašovací údaje.';
    header('Location: /login');
    exit;
}

function handleLogout(): void
{
    logout();
}

// ── Layout ────────────────────────────────────────────────────────────────────
function htmlHead(string $fullTitle): void
{
    ?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $fullTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        .btn-primary {
            @apply px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors shadow-sm;
        }
        .card {
            @apply bg-white rounded-2xl shadow-sm border border-slate-100 p-6;
        }
        .form-label {
            @apply block text-sm font-medium text-slate-600 mb-1;
        }
        .form-input {
            @apply w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white;
        }
    </style>
</head>
    <?php
}

function renderPage(string $title, callable $body): void
{
    $appName   = htmlspecialchars(APP_NAME);
    $fullTitle = htmlspecialchars($title) . ' — ' . $appName;

    htmlHead($fullTitle);
    ?>
<body class="bg-slate-50 min-h-screen font-sans antialiased">

    <?php require __DIR__ . '/../src/components/statusbar.php'; ?>

    <nav class="bg-white border-b border-slate-100 shadow-sm">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 font-black text-xl text-slate-800">
                <span>🧊</span> <?= $appName ?>
            </a>
            <div class="flex gap-6 text-sm font-medium text-slate-500">
                <a href="/dashboard" class="hover:text-indigo-600 transition-colors">Dashboard</a>
                <a href="/uzivatele" class="hover:text-indigo-600 transition-colors">Uživatelé</a>
                <a href="/auta" class="hover:text-indigo-600 transition-colors">Auta</a>
                <a href="/jizdy" class="hover:text-indigo-600 transition-colors">Jízdy</a>
                <a href="/tankovani" class="hover:text-indigo-600 transition-colors">Tankování</a>
                <a href="/servis" class="hover:text-indigo-600 transition-colors">Servis</a>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-6 py-12">
        <?php $body(); ?>
    </main>

    <footer class="text-center py-8 text-sm text-slate-400">
        <?= $appName ?> &middot; PHP <?= PHP_VERSION ?>
    </footer>

</body>
</html>
    <?php
}

function renderLoginPage(callable $body): void
{
    $appName   = htmlspecialchars(APP_NAME);
    htmlHead($appName . ' — Přihlášení');
    ?>
<body class="bg-slate-50 font-sans antialiased">
    <?php $body(); ?>
</body>
</html>
    <?php
}
