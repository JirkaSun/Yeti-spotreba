<?php
// ── Stavová lišta ─────────────────────────────────────────────────────────────
// Zobrazuje info o přihlášeném uživateli + stavové zprávy aplikace.
// Přidávej další bloky do sekce označené "STAVOVÉ INFORMACE".

declare(strict_types=1);

$u = auth();
if ($u === null) return;

$jmeno     = htmlspecialchars($u['jmeno'] . ' ' . $u['prijmeni']);
$prezdivka = $u['prezdivka'] ? '(' . htmlspecialchars($u['prezdivka']) . ')' : '';
$cas       = date('H:i', $u['cas']);
$datum     = date('j. n. Y', $u['cas']);
?>
<div id="statusbar" class="bg-slate-800 text-slate-300 text-xs px-6 py-1.5 flex items-center justify-between gap-4 flex-wrap">

    <!-- Uživatel -->
    <div class="flex items-center gap-3">
        <span class="text-slate-500">●</span>
        <span class="text-white font-medium"><?= $jmeno ?></span>
        <?php if ($prezdivka): ?>
            <span class="text-slate-400"><?= $prezdivka ?></span>
        <?php endif; ?>
        <span class="text-slate-600">|</span>
        <span class="text-slate-400">přihlášen <?= $datum ?> v <?= $cas ?></span>
    </div>

    <!-- STAVOVÉ INFORMACE — sem přidávej bloky pro upozornění, notifikace atd. -->
    <div id="statusbar-info" class="flex items-center gap-4">
        <!-- placeholder: sem přijdou upozornění na servis, tankování atd. -->
    </div>

    <!-- Odhlášení -->
    <a href="/logout" class="text-slate-500 hover:text-red-400 transition-colors ml-auto">
        Odhlásit
    </a>

</div>
