<?php

declare(strict_types=1);

// ── Helpers ───────────────────────────────────────────────────────────────────

function uzivatelNajdi(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM uzivatele WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function roleBadge(string $role): string
{
    return match($role) {
        'admin' => '<span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">Admin</span>',
        'ridic' => '<span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">Řidič</span>',
        default => htmlspecialchars($role),
    };
}

function initials(string $jmeno, string $prijmeni): string
{
    return mb_strtoupper(mb_substr($jmeno, 0, 1) . mb_substr($prijmeni, 0, 1));
}

function uzivatelFormHtml(array $data = [], ?string $chyba = null): void
{
    $id        = (int)($data['id'] ?? 0);
    $action    = $id ? "/uzivatele/{$id}/upravit" : '/uzivatele/novy';
    $jmeno     = htmlspecialchars($data['jmeno']     ?? '');
    $prijmeni  = htmlspecialchars($data['prijmeni']  ?? '');
    $prezdivka = htmlspecialchars($data['prezdivka'] ?? '');
    $telefon   = htmlspecialchars($data['telefon']   ?? '');
    $email     = htmlspecialchars($data['email']     ?? '');
    $role      = $data['role'] ?? 'ridic';
    $isEdit    = $id > 0;
    $hesloPopis = $isEdit ? 'Nechte prázdné pro zachování stávajícího hesla.' : '';
    $hesloPH    = $isEdit ? '••••••••' : '';
    $chybaTpl   = $chyba
        ? '<div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600">' . htmlspecialchars($chyba) . '</div>'
        : '';

    $adminSel = $role === 'admin' ? 'selected' : '';
    $ridicSel = $role === 'ridic' ? 'selected' : '';

    $roleField = isAdmin()
        ? <<<ROLE
                <div>
                    <label class="form-label">Role</label>
                    <select name="role" class="form-input">
                        <option value="ridic" $ridicSel>Řidič</option>
                        <option value="admin" $adminSel>Admin</option>
                    </select>
                </div>
        ROLE
        : "<input type=\"hidden\" name=\"role\" value=\"{$role}\">";

    echo <<<HTML
    $chybaTpl
    <form method="POST" action="$action" class="space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Jméno</label>
                <input type="text" name="jmeno" value="$jmeno" required class="form-input">
            </div>
            <div>
                <label class="form-label">Příjmení</label>
                <input type="text" name="prijmeni" value="$prijmeni" required class="form-input">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Přezdívka</label>
                <input type="text" name="prezdivka" value="$prezdivka" class="form-input">
            </div>
            <div>
                <label class="form-label">Telefon</label>
                <input type="tel" name="telefon" value="$telefon" class="form-input">
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="form-label">E-mail</label>
                <input type="email" name="email" value="$email" required class="form-input">
            </div>
            $roleField
        </div>
        <div>
            <label class="form-label">Heslo <span class="text-slate-400 font-normal text-xs">$hesloPopis</span></label>
            <input type="password" name="heslo" placeholder="$hesloPH" class="form-input" autocomplete="new-password">
        </div>
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn-primary">
                Uložit uživatele
            </button>
            <a href="/uzivatele" class="text-sm text-slate-400 hover:text-slate-600">Zrušit</a>
        </div>
    </form>
    HTML;
}

// ── Handlery ─────────────────────────────────────────────────────────────────

function handleUzivateleSeznam(): void
{
    authRequired();

    $uzivatele = db()->query(
        'SELECT id, jmeno, prijmeni, prezdivka, email, role, posledni_prihlaseni FROM uzivatele ORDER BY prijmeni, jmeno'
    )->fetchAll();

    $ok    = flashGet('ok');
    $err   = flashGet('err');
    $okTpl  = $ok  ? "<div class=\"mb-6 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-700\">{$ok}</div>"  : '';
    $errTpl = $err ? "<div class=\"mb-6 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600\">{$err}</div>" : '';

    renderPage('Uživatelé', function () use ($uzivatele, $okTpl, $errTpl) {
        $aktualni = auth()['id'];
        $novyBtn  = isAdmin() ? '<a href="/uzivatele/novy" class="btn-primary">+ Nový uživatel</a>' : '';
        echo $okTpl;
        echo $errTpl;
        echo <<<HTML
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Uživatelé</h1>
            $novyBtn
        </div>
        <div class="card p-0 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="text-left px-6 py-3 font-semibold text-slate-500">Uživatel</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-500">E-mail</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-500">Role</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-500">Poslední přihlášení</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
        HTML;

        foreach ($uzivatele as $u) {
            $initials  = initials($u['jmeno'], $u['prijmeni']);
            $jmeno     = htmlspecialchars($u['jmeno'] . ' ' . $u['prijmeni']);
            $prezdivka = $u['prezdivka'] ? '<span class="text-slate-400 text-xs ml-1">(' . htmlspecialchars($u['prezdivka']) . ')</span>' : '';
            $email     = htmlspecialchars($u['email']);
            $badge     = roleBadge($u['role']);
            $prihl     = $u['posledni_prihlaseni']
                ? date('j. n. Y H:i', strtotime($u['posledni_prihlaseni']))
                : '<span class="text-slate-300">—</span>';
            $id        = (int)$u['id'];
            $jeAktualni  = $id === $aktualni;
            $upravitBtn  = canEditRecord($id)
                ? "<a href=\"/uzivatele/{$id}/upravit\" class=\"text-indigo-500 hover:text-indigo-700 text-xs font-medium\">Upravit</a>"
                : '';
            if (!isAdmin()) {
                $smazatBtn = '';
            } elseif ($jeAktualni) {
                $smazatBtn = '<span class="text-slate-200 text-xs cursor-not-allowed" title="Nelze smazat sebe">Smazat</span>';
            } else {
                $smazatBtn = <<<FORM
                    <form method="POST" action="/uzivatele/{$id}/smazat" onsubmit="return confirm('Opravdu smazat {$jmeno}?')" class="inline">
                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs">Smazat</button>
                    </form>
                    FORM;
            }

            echo <<<HTML
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 font-bold text-sm flex items-center justify-center flex-shrink-0">
                                    $initials
                                </div>
                                <div>
                                    <p class="font-medium text-slate-800">$jmeno $prezdivka</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500">$email</td>
                        <td class="px-6 py-4">$badge</td>
                        <td class="px-6 py-4 text-slate-400 text-xs">$prihl</td>
                        <td class="px-6 py-4 text-right space-x-4">
                            $upravitBtn
                            $smazatBtn
                        </td>
                    </tr>
            HTML;
        }

        echo <<<HTML
                </tbody>
            </table>
        </div>
        HTML;
    });
}

function handleUzivateleNovy(): void
{
    authRequired();
    if (!isAdmin()) {
        flash('err', 'Nemáte oprávnění vytvářet nové uživatele.');
        header('Location: /');
        exit;
    }
    renderPage('Nový uživatel', function () {
        echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Nový uživatel</h1></div>';
        echo '<div class="card max-w-2xl">';
        uzivatelFormHtml();
        echo '</div>';
    });
}

function handleUzivateleNovyPost(): void
{
    authRequired();
    if (!isAdmin()) {
        flash('err', 'Nemáte oprávnění vytvářet nové uživatele.');
        header('Location: /');
        exit;
    }

    $chyba = uzivatelUloz(0, $_POST);
    if ($chyba) {
        renderPage('Nový uživatel', function () use ($chyba) {
            echo '<div class="mb-6"><h1 class="text-2xl font-bold text-slate-800">Nový uživatel</h1></div>';
            echo '<div class="card max-w-2xl">';
            uzivatelFormHtml($_POST, $chyba);
            echo '</div>';
        });
        return;
    }

    flash('ok', 'Uživatel byl úspěšně vytvořen.');
    header('Location: /uzivatele');
    exit;
}

function handleUzivateleUpravit(string $id): void
{
    authRequired();
    $u = uzivatelNajdi((int)$id);
    if (!$u) { http_response_code(404); return; }
    if (!canEditRecord((int)$id)) {
        flash('err', 'Nemáte oprávnění upravovat tohoto uživatele.');
        header('Location: /uzivatele');
        exit;
    }

    renderPage('Upravit uživatele', function () use ($u) {
        $jmeno = htmlspecialchars($u['jmeno'] . ' ' . $u['prijmeni']);
        echo "<div class=\"mb-6\"><h1 class=\"text-2xl font-bold text-slate-800\">Upravit: $jmeno</h1></div>";
        echo '<div class="card max-w-2xl">';
        uzivatelFormHtml($u);
        echo '</div>';
    });
}

function handleUzivateleUpravitPost(string $id): void
{
    authRequired();
    $u = uzivatelNajdi((int)$id);
    if (!$u) { http_response_code(404); return; }
    if (!canEditRecord((int)$id)) {
        flash('err', 'Nemáte oprávnění upravovat tohoto uživatele.');
        header('Location: /uzivatele');
        exit;
    }
    if (!isAdmin()) {
        $_POST['role'] = $u['role']; // řidič nemůže změnit svou roli
    }

    $chyba = uzivatelUloz((int)$id, $_POST);
    if ($chyba) {
        $data = array_merge($u, $_POST, ['id' => $id]);
        renderPage('Upravit uživatele', function () use ($data, $chyba) {
            $jmeno = htmlspecialchars(($data['jmeno'] ?? '') . ' ' . ($data['prijmeni'] ?? ''));
            echo "<div class=\"mb-6\"><h1 class=\"text-2xl font-bold text-slate-800\">Upravit: $jmeno</h1></div>";
            echo '<div class="card max-w-2xl">';
            uzivatelFormHtml($data, $chyba);
            echo '</div>';
        });
        return;
    }

    flash('ok', 'Profil byl úspěšně upraven.');
    header('Location: ' . (isAdmin() ? '/uzivatele' : '/'));
    exit;
}

function handleUzivateleSmazat(string $id): void
{
    authRequired();
    if (!isAdmin()) {
        flash('err', 'Nemáte oprávnění mazat uživatele.');
        header('Location: /uzivatele');
        exit;
    }
    $aktualni = auth()['id'];

    if ((int)$id === $aktualni) {
        flash('ok', 'Nelze smazat přihlášeného uživatele.');
        header('Location: /uzivatele');
        exit;
    }

    db()->prepare('DELETE FROM uzivatele WHERE id = ?')->execute([(int)$id]);
    flash('ok', 'Uživatel byl smazán.');
    header('Location: /uzivatele');
    exit;
}

// ── Logika uložení (nový i edit) ──────────────────────────────────────────────

function uzivatelUloz(int $id, array $post): ?string
{
    $jmeno     = trim($post['jmeno']     ?? '');
    $prijmeni  = trim($post['prijmeni']  ?? '');
    $prezdivka = trim($post['prezdivka'] ?? '') ?: null;
    $telefon   = trim($post['telefon']   ?? '') ?: null;
    $email     = trim($post['email']     ?? '');
    $role      = in_array($post['role'] ?? '', ['admin', 'ridic'], true) ? $post['role'] : 'ridic';
    $heslo     = $post['heslo'] ?? '';

    if (!$jmeno || !$prijmeni || !$email) {
        return 'Jméno, příjmení a e-mail jsou povinné.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Neplatný formát e-mailu.';
    }
    if ($id === 0 && strlen($heslo) < 6) {
        return 'Heslo musí mít alespoň 6 znaků.';
    }

    // Ověř unikátnost e-mailu
    $stmt = db()->prepare('SELECT id FROM uzivatele WHERE email = ? AND id != ?');
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        return 'Tento e-mail je již obsazen.';
    }

    // Ověř unikátnost přezdívky (jen pokud je zadána)
    if ($prezdivka !== null) {
        $stmt = db()->prepare('SELECT id FROM uzivatele WHERE prezdivka = ? AND id != ?');
        $stmt->execute([$prezdivka, $id]);
        if ($stmt->fetch()) {
            return 'Tato přezdívka je již obsazena.';
        }
    }

    // Ověř unikátnost telefonu (jen pokud je zadán)
    if ($telefon !== null) {
        $stmt = db()->prepare('SELECT id FROM uzivatele WHERE telefon = ? AND id != ?');
        $stmt->execute([$telefon, $id]);
        if ($stmt->fetch()) {
            return 'Toto telefonní číslo je již obsazeno.';
        }
    }

    if ($id === 0) {
        $stmt = db()->prepare(
            'INSERT INTO uzivatele (jmeno, prijmeni, prezdivka, telefon, email, heslo, role) VALUES (?,?,?,?,?,?,?)'
        );
        $stmt->execute([$jmeno, $prijmeni, $prezdivka, $telefon, $email, password_hash($heslo, PASSWORD_BCRYPT), $role]);
    } else {
        if (strlen($heslo) > 0) {
            if (strlen($heslo) < 6) return 'Heslo musí mít alespoň 6 znaků.';
            $stmt = db()->prepare(
                'UPDATE uzivatele SET jmeno=?, prijmeni=?, prezdivka=?, telefon=?, email=?, heslo=?, role=? WHERE id=?'
            );
            $stmt->execute([$jmeno, $prijmeni, $prezdivka, $telefon, $email, password_hash($heslo, PASSWORD_BCRYPT), $role, $id]);
        } else {
            $stmt = db()->prepare(
                'UPDATE uzivatele SET jmeno=?, prijmeni=?, prezdivka=?, telefon=?, email=?, role=? WHERE id=?'
            );
            $stmt->execute([$jmeno, $prijmeni, $prezdivka, $telefon, $email, $role, $id]);
        }
    }

    return null;
}
