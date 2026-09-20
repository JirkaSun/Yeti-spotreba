<?php

declare(strict_types=1);

function handleDashboard(): void
{
    authRequired();

    $db  = db();
    $u   = auth();
    $mes = (int)date('n');
    $rok = (int)date('Y');

    // ── Statistiky ────────────────────────────────────────────────────────────
    $pocetAut      = (int)$db->query('SELECT COUNT(*) FROM auta')->fetchColumn();
    $pocetUziv     = (int)$db->query('SELECT COUNT(*) FROM uzivatele')->fetchColumn();

    $statJizdy = $db->prepare(
        'SELECT COUNT(*) as pocet, COALESCE(SUM(km_konec - km_start), 0) as km
         FROM jizdy
         WHERE MONTH(datum_odjezdu) = ? AND YEAR(datum_odjezdu) = ? AND km_konec IS NOT NULL'
    );
    $statJizdy->execute([$mes, $rok]);
    $jizdy = $statJizdy->fetch();

    $statPalivo = $db->prepare(
        'SELECT COALESCE(SUM(celkova_cena), 0) as cena, COALESCE(SUM(litry), 0) as litry
         FROM tankovani
         WHERE MONTH(datum_cas) = ? AND YEAR(datum_cas) = ?'
    );
    $statPalivo->execute([$mes, $rok]);
    $palivo = $statPalivo->fetch();

    // ── Poslední jízdy ────────────────────────────────────────────────────────
    $posledniJizdy = $db->query(
        'SELECT j.*, a.vyrobce, a.model, a.spz,
                u.jmeno, u.prijmeni
         FROM jizdy j
         JOIN auta a ON a.id = j.auto_id
         JOIN uzivatele u ON u.id = j.uzivatel_id
         ORDER BY j.datum_odjezdu DESC LIMIT 5'
    )->fetchAll();

    // ── Upozornění na servis ──────────────────────────────────────────────────
    $servisUpozorneni = $db->query(
        'SELECT a.vyrobce, a.model, a.spz, s.typ,
                s.dalsi_servis_datum, s.dalsi_servis_km,
                (SELECT MAX(km_stav) FROM tankovani t WHERE t.auto_id = a.id) as aktualni_km
         FROM servis s
         JOIN auta a ON a.id = s.auto_id
         WHERE s.dalsi_servis_datum IS NOT NULL
           AND s.dalsi_servis_datum <= DATE_ADD(NOW(), INTERVAL 60 DAY)
         ORDER BY s.dalsi_servis_datum ASC LIMIT 5'
    )->fetchAll();

    renderPage('Dashboard', function () use ($u, $pocetAut, $pocetUziv, $jizdy, $palivo, $posledniJizdy, $servisUpozorneni) {
        $jmeno    = htmlspecialchars($u['jmeno']);
        $mesicNaz = ['', 'ledna', 'února', 'března', 'dubna', 'května', 'června',
                     'července', 'srpna', 'září', 'října', 'listopadu', 'prosince'];
        $datumDnes = date('j. ') . $mesicNaz[(int)date('n')] . date(' Y');
        $kmMesic   = number_format((int)$jizdy['km'], 0, ',', ' ');
        $palivoCena = number_format((float)$palivo['cena'], 0, ',', ' ');

        // ── Pozdrav ───────────────────────────────────────────────────────────
        echo <<<HTML
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Dobrý den, {$jmeno}!</h1>
                <p class="text-slate-400 text-sm mt-0.5">{$datumDnes}</p>
            </div>
        </div>
        HTML;

        // ── Stat karty ────────────────────────────────────────────────────────
        $stats = [
            ['🚗', $pocetAut,              'Auta',            'text-indigo-600',  '/auta'],
            ['👤', $pocetUziv,             'Uživatelé',       'text-slate-600',   '/uzivatele'],
            ['🛣️',  $jizdy['pocet'],        'Jízdy (měsíc)',   'text-emerald-600', null],
            ['📍', $kmMesic . ' km',       'Najeto (měsíc)',  'text-emerald-600', null],
            ['⛽', $palivoCena . ' Kč',    'Palivo (měsíc)',  'text-amber-600',   null],
        ];

        echo '<div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-8">';
        foreach ($stats as [$icon, $hodnota, $popis, $barva, $link]) {
            $wrap   = $link ? "href=\"$link\"" : '';
            $tag    = $link ? 'a' : 'div';
            $hover  = $link ? 'hover:border-indigo-200 hover:shadow-md transition-all cursor-pointer' : '';
            echo <<<HTML
            <{$tag} {$wrap} class="card text-center py-5 {$hover}">
                <p class="text-2xl mb-1">{$icon}</p>
                <p class="text-2xl font-black {$barva}">{$hodnota}</p>
                <p class="text-xs text-slate-400 mt-0.5">{$popis}</p>
            </{$tag}>
            HTML;
        }
        echo '</div>';

        // ── Spodní sekce ──────────────────────────────────────────────────────
        echo '<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">';

        // Poslední jízdy
        echo '<div class="lg:col-span-2 card p-0 overflow-hidden">';
        echo '<div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">';
        echo '<h2 class="font-semibold text-slate-700">Poslední jízdy</h2>';
        echo '<a href="/jizdy" class="text-xs text-indigo-500 hover:text-indigo-700">Všechny →</a>';
        echo '</div>';

        if (!$posledniJizdy) {
            echo '<div class="px-6 py-12 text-center text-slate-400"><p class="text-3xl mb-2">🛣️</p><p class="text-sm">Žádné jízdy zatím.</p></div>';
        } else {
            echo '<table class="w-full text-sm"><tbody class="divide-y divide-slate-50">';
            foreach ($posledniJizdy as $j) {
                $auto   = htmlspecialchars("{$j['vyrobce']} {$j['model']}");
                $spz    = $j['spz'] ? '<span class="font-mono text-xs text-slate-400">' . htmlspecialchars($j['spz']) . '</span>' : '';
                $ridic  = htmlspecialchars("{$j['jmeno']} {$j['prijmeni']}");
                $datum  = date('j. n.', strtotime($j['datum_odjezdu']));
                $km     = $j['km_konec'] ? number_format($j['km_konec'] - $j['km_start'], 0, ',', ' ') . ' km' : '—';
                $ucel   = $j['ucel'] === 'sluzebni'
                    ? '<span class="text-xs text-indigo-500">Služební</span>'
                    : '<span class="text-xs text-slate-400">Soukromá</span>';
                echo <<<HTML
                <tr class="hover:bg-slate-50/60">
                    <td class="px-6 py-3">
                        <p class="font-medium text-slate-800">{$auto} {$spz}</p>
                        <p class="text-xs text-slate-400">{$ridic}</p>
                    </td>
                    <td class="px-4 py-3 text-xs text-slate-400">{$datum}</td>
                    <td class="px-4 py-3">{$ucel}</td>
                    <td class="px-6 py-3 text-right font-semibold text-slate-600">{$km}</td>
                </tr>
                HTML;
            }
            echo '</tbody></table>';
        }
        echo '</div>';

        // Upozornění na servis
        echo '<div class="card p-0 overflow-hidden">';
        echo '<div class="px-6 py-4 border-b border-slate-100">';
        echo '<h2 class="font-semibold text-slate-700">Blížící se servis</h2>';
        echo '</div>';

        if (!$servisUpozorneni) {
            echo '<div class="px-6 py-12 text-center text-slate-400"><p class="text-3xl mb-2">✅</p><p class="text-sm">Vše v pořádku.</p></div>';
        } else {
            echo '<ul class="divide-y divide-slate-50">';
            foreach ($servisUpozorneni as $s) {
                $auto  = htmlspecialchars("{$s['vyrobce']} {$s['model']}");
                $spz   = $s['spz'] ? ' · ' . htmlspecialchars($s['spz']) : '';
                $typ   = htmlspecialchars($s['typ']);
                $datum = date('j. n. Y', strtotime($s['dalsi_servis_datum']));
                $dnu   = (int)ceil((strtotime($s['dalsi_servis_datum']) - time()) / 86400);
                $barvaDnu = $dnu <= 14 ? 'text-red-500' : 'text-amber-500';
                echo <<<HTML
                <li class="px-6 py-4">
                    <p class="font-medium text-slate-700 text-sm">{$auto}{$spz}</p>
                    <p class="text-xs text-slate-400">{$typ}</p>
                    <p class="text-xs {$barvaDnu} mt-1">📅 {$datum} (za {$dnu} dní)</p>
                </li>
                HTML;
            }
            echo '</ul>';
        }
        echo '</div>';

        echo '</div>'; // grid
    });
}
