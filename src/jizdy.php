<?php

declare(strict_types=1);

// ── Helpers ───────────────────────────────────────────────────────────────────

function jizdaNajdi(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM jizdy WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function ucelBadge(string $ucel): string
{
    return match($ucel) {
        'sluzebni' => '<span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">Služební</span>',
        'soukroma' => '<span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">Soukromá</span>',
        default    => htmlspecialchars($ucel),
    };
}

// Převod DB datetime nebo datetime-local na hodnotu pro <input type="datetime-local">
function dtLocal(string $val): string
{
    if (!$val) return '';
    return substr(str_replace(' ', 'T', $val), 0, 16);
}

function jizdaFormHtml(array $auta, array $uzivatele, array $data = [], ?string $chyba = null): void
{
    $id            = (int)($data['id'] ?? 0);
    $action        = $id ? "/jizdy/{$id}/upravit" : '/jizdy/nove';
    $autoId        = (int)($data['auto_id']      ?? 0);
    $uzivId        = (int)($data['uzivatel_id']  ?? auth()['id']);
    $datumOdjezdu  = dtLocal($data['datum_odjezdu']  ?? date('Y-m-d H:i'));
    $datumPrijezdu = dtLocal($data['datum_prijezdu'] ?? '');
    $kmStart       = htmlspecialchars((string)($data['km_start'] ?? ''));
    $kmKonec       = htmlspecialchars((string)($data['km_konec'] ?? ''));
    $odkud         = htmlspecialchars($data['odkud']    ?? '');
    $kam           = htmlspecialchars($data['kam']      ?? '');
    $ucel          = $data['ucel'] ?? 'sluzebni';
    $poznamka      = htmlspecialchars($data['poznamka'] ?? '');

    $autaOpts = '<option value="">— vyberte auto —</option>';
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
        $ridicJmeno   = '';
        foreach ($uzivatele as $u) {
            if ($u['id'] == $uzivId) { $ridicJmeno = htmlspecialchars("{$u['jmeno']} {$u['prijmeni']}"); break; }
        }
        $ridicField = "<input type=\"hidden\" name=\"uzivatel_id\" value=\"{$uzivId}\"><span class=\"form-input block bg-slate-50 text-slate-500 cursor-default\">{$ridicJmeno}</span>";
    } else {
        $ridicField = "<select name=\"uzivatel_id\" required class=\"form-input\">$uzivOpts</select>";
    }

    $sluzSel = $ucel === 'sluzebni' ? 'selected' : '';
    $soukSel = $ucel === 'soukroma' ? 'selected' : '';

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
                    $autaOpts
                </select>
            </div>
            <div>
                <label class="form-label">Řidič</label>
                $ridicField
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Datum a čas odjezdu</label>
                <input type="datetime-local" name="datum_odjezdu" value="$datumOdjezdu" required class="form-input">
            </div>
            <div>
                <label class="form-label">Datum a čas příjezdu <span class="text-slate-400 text-xs font-normal">(nepovinné)</span></label>
                <input type="datetime-local" name="datum_prijezdu" value="$datumPrijezdu" class="form-input">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Odkud</label>
                <input type="text" name="odkud" value="$odkud" required placeholder="např. Praha – Holešovice" class="form-input">
            </div>
            <div>
                <label class="form-label">Kam</label>
                <input type="text" name="kam" value="$kam" required placeholder="např. Brno – centrum" class="form-input">
            </div>
        </div>

        <div class="grid grid-cols-3 gap-5">
            <div>
                <label class="form-label">Km při odjezdu</label>
                <input type="number" name="km_start" id="inp_km_start" value="$kmStart" required min="0" class="form-input">
            </div>
            <div>
                <label class="form-label">Km při příjezdu <span class="text-slate-400 text-xs font-normal">(nepovinné)</span></label>
                <input type="number" name="km_konec" id="inp_km_konec" value="$kmKonec" min="0" class="form-input">
            </div>
            <div>
                <label class="form-label">Najeto</label>
                <input type="text" id="inp_najeto" readonly placeholder="—"
                       class="form-input bg-slate-50 text-slate-500 cursor-default">
            </div>
        </div>

        <div>
            <label class="form-label">Účel jízdy</label>
            <select name="ucel" class="form-input">
                <option value="sluzebni" $sluzSel>Služební</option>
                <option value="soukroma" $soukSel>Soukromá</option>
            </select>
        </div>

        <div>
            <label class="form-label">Poznámka</label>
            <textarea name="poznamka" rows="2" class="form-input resize-none">$poznamka</textarea>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn-primary">Uložit jízdu</button>
            <a href="/jizdy" class="text-sm text-slate-400 hover:text-slate-600">Zrušit</a>
        </div>
    </form>

    <script>
    function prepocitatNajeto() {
        const s  = parseInt(document.getElementById('inp_km_start').value, 10);
        const k  = parseInt(document.getElementById('inp_km_konec').value, 10);
        const el = document.getElementById('inp_najeto');
        el.value = (!isNaN(s) && !isNaN(k) && k > s) ? (k - s).toLocaleString('cs-CZ') + ' km' : '';
    }
    document.getElementById('inp_km_start').addEventListener('input', prepocitatNajeto);
    document.getElementById('inp_km_konec').addEventListener('input', prepocitatNajeto);
    prepocitatNajeto();
    </script>
    HTML;
}

// ── Handlery ─────────────────────────────────────────────────────────────────

function handleJizdySeznam(): void
{
    authRequired();

    $jizdy = db()->query(
        'SELECT j.*, a.vyrobce, a.model, a.spz, u.jmeno, u.prijmeni
         FROM jizdy j
         JOIN auta a ON a.id = j.auto_id
         JOIN uzivatele u ON u.id = j.uzivatel_id
         ORDER BY j.datum_odjezdu DESC
         LIMIT 200'
    )->fetchAll();

    $ok    = flashGet('ok');
    $err   = flashGet('err');
    $okTpl  = $ok  ? "<div class=\"mb-6 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700\">{$ok}</div>"  : '';
    $errTpl = $err ? "<div class=\"mb-6 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600\">{$err}</div>" : '';

    renderPage('Jízdy', function () use ($jizdy, $okTpl, $errTpl) {
        echo $okTpl;
        echo $errTpl;
        echo <<<HTML
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Jízdy</h1>
            <a href="/jizdy/nove" class="btn-primary">+ Přidat jízdu</a>
        </div>
        HTML;

        if (!$jizdy) {
            echo <<<HTML
            <div class="card text-center py-16 text-slate-400">
                <p class="text-4xl mb-3">🛣️</p>
                <p class="font-medium">Žádné jízdy zatím.</p>
                <a href="/jizdy/nove" class="mt-4 inline-block text-indigo-500 hover:text-indigo-700 text-sm">Přidat první jízdu →</a>
            </div>
            HTML;
            return;
        }

        echo '<div class="card p-0 overflow-hidden"><table class="w-full text-sm">';
        echo '<thead class="bg-slate-50 border-b border-slate-100"><tr>';
        foreach (['Odjezd', 'Auto', 'Řidič', 'Trasa', 'Účel', 'Najeto', ''] as $h) {
            echo "<th class=\"text-left px-4 py-3 font-semibold text-slate-500 text-xs\">$h</th>";
        }
        echo '</tr></thead><tbody class="divide-y divide-slate-50">';

        foreach ($jizdy as $j) {
            $auto   = htmlspecialchars("{$j['vyrobce']} {$j['model']}" . ($j['spz'] ? " · {$j['spz']}" : ''));
            $ridic  = htmlspecialchars("{$j['jmeno']} {$j['prijmeni']}");
            $datum  = date('j. n. Y H:i', strtotime($j['datum_odjezdu']));
            $odkud  = htmlspecialchars($j['odkud']);
            $kam    = htmlspecialchars($j['kam']);
            $badge  = ucelBadge($j['ucel']);
            $najeto = ($j['km_konec'] !== null && $j['km_start'] !== null)
                ? number_format((int)$j['km_konec'] - (int)$j['km_start'], 0, ',', ' ') . ' km'
                : '<span class="text-slate-300">—</span>';
            $id      = (int)$j['id'];
            $vlastni = canEditRecord((int)$j['uzivatel_id']);
            $akceHtml = $vlastni ? <<<AKCE
                    <a href="/jizdy/{$id}/upravit" class="text-indigo-500 hover:text-indigo-700 text-xs font-medium">Upravit</a>
                    <form method="POST" action="/jizdy/{$id}/smazat"
                          onsubmit="return confirm('Opravdu smazat tuto jízdu?')" class="inline">
                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs">Smazat</button>
                    </form>
                AKCE : '';

            echo <<<HTML
            <tr class="hover:bg-slate-50/60 transition-colors">
                <td class="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">$datum</td>
                <td class="px-4 py-3 font-medium text-slate-700">$auto</td>
                <td class="px-4 py-3 text-slate-500 text-xs">$ridic</td>
                <td class="px-4 py-3">
                    <span class="text-slate-700">$odkud</span>
                    <span class="text-slate-300 mx-1">→</span>
                    <span class="text-slate-700">$kam</span>
                </td>
                <td class="px-4 py-3">$badge</td>
                <td class="px-4 py-3 font-semibold text-slate-600">$najeto</td>
                <td class="px-4 py-3 text-right space-x-3">$akceHtml</td>
            </tr>
            HTML;
        }

        echo '</tbody></table></div>';
    });
}

function handleJizdyNove(): void
{
    authRequired();
    [$auta, $uzivatele] = jizdaSelecty();
    renderPage('Přidat jízdu', function () use ($auta, $uzivatele) {
        echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Přidat jízdu</h1></div>';
        echo '<div class="card max-w-2xl">';
        jizdaFormHtml($auta, $uzivatele);
        echo '</div>';
    });
}

function handleJizdyNovePost(): void
{
    authRequired();
    if (!isAdmin()) {
        $_POST['uzivatel_id'] = (string)auth()['id'];
    }
    [$auta, $uzivatele] = jizdaSelecty();
    $chyba = jizdaUloz(0, $_POST);
    if ($chyba) {
        renderPage('Přidat jízdu', function () use ($auta, $uzivatele, $chyba) {
            echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Přidat jízdu</h1></div>';
            echo '<div class="card max-w-2xl">';
            jizdaFormHtml($auta, $uzivatele, $_POST, $chyba);
            echo '</div>';
        });
        return;
    }
    flash('ok', 'Jízda byla uložena.');
    header('Location: /jizdy');
    exit;
}

function handleJizdyUpravit(string $id): void
{
    authRequired();
    $j = jizdaNajdi((int)$id);
    if (!$j) { http_response_code(404); return; }
    if (!canEditRecord((int)$j['uzivatel_id'])) {
        flash('err', 'Nemáte oprávnění upravovat tuto jízdu.');
        header('Location: /jizdy');
        exit;
    }
    [$auta, $uzivatele] = jizdaSelecty();
    renderPage('Upravit jízdu', function () use ($j, $auta, $uzivatele) {
        echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Upravit jízdu</h1></div>';
        echo '<div class="card max-w-2xl">';
        jizdaFormHtml($auta, $uzivatele, $j);
        echo '</div>';
    });
}

function handleJizdyUpravitPost(string $id): void
{
    authRequired();
    $j = jizdaNajdi((int)$id);
    if (!$j) { http_response_code(404); return; }
    if (!canEditRecord((int)$j['uzivatel_id'])) {
        flash('err', 'Nemáte oprávnění upravovat tuto jízdu.');
        header('Location: /jizdy');
        exit;
    }
    if (!isAdmin()) {
        $_POST['uzivatel_id'] = (string)$j['uzivatel_id'];
    }
    [$auta, $uzivatele] = jizdaSelecty();
    $chyba = jizdaUloz((int)$id, $_POST);
    if ($chyba) {
        $data = array_merge($j, $_POST, ['id' => $id]);
        renderPage('Upravit jízdu', function () use ($data, $auta, $uzivatele, $chyba) {
            echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Upravit jízdu</h1></div>';
            echo '<div class="card max-w-2xl">';
            jizdaFormHtml($auta, $uzivatele, $data, $chyba);
            echo '</div>';
        });
        return;
    }
    flash('ok', 'Jízda byla upravena.');
    header('Location: /jizdy');
    exit;
}

function handleJizdySmazat(string $id): void
{
    authRequired();
    $j = jizdaNajdi((int)$id);
    if (!$j || !canEditRecord((int)$j['uzivatel_id'])) {
        flash('err', 'Nemáte oprávnění smazat tuto jízdu.');
        header('Location: /jizdy');
        exit;
    }
    db()->prepare('DELETE FROM jizdy WHERE id = ?')->execute([(int)$id]);
    flash('ok', 'Jízda byla smazána.');
    header('Location: /jizdy');
    exit;
}

// ── Sdílené ───────────────────────────────────────────────────────────────────

function jizdaSelecty(): array
{
    $auta      = db()->query('SELECT id, vyrobce, model, spz FROM auta ORDER BY vyrobce, model')->fetchAll();
    $uzivatele = db()->query('SELECT id, jmeno, prijmeni FROM uzivatele ORDER BY prijmeni, jmeno')->fetchAll();
    return [$auta, $uzivatele];
}

function jizdaUloz(int $id, array $post): ?string
{
    $autoId        = (int)($post['auto_id']     ?? 0);
    $uzivId        = (int)($post['uzivatel_id'] ?? 0);
    $datumOdjezdu  = trim($post['datum_odjezdu']  ?? '');
    $datumPrijezdu = trim($post['datum_prijezdu'] ?? '') ?: null;
    $odkud         = trim($post['odkud'] ?? '');
    $kam           = trim($post['kam']   ?? '');
    $ucel          = in_array($post['ucel'] ?? '', ['sluzebni', 'soukroma'], true) ? $post['ucel'] : 'sluzebni';
    $poznamka      = trim($post['poznamka'] ?? '') ?: null;

    if (!$autoId || !$uzivId) return 'Vyberte auto a řidiče.';
    if (!$datumOdjezdu)       return 'Datum odjezdu je povinné.';
    if (!$odkud || !$kam)     return 'Odkud a Kam jsou povinná pole.';
    if (!isset($post['km_start']) || $post['km_start'] === '') return 'Km při odjezdu je povinné.';

    $kmStart = (int)$post['km_start'];
    $kmKonec = ($post['km_konec'] !== '' && $post['km_konec'] !== null)
        ? (int)$post['km_konec']
        : null;

    if ($kmKonec !== null && $kmKonec <= $kmStart) {
        return 'Km při příjezdu musí být větší než km při odjezdu.';
    }

    $datumOdjezdDb  = date('Y-m-d H:i:s', strtotime($datumOdjezdu));
    $datumPrijezdDb = $datumPrijezdu ? date('Y-m-d H:i:s', strtotime($datumPrijezdu)) : null;

    if ($id === 0) {
        $stmt = db()->prepare(
            'INSERT INTO jizdy (auto_id, uzivatel_id, datum_odjezdu, datum_prijezdu, km_start, km_konec, odkud, kam, ucel, poznamka)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([$autoId, $uzivId, $datumOdjezdDb, $datumPrijezdDb, $kmStart, $kmKonec, $odkud, $kam, $ucel, $poznamka]);
    } else {
        $stmt = db()->prepare(
            'UPDATE jizdy SET auto_id=?, uzivatel_id=?, datum_odjezdu=?, datum_prijezdu=?, km_start=?, km_konec=?, odkud=?, kam=?, ucel=?, poznamka=?
             WHERE id=?'
        );
        $stmt->execute([$autoId, $uzivId, $datumOdjezdDb, $datumPrijezdDb, $kmStart, $kmKonec, $odkud, $kam, $ucel, $poznamka, $id]);
    }

    return null;
}
