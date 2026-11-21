<?php
require_once __DIR__ . '/_admin.php';
// Every sign-in and sign-up on the web and on USSD, newest first.
// Details name the person (name, phone, ward) — see login.php and
// includes/ussd.php, which write those details into the log.
$rows = q("SELECT page, action, details, created_at FROM activity_log WHERE action IN ('farmer_register','farmer_login','staff_login','ussd_subscribe','ussd_opt_out') ORDER BY log_id DESC LIMIT 100");
$today = q("SELECT action, COUNT(*) n FROM activity_log WHERE DATE(created_at) = CURDATE() AND action IN ('farmer_register','farmer_login','staff_login','ussd_subscribe') GROUP BY action");
admin_top('Sign-ins', 'signins.php');
?>
    <section class="page-intro">
        <h2>Sign-ins</h2>
        <p>Today:
<?php foreach ($today as $t): ?><?= e($t['action']) ?>: <?= (int)$t['n'] ?> &middot; <?php endforeach; ?>
        </p>
    </section>
    <table><thead><tr><th>When</th><th>What happened</th><th>Who (name, phone, ward)</th><th>Where</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
        <tr><td><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td><td><?= e($r['action']) ?></td><td><?= e($r['details']) ?></td><td><?= e($r['page']) ?></td></tr>
<?php endforeach; ?>
    </tbody></table>
<?php if (!$rows): ?><p class="empty">No sign-ins recorded yet.</p><?php endif; ?>
<?php admin_bottom();
