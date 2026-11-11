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

sessionStart();

// ── CSRF ──────────────────────────────────────────────────────────────────────
// Do každého <form method="POST"> v odpovědi se automaticky vloží skryté pole s tokenem.
ob_start(static function (string $html): string {
    $pole = '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrfToken()) . '">';
    return preg_replace('/(<form\b[^>]*\bmethod=["\']POST["\'][^>]*>)/i', '$1' . $pole, $html) ?? $html;
});

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

if ($method === 'POST' && !csrfOver()) {
    http_response_code(403);
    renderPage('403 — Neplatný požadavek', fn() => print(<<<HTML
        <div class="text-center py-24">
            <p class="text-8xl font-black text-indigo-200">403</p>
            <p class="mt-4 text-xl text-slate-500">Platnost formuláře vypršela nebo je požadavek neplatný. Zkuste to prosím znovu.</p>
            <a href="/" class="mt-8 inline-block btn-primary">← Zpět na úvod</a>
        </div>
    HTML));
    exit;
}

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
            $detail   = APP_ENV === 'development' ? ' ' . htmlspecialchars($e->getMessage()) : '';
            $dbStatus = '<span class="text-red-500 font-semibold">✗ Chyba připojení' . $detail . '</span>';
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
        <div class="flex items-center justify-center bg-slate-50 pt-8 pb-16">
            <div class="w-full max-w-sm">
                <div class="text-center mb-8">
                    <img src="/yeti-logo.png" alt="Yeti" class="h-10 w-auto mx-auto">
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

    if (loginPokusy($identifikator) >= LOGIN_MAX_POKUSU) {
        $_SESSION['login_chyba'] = 'Příliš mnoho neúspěšných pokusů. Zkuste to znovu za ' . LOGIN_OKNO_MIN . ' minut.';
        header('Location: /login');
        exit;
    }

    if (login($identifikator, $heslo)) {
        header('Location: /dashboard');
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
<html lang="cs" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $fullTitle ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
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
    <style>
        /* ── Světlý režim — statusbar ── */
        #statusbar                        { background-color: #e7d5c0 !important; }
        #statusbar *                      { color: #6b3a1f !important; }
        #statusbar a:hover                { color: #3d1a08 !important; }
        #statusbar .text-green-400        { color: #16a34a !important; }
        #statusbar .text-red-500          { color: #dc2626 !important; }

        /* ── Tmavý režim — modrá navy paleta ── */

        /* Pozadí */
        .dark body                        { background-color: #06101e !important; color: #c8daf0; }
        .dark .bg-slate-50                { background-color: #06101e !important; }
        .dark .bg-white                   { background-color: #0d1f38 !important; }
        .dark nav                         { background-color: #0a1a30 !important; border-color: #1a3555 !important; }
        .dark .card                       { background-color: #0d1f38 !important; border-color: #1a3555 !important; }
        .dark .bg-slate-800               { background-color: #040e1a !important; }
        .dark #statusbar                  { background-color: #0a2318 !important; }
        .dark #statusbar *                { color: #2dd4bf !important; }
        .dark #statusbar a:hover          { color: #99f6e4 !important; }
        .dark #statusbar .text-green-400  { color: #4ade80 !important; }
        .dark #statusbar .text-red-500    { color: #f87171 !important; }

        /* Šedé texty → modrobílé tóny */
        .dark .text-slate-800             { color: #e8f1fb !important; }
        .dark .text-slate-700             { color: #d4e4f5 !important; }
        .dark .text-slate-600             { color: #a8c4e0 !important; }
        .dark .text-slate-500             { color: #7ba5c8 !important; }
        .dark .text-slate-400             { color: #5585a8 !important; }
        .dark .text-slate-300             { color: #3d6a8c !important; }

        /* Akcentové barvy → různé odstíny modré */
        .dark .text-indigo-700, .dark .text-indigo-600, .dark .text-indigo-500 { color: #93c5fd !important; }
        .dark .text-blue-700,   .dark .text-blue-600                           { color: #60a5fa !important; }
        .dark .text-cyan-700,   .dark .text-cyan-600                           { color: #38bdf8 !important; }
        .dark .text-teal-700,   .dark .text-teal-600                           { color: #22d3ee !important; }
        .dark .text-emerald-700,.dark .text-emerald-600                        { color: #7dd3fc !important; }
        .dark .text-green-700,  .dark .text-green-600                          { color: #a5f3fc !important; }
        .dark .text-purple-700, .dark .text-purple-600                         { color: #a5b4fc !important; }
        .dark .text-amber-700,  .dark .text-amber-600, .dark .text-amber-500   { color: #bfdbfe !important; }
        .dark .text-red-700,    .dark .text-red-600,   .dark .text-red-500     { color: #7dd3fc !important; }
        .dark .text-red-400                                                    { color: #60a5fa !important; }

        /* Speciální — chybové / varovné zachovat aspoň trochu odlišně */
        .dark .text-red-400.font-semibold  { color: #f87171 !important; }

        /* Orámování a stíny */
        .dark .border-slate-100           { border-color: #1a3555 !important; }
        .dark .border-slate-200           { border-color: #1a3555 !important; }
        .dark .divide-slate-50 > * + *    { border-color: #1a3555 !important; }
        .dark .shadow-sm                  { box-shadow: 0 1px 4px rgba(0,0,0,0.5) !important; }

        /* Formuláře */
        .dark .form-input                 { background-color: #06101e !important; border-color: #1a3555 !important; color: #c8daf0 !important; }
        .dark .form-label                 { color: #7ba5c8 !important; }

        /* Footer, hover */
        .dark footer                      { color: #3d6a8c !important; }
        .dark .hover\:text-indigo-600:hover { color: #60a5fa !important; }
    </style>
    <script>
        (function () {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
        function toggleTheme() {
            const dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', dark ? 'dark' : 'light');
            updateThemeIcon();
        }
        function updateThemeIcon() {
            const dark = document.documentElement.classList.contains('dark');
            ['icon-sun', 'sb-icon-sun'].forEach(id => { const el = document.getElementById(id); if (el) el.classList.toggle('hidden', !dark); });
            ['icon-moon', 'sb-icon-moon'].forEach(id => { const el = document.getElementById(id); if (el) el.classList.toggle('hidden', dark); });
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
    </script>
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
                <img src="/yeti-logo.png" alt="Yeti" class="h-8 w-auto"> <?= $appName ?>
            </a>
            <div class="flex items-center gap-6 text-sm font-medium text-slate-500">
                <a href="/dashboard" class="hover:text-indigo-600 transition-colors">Dashboard</a>
                <a href="/uzivatele" class="hover:text-indigo-600 transition-colors">Uživatelé</a>
                <a href="/auta" class="hover:text-indigo-600 transition-colors">Auta</a>
                <a href="/jizdy" class="hover:text-indigo-600 transition-colors">Jízdy</a>
                <a href="/tankovani" class="hover:text-indigo-600 transition-colors">Tankování</a>
                <a href="/servis" class="hover:text-indigo-600 transition-colors">Servis</a>
                <button onclick="toggleTheme()" title="Přepnout režim"
                    class="ml-2 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                    id="theme-btn">
                    <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>
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
    <?php require __DIR__ . '/../src/components/statusbar.php'; ?>
    <?php $body(); ?>
</body>
</html>
    <?php
}
