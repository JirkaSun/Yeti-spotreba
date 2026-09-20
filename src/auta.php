<?php

declare(strict_types=1);

// ── Helpers ───────────────────────────────────────────────────────────────────

function autoNajdi(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM auta WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function palivoBadge(string $palivo): string
{
    $map = [
        'benzin'  => ['bg-amber-100',   'text-amber-700',   'Benzín'],
        'nafta'   => ['bg-slate-100',   'text-slate-600',   'Nafta'],
        'lpg'     => ['bg-green-100',   'text-green-700',   'LPG'],
        'elektro' => ['bg-blue-100',    'text-blue-700',    'Elektro'],
        'hybrid'  => ['bg-teal-100',    'text-teal-700',    'Hybrid'],
    ];
    [$bg, $text, $label] = $map[$palivo] ?? ['bg-slate-100', 'text-slate-500', $palivo];
    return "<span class=\"inline-block px-2 py-0.5 rounded-full text-xs font-semibold $bg $text\">$label</span>";
}

function spzHistorieHtml(string $json): string
{
    $zaznamy = json_decode($json, true);
    if (!$zaznamy) return '';

    $html = '<ul class="text-xs text-slate-400 space-y-0.5 mt-1">';
    foreach (array_reverse($zaznamy) as $z) {
        $do = $z['do'] ?? 'dosud';
        $html .= "<li>{$z['spz']} <span class=\"text-slate-300\">({$z['od']} – $do)</span></li>";
    }
    return $html . '</ul>';
}

function autoFormHtml(array $data = [], ?string $chyba = null): void
{
    $id          = (int)($data['id'] ?? 0);
    $action      = $id ? "/auta/{$id}/upravit" : '/auta/nove';
    $vyrobce     = htmlspecialchars($data['vyrobce']      ?? '');
    $model       = htmlspecialchars($data['model']        ?? '');
    $rokVyroby   = htmlspecialchars((string)($data['rok_vyroby']   ?? ''));
    $obsahMotoru = htmlspecialchars((string)($data['obsah_motoru'] ?? ''));
    $vin         = htmlspecialchars($data['vin']          ?? '');
    $spz         = htmlspecialchars($data['spz']          ?? '');
    $barva       = htmlspecialchars($data['barva']        ?? '');
    $palivo      = $data['palivo'] ?? 'benzin';
    $spzHistorie = !empty($data['spz_historie']) ? spzHistorieHtml($data['spz_historie']) : '';

    $palivaOptions = '';
    foreach (['benzin' => 'Benzín', 'nafta' => 'Nafta', 'lpg' => 'LPG', 'elektro' => 'Elektro', 'hybrid' => 'Hybrid'] as $val => $lab) {
        $sel = $palivo === $val ? 'selected' : '';
        $palivaOptions .= "<option value=\"$val\" $sel>$lab</option>";
    }

    $rokMin  = 1970;
    $rokMax  = (int)date('Y') + 1;
    $spzHistorieTpl = $spzHistorie ? "<div class=\"mt-2\"><p class=\"text-xs text-slate-400 font-medium\">Historie SPZ:</p>$spzHistorie</div>" : '';

    $chybaTpl = $chyba
        ? '<div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">' . htmlspecialchars($chyba) . '</div>'
        : '';

    echo <<<HTML
    $chybaTpl
    <form method="POST" action="$action" class="space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Výrobce</label>
                <input type="text" name="vyrobce" value="$vyrobce" required placeholder="např. Škoda" class="form-input">
            </div>
            <div>
                <label class="form-label">Model</label>
                <input type="text" name="model" value="$model" required placeholder="např. Octavia" class="form-input">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="form-label">Rok výroby</label>
                <input type="number" name="rok_vyroby" value="$rokVyroby" required min="$rokMin" max="$rokMax" class="form-input">
            </div>
            <div>
                <label class="form-label">Obsah motoru <span class="text-slate-400 font-normal text-xs">(cm³)</span></label>
                <input type="number" name="obsah_motoru" value="$obsahMotoru" min="0" max="9999" placeholder="např. 1984" class="form-input">
            </div>
            <div>
                <label class="form-label">Palivo</label>
                <select name="palivo" class="form-input">$palivaOptions</select>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="form-label">SPZ</label>
                <input type="text" name="spz" value="$spz" placeholder="např. 1AB2345" class="form-input" style="text-transform:uppercase">
                $spzHistorieTpl
            </div>
            <div>
                <label class="form-label">VIN <span class="text-slate-400 font-normal text-xs">(17 znaků)</span></label>
                <input type="text" name="vin" value="$vin" maxlength="17" class="form-input" style="font-family:monospace">
            </div>
        </div>
        <div>
            <label class="form-label">Barva</label>
            <input type="text" name="barva" value="$barva" placeholder="např. Stříbrná metalíza" class="form-input">
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn-primary">Uložit auto</button>
            <a href="/auta" class="text-sm text-slate-400 hover:text-slate-600">Zrušit</a>
        </div>
    </form>
    HTML;
}

// ── Handlery ─────────────────────────────────────────────────────────────────

function handleAutaSeznam(): void
{
    authRequired();

    $auta = db()->query(
        'SELECT id, vyrobce, model, rok_vyroby, spz, palivo, barva FROM auta ORDER BY vyrobce, model'
    )->fetchAll();

    $ok    = flashGet('ok');
    $okTpl = $ok ? "<div class=\"mb-6 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700\">{$ok}</div>" : '';

    renderPage('Auta', function () use ($auta, $okTpl) {
        echo $okTpl;
        echo <<<HTML
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Auta</h1>
            <a href="/auta/nove" class="btn-primary">+ Přidat auto</a>
        </div>
        HTML;

        if (!$auta) {
            echo <<<HTML
            <div class="card text-center py-16 text-slate-400">
                <p class="text-4xl mb-3">🚗</p>
                <p class="font-medium">Zatím žádná auta.</p>
                <a href="/auta/nove" class="mt-4 inline-block text-indigo-500 hover:text-indigo-700 text-sm">Přidat první auto →</a>
            </div>
            HTML;
            return;
        }

        echo '<div class="card p-0 overflow-hidden"><table class="w-full text-sm"><thead class="bg-slate-50 border-b border-slate-100"><tr>';
        echo '<th class="text-left px-6 py-3 font-semibold text-slate-500">Vozidlo</th>';
        echo '<th class="text-left px-6 py-3 font-semibold text-slate-500">SPZ</th>';
        echo '<th class="text-left px-6 py-3 font-semibold text-slate-500">Palivo</th>';
        echo '<th class="text-left px-6 py-3 font-semibold text-slate-500">Barva</th>';
        echo '<th class="px-6 py-3"></th>';
        echo '</tr></thead><tbody class="divide-y divide-slate-50">';

        foreach ($auta as $a) {
            $nazev  = htmlspecialchars("{$a['vyrobce']} {$a['model']}");
            $rok    = htmlspecialchars($a['rok_vyroby'] ?? '');
            $spz    = $a['spz'] ? '<span class="font-mono font-semibold text-slate-700">' . htmlspecialchars($a['spz']) . '</span>' : '<span class="text-slate-300">—</span>';
            $badge  = palivoBadge($a['palivo']);
            $barva  = $a['barva'] ? htmlspecialchars($a['barva']) : '<span class="text-slate-300">—</span>';
            $id     = (int)$a['id'];

            echo <<<HTML
            <tr class="hover:bg-slate-50/60 transition-colors">
                <td class="px-6 py-4">
                    <p class="font-semibold text-slate-800">$nazev</p>
                    <p class="text-xs text-slate-400">$rok</p>
                </td>
                <td class="px-6 py-4">$spz</td>
                <td class="px-6 py-4">$badge</td>
                <td class="px-6 py-4 text-slate-500 text-xs">$barva</td>
                <td class="px-6 py-4 text-right space-x-4">
                    <a href="/auta/{$id}/upravit" class="text-indigo-500 hover:text-indigo-700 text-xs font-medium">Upravit</a>
                    <form method="POST" action="/auta/{$id}/smazat"
                          onsubmit="return confirm('Opravdu smazat $nazev?')" class="inline">
                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs">Smazat</button>
                    </form>
                </td>
            </tr>
            HTML;
        }

        echo '</tbody></table></div>';
    });
}

function handleAutaNove(): void
{
    authRequired();
    renderPage('Přidat auto', function () {
        echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Přidat auto</h1></div>';
        echo '<div class="card max-w-2xl">';
        autoFormHtml();
        echo '</div>';
    });
}

function handleAutaNovePost(): void
{
    authRequired();

    $chyba = autoUloz(0, $_POST);
    if ($chyba) {
        renderPage('Přidat auto', function () use ($chyba) {
            echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Přidat auto</h1></div>';
            echo '<div class="card max-w-2xl">';
            autoFormHtml($_POST, $chyba);
            echo '</div>';
        });
        return;
    }

    flash('ok', 'Auto bylo úspěšně přidáno.');
    header('Location: /auta');
    exit;
}

function handleAutaUpravit(string $id): void
{
    authRequired();
    $a = autoNajdi((int)$id);
    if (!$a) { http_response_code(404); return; }

    renderPage('Upravit auto', function () use ($a) {
        $nazev = htmlspecialchars("{$a['vyrobce']} {$a['model']}");
        echo "<div class=\"mb-6\"><h1 class=\"text-2xl font-bold text-slate-800\">Upravit: $nazev</h1></div>";
        echo '<div class="card max-w-2xl">';
        autoFormHtml($a);
        echo '</div>';
    });
}

function handleAutaUpravitPost(string $id): void
{
    authRequired();
    $a = autoNajdi((int)$id);
    if (!$a) { http_response_code(404); return; }

    $chyba = autoUloz((int)$id, $_POST, $a);
    if ($chyba) {
        $data = array_merge($a, $_POST, ['id' => $id]);
        renderPage('Upravit auto', function () use ($data, $chyba) {
            $nazev = htmlspecialchars(($data['vyrobce'] ?? '') . ' ' . ($data['model'] ?? ''));
            echo "<div class=\"mb-6\"><h1 class=\"text-2xl font-bold text-slate-800\">Upravit: $nazev</h1></div>";
            echo '<div class="card max-w-2xl">';
            autoFormHtml($data, $chyba);
            echo '</div>';
        });
        return;
    }

    flash('ok', 'Auto bylo úspěšně upraveno.');
    header('Location: /auta');
    exit;
}

function handleAutaSmazat(string $id): void
{
    authRequired();
    db()->prepare('DELETE FROM auta WHERE id = ?')->execute([(int)$id]);
    flash('ok', 'Auto bylo smazáno.');
    header('Location: /auta');
    exit;
}

// ── Logika uložení ────────────────────────────────────────────────────────────

function autoUloz(int $id, array $post, array $stavajici = []): ?string
{
    $vyrobce     = trim($post['vyrobce']      ?? '');
    $model       = trim($post['model']        ?? '');
    $rokVyroby   = (int)($post['rok_vyroby'] ?? 0);
    $obsahMotoru = strlen(trim($post['obsah_motoru'] ?? '')) ? (int)$post['obsah_motoru'] : null;
    $vin         = strtoupper(trim($post['vin'] ?? '')) ?: null;
    $spz         = strtoupper(trim($post['spz'] ?? '')) ?: null;
    $barva       = trim($post['barva'] ?? '') ?: null;
    $paliva      = ['benzin', 'nafta', 'lpg', 'elektro', 'hybrid'];
    $palivo      = in_array($post['palivo'] ?? '', $paliva, true) ? $post['palivo'] : 'benzin';

    if (!$vyrobce || !$model) return 'Výrobce a model jsou povinné.';
    if ($rokVyroby < 1970 || $rokVyroby > (int)date('Y') + 1) return 'Neplatný rok výroby.';
    if ($vin && strlen($vin) !== 17) return 'VIN musí mít přesně 17 znaků.';

    // Ověř unikátnost VIN
    if ($vin) {
        $stmt = db()->prepare('SELECT id FROM auta WHERE vin = ? AND id != ?');
        $stmt->execute([$vin, $id]);
        if ($stmt->fetch()) return 'Tento VIN je již použit u jiného auta.';
    }

    // Aktualizace SPZ historie při změně
    $spzHistorie = null;
    if ($id > 0 && $spz !== ($stavajici['spz'] ?: null)) {
        $historie = json_decode($stavajici['spz_historie'] ?? '[]', true) ?: [];
        if ($stavajici['spz']) {
            // Uzavři poslední záznam
            if ($historie && !isset(end($historie)['do'])) {
                $historie[array_key_last($historie)]['do'] = date('Y-m-d');
            }
            // Přidej starý záznam pokud history ještě neexistuje pro tuto SPZ
            $existuje = array_filter($historie, fn($z) => $z['spz'] === $stavajici['spz']);
            if (!$existuje) {
                $historie[] = ['spz' => $stavajici['spz'], 'od' => $stavajici['created_at'] ? date('Y-m-d', strtotime($stavajici['created_at'])) : date('Y-m-d'), 'do' => date('Y-m-d')];
            }
        }
        if ($spz) {
            $historie[] = ['spz' => $spz, 'od' => date('Y-m-d'), 'do' => null];
        }
        $spzHistorie = json_encode($historie, JSON_UNESCAPED_UNICODE);
    }

    if ($id === 0) {
        $initHistorie = $spz ? json_encode([['spz' => $spz, 'od' => date('Y-m-d'), 'do' => null]], JSON_UNESCAPED_UNICODE) : null;
        $stmt = db()->prepare(
            'INSERT INTO auta (vyrobce, model, rok_vyroby, obsah_motoru, vin, spz, spz_historie, palivo, barva) VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([$vyrobce, $model, $rokVyroby, $obsahMotoru, $vin, $spz, $initHistorie, $palivo, $barva]);
    } else {
        $cols = 'vyrobce=?, model=?, rok_vyroby=?, obsah_motoru=?, vin=?, spz=?, palivo=?, barva=?';
        $vals = [$vyrobce, $model, $rokVyroby, $obsahMotoru, $vin, $spz, $palivo, $barva];
        if ($spzHistorie !== null) {
            $cols .= ', spz_historie=?';
            $vals[] = $spzHistorie;
        }
        $vals[] = $id;
        db()->prepare("UPDATE auta SET $cols WHERE id=?")->execute($vals);
    }

    return null;
}
