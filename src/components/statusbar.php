<?php
// ── Stavová lišta ─────────────────────────────────────────────────────────────
declare(strict_types=1);

$u           = auth();
$prihlasen   = $u !== null;
$jmeno       = $prihlasen ? htmlspecialchars($u['jmeno'] . ' ' . $u['prijmeni']) : '';
$prezdivka   = ($prihlasen && $u['prezdivka']) ? '(' . htmlspecialchars($u['prezdivka']) . ')' : '';
$cas         = $prihlasen ? date('H:i', $u['cas']) : '';
$datum       = $prihlasen ? date('j. n. Y', $u['cas']) : '';

// ── Svátek ────────────────────────────────────────────────────────────────────
$svatky = [
    '1.1'=>'Nový rok','2.1'=>'Karina','3.1'=>'Radmila','4.1'=>'Diana','5.1'=>'Dalimil',
    '6.1'=>'Kašpar','7.1'=>'Vilma','8.1'=>'Čestmír','9.1'=>'Vladan','10.1'=>'Břetislav',
    '11.1'=>'Bohdana','12.1'=>'Pravoslav','13.1'=>'Edita','14.1'=>'Radovan','15.1'=>'Alice',
    '16.1'=>'Ctirad','17.1'=>'Drahoslav','18.1'=>'Vladislav','19.1'=>'Doubravka','20.1'=>'Ilona',
    '21.1'=>'Běla','22.1'=>'Slavomír','23.1'=>'Zdeněk','24.1'=>'Milena','25.1'=>'Miloš',
    '26.1'=>'Zora','27.1'=>'Ingrid','28.1'=>'Otýlie','29.1'=>'Zdislava','30.1'=>'Robin','31.1'=>'Marika',
    '1.2'=>'Hynek','2.2'=>'Nela','3.2'=>'Blažej','4.2'=>'Jarmila','5.2'=>'Dobromila',
    '6.2'=>'Vanda','7.2'=>'Veronika','8.2'=>'Milada','9.2'=>'Apolena','10.2'=>'Mojmír',
    '11.2'=>'Desanka','12.2'=>'Slavěna','13.2'=>'Věnceslava','14.2'=>'Valentýn','15.2'=>'Jiřina',
    '16.2'=>'Ljuba','17.2'=>'Miloslava','18.2'=>'Gizela','19.2'=>'Patrik','20.2'=>'Oldřiška',
    '21.2'=>'Lenka','22.2'=>'Petr','23.2'=>'Svatopluk','24.2'=>'Matěj','25.2'=>'Liliana',
    '26.2'=>'Dorota','27.2'=>'Alexandr','28.2'=>'Lumír','29.2'=>'Horymír',
    '1.3'=>'Bedřich','2.3'=>'Anežka','3.3'=>'Kamil','4.3'=>'Stela','5.3'=>'Kazimír',
    '6.3'=>'Miroslav','7.3'=>'Tomáš','8.3'=>'Gabriela','9.3'=>'Františka','10.3'=>'Viktorie',
    '11.3'=>'Anděla','12.3'=>'Řehoř','13.3'=>'Růžena','14.3'=>'Rút','15.3'=>'Ida',
    '16.3'=>'Elena','17.3'=>'Vlastimil','18.3'=>'Eduard','19.3'=>'Josef','20.3'=>'Světlana',
    '21.3'=>'Radek','22.3'=>'Leona','23.3'=>'Ivona','24.3'=>'Gabriel','25.3'=>'Marián',
    '26.3'=>'Emanuel','27.3'=>'Dita','28.3'=>'Soňa','29.3'=>'Taťána','30.3'=>'Arnošt','31.3'=>'Kvído',
    '1.4'=>'Hugo','2.4'=>'Erika','3.4'=>'Richard','4.4'=>'Ivana','5.4'=>'Miroslava',
    '6.4'=>'Vendula','7.4'=>'Heřman','8.4'=>'Ema','9.4'=>'Dušan','10.4'=>'Dáša',
    '11.4'=>'Izabela','12.4'=>'Julius','13.4'=>'Aleš','14.4'=>'Vincenc','15.4'=>'Anastázie',
    '16.4'=>'Irena','17.4'=>'Rudolf','18.4'=>'Valérie','19.4'=>'Rostislav','20.4'=>'Marcela',
    '21.4'=>'Alexandra','22.4'=>'Evženie','23.4'=>'Vojtěch','24.4'=>'Jiří','25.4'=>'Marek',
    '26.4'=>'Oto','27.4'=>'Jaroslav','28.4'=>'Vlastislav','29.4'=>'Robert','30.4'=>'Blahoslav',
    '1.5'=>'Svátek práce','2.5'=>'Zikmund','3.5'=>'Alexej','4.5'=>'Florian','5.5'=>'Klaudie',
    '6.5'=>'Radoslav','7.5'=>'Stanislav','8.5'=>'Den vítězství','9.5'=>'Ctibor','10.5'=>'Blažena',
    '11.5'=>'Svatava','12.5'=>'Pankrác','13.5'=>'Servác','14.5'=>'Bonifác','15.5'=>'Žofie',
    '16.5'=>'Přemysl','17.5'=>'Aneta','18.5'=>'Nataša','19.5'=>'Ivo','20.5'=>'Zbyněk',
    '21.5'=>'Monika','22.5'=>'Emil','23.5'=>'Vladimír','24.5'=>'Jana','25.5'=>'Viola',
    '26.5'=>'Filip','27.5'=>'Valdemar','28.5'=>'Vilém','29.5'=>'Maxmilián','30.5'=>'Ferdinand','31.5'=>'Kamila',
    '1.6'=>'Laura','2.6'=>'Jarmil','3.6'=>'Tamara','4.6'=>'Dalibor','5.6'=>'Dobroslav',
    '6.6'=>'Norbert','7.6'=>'Slavoj','8.6'=>'Medard','9.6'=>'Stanislava','10.6'=>'Gita',
    '11.6'=>'Bruno','12.6'=>'Antonie','13.6'=>'Antonín','14.6'=>'Roland','15.6'=>'Vít',
    '16.6'=>'Zbyšek','17.6'=>'Adolf','18.6'=>'Milan','19.6'=>'Leoš','20.6'=>'Květa',
    '21.6'=>'Alois','22.6'=>'Pavla','23.6'=>'Zdeňka','24.6'=>'Jan','25.6'=>'Ivan',
    '26.6'=>'Adriana','27.6'=>'Ladislav','28.6'=>'Lubomír','29.6'=>'Petr a Pavel','30.6'=>'Šárka',
    '1.7'=>'Jaroslava','2.7'=>'Patricie','3.7'=>'Radomír','4.7'=>'Prokop','5.7'=>'Cyril a Metoděj',
    '6.7'=>'Jan Hus','7.7'=>'Bohuslava','8.7'=>'Nora','9.7'=>'Drahoslava','10.7'=>'Libuše',
    '11.7'=>'Olga','12.7'=>'Bořek','13.7'=>'Markéta','14.7'=>'Karolína','15.7'=>'Jindřich',
    '16.7'=>'Luboš','17.7'=>'Martina','18.7'=>'Drahomíra','19.7'=>'Čeněk','20.7'=>'Ilja',
    '21.7'=>'Vítězslav','22.7'=>'Magdaléna','23.7'=>'Libor','24.7'=>'Kristýna','25.7'=>'Jakub',
    '26.7'=>'Anna','27.7'=>'Božena','28.7'=>'Viktor','29.7'=>'Marta','30.7'=>'Bořivoj','31.7'=>'Ignác',
    '1.8'=>'Oskar','2.8'=>'Gustav','3.8'=>'Miluše','4.8'=>'Dominik','5.8'=>'Kristián',
    '6.8'=>'Oldřich','7.8'=>'Lada','8.8'=>'Soběslav','9.8'=>'Roman','10.8'=>'Vavřinec',
    '11.8'=>'Zuzana','12.8'=>'Klára','13.8'=>'Alžběta','14.8'=>'Arnošt','15.8'=>'Hana',
    '16.8'=>'Jáchym','17.8'=>'Petra','18.8'=>'Helena','19.8'=>'Ludvík','20.8'=>'Bernard',
    '21.8'=>'Johana','22.8'=>'Bohuslav','23.8'=>'Sandra','24.8'=>'Bartoloměj','25.8'=>'Radim',
    '26.8'=>'Luděk','27.8'=>'Otakar','28.8'=>'Augustýn','29.8'=>'Evelína','30.8'=>'Vladěna','31.8'=>'Pavlína',
    '1.9'=>'Linda','2.9'=>'Adéla','3.9'=>'Bronislav','4.9'=>'Jindřiška','5.9'=>'Boris',
    '6.9'=>'Boleslav','7.9'=>'Regína','8.9'=>'Mariana','9.9'=>'Daniela','10.9'=>'Irma',
    '11.9'=>'Denisa','12.9'=>'Marie','13.9'=>'Lubor','14.9'=>'Radka','15.9'=>'Jolana',
    '16.9'=>'Ludmila','17.9'=>'Naděžda','18.9'=>'Kryštof','19.9'=>'Zita','20.9'=>'Oleg',
    '21.9'=>'Matouš','22.9'=>'Darina','23.9'=>'Berta','24.9'=>'Jaromír','25.9'=>'Zlata',
    '26.9'=>'Andrea','27.9'=>'Jonáš','28.9'=>'Václav','29.9'=>'Michal','30.9'=>'Jeroným',
    '1.10'=>'Igor','2.10'=>'Olivie','3.10'=>'Bohumil','4.10'=>'František','5.10'=>'Eliška',
    '6.10'=>'Hanuš','7.10'=>'Justýna','8.10'=>'Věra','9.10'=>'Štefan','10.10'=>'Marina',
    '11.10'=>'Andrej','12.10'=>'Marcel','13.10'=>'Renáta','14.10'=>'Agáta','15.10'=>'Tereza',
    '16.10'=>'Havel','17.10'=>'Hedvika','18.10'=>'Lukáš','19.10'=>'Michaela','20.10'=>'Vendelín',
    '21.10'=>'Brigita','22.10'=>'Sabina','23.10'=>'Teodor','24.10'=>'Nina','25.10'=>'Beáta',
    '26.10'=>'Erik','27.10'=>'Šarlota','28.10'=>'Den vzniku ČSR','29.10'=>'Silvie','30.10'=>'Tadeáš','31.10'=>'Štěpánka',
    '1.11'=>'Felix','2.11'=>'Dušičky','3.11'=>'Hubert','4.11'=>'Karel','5.11'=>'Miriam',
    '6.11'=>'Liběna','7.11'=>'Saskie','8.11'=>'Bohumír','9.11'=>'Bohdan','10.11'=>'Evžen',
    '11.11'=>'Martin','12.11'=>'Benedikt','13.11'=>'Tibor','14.11'=>'Sáva','15.11'=>'Leopold',
    '16.11'=>'Otmar','17.11'=>'Den svobody','18.11'=>'Romana','19.11'=>'Alžběta','20.11'=>'Nikola',
    '21.11'=>'Albert','22.11'=>'Cecílie','23.11'=>'Klement','24.11'=>'Emílie','25.11'=>'Kateřina',
    '26.11'=>'Artur','27.11'=>'Xenie','28.11'=>'René','29.11'=>'Zina','30.11'=>'Ondřej',
    '1.12'=>'Iva','2.12'=>'Blanka','3.12'=>'Svatoslav','4.12'=>'Barbora','5.12'=>'Mikuláš',
    '6.12'=>'Mikuláš','7.12'=>'Ambrož','8.12'=>'Květoslava','9.12'=>'Vratislav','10.12'=>'Julie',
    '11.12'=>'Dana','12.12'=>'Simona','13.12'=>'Lucie','14.12'=>'Lýdie','15.12'=>'Radana',
    '16.12'=>'Albína','17.12'=>'Daniel','18.12'=>'Miloslav','19.12'=>'Ester','20.12'=>'Dagmar',
    '21.12'=>'Natálie','22.12'=>'Šimon','23.12'=>'Vlasta','24.12'=>'Štědrý den','25.12'=>'Boží hod',
    '26.12'=>'Štěpán','27.12'=>'Žaneta','28.12'=>'Bohumila','29.12'=>'Judita','30.12'=>'David','31.12'=>'Silvestr',
];
$dnesniSvatek  = $svatky[date('j.n')] ?? null;
$zitrejsiSvatek = $svatky[date('j.n', strtotime('+1 day'))] ?? null;

// ── Počasí (Open-Meteo, cache 1h) ─────────────────────────────────────────
function fetchPocasi(): array
{
    $cache = sys_get_temp_dir() . '/yeti_weather.json';
    if (file_exists($cache) && time() - filemtime($cache) < 3600) {
        $data = json_decode((string) file_get_contents($cache), true);
        if (is_array($data)) return $data;
    }
    $url = 'https://api.open-meteo.com/v1/forecast'
         . '?latitude=50.08&longitude=14.44'
         . '&daily=temperature_2m_max,temperature_2m_min,weathercode'
         . '&timezone=Europe%2FPrague&forecast_days=2';
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return [];
    $json = json_decode($raw, true);
    if (!isset($json['daily']['time'])) return [];
    $result = [];
    foreach ([0, 1] as $i) {
        $result[] = [
            'max'  => (int) round($json['daily']['temperature_2m_max'][$i]),
            'min'  => (int) round($json['daily']['temperature_2m_min'][$i]),
            'code' => (int) $json['daily']['weathercode'][$i],
            'den'  => $i === 0 ? 'Dnes' : 'Zítra',
        ];
    }
    file_put_contents($cache, json_encode($result));
    return $result;
}

function wmoText(int $code): string
{
    if ($code === 0)            return 'Jasno';
    if ($code <= 2)             return 'Polojasno';
    if ($code === 3)            return 'Zataženo';
    if ($code <= 48)            return 'Mlha';
    if ($code <= 55)            return 'Mrholení';
    if ($code <= 65)            return 'Déšť';
    if ($code <= 75)            return 'Sněžení';
    if ($code <= 82)            return 'Přeháňky';
    return 'Bouřky';
}

$pocasi = fetchPocasi();
?>
<div class="max-w-5xl mx-auto px-6 py-1.5">
    <div id="statusbar" class="bg-slate-800 rounded-2xl text-xs px-5 py-2 flex items-center gap-6 flex-wrap">

        <!-- Stav přihlášení -->
        <div class="flex items-center gap-3">
            <span class="<?= $prihlasen ? 'text-green-400' : 'text-red-500' ?> text-sm leading-none">●</span>
            <?php if ($prihlasen): ?>
                <span class="text-white font-medium"><?= $jmeno ?></span>
                <?php if ($prezdivka): ?>
                    <span class="text-white"><?= $prezdivka ?></span>
                <?php endif; ?>
                <span class="text-white">|</span>
                <span class="text-white">přihlášen <?= $datum ?> v <?= $cas ?></span>
            <?php else: ?>
                <span class="text-white">Nepřihlášen</span>
            <?php endif; ?>
        </div>

        <!-- Svátek -->
        <div class="flex items-center gap-2 text-white">
            <span class="text-white">|</span>
            <?php if ($dnesniSvatek): ?>
                <span>Dnes: <span class="font-medium"><?= htmlspecialchars($dnesniSvatek) ?></span></span>
            <?php endif; ?>
            <?php if ($zitrejsiSvatek): ?>
                <?php if ($dnesniSvatek): ?><span class="text-white">/</span><?php endif; ?>
                <span>Zítra: <span class="font-medium"><?= htmlspecialchars($zitrejsiSvatek) ?></span></span>
            <?php endif; ?>
        </div>

        <!-- Počasí -->
        <?php if ($pocasi): ?>
            <div class="flex items-center gap-4 ml-auto">
                <span class="text-white">|</span>
                <?php foreach ($pocasi as $den): ?>
                    <div class="text-white whitespace-nowrap">
                        <span class="text-white"><?= $den['den'] ?>:</span>
                        <?= wmoText($den['code']) ?>
                        <span class="font-medium"><?= $den['max'] ?>°</span><span class="text-white"> / <?= $den['min'] ?>°</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Odhlášení / přepínač režimu -->
        <?php if ($prihlasen): ?>
            <a href="/logout" class="text-white hover:text-red-400 transition-colors <?= $pocasi ? '' : 'ml-auto' ?>">
                Odhlásit
            </a>
        <?php else: ?>
            <button onclick="toggleTheme()" title="Přepnout režim"
                    class="ml-auto flex items-center justify-center w-6 h-6 rounded-full opacity-70 hover:opacity-100 transition-opacity">
                <svg id="sb-icon-sun" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
                <svg id="sb-icon-moon" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </button>
        <?php endif; ?>

    </div>
</div>
