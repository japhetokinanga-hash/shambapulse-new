<?php
// ShambaPulse — shared setup for every public page.
// Nyando Sub-County, Kisumu County ONLY.
// XAMPP default database: root, no password, database `shambapulse`.
declare(strict_types=1);
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'shambapulse');
define('DB_USER', 'root');
define('DB_PASS', '');

// Staff sign-in. Username `admin`, password `admin123`.
// CHANGE THIS BEFORE ANY REAL USE: run
//   php -r "echo password_hash('new-password', PASSWORD_DEFAULT);"
// and paste the hash below in place of the password_hash(...) call.
define('ADMIN_USER', 'admin');
define('ADMIN_HASH', password_hash('admin123', PASSWORD_DEFAULT));

// --- Scope: Nyando Sub-County, Kisumu County only -------------------------
// Kisii and Nyamira were removed on 21 Nov 2026. There is one county
// (Kisumu) and five wards. Every county/ward list in the site comes
// from here, so no other county can be registered, priced or alerted.
const COUNTIES = [
    'Kisumu' => ['Ahero', 'Awasi/Onjiko', 'East Kano/Wawidhi', 'Kabonyo/Kanyagwal', 'Kobura'],
];
// Market -> ward, for the six Nyando markets.
const NYANDO_MARKETS = [
    'Ahero' => 'Ahero',
    'Awasi' => 'Awasi/Onjiko',
    'Rabuor' => 'Kobura',
    'Pap Onditi' => 'East Kano/Wawidhi',
    'Nyando' => 'Kabonyo/Kanyagwal',
    'Katito' => 'East Kano/Wawidhi',
];
const SCOPE_COUNTY = 'Kisumu';
const SCOPE_SUBCOUNTY = 'Nyando';

// --- Database (PDO, prepared statements throughout) -----------------------
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        ensure_columns($pdo);
    }
    return $pdo;
}

// Columns added after the first import. Each ALTER is tried once and
// ignored if the column already exists, so old databases keep working
// without a re-import of shambapulse.sql.
function ensure_columns(PDO $pdo): void {
    foreach ([
        "ALTER TABLE message ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE farmer ADD COLUMN password_hash VARCHAR(255) NULL",
        "ALTER TABLE farmer ADD COLUMN last_login_at TIMESTAMP NULL",
        "ALTER TABLE farmer ADD COLUMN consent_at TIMESTAMP NULL",
        "ALTER TABLE price ADD COLUMN recorded_by INT NULL",
        "ALTER TABLE alert ADD COLUMN title VARCHAR(120) NOT NULL DEFAULT ''",
        "ALTER TABLE alert ADD COLUMN severity VARCHAR(10) NOT NULL DEFAULT 'info'",
        "ALTER TABLE alert ADD COLUMN ward VARCHAR(50) NOT NULL DEFAULT ''",
        "ALTER TABLE alert ADD COLUMN created_by INT NULL",
    ] as $sql) {
        try { $pdo->exec($sql); } catch (Throwable $e) { /* column already there */ }
    }
    ensure_doc_tables($pdo);
}

// Project document (Ch. 3-4) tables: ward, market, commodity, staff,
// forecast, advisory, subscription, message_log. Created on first load
// so an existing install gets them without re-importing the SQL file
// (re-importing drops the database). Reference data is seeded only
// when its table is empty.
function ensure_doc_tables(PDO $pdo): void {
    $tables = [
        "CREATE TABLE IF NOT EXISTS ward (ward_id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE, sub_county VARCHAR(50) NOT NULL DEFAULT 'Nyando', county VARCHAR(50) NOT NULL DEFAULT 'Kisumu', lat DECIMAL(9,6) NOT NULL, lon DECIMAL(9,6) NOT NULL)",
        "CREATE TABLE IF NOT EXISTS market (market_id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE, ward VARCHAR(50) NOT NULL)",
        "CREATE TABLE IF NOT EXISTS commodity (commodity_id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE, unit VARCHAR(30) NOT NULL)",
        "CREATE TABLE IF NOT EXISTS staff (staff_id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL, username VARCHAR(50) NOT NULL UNIQUE, email VARCHAR(100) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, role VARCHAR(10) NOT NULL DEFAULT 'officer', ward VARCHAR(50) NOT NULL DEFAULT '', active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE IF NOT EXISTS forecast (forecast_id INT AUTO_INCREMENT PRIMARY KEY, ward VARCHAR(50) NOT NULL, forecast_date DATE NOT NULL, temp_max DECIMAL(4,1) NULL, temp_min DECIMAL(4,1) NULL, rainfall_mm DECIMAL(5,1) NOT NULL DEFAULT 0, summary VARCHAR(60) NOT NULL DEFAULT '', source VARCHAR(30) NOT NULL DEFAULT 'SIMULATED', fetched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uk_forecast (ward, forecast_date, source))",
        "CREATE TABLE IF NOT EXISTS advisory (advisory_id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(120) NOT NULL, body_en TEXT NOT NULL, body_sw TEXT NOT NULL, ward VARCHAR(50) NOT NULL, published_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, published_by INT NULL)",
        "CREATE TABLE IF NOT EXISTS subscription (subscription_id INT AUTO_INCREMENT PRIMARY KEY, farmer_id INT NOT NULL, ward VARCHAR(50) NOT NULL, channel VARCHAR(10) NOT NULL DEFAULT 'sms', active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uk_subscription (farmer_id, ward, channel), FOREIGN KEY (farmer_id) REFERENCES farmer (farmer_id))",
        "CREATE TABLE IF NOT EXISTS message_log (log_id INT AUTO_INCREMENT PRIMARY KEY, channel VARCHAR(10) NOT NULL DEFAULT 'sms', phone VARCHAR(20) NOT NULL DEFAULT '', ward_name VARCHAR(50) NOT NULL DEFAULT '', alert_title VARCHAR(120) NOT NULL DEFAULT '', message VARCHAR(300) NOT NULL DEFAULT '', direction VARCHAR(10) NOT NULL DEFAULT 'out', status VARCHAR(10) NOT NULL DEFAULT 'simulated', gateway_ref VARCHAR(200) NOT NULL DEFAULT '', farmer_id INT NULL, alert_id INT NULL, ward VARCHAR(50) NOT NULL DEFAULT '', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)",
    ];
    foreach ($tables as $sql) {
        try { $pdo->exec($sql); } catch (Throwable $e) { /* already there */ }
    }
    try {
        if ((int)($pdo->query("SELECT COUNT(*) FROM ward")->fetchColumn()) === 0) {
            $pdo->exec("INSERT INTO ward (name, lat, lon) VALUES ('Ahero', -0.1728, 34.9214), ('Awasi/Onjiko', -0.1833, 34.9833), ('East Kano/Wawidhi', -0.2167, 34.9667), ('Kabonyo/Kanyagwal', -0.2833, 34.9167), ('Kobura', -0.2333, 34.8333)");
        }
        if ((int)($pdo->query("SELECT COUNT(*) FROM market")->fetchColumn()) === 0) {
            $pdo->exec("INSERT INTO market (name, ward) VALUES ('Ahero', 'Ahero'), ('Awasi', 'Awasi/Onjiko'), ('Rabuor', 'Kobura'), ('Pap Onditi', 'East Kano/Wawidhi'), ('Nyando', 'Kabonyo/Kanyagwal'), ('Katito', 'East Kano/Wawidhi')");
        }
        if ((int)($pdo->query("SELECT COUNT(*) FROM commodity")->fetchColumn()) === 0) {
            $pdo->exec("INSERT INTO commodity (name, unit) VALUES ('Maize', '90kg bag'), ('Beans', '90kg bag'), ('Rice', '90kg bag'), ('Sorghum', '90kg bag'), ('Finger Millet', '90kg bag'), ('Groundnuts', 'kg'), ('Tomatoes', 'kg'), ('Onions', 'kg'), ('Sukuma Wiki', 'bunch'), ('Milk', 'litre'), ('Eggs', 'tray'), ('Live Chicken', 'bird'), ('Goat', 'animal'), ('Cattle', 'animal')");
        }
        if ((int)($pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn()) === 0) {
            $st = $pdo->prepare("INSERT INTO staff (name, username, email, password_hash, role, ward) VALUES (?,?,?,?,?,?)");
            $st->execute(['ShambaPulse Admin', 'admin', 'admin@shambapulse.ke', password_hash('admin123', PASSWORD_DEFAULT), 'admin', '']);
            $st->execute(['Ahero Extension Officer', 'officer', 'officer@shambapulse.ke', password_hash('Officer123!', PASSWORD_DEFAULT), 'officer', 'Ahero']);
        }
        if ((int)($pdo->query("SELECT COUNT(*) FROM forecast")->fetchColumn()) === 0) seed_forecasts($pdo);
        if ((int)($pdo->query("SELECT COUNT(*) FROM advisory")->fetchColumn()) === 0) {
            $pdo->exec("INSERT INTO advisory (title, body_en, body_sw, ward, published_by) VALUES ('Planting after the rains', 'Plant maize within 3 days of the first heavy rain. Use certified seed and apply fertilizer after germination, not before the rain.', 'Panda mahindi ndani ya siku 3 baada ya mvua ya kwanza. Tumia mbegu bora na weka mbolea baada ya kumea, si kabla ya mvua.', 'Ahero', 2), ('Mulching during dry spells', 'Cover your crops with dry grass or leaves to keep soil moisture. Water early in the morning or late in the evening.', 'Funika mazao yako kwa majani makavu ili kuhifadhi unyevu. Mwagilia maji asubuhi na mapema au jioni.', 'Awasi/Onjiko', 2), ('Vaccinate livestock before the rains', 'Take goats and cattle for vaccination at the nearest veterinary point before the heavy rains start.', 'Peleka mbuzi na ngombe kwa chanjo kwenye kituo cha mifugo kabla ya mvua kubwa kuanza.', 'Kobura', 2)");
        }
        if ((int)($pdo->query("SELECT COUNT(*) FROM subscription")->fetchColumn()) === 0) {
            $pdo->exec("INSERT IGNORE INTO subscription (farmer_id, ward, channel, active) SELECT farmer_id, ward, 'sms', subscribed FROM farmer");
        }
        $pdo->exec("UPDATE farmer SET consent_at = created_at WHERE consent = 1 AND consent_at IS NULL");
    } catch (Throwable $e) { /* seeding must never break the page */ }
}

// 7-day SIMULATED forecast per ward, used until a live Open-Meteo
// fetch succeeds. Labelled SIMULATED so it is never mistaken for a
// live forecast (doc 1.7: SIMULATED tags all prototype feeds).
function seed_forecasts(PDO $pdo): void {
    $summaries = ['Sunny intervals', 'Light rain', 'Heavy rain', 'Cloudy', 'Sunny intervals', 'Light rain', 'Cloudy'];
    $rain = [0, 4, 25, 2, 0, 6, 1];
    $wards = ['Ahero', 'Awasi/Onjiko', 'East Kano/Wawidhi', 'Kabonyo/Kanyagwal', 'Kobura'];
    $st = $pdo->prepare("INSERT IGNORE INTO forecast (ward, forecast_date, temp_max, temp_min, rainfall_mm, summary, source) VALUES (?,?, ?,?,?,?, 'SIMULATED')");
    foreach ($wards as $w) {
        for ($i = 0; $i < 7; $i++) {
            $st->execute([$w, date('Y-m-d', strtotime("+$i days")), 27 + ($i % 3), 17 + ($i % 2), $rain[$i], $summaries[$i]]);
        }
    }
}

function q(string $sql, array $params = []): array {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function run(string $sql, array $params = []): int {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

// --- Helpers ---------------------------------------------------------------
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_ok(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals((string)$_SESSION['csrf'], (string)$_POST['csrf']);
}

// 0712 345 678 / 712345678 / 254712345678  ->  +254712345678
function norm_phone(string $p): string {
    $p = preg_replace('/[\s\-()]/', '', trim($p));
    if (str_starts_with($p, '0')) return '+254' . substr($p, 1);
    if (str_starts_with($p, '254')) return '+' . $p;
    if (str_starts_with($p, '+254')) return $p;
    return $p;
}

// --- Activity log: the record staff see in the admin area -----------------
// details is capped at 200 chars by the table, so callers must put the
// identifying facts FIRST: name, phone, ward — never only a county or a
// type. A log row that does not say WHO did it is useless to staff.
function log_activity(string $page, string $action, string $details = ''): void {
    try {
        run('INSERT INTO activity_log (page, action, details) VALUES (?,?,?)',
            [$page, $action, mb_substr($details, 0, 200)]);
    } catch (Throwable $e) { /* logging must never break the page */ }
}

// How many contact messages staff have not opened yet. Shown as a badge
// on the admin dashboard and on the Messages link, so a new message
// can never sit unseen.
function unread_message_count(): int {
    try {
        return (int)(q("SELECT COUNT(*) n FROM message WHERE is_read = 0")[0]['n'] ?? 0);
    } catch (Throwable $e) { return 0; }
}

// --- Staff and roles (doc Table 3.8, 3.4.5) ------------------------------
// Staff sign in with email and password; roles are enforced on the
// server, not only hidden in the interface. An officer only ever
// sees their own ward; an administrator sees everything.
function staff_row(): ?array {
    if (empty($_SESSION['staff_id'])) return null;
    $r = q('SELECT * FROM staff WHERE staff_id = ? AND active = 1', [(int)$_SESSION['staff_id']]);
    return $r[0] ?? null;
}
function staff_role(): string { return (string)($_SESSION['staff_role'] ?? ''); }
function staff_ward(): ?string { // null = administrator, sees all wards
    return staff_role() === 'officer' ? (string)($_SESSION['staff_ward'] ?? '') : null;
}
function can_see_ward(string $ward): bool {
    $w = staff_ward();
    return $w === null || $w === $ward;
}
function require_staff(): void {
    if (!staff_row()) { header('Location: ../login.php?next=admin'); exit; }
}
function require_admin(): void {
    if (staff_role() !== 'admin') { header('Location: index.php'); exit; }
}

// --- Reference data (doc Table 4.3) ---------------------------------------
function ward_list(): array {
    try { return array_column(q('SELECT name, lat, lon FROM ward ORDER BY ward_id'), 'name'); }
    catch (Throwable $e) { return array_keys(COUNTIES['Kisumu']); }
}
function ward_names(): array {
    try { return array_column(q('SELECT name FROM ward ORDER BY ward_id'), 'name'); }
    catch (Throwable $e) { return COUNTIES[SCOPE_COUNTY]; }
}
function market_list(): array { // name => ward
    try { return array_column(q('SELECT name, ward FROM market ORDER BY market_id'), 'ward', 'name'); }
    catch (Throwable $e) { return NYANDO_MARKETS; }
}
function commodity_list(): array { // name => unit
    try { return array_column(q('SELECT name, unit FROM commodity ORDER BY name'), 'unit', 'name'); }
    catch (Throwable $e) { return []; }
}

// --- Subscriptions (doc FR-F-04/05, FR-A-07) ------------------------------
// At most one subscription per farmer, ward and channel (unique key).
// A repeat subscribe reactivates the row instead of duplicating it.
// Reactivation is the farmer's own act (USSD option 4 or My account);
// staff may deactivate, but never reactivate on a farmer's behalf.
function farmer_subscriptions(int $farmer_id): array {
    return q('SELECT * FROM subscription WHERE farmer_id = ? ORDER BY ward', [$farmer_id]);
}
function subscribe_farmer(int $farmer_id, string $ward): void {
    run("INSERT INTO subscription (farmer_id, ward, channel, active) VALUES (?,?, 'sms', 1) ON DUPLICATE KEY UPDATE active = 1", [$farmer_id, $ward]);
    run('UPDATE farmer SET subscribed = 1 WHERE farmer_id = ?', [$farmer_id]);
}
function unsubscribe_farmer(int $farmer_id, ?string $ward = null): void {
    if ($ward === null) {
        run('UPDATE subscription SET active = 0 WHERE farmer_id = ?', [$farmer_id]);
        run('UPDATE farmer SET subscribed = 0 WHERE farmer_id = ?', [$farmer_id]);
    } else {
        run('UPDATE subscription SET active = 0 WHERE farmer_id = ? AND ward = ?', [$farmer_id, $ward]);
        if (!q('SELECT 1 FROM subscription WHERE farmer_id = ? AND active = 1', [$farmer_id])) {
            run('UPDATE farmer SET subscribed = 0 WHERE farmer_id = ?', [$farmer_id]);
        }
    }
}

// --- SMS templates (doc Appendix C.2) -------------------------------------
// Six templates, English + Kiswahili, {placeholders}. 1 segment = 160
// GSM chars, more = ceil(n / 153) for concatenated messages.
const SMS_TEMPLATES = [
    'heavy_rain' => [
        'en' => 'Heavy rain ({rainfall}mm) expected in {ward} on {date}. Protect your crops and livestock; delay fertilizer application.',
        'sw' => 'Mvua kubwa ({rainfall}mm) inatarajiwa {ward} tarehe {date}. Linda mazao na mifugo yako; chelewesha kupanda mbolea.',
    ],
    'dry_spell' => [
        'en' => 'A dry spell is expected in {ward} over the next {days} days. Conserve water, mulch your crops and delay planting.',
        'sw' => 'Kiangazi kinatarajiwa {ward} kwa siku {days} zijazo. Hifadhi maji, funika mazao na chelewesha kupanda.',
    ],
    'price_rise' => [
        'en' => '{commodity} is now KSh {price} per {unit} at {market} ({date}). A good time to sell.',
        'sw' => '{commodity} sasa ni KSh {price} kwa {unit} katika {market} ({date}). Wakati mzuri wa kuuza.',
    ],
    'price_drop' => [
        'en' => '{commodity} has dropped to KSh {price} per {unit} at {market} ({date}). Consider holding your stock.',
        'sw' => '{commodity} imeshuka hadi KSh {price} kwa {unit} katika {market} ({date}). Fikiria kuhifadhi mazao yako.',
    ],
    'advisory' => [
        'en' => 'New advisory for {ward}: {title}. Open ShambaPulse for the full guidance.',
        'sw' => 'Ushauri mpya kwa {ward}: {title}. Fungua ShambaPulse kwa maelezo kamili.',
    ],
    'welcome' => [
        'en' => 'Welcome to ShambaPulse, {name}! You will receive climate alerts and market prices for {ward} by SMS. Reply STOP to opt out.',
        'sw' => 'Karibu ShambaPulse, {name}! Utapokea tahadhari za hali ya hewa na bei za soko za {ward} kwa SMS. Tuma STOP kujiondoa.',
    ],
];
function sms_render(string $key, string $lang, array $vars): string {
    $t = SMS_TEMPLATES[$key][$lang] ?? SMS_TEMPLATES[$key]['en'] ?? '';
    foreach ($vars as $k => $v) $t = str_replace('{' . $k . '}', (string)$v, $t);
    return $t;
}
function sms_segments(string $text): int {
    $n = mb_strlen($text);
    return $n <= 160 ? 1 : (int)ceil($n / 153);
}
function mask_phone(string $p): string { // doc 3.4.5: mask phones in logs
    $d = preg_replace('/\D/', '', $p);
    return strlen($d) > 5 ? substr($d, 0, 4) . '****' . substr($d, -2) : '****';
}

// Africa's Talking keys: leave empty for the simulated demo (default).
// Fill them in to send real SMS; a failed send is logged as failed,
// never silently treated as delivered (doc NFR-03/04).
define('AT_USERNAME', '');
define('AT_API_KEY', '');

// Send one SMS and log it in message_log. Simulated (default) logs as
// simulated and delivers nothing to the farmer.
function send_sms(array $a): array {
    $row = [
        'channel' => 'sms', 'phone' => $a['phone'], 'ward_name' => $a['ward_name'] ?? '',
        'alert_title' => $a['alert_title'] ?? '', 'message' => $a['message'],
        'direction' => 'out', 'status' => 'simulated', 'gateway_ref' => '',
        'farmer_id' => $a['farmer_id'] ?? null, 'alert_id' => $a['alert_id'] ?? null,
        'ward' => $a['ward'] ?? '',
    ];
    if (AT_USERNAME === '' || AT_API_KEY === '') {
        run('INSERT INTO message_log (channel, phone, ward_name, alert_title, message, direction, status, gateway_ref, farmer_id, alert_id, ward) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            array_values($row));
        $row['status'] = 'simulated';
        return $row;
    }
    $ch = curl_init('https://api.africastalking.com/version1/messaging');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'apiKey: ' . AT_API_KEY, 'Accept: application/json'],
        CURLOPT_POSTFIELDS => http_build_query(['username' => AT_USERNAME, 'to' => $a['phone'], 'message' => $a['message']])]);
    $resp = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
    $row['status'] = ($resp !== false && $code >= 200 && $code < 300) ? 'submitted' : 'failed';
    $row['gateway_ref'] = mb_substr((string)$resp, 0, 200);
    run('INSERT INTO message_log (channel, phone, ward_name, alert_title, message, direction, status, gateway_ref, farmer_id, alert_id, ward) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
        array_values($row));
    return $row;
}

// Dispatch one alert (doc Fig. 4.5/4.7): active subscribers of the
// alert's ward, skipping any farmer without recorded consent. The
// alert is marked sent only when at least one message was submitted
// (simulated counts as submitted in the demo); with no eligible
// recipients nothing is sent and the alert stays a draft.
function dispatch_alert(int $alert_id): array {
    $a = q('SELECT * FROM alert WHERE alert_id = ?', [$alert_id])[0] ?? null;
    if (!$a) return ['sent' => 0, 'skipped' => 0];
    $farmers = q("SELECT f.* FROM farmer f JOIN subscription s ON s.farmer_id = f.farmer_id AND s.ward = ? AND s.channel = 'sms' AND s.active = 1 WHERE f.consent = 1 AND f.consent_at IS NOT NULL ORDER BY f.farmer_id", [$a['ward']]);
    $sent = 0;
    foreach ($farmers as $f) {
        $lang = $f['language'] === 'Kiswahili' ? 'sw' : 'en';
        $msg = $lang === 'sw' ? $a['message_sw'] : $a['message_en'];
        send_sms(['phone' => $f['phone'], 'ward_name' => $a['ward'], 'alert_title' => $a['title'] ?: $a['type'],
            'message' => $msg, 'farmer_id' => (int)$f['farmer_id'], 'alert_id' => $alert_id, 'ward' => $a['ward']]);
        $sent++;
    }
    if ($sent > 0) run("UPDATE alert SET status = 'sent' WHERE alert_id = ?", [$alert_id]);
    return ['sent' => $sent, 'skipped' => 0];
}
function delivery_counts(int $alert_id): array {
    $rows = q('SELECT status, COUNT(*) n FROM message_log WHERE alert_id = ? GROUP BY status', [$alert_id]);
    $out = ['submitted' => 0, 'delivered' => 0, 'failed' => 0, 'simulated' => 0];
    foreach ($rows as $r) $out[$r['status']] = (int)$r['n'];
    return $out;
}

// --- Forecast (doc 1.7, NFR-01/04) -----------------------------------------
// Open-Meteo 7-day forecast per ward centroid, fetched at 06:00 EAT
// (call fetch_forecast.php daily, or let the forecast page refresh
// when the stored data is older than a day). A failed fetch never
// breaks the page — the stored forecast stays, labelled by source.
function fetch_forecasts(): array {
    $wards = q('SELECT name, lat, lon FROM ward ORDER BY ward_id');
    $fetched = 0;
    foreach ($wards as $w) {
        $url = 'https://api.open-meteo.com/v1/forecast?latitude=' . $w['lat'] . '&longitude=' . $w['lon']
            . '&daily=temperature_2m_max,temperature_2m_min,precipitation_sum&timezone=Africa%2FNairobi&forecast_days=7';
        $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
        $json = @file_get_contents($url, false, $ctx);
        if (!$json) continue;
        $d = json_decode($json, true);
        if (empty($d['daily']['time'])) continue;
        foreach ($d['daily']['time'] as $i => $date) {
            $rain = (float)($d['daily']['precipitation_sum'][$i] ?? 0);
            $summary = $rain >= 20 ? 'Heavy rain' : ($rain >= 1 ? 'Light rain' : ((float)($d['daily']['temperature_2m_max'][$i] ?? 0) >= 28 ? 'Sunny intervals' : 'Cloudy'));
            run("INSERT INTO forecast (ward, forecast_date, temp_max, temp_min, rainfall_mm, summary, source) VALUES (?,?,?,?,?,?,'Open-Meteo') ON DUPLICATE KEY UPDATE temp_max = VALUES(temp_max), temp_min = VALUES(temp_min), rainfall_mm = VALUES(rainfall_mm), summary = VALUES(summary), source = 'Open-Meteo', fetched_at = NOW()",
                [$w['name'], $date, $d['daily']['temperature_2m_max'][$i] ?? null, $d['daily']['temperature_2m_min'][$i] ?? null, $rain, $summary]);
            $fetched++;
        }
    }
    return ['fetched' => $fetched];
}
function maybe_refresh_forecasts(): void {
    try {
        $fresh = q("SELECT COUNT(*) n FROM forecast WHERE source = 'Open-Meteo' AND forecast_date >= CURDATE()")[0]['n'] ?? 0;
        if ((int)$fresh === 0) fetch_forecasts();
    } catch (Throwable $e) { /* offline: keep the stored forecast */ }
}
function ward_forecast(string $ward): array {
    // Live Open-Meteo rows first; SIMULATED seed rows only fill gaps.
    return q("SELECT * FROM forecast WHERE ward = ? AND forecast_date >= CURDATE() ORDER BY (source = 'Open-Meteo') DESC, forecast_date LIMIT 7", [$ward]);
}

// --- Page layout (Japhet's own css/style.css, unchanged) -------------------
function layout_top(string $title, string $current, string $bodyClass = 'inner'): void {
    $staff = !empty($_SESSION['staff']);
    $farmer = !empty($_SESSION['farmer_id']);
    $links = [
        ['index.php', 'Home'], ['about.php', 'About'], ['alerts.php', 'Alerts'],
        ['forecast.php', 'Forecast'], ['advisories.php', 'Advisories'],
        ['prices.php', 'Prices'], ['ussd.php', 'USSD *384#'], ['contact.php', 'Contact'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> - ShambaPulse</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&amp;family=Source+Serif+4:wght@600&amp;display=swap">
<link rel="stylesheet" href="css/style.css">
<link rel="icon" type="image/jpeg" href="img/bcd.jpg">
</head>
<body class="<?= e($bodyClass) ?>">
<header>
    <h1><img class="logo" src="img/logo.jpg" alt="ShambaPulse logo"> ShambaPulse</h1>
    <p>Where farmers meet quality services.</p>
</header>
<nav aria-label="Main menu"><ul>
<?php foreach ($links as [$href, $label]): ?>
    <li><a href="<?= e($href) ?>"<?= $href === $current ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
<?php endforeach; ?>
<?php if ($staff): ?>
    <li><a href="admin/index.php">Staff area<?php $u = unread_message_count(); if ($u > 0): ?> (<?= $u ?>)<?php endif; ?></a></li>
    <li><a href="logout.php">Sign out</a></li>
<?php elseif ($farmer): ?>
    <li><a href="account.php">My account</a></li>
    <li><a href="logout.php">Sign out</a></li>
<?php else: ?>
    <li><a href="login.php">Sign in</a></li>
<?php endif; ?>
</ul></nav>
<main>
<?php
}

function layout_bottom(): void {
    ?>
</main>
<footer>
    <p>ShambaPulse, Nairobi</p>
    <p>Site built by Kinanga Japhet and Gideon Francis, DBIT Year 1, 2026.</p>
</footer>
</body>
</html>
<?php
}
