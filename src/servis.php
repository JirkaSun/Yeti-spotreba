<?php

declare(strict_types=1);

// ── Helpers ───────────────────────────────────────────────────────────────────

function servisNajdi(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM servis WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function servisTypy(): array
{
    return [
        'olej'        => 'Výměna oleje',
        'brzdy'       => 'Brzdy',
        'pneu'        => 'Pneumatiky',
        'technicka'   => 'Technická prohlídka',
        'emisni'      => 'Emisní kontrola',
        'rozvody'     => 'Rozvody / řemen',
        'klimatizace' => 'Klimatizace',
        'ostatni'     => 'Ostatní',
    ];
}

function servisTypBadge(string $typ): string
{
    $mapy = [
        'olej'        => ['bg-amber-100 text-amber-700',   '🛢',  'Olej'],
        'brzdy'       => ['bg-red-100 text-red-700',        '🔴',  'Brzdy'],
        'pneu'        => ['bg-slate-100 text-slate-600',    '🔘',  'Pneu'],
        'technicka'   => ['bg-blue-100 text-blue-700',      '📋',  'STK'],
        'emisni'      => ['bg-green-100 text-green-700',    '🌿',  'Emise'],
        'rozvody'     => ['bg-purple-100 text-purple-700',  '⚙',  'Rozvody'],
        'klimatizace' => ['bg-cyan-100 text-cyan-700',      '❄',  'Klima'],
        'ostatni'     => ['bg-slate-100 text-slate-600',    '🔧',  'Ostatní'],
    ];
    [$cls, $ico, $label] = $mapy[$typ] ?? ['bg-slate-100 text-slate-600', '🔧', htmlspecialchars($typ)];
    return "<span class=\"inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {$cls}\">{$ico} {$label}</span>";
}

function servisFormHtml(array $auta, array $uzivatele, array $data = [], ?string $chyba = null): void
{
    $id        = (int)($data['id'] ?? 0);
    $action    = $id ? "/servis/{$id}/upravit" : '/servis/nove';
    $autoId    = (int)($data['auto_id']     ?? 0);
    $uzivId    = (int)($data['uzivatel_id'] ?? auth()['id']);
    $datum     = htmlspecialchars($data['datum']            ?? date('Y-m-d'));
    $kmStav    = htmlspecialchars((string)($data['km_stav'] ?? ''));
    $typ       = $data['typ'] ?? 'ostatni';
    $popis     = htmlspecialchars($data['popis']            ?? '');
    $misto     = htmlspecialchars($data['servisni_misto']   ?? '');
    $cena      = htmlspecialchars((string)($data['cena']    ?? ''));
    $dalsiKm   = htmlspecialchars((string)($data['dalsi_servis_km']    ?? ''));
    $dalsiDat  = htmlspecialchars($data['dalsi_servis_datum'] ?? '');

    $autaOpts = '';
    foreach ($auta as $a) {
        $sel   = $a['id'] == $autoId ? 'selected' : '';
        $label = htmlspecialchars("{$a['vyrobce']} {$a['model']}" . ($a['spz'] ? " ({$a['spz']})" : ''));
        $autaOpts .= "<option value=\"{$a['id']}\" $sel>$label</option>";
    }

    $uzivOpts = '';
    foreach ($uzivatele as $u) {
        $sel   = $u['id'] == $uzivId ? 'selected' : '';
        $label = htmlspecialchars("{$u['jmeno']} {$u['prijmeni']}");
        $uzivOpts .= "<option value=\"{$u['id']}\" $sel>$label</option>";
    }
    if (!isAdmin()) {
        $ridicJmeno = '';
        foreach ($uzivatele as $u) {
            if ($u['id'] == $uzivId) { $ridicJmeno = htmlspecialchars("{$u['jmeno']} {$u['prijmeni']}"); break; }
        }
        $ridicField = "<input type=\"hidden\" name=\"uzivatel_id\" value=\"{$uzivId}\"><span class=\"form-input block bg-slate-50 text-slate-500 cursor-default\">{$ridicJmeno}</span>";
    } else {
        $ridicField = "<select name=\"uzivatel_id\" required class=\"form-input\">$uzivOpts</select>";
    }

    $typOpts = '';
    foreach (servisTypy() as $v => $l) {
        $sel = $typ === $v ? 'selected' : '';
        $typOpts .= "<option value=\"$v\" $sel>$l</option>";
    }

    $chybaTpl = $chyba
        ? '<div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">' . htmlspecialchars($chyba) . '</div>'
        : '';

    echo <<<HTML
    $chybaTpl
    <form method="POST" action="$action" class="space-y-5">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Auto</label>
                <select name="auto_id" required class="form-input">
                    <option value="">— vyberte auto —</option>
                    $autaOpts
                </select>
            </div>
            <div>
                <label class="form-label">Technik / Řidič</label>
                $ridicField
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="form-label">Datum servisu</label>
                <input type="date" name="datum" value="$datum" required class="form-input">
            </div>
            <div>
                <label class="form-label">Stav tachometru <span class="text-slate-400 text-xs font-normal">(km)</span></label>
                <input type="number" name="km_stav" value="$kmStav" required min="0" class="form-input">
            </div>
            <div>
                <label class="form-label">Typ servisu</label>
                <select name="typ" required class="form-input">$typOpts</select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Servisní místo <span class="text-slate-400 text-xs font-normal">(nepovinné)</span></label>
                <input type="text" name="servisni_misto" value="$misto" maxlength="200" class="form-input"
                       placeholder="Např. Autoservis Novák, Praha">
            </div>
            <div>
                <label class="form-label">Cena Kč <span class="text-slate-400 text-xs font-normal">(nepovinné)</span></label>
                <input type="number" name="cena" value="$cena" step="0.01" min="0" class="form-input">
            </div>
        </div>

        <div>
            <label class="form-label">Popis / poznámka <span class="text-slate-400 text-xs font-normal">(nepovinné)</span></label>
            <textarea name="popis" rows="3" class="form-input resize-none"
                      placeholder="Co bylo vyměněno, jaký olej, jaký problém byl opraven…">$popis</textarea>
        </div>

        <div class="card bg-slate-50 border-slate-200 space-y-4">
            <p class="text-sm font-semibold text-slate-600">Příští servis <span class="font-normal text-slate-400">(nepovinné)</span></p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="form-label">Km do příštího servisu</label>
                    <input type="number" name="dalsi_servis_km" value="$dalsiKm" min="0" class="form-input"
                           placeholder="Např. 310000">
                </div>
                <div>
                    <label class="form-label">Datum příštího servisu</label>
                    <input type="date" name="dalsi_servis_datum" value="$dalsiDat" class="form-input">
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn-primary">Uložit záznam</button>
            <a href="/servis" class="text-sm text-slate-400 hover:text-slate-600">Zrušit</a>
        </div>
    </form>
    HTML;
}

// ── Handlery ─────────────────────────────────────────────────────────────────

function handleServisSeznam(): void
{
    authRequired();

    $zaznamy = db()->query(
        'SELECT s.*, a.vyrobce, a.model, a.spz, u.jmeno, u.prijmeni
         FROM servis s
         JOIN auta a ON a.id = s.auto_id
         JOIN uzivatele u ON u.id = s.uzivatel_id
         ORDER BY s.datum DESC, s.id DESC
         LIMIT 200'
    )->fetchAll();

    $ok    = flashGet('ok');
    $err   = flashGet('err');
    $okTpl  = $ok  ? "<div class=\"mb-6 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700\">{$ok}</div>"  : '';
    $errTpl = $err ? "<div class=\"mb-6 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600\">{$err}</div>" : '';

    renderPage('Servis', function () use ($zaznamy, $okTpl, $errTpl) {
        echo $okTpl;
        echo $errTpl;
        echo <<<HTML
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Servis</h1>
            <a href="/servis/nove" class="btn-primary">+ Přidat záznam</a>
        </div>
        HTML;

        if (!$zaznamy) {
            echo <<<HTML
            <div class="card text-center py-16 text-slate-400">
                <p class="text-4xl mb-3">🔧</p>
                <p class="font-medium">Žádné záznamy zatím.</p>
                <a href="/servis/nove" class="mt-4 inline-block text-indigo-500 hover:text-indigo-700 text-sm">Přidat první servis →</a>
            </div>
            HTML;
            return;
        }

        echo '<div class="card p-0 overflow-hidden"><table class="w-full text-sm">';
        echo '<thead class="bg-slate-50 border-b border-slate-100"><tr>';
        foreach (['Datum', 'Auto', 'Km stav', 'Typ', 'Servisní místo', 'Cena', 'Příštísrv.', ''] as $h) {
            echo "<th class=\"text-left px-4 py-3 font-semibold text-slate-500 text-xs\">$h</th>";
        }
        echo '</tr></thead><tbody class="divide-y divide-slate-50">';

        foreach ($zaznamy as $s) {
            $auto   = htmlspecialchars("{$s['vyrobce']} {$s['model']}" . ($s['spz'] ? " · {$s['spz']}" : ''));
            $datum  = date('j. n. Y', strtotime($s['datum']));
            $km     = number_format((int)$s['km_stav'], 0, ',', ' ') . ' km';
            $badge  = servisTypBadge($s['typ']);
            $misto  = $s['servisni_misto']
                ? '<span class="text-slate-600 text-xs">' . htmlspecialchars(mb_strimwidth($s['servisni_misto'], 0, 28, '…')) . '</span>'
                : '<span class="text-slate-300 text-xs">—</span>';
            $cena   = $s['cena'] !== null
                ? '<span class="font-semibold text-slate-700">' . number_format((float)$s['cena'], 0, ',', ' ') . ' Kč</span>'
                : '<span class="text-slate-300 text-xs">—</span>';

            $dalsi = '';
            if ($s['dalsi_servis_datum'] || $s['dalsi_servis_km']) {
                $parts = [];
                if ($s['dalsi_servis_km'])   $parts[] = number_format((int)$s['dalsi_servis_km'], 0, ',', ' ') . ' km';
                if ($s['dalsi_servis_datum']) $parts[] = date('j. n. Y', strtotime($s['dalsi_servis_datum']));
                $dalsi = '<span class="text-slate-400 text-xs">' . implode(' / ', $parts) . '</span>';
            } else {
                $dalsi = '<span class="text-slate-300 text-xs">—</span>';
            }

            $id      = (int)$s['id'];
            $vlastni = canEditRecord((int)$s['uzivatel_id']);
            $akceHtml = $vlastni ? <<<AKCE
                    <a href="/servis/{$id}/upravit" class="text-indigo-500 hover:text-indigo-700 text-xs font-medium">Upravit</a>
                    <form method="POST" action="/servis/{$id}/smazat" onsubmit="return confirm('Smazat tento záznam?')" class="inline">
                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs">Smazat</button>
                    </form>
                AKCE : '';

            echo <<<HTML
            <tr class="hover:bg-slate-50/60 transition-colors">
                <td class="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">$datum</td>
                <td class="px-4 py-3 font-medium text-slate-700">$auto</td>
                <td class="px-4 py-3 font-mono text-slate-600">$km</td>
                <td class="px-4 py-3">$badge</td>
                <td class="px-4 py-3">$misto</td>
                <td class="px-4 py-3">$cena</td>
                <td class="px-4 py-3">$dalsi</td>
                <td class="px-4 py-3 text-right space-x-3">$akceHtml</td>
            </tr>
            HTML;
        }

        echo '</tbody></table></div>';
    });
}

function handleServisNove(): void
{
    authRequired();
    [$auta, $uzivatele] = servisSelecty();
    renderPage('Přidat servis', function () use ($auta, $uzivatele) {
        echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Přidat servis</h1></div>';
        echo '<div class="card max-w-3xl">';
        servisFormHtml($auta, $uzivatele);
        echo '</div>';
    });
}

function handleServisNovePost(): void
{
    authRequired();
    if (!isAdmin()) {
        $_POST['uzivatel_id'] = (string)auth()['id'];
    }
    [$auta, $uzivatele] = servisSelecty();
    $chyba = servisUloz(0, $_POST);
    if ($chyba) {
        renderPage('Přidat servis', function () use ($auta, $uzivatele, $chyba) {
            echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Přidat servis</h1></div>';
            echo '<div class="card max-w-3xl">';
            servisFormHtml($auta, $uzivatele, $_POST, $chyba);
            echo '</div>';
        });
        return;
    }
    flash('ok', 'Servis byl uložen.');
    header('Location: /servis');
    exit;
}

function handleServisUpravit(string $id): void
{
    authRequired();
    $s = servisNajdi((int)$id);
    if (!$s) { http_response_code(404); return; }
    if (!canEditRecord((int)$s['uzivatel_id'])) {
        flash('err', 'Nemáte oprávnění upravovat tento servis.');
        header('Location: /servis');
        exit;
    }
    [$auta, $uzivatele] = servisSelecty();
    renderPage('Upravit servis', function () use ($s, $auta, $uzivatele) {
        echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Upravit servis</h1></div>';
        echo '<div class="card max-w-3xl">';
        servisFormHtml($auta, $uzivatele, $s);
        echo '</div>';
    });
}

function handleServisUpravitPost(string $id): void
{
    authRequired();
    $s = servisNajdi((int)$id);
    if (!$s) { http_response_code(404); return; }
    if (!canEditRecord((int)$s['uzivatel_id'])) {
        flash('err', 'Nemáte oprávnění upravovat tento servis.');
        header('Location: /servis');
        exit;
    }
    if (!isAdmin()) {
        $_POST['uzivatel_id'] = (string)$s['uzivatel_id'];
    }
    [$auta, $uzivatele] = servisSelecty();
    $chyba = servisUloz((int)$id, $_POST);
    if ($chyba) {
        $data = array_merge($s, $_POST, ['id' => $id]);
        renderPage('Upravit servis', function () use ($data, $auta, $uzivatele, $chyba) {
            echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Upravit servis</h1></div>';
            echo '<div class="card max-w-3xl">';
            servisFormHtml($auta, $uzivatele, $data, $chyba);
            echo '</div>';
        });
        return;
    }
    flash('ok', 'Záznam byl upraven.');
    header('Location: /servis');
    exit;
}

function handleServisSmazat(string $id): void
{
    authRequired();
    $s = servisNajdi((int)$id);
    if (!$s || !canEditRecord((int)$s['uzivatel_id'])) {
        flash('err', 'Nemáte oprávnění smazat tento záznam.');
        header('Location: /servis');
        exit;
    }
    db()->prepare('DELETE FROM servis WHERE id = ?')->execute([(int)$id]);
    flash('ok', 'Záznam byl smazán.');
    header('Location: /servis');
    exit;
}

// ── Sdílené ───────────────────────────────────────────────────────────────────

function servisSelecty(): array
{
    $auta      = db()->query('SELECT id, vyrobce, model, spz FROM auta ORDER BY vyrobce, model')->fetchAll();
    $uzivatele = db()->query('SELECT id, jmeno, prijmeni FROM uzivatele ORDER BY prijmeni, jmeno')->fetchAll();
    return [$auta, $uzivatele];
}

function servisUloz(int $id, array $post): ?string
{
    $autoId  = (int)($post['auto_id']      ?? 0);
    $uzivId  = (int)($post['uzivatel_id']  ?? 0);
    $datum   = trim($post['datum']         ?? '');
    $kmStav  = (int)($post['km_stav']      ?? 0);
    $typy    = array_keys(servisTypy());
    $typ     = in_array($post['typ'] ?? '', $typy, true) ? $post['typ'] : 'ostatni';
    $popis   = trim($post['popis']         ?? '') ?: null;
    $misto   = trim($post['servisni_misto'] ?? '') ?: null;
    $cena    = $post['cena'] !== '' ? (float)$post['cena'] : null;
    $dalsiKm = $post['dalsi_servis_km'] !== '' ? (int)$post['dalsi_servis_km'] : null;
    $dalsiDat = trim($post['dalsi_servis_datum'] ?? '') ?: null;

    if (!$autoId || !$uzivId) return 'Vyberte auto a technika.';
    if (!$datum)              return 'Datum servisu je povinné.';
    if ($kmStav <= 0)         return 'Stav tachometru musí být kladné číslo.';

    $cols = ['auto_id', 'uzivatel_id', 'datum', 'km_stav', 'typ', 'popis', 'servisni_misto', 'cena', 'dalsi_servis_km', 'dalsi_servis_datum'];
    $vals = [$autoId, $uzivId, $datum, $kmStav, $typ, $popis, $misto, $cena, $dalsiKm, $dalsiDat];

    if ($id === 0) {
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $colsSql = implode(',', $cols);
        db()->prepare("INSERT INTO servis ($colsSql) VALUES ($placeholders)")->execute($vals);
    } else {
        $setSql = implode(', ', array_map(fn($c) => "$c=?", $cols));
        $vals[] = $id;
        db()->prepare("UPDATE servis SET $setSql WHERE id=?")->execute($vals);
    }

    return null;
}
