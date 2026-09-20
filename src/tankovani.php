<?php

declare(strict_types=1);

// ── Helpers ───────────────────────────────────────────────────────────────────

function tankNajdi(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM tankovani WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function tankFormHtml(array $auta, array $uzivatele, array $data = [], ?string $chyba = null): void
{
    $id         = (int)($data['id'] ?? 0);
    $action     = $id ? "/tankovani/{$id}/upravit" : '/tankovani/nove';
    $autoId     = (int)($data['auto_id']     ?? 0);
    $uzivId     = (int)($data['uzivatel_id'] ?? auth()['id']);
    $datumCas   = htmlspecialchars($data['datum_cas']    ?? date('Y-m-d\TH:i'));
    $kmStav     = htmlspecialchars((string)($data['km_stav']      ?? ''));
    $litry      = htmlspecialchars((string)($data['litry']        ?? ''));
    $cenaLitr   = htmlspecialchars((string)($data['cena_za_litr'] ?? ''));
    $celkCena   = htmlspecialchars((string)($data['celkova_cena'] ?? ''));
    $palivo     = $data['palivo']    ?? 'benzin';
    $plnaNadrz  = ($data['plna_nadrz'] ?? 1) ? 'checked' : '';
    $poznamka   = htmlspecialchars($data['poznamka']   ?? '');
    $pumkaNazev = htmlspecialchars($data['pumpa_nazev'] ?? '');
    $pumpaLat   = htmlspecialchars((string)($data['pumpa_lat'] ?? ''));
    $pumpaLng   = htmlspecialchars((string)($data['pumpa_lng'] ?? ''));

    // Selecty pro auto a uživatele
    $autaOpts = '';
    foreach ($auta as $a) {
        $sel = $a['id'] == $autoId ? 'selected' : '';
        $label = htmlspecialchars("{$a['vyrobce']} {$a['model']}" . ($a['spz'] ? " ({$a['spz']})" : ''));
        $autaOpts .= "<option value=\"{$a['id']}\" $sel>$label</option>";
    }
    $uzivOpts = '';
    foreach ($uzivatele as $u) {
        $sel = $u['id'] == $uzivId ? 'selected' : '';
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

    $palivaOpts = '';
    foreach (['benzin' => 'Benzín', 'nafta' => 'Nafta', 'lpg' => 'LPG', 'elektro' => 'Elektro', 'hybrid' => 'Hybrid'] as $v => $l) {
        $sel = $palivo === $v ? 'selected' : '';
        $palivaOpts .= "<option value=\"$v\" $sel>$l</option>";
    }

    $chybaTpl = $chyba
        ? '<div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">' . htmlspecialchars($chyba) . '</div>'
        : '';

    // Veškerá PHP data pro JS mapu — přes json_encode, žádné proměnné v JS kódu
    $mapCfg = json_encode([
        'lat'    => $pumpaLat !== '' ? (float)$pumpaLat : 49.8175,
        'lng'    => $pumpaLng !== '' ? (float)$pumpaLng : 15.4730,
        'zoom'   => $pumpaLat !== '' ? 15 : 7,
        'hasLoc' => $pumpaLat !== '' && $pumpaLng !== '',
        'nazev'  => $pumkaNazev,
    ], JSON_UNESCAPED_UNICODE);

    echo '<script>const MAP_CFG = ' . $mapCfg . ';</script>';

    echo <<<HTML
    $chybaTpl
    <form method="POST" action="$action" id="tankForm" class="space-y-5">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Auto</label>
                <select name="auto_id" required class="form-input">
                    <option value="">— vyberte auto —</option>
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
                <label class="form-label">Datum a čas</label>
                <input type="datetime-local" name="datum_cas" value="$datumCas" required class="form-input">
            </div>
            <div>
                <label class="form-label">Stav tachometru <span class="text-slate-400 text-xs font-normal">(km)</span></label>
                <input type="number" name="km_stav" value="$kmStav" required min="0" class="form-input">
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-5">
            <div>
                <label class="form-label">Palivo</label>
                <select name="palivo" class="form-input">$palivaOpts</select>
            </div>
            <div>
                <label class="form-label">Litry</label>
                <input type="number" name="litry" value="$litry" step="0.01" min="0" required class="form-input"
                       id="inp_litry">
            </div>
            <div>
                <label class="form-label">Kč/litr</label>
                <input type="number" name="cena_za_litr" value="$cenaLitr" step="0.001" min="0" required class="form-input"
                       id="inp_cena_litr">
            </div>
            <div>
                <label class="form-label">Celkem Kč</label>
                <input type="number" name="celkova_cena" value="$celkCena" step="0.01" min="0" required class="form-input"
                       id="inp_celkem">
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="plna_nadrz" id="plna_nadrz" value="1" $plnaNadrz
                   class="w-4 h-4 accent-indigo-600">
            <label for="plna_nadrz" class="text-sm text-slate-600">Plná nádrž</label>
        </div>

        <!-- Benzínová pumpa — výběr z mapy -->
        <div class="space-y-3">
            <label class="form-label">Benzínová pumpa</label>

            <!-- Vyhledávání -->
            <div class="flex gap-2">
                <input type="text" id="mapSearch"
                       placeholder="Hledat adresu nebo název pumpy…"
                       class="form-input flex-1">
                <button type="button" onclick="mapHledat()"
                        class="px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-sm rounded-lg transition-colors">
                    Hledat
                </button>
                <button type="button" onclick="mapGps()"
                        title="Použít moji polohu"
                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm rounded-lg transition-colors">
                    📍
                </button>
            </div>

            <!-- Mapa -->
            <div id="mapa" class="w-full rounded-xl border border-slate-200 overflow-hidden" style="height:320px"></div>

            <!-- Výsledek výběru -->
            <div id="pumpaVybrana" class="hidden p-3 bg-indigo-50 border border-indigo-100 rounded-lg text-sm text-indigo-700 flex items-start gap-2">
                <span>📍</span>
                <span id="pumpaVybranaText"></span>
            </div>

            <!-- Skryté fieldy -->
            <input type="hidden" name="pumpa_nazev" id="pumpa_nazev" value="$pumkaNazev">
            <input type="hidden" name="pumpa_lat"   id="pumpa_lat"   value="$pumpaLat">
            <input type="hidden" name="pumpa_lng"   id="pumpa_lng"   value="$pumpaLng">
        </div>

        <div>
            <label class="form-label">Poznámka</label>
            <textarea name="poznamka" rows="2" class="form-input resize-none">$poznamka</textarea>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn-primary">Uložit záznam</button>
            <a href="/tankovani" class="text-sm text-slate-400 hover:text-slate-600">Zrušit</a>
        </div>
    </form>

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css">
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js"></script>

    <script>
    // ── Inicializace mapy ─────────────────────────────────────────────────────
    const mapa   = L.map('mapa').setView([MAP_CFG.lat, MAP_CFG.lng], MAP_CFG.zoom);
    const marker = L.marker([0, 0], { draggable: true });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19
    }).addTo(mapa);

    // Obnov marker pokud je uložená pozice
    if (MAP_CFG.hasLoc) {
        marker.setLatLng([MAP_CFG.lat, MAP_CFG.lng]).addTo(mapa);
        zobrazVybrano(MAP_CFG.nazev || 'Uložená poloha', MAP_CFG.lat, MAP_CFG.lng);
    }

    // Klik na mapu → umísti marker
    mapa.on('click', e => {
        nastavMarker(e.latlng.lat, e.latlng.lng);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
    });

    // Drag markeru → aktualizuj pozici
    marker.on('dragend', () => {
        const pos = marker.getLatLng();
        nastavPozici(pos.lat, pos.lng);
        reverseGeocode(pos.lat, pos.lng);
    });

    function nastavMarker(lat, lng) {
        if (!mapa.hasLayer(marker)) marker.addTo(mapa);
        marker.setLatLng([lat, lng]);
        nastavPozici(lat, lng);
    }

    function nastavPozici(lat, lng) {
        document.getElementById('pumpa_lat').value = lat.toFixed(7);
        document.getElementById('pumpa_lng').value = lng.toFixed(7);
    }

    function zobrazVybrano(nazev, lat, lng) {
        document.getElementById('pumpa_nazev').value   = nazev;
        document.getElementById('pumpaVybranaText').textContent = nazev;
        document.getElementById('pumpaVybrana').classList.remove('hidden');
    }

    // Reverse geocoding přes Nominatim
    function reverseGeocode(lat, lng) {
        fetch('https://nominatim.openstreetmap.org/reverse?lat=' + lat + '&lon=' + lng + '&format=json')
            .then(r => r.json())
            .then(d => {
                const nazev = d.display_name || (lat.toFixed(5) + ', ' + lng.toFixed(5));
                zobrazVybrano(nazev, lat, lng);
            })
            .catch(() => zobrazVybrano(lat.toFixed(5) + ', ' + lng.toFixed(5), lat, lng));
    }

    // Vyhledávání přes Nominatim
    function mapHledat() {
        const q = document.getElementById('mapSearch').value.trim();
        if (!q) return;
        fetch('https://nominatim.openstreetmap.org/search?q=' + encodeURIComponent(q) + '&format=json&limit=1&countrycodes=cz,sk')
            .then(r => r.json())
            .then(d => {
                if (!d.length) { alert('Adresa nenalezena.'); return; }
                const lat = parseFloat(d[0].lat);
                const lng = parseFloat(d[0].lon);
                mapa.setView([lat, lng], 16);
                nastavMarker(lat, lng);
                zobrazVybrano(d[0].display_name, lat, lng);
            });
    }

    // Enter ve vyhledávacím poli
    document.getElementById('mapSearch').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); mapHledat(); }
    });

    // GPS poloha
    function mapGps() {
        if (!navigator.geolocation) { alert('GPS není dostupná.'); return; }
        navigator.geolocation.getCurrentPosition(pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            mapa.setView([lat, lng], 16);
            nastavMarker(lat, lng);
            reverseGeocode(lat, lng);
        }, () => alert('Nepodařilo se získat polohu.'));
    }

    // Automatický výpočet celkové ceny
    function prepocitatCenu() {
        const litry  = parseFloat(document.getElementById('inp_litry').value)    || 0;
        const kczl   = parseFloat(document.getElementById('inp_cena_litr').value) || 0;
        if (litry && kczl) {
            document.getElementById('inp_celkem').value = (litry * kczl).toFixed(2);
        }
    }
    document.getElementById('inp_litry').addEventListener('input',      prepocitatCenu);
    document.getElementById('inp_cena_litr').addEventListener('input',  prepocitatCenu);
    </script>
    HTML;
}

// ── Handlery ─────────────────────────────────────────────────────────────────

function handleTankovaníSeznam(): void
{
    authRequired();

    $zaznamy = db()->query(
        'SELECT t.*, a.vyrobce, a.model, a.spz, u.jmeno, u.prijmeni
         FROM tankovani t
         JOIN auta a ON a.id = t.auto_id
         JOIN uzivatele u ON u.id = t.uzivatel_id
         ORDER BY t.datum_cas DESC
         LIMIT 100'
    )->fetchAll();

    $ok    = flashGet('ok');
    $err   = flashGet('err');
    $okTpl  = $ok  ? "<div class=\"mb-6 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700\">{$ok}</div>"  : '';
    $errTpl = $err ? "<div class=\"mb-6 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600\">{$err}</div>" : '';

    renderPage('Tankování', function () use ($zaznamy, $okTpl, $errTpl) {
        echo $okTpl;
        echo $errTpl;
        echo <<<HTML
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Tankování</h1>
            <a href="/tankovani/nove" class="btn-primary">+ Přidat záznam</a>
        </div>
        HTML;

        if (!$zaznamy) {
            echo <<<HTML
            <div class="card text-center py-16 text-slate-400">
                <p class="text-4xl mb-3">⛽</p>
                <p class="font-medium">Žádné záznamy zatím.</p>
                <a href="/tankovani/nove" class="mt-4 inline-block text-indigo-500 hover:text-indigo-700 text-sm">Přidat první tankování →</a>
            </div>
            HTML;
            return;
        }

        echo '<div class="card p-0 overflow-hidden"><table class="w-full text-sm">';
        echo '<thead class="bg-slate-50 border-b border-slate-100"><tr>';
        foreach (['Datum', 'Auto', 'Km stav', 'Palivo / Litry', 'Cena', 'Pumpa', ''] as $h) {
            echo "<th class=\"text-left px-4 py-3 font-semibold text-slate-500 text-xs\">$h</th>";
        }
        echo '</tr></thead><tbody class="divide-y divide-slate-50">';

        foreach ($zaznamy as $t) {
            $auto    = htmlspecialchars("{$t['vyrobce']} {$t['model']}" . ($t['spz'] ? " · {$t['spz']}" : ''));
            $datum   = date('j. n. Y H:i', strtotime($t['datum_cas']));
            $km      = number_format((int)$t['km_stav'], 0, ',', ' ') . ' km';
            $badge   = palivoBadge($t['palivo']);
            $litry   = number_format((float)$t['litry'], 2, ',', ' ') . ' l';
            $cena    = number_format((float)$t['celkova_cena'], 2, ',', ' ') . ' Kč';
            $pumpa   = $t['pumpa_nazev']
                ? '<span title="' . htmlspecialchars($t['pumpa_nazev']) . '" class="truncate max-w-[140px] inline-block align-middle text-slate-400 text-xs">📍 ' . htmlspecialchars(mb_strimwidth($t['pumpa_nazev'], 0, 30, '…')) . '</span>'
                : '<span class="text-slate-300 text-xs">—</span>';
            $id      = (int)$t['id'];
            $vlastni = canEditRecord((int)$t['uzivatel_id']);
            $akceHtml = $vlastni ? <<<AKCE
                    <a href="/tankovani/{$id}/upravit" class="text-indigo-500 hover:text-indigo-700 text-xs font-medium">Upravit</a>
                    <form method="POST" action="/tankovani/{$id}/smazat" onsubmit="return confirm('Smazat tento záznam?')" class="inline">
                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs">Smazat</button>
                    </form>
                AKCE : '';

            echo <<<HTML
            <tr class="hover:bg-slate-50/60 transition-colors">
                <td class="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">$datum</td>
                <td class="px-4 py-3 font-medium text-slate-700">$auto</td>
                <td class="px-4 py-3 font-mono text-slate-600">$km</td>
                <td class="px-4 py-3">$badge <span class="text-slate-400 text-xs ml-1">$litry</span></td>
                <td class="px-4 py-3 font-semibold text-slate-700">$cena</td>
                <td class="px-4 py-3">$pumpa</td>
                <td class="px-4 py-3 text-right space-x-3">$akceHtml</td>
            </tr>
            HTML;
        }

        echo '</tbody></table></div>';
    });
}

function handleTankovaníNove(): void
{
    authRequired();
    [$auta, $uzivatele] = tankSelecty();
    renderPage('Přidat tankování', function () use ($auta, $uzivatele) {
        echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Přidat tankování</h1></div>';
        echo '<div class="card max-w-3xl">';
        tankFormHtml($auta, $uzivatele);
        echo '</div>';
    });
}

function handleTankovaníNovePost(): void
{
    authRequired();
    if (!isAdmin()) {
        $_POST['uzivatel_id'] = (string)auth()['id'];
    }
    [$auta, $uzivatele] = tankSelecty();
    $chyba = tankUloz(0, $_POST);
    if ($chyba) {
        renderPage('Přidat tankování', function () use ($auta, $uzivatele, $chyba) {
            echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Přidat tankování</h1></div>';
            echo '<div class="card max-w-3xl">';
            tankFormHtml($auta, $uzivatele, $_POST, $chyba);
            echo '</div>';
        });
        return;
    }
    flash('ok', 'Tankování bylo uloženo.');
    header('Location: /tankovani');
    exit;
}

function handleTankovaníUpravit(string $id): void
{
    authRequired();
    $t = tankNajdi((int)$id);
    if (!$t) { http_response_code(404); return; }
    if (!canEditRecord((int)$t['uzivatel_id'])) {
        flash('err', 'Nemáte oprávnění upravovat toto tankování.');
        header('Location: /tankovani');
        exit;
    }
    [$auta, $uzivatele] = tankSelecty();
    renderPage('Upravit tankování', function () use ($t, $auta, $uzivatele) {
        echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Upravit tankování</h1></div>';
        echo '<div class="card max-w-3xl">';
        tankFormHtml($auta, $uzivatele, $t);
        echo '</div>';
    });
}

function handleTankovaníUpravitPost(string $id): void
{
    authRequired();
    $t = tankNajdi((int)$id);
    if (!$t) { http_response_code(404); return; }
    if (!canEditRecord((int)$t['uzivatel_id'])) {
        flash('err', 'Nemáte oprávnění upravovat toto tankování.');
        header('Location: /tankovani');
        exit;
    }
    if (!isAdmin()) {
        $_POST['uzivatel_id'] = (string)$t['uzivatel_id'];
    }
    [$auta, $uzivatele] = tankSelecty();
    $chyba = tankUloz((int)$id, $_POST);
    if ($chyba) {
        $data = array_merge($t, $_POST, ['id' => $id]);
        renderPage('Upravit tankování', function () use ($data, $auta, $uzivatele, $chyba) {
            echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Upravit tankování</h1></div>';
            echo '<div class="card max-w-3xl">';
            tankFormHtml($auta, $uzivatele, $data, $chyba);
            echo '</div>';
        });
        return;
    }
    flash('ok', 'Záznam byl upraven.');
    header('Location: /tankovani');
    exit;
}

function handleTankovaníSmazat(string $id): void
{
    authRequired();
    $t = tankNajdi((int)$id);
    if (!$t || !canEditRecord((int)$t['uzivatel_id'])) {
        flash('err', 'Nemáte oprávnění smazat tento záznam.');
        header('Location: /tankovani');
        exit;
    }
    db()->prepare('DELETE FROM tankovani WHERE id = ?')->execute([(int)$id]);
    flash('ok', 'Záznam byl smazán.');
    header('Location: /tankovani');
    exit;
}

// ── Sdílené ───────────────────────────────────────────────────────────────────

function tankSelecty(): array
{
    $auta      = db()->query('SELECT id, vyrobce, model, spz FROM auta ORDER BY vyrobce, model')->fetchAll();
    $uzivatele = db()->query('SELECT id, jmeno, prijmeni FROM uzivatele ORDER BY prijmeni, jmeno')->fetchAll();
    return [$auta, $uzivatele];
}

function tankUloz(int $id, array $post): ?string
{
    $autoId    = (int)($post['auto_id']     ?? 0);
    $uzivId    = (int)($post['uzivatel_id'] ?? 0);
    $datumCas  = trim($post['datum_cas']    ?? '');
    $kmStav    = (int)($post['km_stav']     ?? 0);
    $litry     = (float)($post['litry']     ?? 0);
    $cenaLitr  = (float)($post['cena_za_litr'] ?? 0);
    $celkCena  = (float)($post['celkova_cena'] ?? 0);
    $paliva    = ['benzin', 'nafta', 'lpg', 'elektro', 'hybrid'];
    $palivo    = in_array($post['palivo'] ?? '', $paliva, true) ? $post['palivo'] : 'benzin';
    $plnaNadrz = isset($post['plna_nadrz']) ? 1 : 0;
    $poznamka  = trim($post['poznamka'] ?? '') ?: null;
    $pumkaNazev = trim($post['pumpa_nazev'] ?? '') ?: null;
    $pumpaLat  = $post['pumpa_lat'] !== '' ? (float)$post['pumpa_lat'] : null;
    $pumpaLng  = $post['pumpa_lng'] !== '' ? (float)$post['pumpa_lng'] : null;

    if (!$autoId || !$uzivId)   return 'Vyberte auto a řidiče.';
    if (!$datumCas)             return 'Datum a čas jsou povinné.';
    if ($kmStav <= 0)           return 'Stav tachometru musí být kladné číslo.';
    if ($litry <= 0)            return 'Počet litrů musí být kladné číslo.';
    if ($celkCena <= 0)         return 'Celková cena musí být kladné číslo.';

    // Převod datetime-local na MySQL formát
    $datumCasDb = date('Y-m-d H:i:s', strtotime($datumCas));

    $cols = ['auto_id', 'uzivatel_id', 'datum_cas', 'km_stav', 'litry', 'cena_za_litr', 'celkova_cena', 'palivo', 'plna_nadrz', 'poznamka', 'pumpa_nazev', 'pumpa_lat', 'pumpa_lng'];
    $vals = [$autoId, $uzivId, $datumCasDb, $kmStav, $litry, $cenaLitr, $celkCena, $palivo, $plnaNadrz, $poznamka, $pumkaNazev, $pumpaLat, $pumpaLng];

    if ($id === 0) {
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $colsSql = implode(',', $cols);
        db()->prepare("INSERT INTO tankovani ($colsSql) VALUES ($placeholders)")->execute($vals);
    } else {
        $setSql = implode(', ', array_map(fn($c) => "$c=?", $cols));
        $vals[] = $id;
        db()->prepare("UPDATE tankovani SET $setSql WHERE id=?")->execute($vals);
    }

    return null;
}
