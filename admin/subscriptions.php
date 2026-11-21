<?php
// Subscriptions (doc FR-F-04, FR-A-07): one per farmer, ward and
// channel. Staff may deactivate any subscription. Reactivation is
// the farmer's own act — USSD option 4 or My account — never done
// by staff alone, because it needs the farmer's authorisation.
require_once __DIR__ . '/_admin.php';
$ward = staff_ward();
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok() && isset($_POST['deactivate'])) {
    $id = (int)$_POST['deactivate'];
    $s = q('SELECT s.*, f.full_name FROM subscription s JOIN farmer f ON f.farmer_id = s.farmer_id WHERE s.subscription_id = ?', [$id])[0] ?? null;
    if (!$s || ($ward !== null && $s['ward'] !== $ward)) $err = 'Subscription not found.';
    else {
        run('UPDATE subscription SET active = 0 WHERE subscription_id = ?', [$id]);
        unsubscribe_farmer((int)$s['farmer_id'], $s['ward']);
        $msg = 'Subscription deactivated for ' . $s['full_name'] . ' (' . $s['ward'] . ').';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $err = 'Your session expired. Please try again.';
$rows = $ward === null
    ? q('SELECT s.*, f.full_name, f.phone, f.language FROM subscription s JOIN farmer f ON f.farmer_id = s.farmer_id ORDER BY s.active DESC, s.ward, f.full_name')
    : q('SELECT s.*, f.full_name, f.phone, f.language FROM subscription s JOIN farmer f ON f.farmer_id = s.farmer_id WHERE s.ward = ? ORDER BY s.active DESC, f.full_name', [$ward]);
admin_top('Subscriptions', 'subscriptions.php');
?>
    <section class="page-intro"><h2>Subscriptions (<?= count($rows) ?>)<?= $ward ? ' — ' . e($ward) : '' ?></h2>
    <p>Staff can deactivate a subscription. To subscribe again, the farmer dials *384# and chooses 4, or subscribes on My account.</p></section>
<?php if ($msg): ?><p role="status"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err): ?><p role="alert"><?= e($err) ?></p><?php endif; ?>
    <table><thead><tr><th>Farmer</th><th>Phone</th><th>Ward</th><th>Channel</th><th>Language</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $s): ?>
        <tr><td><?= e($s['full_name']) ?></td><td><?= e($s['phone']) ?></td><td><?= e($s['ward']) ?></td><td><?= e($s['channel']) ?></td><td><?= e($s['language']) ?></td><td><?= $s['active'] ? 'Active' : 'Opted out' ?></td>
        <td><?php if ($s['active']): ?><form method="post" style="display:inline" onsubmit="return confirm('Deactivate this subscription?')"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button type="submit" name="deactivate" value="<?= (int)$s['subscription_id'] ?>">Deactivate</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
    </tbody></table>
<?php admin_bottom();
