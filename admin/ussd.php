<?php
require_once __DIR__ . '/_admin.php';
$rows = q('SELECT phone, language, menu_choice, created_at FROM ussd_session ORDER BY session_id DESC LIMIT 100');
$labels = [1 => 'Weather', 2 => 'Market prices', 3 => 'Advisories', 4 => 'Subscribe', 5 => 'Opt out'];
admin_top('USSD visits', 'ussd.php');
?>
    <section class="page-intro"><h2>USSD visits — *384#</h2></section>
    <table><thead><tr><th>When</th><th>Phone</th><th>Language</th><th>Choice</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td><td><?= e($r['phone']) ?></td><td><?= e($r['language']) ?></td><td><?= e($labels[(int)$r['menu_choice']] ?? $r['menu_choice']) ?></td></tr><?php endforeach; ?>
    </tbody></table>
<?php admin_bottom();
