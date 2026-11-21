<?php
// check.php — diagnostic page. Open it in the browser to see which site
// and which database THIS copy of ShambaPulse is using. If the numbers
// here do not match what the admin area shows, you are opening two
// different copies of the site (for example an old folder and the new
// folder), and that is why edits do not reflect.
require_once __DIR__ . '/includes/bootstrap.php';

$info = [];
try {
    $db = db();
    $info['database'] = DB_NAME . ' on ' . DB_HOST;
    $info['prices'] = (int)(q('SELECT COUNT(*) n FROM price')[0]['n'] ?? 0);
    $info['messages'] = (int)(q('SELECT COUNT(*) n FROM message')[0]['n'] ?? 0);
    $info['unread'] = unread_message_count();
    $info['farmers'] = (int)(q('SELECT COUNT(*) n FROM farmer')[0]['n'] ?? 0);
    $info['alerts'] = (int)(q('SELECT COUNT(*) n FROM alert')[0]['n'] ?? 0);
    $info['newest_message'] = q('SELECT full_name, message, created_at FROM message ORDER BY message_id DESC LIMIT 1')[0] ?? null;
    $info['newest_price'] = q('SELECT commodity, market, price, price_date FROM price ORDER BY price_id DESC LIMIT 1')[0] ?? null;
    $cols = q("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'message' AND COLUMN_NAME = 'is_read'", [DB_NAME]);
    $info['has_is_read'] = !empty($cols);
    // Write test: log one line and read it straight back. If this says
    // OK, this copy of the site can both write to and read from the
    // same database — so an edit made here must show up here.
    log_activity('check', 'check', 'check page opened');
    $back = q("SELECT * FROM activity_log WHERE page = 'check' ORDER BY log_id DESC LIMIT 1")[0] ?? null;
    $info['write_test'] = $back ? 'OK — write and read-back worked' : 'FAILED — could not read back what was written';
    $info['recent'] = q('SELECT page, action, details, created_at FROM activity_log ORDER BY log_id DESC LIMIT 5');
} catch (Throwable $e) {
    $info['error'] = $e->getMessage();
}
layout_top('Check', 'check.php', 'inner');
?>
    <section class="page-intro"><h2>Check</h2><p>This page shows which site and database this copy is using.</p></section>
    <section class="content">
        <p><strong>Folder on this computer:</strong> <?= e(__DIR__) ?></p>
<?php if (!empty($info['error'])): ?>
        <p role="alert">Database error: <?= e($info['error']) ?></p>
<?php else: ?>
        <p><strong>Database:</strong> <?= e($info['database']) ?></p>
        <p><strong>Prices:</strong> <?= $info['prices'] ?> &middot; <strong>Messages:</strong> <?= $info['messages'] ?> (<?= $info['unread'] ?> unread) &middot; <strong>Farmers:</strong> <?= $info['farmers'] ?> &middot; <strong>Alerts:</strong> <?= $info['alerts'] ?></p>
        <p><strong>New message column (is_read):</strong> <?= $info['has_is_read'] ? 'yes' : 'no — this is an old database' ?></p>
        <p><strong>Newest message:</strong> <?= $info['newest_message'] ? e($info['newest_message']['full_name']) . ' — ' . e(mb_substr($info['newest_message']['message'], 0, 80)) . ' (' . e($info['newest_message']['created_at']) . ')' : 'none yet' ?></p>
        <p><strong>Newest price:</strong> <?= $info['newest_price'] ? e($info['newest_price']['commodity']) . ', ' . e($info['newest_price']['market']) . ', Ksh ' . number_format((float)$info['newest_price']['price']) . ' (' . e($info['newest_price']['price_date']) . ')' : 'none yet' ?></p>
        <p><strong>Write test:</strong> <?= e($info['write_test']) ?></p>
        <h2>Recent activity</h2>
        <ul>
<?php foreach ($info['recent'] as $r): ?>
            <li><?= e($r['created_at']) ?> — <?= e($r['page']) ?> / <?= e($r['action']) ?> — <?= e($r['details']) ?></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
    </section>
<?php layout_bottom();
