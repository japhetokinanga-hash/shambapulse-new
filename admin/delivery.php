<?php
// Delivery report (doc FR-O-04, FR-A-06): the outcome of every alert
// and every message in the message log — submitted, delivered,
// failed, simulated. A simulated message delivers nothing.
require_once __DIR__ . '/_admin.php';
$ward = staff_ward();
$filter_status = trim($_GET['status'] ?? '');
$filter_ward = trim($_GET['ward'] ?? '');
$alerts = $ward === null
    ? q('SELECT * FROM alert ORDER BY alert_id DESC')
    : q('SELECT * FROM alert WHERE ward = ? ORDER BY alert_id DESC', [$ward]);
$where = '1=1'; $params = [];
if (in_array($filter_status, ['submitted', 'delivered', 'failed', 'simulated'], true)) { $where .= ' AND status = ?'; $params[] = $filter_status; }
if ($filter_ward !== '' && in_array($filter_ward, ward_names(), true)) { $where .= ' AND ward = ?'; $params[] = $filter_ward; }
if ($ward !== null) { $where .= ' AND ward = ?'; $params[] = $ward; }
$logs = q("SELECT * FROM message_log WHERE $where ORDER BY log_id DESC LIMIT 100", $params);
admin_top('Delivery', 'delivery.php');
?>
    <section class="page-intro"><h2>Delivery<?= $ward ? ' — ' . e($ward) : '' ?></h2></section>
    <table><thead><tr><th>Alert</th><th>Ward</th><th>Status</th><th>Submitted</th><th>Delivered</th><th>Failed</th><th>Simulated</th></tr></thead><tbody>
<?php foreach ($alerts as $a): $c = delivery_counts((int)$a['alert_id']); ?>
        <tr><td><?= e($a['title'] ?: $a['type']) ?></td><td><?= e($a['ward']) ?></td><td><?= e($a['status']) ?></td><td><?= $c['submitted'] ?></td><td><?= $c['delivered'] ?></td><td><?= $c['failed'] ?></td><td><?= $c['simulated'] ?></td></tr>
<?php endforeach; ?>
    </tbody></table>
    <section>
        <h2>Message log</h2>
        <form method="get" class="filters">
            <label>Status
                <select name="status"><option value="">All</option>
<?php foreach (['submitted', 'delivered', 'failed', 'simulated'] as $s): ?>
                    <option<?= $s === $filter_status ? ' selected' : '' ?>><?= $s ?></option>
<?php endforeach; ?>
                </select>
            </label>
            <label>Ward
                <select name="ward"><option value="">All</option>
<?php foreach (ward_names() as $w): ?>
                    <option<?= $w === $filter_ward ? ' selected' : '' ?>><?= e($w) ?></option>
<?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="hero-button">Filter</button>
        </form>
        <table><thead><tr><th>When</th><th>Channel</th><th>Phone</th><th>Ward</th><th>Alert</th><th>Message</th><th>Status</th></tr></thead><tbody>
<?php foreach ($logs as $l): ?>
        <tr><td><?= e(date('d/m/Y H:i', strtotime($l['created_at']))) ?></td><td><?= e($l['channel']) ?></td><td><?= e(mask_phone($l['phone'])) ?></td><td><?= e($l['ward_name']) ?></td><td><?= e($l['alert_title']) ?></td><td><?= e(mb_substr($l['message'], 0, 120)) ?></td><td><?= e($l['status']) ?></td></tr>
<?php endforeach; ?>
        </tbody></table>
        <p><a href="reports.php?export=messages">Download message log as CSV</a></p>
    </section>
<?php admin_bottom();
