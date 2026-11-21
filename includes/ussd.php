<?php
// ShambaPulse *384# menu — Nyando Sub-County, Kisumu County only.
// Doc Appendix C.1: 1 Weather forecast, 2 Market prices, 3 Advisories,
// 4 Subscribe to alerts, 5 Opt out of alerts. Numbered menus only,
// at most three levels deep, each screen within 182 characters.
// Same logic serves the live Africa's Talking callback
// (ussd_callback.php) and the web simulator (ussd.php).
// Returns "CON ..." / "END ...".
declare(strict_types=1);

// Menu wording in English and Kiswahili. A registered farmer sees
// the menu in their own language (doc FR-F-12, NFR-07).
const USSD_TEXT = [
    'en' => [
        'menu' => "ShambaPulse\n1. Weather forecast\n2. Market prices\n3. Advisories\n4. Subscribe to alerts\n5. Opt out of alerts",
        'ward' => 'Choose your ward:',
        'language' => "Choose language:\n1. English\n2. Kiswahili",
        'consent' => 'Data Protection Act 2019: may ShambaPulse store your phone and ward to send alerts? 1. Yes 2. No',
        'no_consent' => 'No problem. Nothing was stored.',
        'subscribed' => 'Subscribed to alerts for {ward}. Karibu ShambaPulse.',
        'already' => 'You are already subscribed to alerts for {ward}.',
        'optout' => 'You will no longer receive alerts. Dial *384# and choose 4 to subscribe again.',
        'commodity' => "Choose a commodity:\n1. Maize\n2. Beans\n3. Milk\n4. Tomatoes",
        'bad_ward' => 'Please choose a ward from 1 to 5.',
        'bad_commodity' => 'Please choose a commodity from 1 to 4.',
        'no_forecast' => 'No forecast for {ward} yet.',
        'no_price' => 'No {commodity} price recorded yet.',
        'no_advisory' => 'No advisory for {ward} yet.',
        'again' => 'Please dial *384# and choose 1 to 5.',
    ],
    'sw' => [
        'menu' => "ShambaPulse\n1. Utabiri wa hali ya hewa\n2. Bei za soko\n3. Ushauri\n4. Jiandikishe kwa tahadhari\n5. Jiondoe kwenye tahadhari",
        'ward' => 'Chagua wadi yako:',
        'language' => "Chagua lugha:\n1. English\n2. Kiswahili",
        'consent' => 'Sheria ya Ulinzi wa Data 2019: ShambaPulse inaweza kuhifadhi namba yako na wadi kutuma tahadhari? 1. Ndiyo 2. Hapana',
        'no_consent' => 'Sawa. Hakuna kilichohifadhiwa.',
        'subscribed' => 'Umejiandikisha kwa tahadhari za {ward}. Karibu ShambaPulse.',
        'already' => 'Tayari umejiandikisha kwa tahadhari za {ward}.',
        'optout' => 'Hutapokea tena tahadhari. Piga *384# na uchague 4 kujiandikisha tena.',
        'commodity' => "Chagua mazao:\n1. Mahindi\n2. Maharagwe\n3. Maziwa\n4. Nyanya",
        'bad_ward' => 'Tafadhali chagua wadi 1 hadi 5.',
        'bad_commodity' => 'Tafadhali chagua mazao 1 hadi 4.',
        'no_forecast' => 'Hakuna utabiri wa {ward} bado.',
        'no_price' => 'Hakuna bei ya {commodity} iliyorekodiwa bado.',
        'no_advisory' => 'Hakuna ushauri wa {ward} bado.',
        'again' => 'Tafadhali piga *384# na uchague 1 hadi 5.',
    ],
];

function ussd_lang(string $phone): string {
    $f = q('SELECT language FROM farmer WHERE phone = ?', [$phone])[0] ?? null;
    return ($f && $f['language'] === 'Kiswahili') ? 'sw' : 'en';
}
function ussd_t(string $lang, string $key, array $vars = []): string {
    $s = USSD_TEXT[$lang][$key] ?? USSD_TEXT['en'][$key];
    foreach ($vars as $k => $v) $s = str_replace('{' . $k . '}', (string)$v, $s);
    return $s;
}

function ussd_wards(): array {
    return COUNTIES[SCOPE_COUNTY]; // Ahero, Awasi/Onjiko, East Kano/Wawidhi, Kabonyo/Kanyagwal, Kobura
}

function ussd_ward_list(): string {
    $out = [];
    foreach (ussd_wards() as $i => $w) $out[] = ($i + 1) . '. ' . $w;
    return implode("\n", $out);
}

function ussd_log(string $phone, int $choice, string $language = 'English'): void {
    try {
        run('INSERT INTO ussd_session (phone, language, menu_choice) VALUES (?,?,?)', [$phone, $language, $choice]);
    } catch (Throwable $e) { /* never break the menu */ }
}

function ussd_handle(string $phone, string $text): string {
    $lang = ussd_lang($phone);
    $steps = $text === '' ? [] : explode('*', $text);
    $choice = (int)($steps[0] ?? 0);

    if ($steps === []) {
        return 'CON ' . ussd_t($lang, 'menu');
    }
    if ($choice >= 1 && $choice <= 5) ussd_log($phone, $choice, $lang === 'sw' ? 'Kiswahili' : 'English');

    // 1 Weather forecast (FR-F-06): 1 -> ward, 1*ward -> today's forecast
    if ($choice === 1) {
        if (count($steps) === 1) return 'CON ' . ussd_t($lang, 'ward') . "\n" . ussd_ward_list();
        $ward = ussd_wards()[(int)$steps[1] - 1] ?? null;
        if (!$ward) return 'END ' . ussd_t($lang, 'bad_ward');
        $f = q("SELECT * FROM forecast WHERE ward = ? AND forecast_date >= CURDATE() ORDER BY (source = 'Open-Meteo') DESC, forecast_date LIMIT 1", [$ward])[0] ?? null;
        if (!$f) return 'END ' . ussd_t($lang, 'no_forecast', ['ward' => $ward]);
        $msg = $lang === 'sw'
            ? "$ward leo: {$f['summary']}, mvua {$f['rainfall_mm']}mm, {$f['temp_min']}-{$f['temp_max']}C"
            : "$ward today: {$f['summary']}, rain {$f['rainfall_mm']}mm, {$f['temp_min']}-{$f['temp_max']}C";
        return 'END ' . mb_substr($msg, 0, 182);
    }

    // 2 Market prices (FR-F-08): 2 -> commodity, 2*commodity -> latest
    // price, shown with its unit, source and date.
    if ($choice === 2) {
        $commodities = ['Maize', 'Beans', 'Milk', 'Tomatoes'];
        if (count($steps) === 1) return 'CON ' . ussd_t($lang, 'commodity');
        $c = $commodities[(int)$steps[1] - 1] ?? null;
        if (!$c) return 'END ' . ussd_t($lang, 'bad_commodity');
        $p = q('SELECT market, unit, price, price_date, source FROM price WHERE commodity = ? AND county = ? ORDER BY price_date DESC, price_id DESC LIMIT 1', [$c, SCOPE_COUNTY])[0] ?? null;
        if (!$p) return 'END ' . ussd_t($lang, 'no_price', ['commodity' => $c]);
        $src = $p['source'] === 'feed' ? 'SIMULATED' : $p['source'];
        return 'END ' . $c . ' ' . $p['market'] . ': KSh ' . number_format((float)$p['price']) . ' per ' . $p['unit'] . ', ' . $src . ' (' . date('d/m/Y', strtotime($p['price_date'])) . ')';
    }

    // 3 Advisories (FR-F-11): 3 -> ward, 3*ward -> latest advisory,
    // in the farmer's language.
    if ($choice === 3) {
        if (count($steps) === 1) return 'CON ' . ussd_t($lang, 'ward') . "\n" . ussd_ward_list();
        $ward = ussd_wards()[(int)$steps[1] - 1] ?? null;
        if (!$ward) return 'END ' . ussd_t($lang, 'bad_ward');
        $a = q('SELECT title, body_en, body_sw FROM advisory WHERE ward = ? ORDER BY published_at DESC, advisory_id DESC LIMIT 1', [$ward])[0] ?? null;
        if (!$a) return 'END ' . ussd_t($lang, 'no_advisory', ['ward' => $ward]);
        $body = $lang === 'sw' ? $a['body_sw'] : $a['body_en'];
        return 'END ' . mb_substr($a['title'] . ': ' . $body, 0, 182);
    }

    // 4 Subscribe (FR-F-01/04): ward -> language -> consent.
    // No consent = nothing stored. An already-registered phone gets
    // the chosen ward added (or reactivated) — never a second account.
    if ($choice === 4) {
        if (count($steps) === 1) return 'CON ' . ussd_t($lang, 'ward') . "\n" . ussd_ward_list();
        $ward = ussd_wards()[(int)$steps[1] - 1] ?? null;
        if (!$ward) return 'END ' . ussd_t($lang, 'bad_ward');
        if (count($steps) === 2) return 'CON ' . ussd_t($lang, 'language');
        $new_lang = ((int)$steps[2] === 2) ? 'Kiswahili' : 'English';
        if (count($steps) === 3) return 'CON ' . ussd_t($lang === 'sw' ? 'sw' : 'en', 'consent');
        if ((int)$steps[3] !== 1) return 'END ' . ussd_t($lang, 'no_consent');
        $ex = q('SELECT farmer_id, ward FROM farmer WHERE phone = ?', [$phone])[0] ?? null;
        $already = false;
        if ($ex) {
            $already = (bool)(q('SELECT 1 FROM subscription WHERE farmer_id = ? AND ward = ? AND channel = ? AND active = 1', [(int)$ex['farmer_id'], $ward, 'sms'])[0] ?? null);
            run('UPDATE farmer SET language = ?, consent = 1, consent_at = NOW(), subscribed = 1 WHERE phone = ?', [$new_lang, $phone]);
            subscribe_farmer((int)$ex['farmer_id'], $ward);
        } else {
            run('INSERT INTO farmer (full_name, phone, county, ward, language, consent, consent_at, subscribed) VALUES (?,?,?,?,?,1,NOW(),1)',
                ['USSD farmer', $phone, SCOPE_COUNTY, $ward, $new_lang]);
            $fid = (int)db()->lastInsertId();
            subscribe_farmer($fid, $ward);
        }
        // Welcome SMS (template C.2) — simulated in the demo.
        $wl = $new_lang === 'Kiswahili' ? 'sw' : 'en';
        send_sms(['phone' => $phone, 'ward_name' => $ward, 'alert_title' => 'Welcome',
            'message' => sms_render('welcome', $wl, ['name' => 'farmer', 'ward' => $ward]),
            'farmer_id' => $ex ? (int)$ex['farmer_id'] : ($fid ?? null), 'ward' => $ward]);
        log_activity('ussd', 'ussd_subscribe', "$phone, $ward, Nyando, $new_lang");
        return 'END ' . ussd_t($lang, $already ? 'already' : 'subscribed', ['ward' => $ward]);
    }

    // 5 Opt out (FR-F-05): all ward subscriptions off.
    if ($choice === 5) {
        $ex = q('SELECT farmer_id FROM farmer WHERE phone = ?', [$phone])[0] ?? null;
        if ($ex) unsubscribe_farmer((int)$ex['farmer_id']);
        else run('UPDATE farmer SET subscribed = 0 WHERE phone = ?', [$phone]);
        log_activity('ussd', 'ussd_opt_out', "$phone, Nyando");
        return 'END ' . ussd_t($lang, 'optout');
    }

    return 'END ' . ussd_t($lang, 'again');
}
