<?php
require_once __DIR__ . '/_admin.php';
$rows = q('SELECT page, action, details, created_at FROM activity_log ORDER BY log_id DESC LIMIT 200');
admin_top('Website activity', 'activity.php');
?>
    <section class="page-intro"><h2>Website activity</h2></section>
    <table><thead><tr><th>When</th><th>Page</th><th>What was done</th><th>Details</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td><td><?= e($r['page']) ?></td><td><?= e($r['action']) ?></td><td><?= e($r['details']) ?></td></tr><?php endforeach; ?>
    </tbody></table>
<?php admin_bottom();
