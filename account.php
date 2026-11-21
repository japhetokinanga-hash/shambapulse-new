<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!empty($_SESSION['staff'])) { header('Location: admin/index.php'); exit; }
if (empty($_SESSION['farmer_id'])) { header('Location: login.php?next=account'); exit; }
$f = q('SELECT * FROM farmer WHERE farmer_id = ?', [$_SESSION['farmer_id']])[0] ?? null;
if (!$f) { header('Location: logout.php'); exit; }

$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) $err = 'Your session expired. Please try again.';
    else switch ($_POST['do'] ?? '') {
        case 'subscribe_ward': // FR-F-04: subscribe to a further ward
            $w = trim($_POST['ward'] ?? '');
            if (in_array($w, COUNTIES[SCOPE_COUNTY], true)) { subscribe_farmer($f['farmer_id'], $w); $msg = "Subscribed to alerts for $w."; }
            else $err = 'Choose a ward.'; break;
        case 'optout_ward': // FR-F-05: opt out of one ward's alerts
            $w = trim($_POST['ward'] ?? '');
            if (in_array($w, COUNTIES[SCOPE_COUNTY], true)) { unsubscribe_farmer($f['farmer_id'], $w); $msg = "You will no longer receive alerts for $w."; }
            else $err = 'Choose a ward.'; break;
        case 'subscribe':
            if (!empty($_POST['on'])) { subscribe_farmer($f['farmer_id'], $f['ward']); $msg = 'You are subscribed to alerts again.'; }
            else { unsubscribe_farmer($f['farmer_id']); $msg = 'You will no longer receive alerts.'; } break;
        case 'language':
            if (in_array($_POST['language'] ?? '', ['English', 'Kiswahili'], true)) { run('UPDATE farmer SET language = ? WHERE farmer_id = ?', [$_POST['language'], $f['farmer_id']]); $msg = 'Language updated.'; } break;
        case 'password':
            if (!password_verify($_POST['current'] ?? '', $f['password_hash'] ?? '')) $err = 'Your current password is not correct.';
            elseif (strlen($_POST['new'] ?? '') < 8) $err = 'The new password must have at least 8 characters.';
            elseif ($_POST['new'] !== ($_POST['new2'] ?? '')) $err = 'The two new passwords do not match.';
            else { run('UPDATE farmer SET password_hash = ? WHERE farmer_id = ?', [password_hash($_POST['new'], PASSWORD_DEFAULT), $f['farmer_id']]); $msg = 'Password changed.'; } break;
    }
    $f = q('SELECT * FROM farmer WHERE farmer_id = ?', [$f['farmer_id']])[0];
} else { log_activity('account', 'view'); }

$sw = $f['language'] === 'Kiswahili';
$subs = farmer_subscriptions($f['farmer_id']);
$my_wards = array_column(array_filter($subs, fn($s) => $s['active']), 'ward');
$alerts = $my_wards
    ? q("SELECT type, message_en, message_sw, created_at FROM alert WHERE status = 'sent' AND ward IN (" . implode(',', array_fill(0, count($my_wards), '?')) . ") ORDER BY created_at DESC, alert_id DESC LIMIT 5", $my_wards)
    : [];
$prices = q('SELECT p.commodity, p.unit, p.market, p.price, p.price_date FROM price p WHERE p.county = ? AND p.price_date = (SELECT MAX(price_date) FROM price WHERE county = ?) ORDER BY p.commodity LIMIT 6', [$f['county'], $f['county']]);
$forecast = ward_forecast($f['ward']);
$csrf = '<input type="hidden" name="csrf" value="' . e(csrf()) . '">';
layout_top('My account', 'account.php', 'contact');
?>
    <section class="contact-intro">
        <h2><?= $sw ? 'Karibu' : 'Welcome' ?>, <?= e($f['full_name']) ?></h2>
        <p><?= e($f['ward']) ?>, <?= e($f['county']) ?> &middot; <?= e($f['phone']) ?></p>
    </section>
<?php if ($msg): ?><p class="flash" role="status"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err): ?><p class="flash error" role="alert"><?= e($err) ?></p><?php endif; ?>

    <section class="contact-grid">
        <div class="contact-card">
            <h3>My details</h3>
            <p><?= e($f['full_name']) ?><br><?= e($f['phone']) ?><br><?= e($f['ward']) ?>, <?= e($f['county']) ?><br><?= e($f['language']) ?><?php if ($f['consent_at']): ?><br>Consent: <?= e(date('d/m/Y', strtotime($f['consent_at']))) ?><?php endif; ?></p>
        </div>
        <div class="contact-card">
            <h3>SMS alerts</h3>
            <p><span class="badge <?= $f['subscribed'] ? '' : 'draft' ?>"><?= $f['subscribed'] ? 'Subscribed' : 'Opted out' ?></span></p>
<?php foreach ($subs as $s): ?>
            <p><?= e($s['ward']) ?> — <?= $s['active'] ? 'subscribed' : 'opted out' ?>
<?php if ($s['active']): ?> <form method="post" style="display:inline"><?= $csrf ?><input type="hidden" name="do" value="optout_ward"><input type="hidden" name="ward" value="<?= e($s['ward']) ?>"><button class="btn-link">Opt out</button></form><?php endif; ?></p>
<?php endforeach; ?>
            <form method="post" class="form-stack"><?= $csrf ?><input type="hidden" name="do" value="subscribe_ward">
                <select name="ward"><?php foreach (COUNTIES[SCOPE_COUNTY] as $w): ?><option><?= e($w) ?></option><?php endforeach; ?></select>
                <button class="hero-button" type="submit">Subscribe to this ward</button></form>
            <form method="post" style="margin-top:.75rem"><?= $csrf ?><input type="hidden" name="do" value="subscribe">
                <?php if ($f['subscribed']): ?><button class="btn-link">Opt out of all alerts</button>
                <?php else: ?><button class="btn-link" name="on" value="1">Subscribe again</button><?php endif; ?></form>
        </div>
        <div class="contact-card">
            <h3>Language</h3>
            <form method="post" class="form-stack"><?= $csrf ?><input type="hidden" name="do" value="language">
                <select name="language"><option<?= !$sw ? ' selected' : '' ?>>English</option><option<?= $sw ? ' selected' : '' ?>>Kiswahili</option></select>
                <button class="hero-button" type="submit">Save</button></form>
        </div>
        <div class="contact-card">
            <h3>Change password</h3>
            <form method="post" class="form-stack"><?= $csrf ?><input type="hidden" name="do" value="password">
                <input type="password" name="current" placeholder="Current password" required autocomplete="current-password">
                <input type="password" name="new" placeholder="New password (8+ characters)" minlength="8" required autocomplete="new-password">
                <input type="password" name="new2" placeholder="Repeat new password" minlength="8" required autocomplete="new-password">
                <button class="hero-button" type="submit">Change</button></form>
        </div>
    </section>

    <section>
        <h2>7-day forecast for <?= e($f['ward']) ?></h2>
<?php if ($forecast): ?>
        <table><thead><tr><th>Date</th><th>Forecast</th><th>Rain</th><th>High</th><th>Low</th></tr></thead><tbody>
<?php foreach ($forecast as $r): ?><tr><td><?= e(date('D d/m', strtotime($r['forecast_date']))) ?></td><td><?= e($r['summary']) ?></td><td><?= e((string)$r['rainfall_mm']) ?> mm</td><td><?= e((string)$r['temp_max']) ?>&deg;C</td><td><?= e((string)$r['temp_min']) ?>&deg;C</td></tr><?php endforeach; ?>
        </tbody></table>
        <p>Source: <?= e($forecast[0]['source']) ?>. <a href="forecast.php?ward=<?= urlencode($f['ward']) ?>">Full forecast</a></p>
<?php else: ?><p class="empty">No forecast recorded for your ward yet.</p><?php endif; ?>
    </section>

    <section class="sample-alerts">
        <h2>Alerts for <?= e($f['county']) ?></h2>
<?php if (!$alerts): ?><p class="empty">No alerts have been sent for your county yet.</p><?php endif; ?>
<?php foreach ($alerts as $a): ?>
        <div class="sms-row" style="margin-bottom:1rem">
            <div class="sms"><span class="sms-label"><?= e($a['type']) ?> &middot; <?= e(date('j M Y', strtotime($a['created_at']))) ?></span><p><?= e($sw ? $a['message_sw'] : $a['message_en']) ?></p></div>
        </div>
<?php endforeach; ?>
    </section>

    <section>
        <h2>Latest prices in <?= e($f['county']) ?></h2>
<?php if ($prices): ?>
        <table><thead><tr><th>Commodity</th><th>Unit</th><th>Market</th><th>Date</th><th>Price</th></tr></thead><tbody>
<?php foreach ($prices as $r): ?><tr><td><?= e($r['commodity']) ?></td><td><?= e($r['unit']) ?></td><td><?= e($r['market']) ?></td><td><?= e(date('d/m/Y', strtotime($r['price_date']))) ?></td><td>Ksh <?= number_format($r['price']) ?></td></tr><?php endforeach; ?>
        </tbody></table>
<?php else: ?><p class="empty">No prices recorded for your county yet.</p><?php endif; ?>
        <p><a href="prices.php">See all prices</a></p>
    </section>
<?php layout_bottom();
