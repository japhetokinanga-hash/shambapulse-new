<?php
require_once __DIR__ . '/_admin.php';
$ward = staff_ward(); // null = administrator (all wards); officer = own ward only (FR-O-01/02)
$unread = unread_message_count();
if ($ward === null) {
    $counts = q("SELECT (SELECT COUNT(*) FROM message) messages, (SELECT COUNT(*) FROM farmer WHERE county = ?) farmers, (SELECT COUNT(*) FROM price WHERE county = ?) prices, (SELECT COUNT(*) FROM alert WHERE county = ?) alerts, (SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE() AND action IN ('farmer_register','farmer_login','staff_login','ussd_subscribe')) signins_today", [SCOPE_COUNTY, SCOPE_COUNTY, SCOPE_COUNTY])[0];
    $latestFarmers = q('SELECT full_name, phone, ward, language, subscribed, created_at FROM farmer WHERE county = ? ORDER BY farmer_id DESC LIMIT 5', [SCOPE_COUNTY]);
    $wardStats = null;
} else {
    $markets = array_keys(array_filter(market_list(), fn($w) => $w === $ward));
    $in = $markets ? "'" . implode("','", array_map('addslashes', $markets)) . "'" : "'__none__'";
    $counts = q("SELECT (SELECT COUNT(*) FROM message) messages, (SELECT COUNT(*) FROM farmer WHERE ward = ?) farmers, (SELECT COUNT(*) FROM price WHERE market IN ($in)) prices, (SELECT COUNT(*) FROM alert WHERE ward = ?) alerts, (SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE() AND action IN ('farmer_register','farmer_login','staff_login','ussd_subscribe')) signins_today", [$ward, $ward])[0];
    $latestFarmers = q('SELECT full_name, phone, ward, language, subscribed, created_at FROM farmer WHERE ward = ? ORDER BY farmer_id DESC LIMIT 5', [$ward]);
    $wardStats = q('SELECT language, COUNT(*) n, SUM(subscribed) s FROM farmer WHERE ward = ? GROUP BY language', [$ward]);
}
$newMessages = q('SELECT message_id, full_name, email, type, message, created_at FROM message WHERE is_read = 0 ORDER BY message_id DESC LIMIT 5');
$signins = q("SELECT page, action, details, created_at FROM activity_log WHERE action IN ('farmer_register','farmer_login','staff_login','ussd_subscribe','ussd_opt_out') ORDER BY log_id DESC LIMIT 10");
admin_top('Dashboard', 'index.php');
?>
    <section class="page-intro">
        <h2>Dashboard<?= $ward ? ' — ' . e($ward) : '' ?></h2>
        <p><?= (int)$counts['messages'] ?> contact messages (<?= $unread ?> unread) &middot; <?= (int)$counts['farmers'] ?> farmers<?= $ward ? ' in ' . e($ward) : '' ?> &middot; <?= (int)$counts['prices'] ?> prices &middot; <?= (int)$counts['alerts'] ?> alerts &middot; <?= (int)$counts['signins_today'] ?> sign-ins today.</p>
<?php if ($wardStats): ?>
        <p><?php foreach ($wardStats as $s): ?><?= e($s['language']) ?>: <?= (int)$s['n'] ?> farmers (<?= (int)$s['s'] ?> subscribed). <?php endforeach; ?></p>
<?php endif; ?>
    </section>

    <section>
        <h2>New contact messages<?= $unread ? " ($unread)" : '' ?></h2>
<?php if (!$newMessages): ?><p class="empty">No unread messages.</p><?php endif; ?>
<?php foreach ($newMessages as $m): ?>
        <div class="sms-row" style="margin-bottom:1rem"><div class="sms">
            <span class="sms-label">NEW &middot; <?= e($m['type']) ?> &middot; <?= e(date('j M Y, H:i', strtotime($m['created_at']))) ?></span>
            <p><strong><?= e($m['full_name']) ?></strong> (<?= e($m['email']) ?>)</p>
            <p><?= e(mb_substr($m['message'], 0, 200)) ?></p>
            <p><a href="reply-message.php?id=<?= (int)$m['message_id'] ?>">Read and reply</a></p>
        </div></div>
<?php endforeach; ?>
        <p><a href="messages.php">See all messages</a></p>
    </section>

    <section>
        <h2>Recent sign-ins</h2>
<?php if (!$signins): ?><p class="empty">No sign-ins recorded yet.</p><?php endif; ?>
        <table><thead><tr><th>When</th><th>What</th><th>Who</th></tr></thead><tbody>
<?php foreach ($signins as $s): ?><tr><td><?= e(date('d/m/Y H:i', strtotime($s['created_at']))) ?></td><td><?= e($s['action']) ?></td><td><?= e($s['details']) ?></td></tr><?php endforeach; ?>
        </tbody></table>
        <p><a href="signins.php">See all sign-ins</a> &middot; <a href="farmers.php">See all farmers</a></p>
    </section>

    <section>
        <h2>Farmers</h2>
<?php if (!$latestFarmers): ?><p class="empty">No farmers registered yet.</p><?php endif; ?>
        <table><thead><tr><th>Name</th><th>Phone</th><th>Ward</th><th>Language</th><th>Alerts</th><th>Joined</th></tr></thead><tbody>
<?php foreach ($latestFarmers as $f): ?><tr><td><?= e($f['full_name']) ?></td><td><?= e($f['phone']) ?></td><td><?= e($f['ward']) ?></td><td><?= e($f['language']) ?></td><td><?= $f['subscribed'] ? 'Subscribed' : 'Opted out' ?></td><td><?= e(date('d/m/Y', strtotime($f['created_at']))) ?></td></tr><?php endforeach; ?>
        </tbody></table>
    </section>
<?php admin_bottom();
