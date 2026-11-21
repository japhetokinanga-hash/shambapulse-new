<?php
require_once __DIR__ . '/_admin.php';
$ward = staff_ward(); // officer: own ward only (FR-O-07)
$rows = $ward === null
    ? q('SELECT farmer_id, full_name, phone, ward, language, consent, subscribed, created_at, last_login_at FROM farmer WHERE county = ? ORDER BY farmer_id DESC', [SCOPE_COUNTY])
    : q('SELECT farmer_id, full_name, phone, ward, language, consent, subscribed, created_at, last_login_at FROM farmer WHERE ward = ? ORDER BY farmer_id DESC', [$ward]);
admin_top('Farmers', 'farmers.php');
?>
    <section class="page-intro">
        <h2>Farmers (<?= count($rows) ?>)<?= $ward ? ' — ' . e($ward) : '' ?></h2>
    </section>
    <table><thead><tr><th>Name</th><th>Phone</th><th>Ward</th><th>Language</th><th>Alerts</th><th>Subscribed wards</th><th>Joined</th><th>Last sign-in</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $f): $subs = array_column(array_filter(farmer_subscriptions((int)$f['farmer_id']), fn($s) => $s['active']), 'ward'); ?>
        <tr><td><?= e($f['full_name']) ?></td><td><?= e($f['phone']) ?></td><td><?= e($f['ward']) ?></td><td><?= e($f['language']) ?></td><td><?= $f['subscribed'] ? 'Subscribed' : 'Opted out' ?></td><td><?= e(implode(', ', $subs)) ?></td><td><?= e(date('d/m/Y', strtotime($f['created_at']))) ?></td><td><?= $f['last_login_at'] ? e(date('d/m/Y H:i', strtotime($f['last_login_at']))) : '—' ?></td><td><a href="edit-farmer.php?id=<?= (int)$f['farmer_id'] ?>">Edit / set password</a></td></tr>
<?php endforeach; ?>
    </tbody></table>
<?php admin_bottom();
