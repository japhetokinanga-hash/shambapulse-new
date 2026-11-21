<?php
// Reports (doc 3.4.6, FR-O-10, FR-A-06): farmers by ward and
// language, alerts sent and delivered, prices by commodity and
// market, and the 7-day forecast. Officers see their ward only.
// Every report can be downloaded as CSV for the county office.
require_once __DIR__ . '/_admin.php';
$ward = staff_ward();
$export = trim($_GET['export'] ?? '');

function csv_out(string $name, array $head, array $rows): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '.csv"');
    $o = fopen('php://output', 'w');
    fputcsv($o, $head);
    foreach ($rows as $r) fputcsv($o, $r);
    fclose($o);
    exit;
}
$wfilter = $ward === null ? '' : ' WHERE ward = ' . "'" . addslashes($ward) . "'";
if ($export === 'farmers') {
    $rows = q('SELECT full_name, phone, ward, language, consent, subscribed, created_at FROM farmer' . ($ward === null ? '' : ' WHERE ward = ?') . ' ORDER BY ward, full_name', $ward === null ? [] : [$ward]);
    csv_out('farmers', ['Name', 'Phone', 'Ward', 'Language', 'Consent', 'Subscribed', 'Joined'], array_map(fn($r) => [$r['full_name'], $r['phone'], $r['ward'], $r['language'], $r['consent'], $r['subscribed'], $r['created_at']], $rows));
} elseif ($export === 'prices') {
    $rows = q('SELECT commodity, unit, market, county, price_date, price, source FROM price ORDER BY price_date DESC, commodity, market');
    if ($ward !== null) { $mk = array_keys(array_filter(market_list(), fn($w) => $w === $ward)); $rows = array_values(array_filter($rows, fn($r) => in_array($r['market'], $mk, true))); }
    csv_out('prices', ['Commodity', 'Unit', 'Market', 'County', 'Date', 'Price', 'Source'], array_map(fn($r) => [$r['commodity'], $r['unit'], $r['market'], $r['county'], $r['price_date'], $r['price'], $r['source']], $rows));
} elseif ($export === 'messages') {
    $rows = q('SELECT created_at, channel, phone, ward_name, alert_title, message, status FROM message_log' . ($ward === null ? '' : ' WHERE ward = ?') . ' ORDER BY log_id DESC', $ward === null ? [] : [$ward]);
    csv_out('messages', ['When', 'Channel', 'Phone', 'Ward', 'Alert', 'Message', 'Status'], array_map(fn($r) => [$r['created_at'], $r['channel'], $r['phone'], $r['ward_name'], $r['alert_title'], $r['message'], $r['status']], $rows));
} elseif ($export === 'alerts') {
    $rows = q('SELECT alert_id, type, title, ward, status, created_at FROM alert' . ($ward === null ? '' : ' WHERE ward = ?') . ' ORDER BY alert_id DESC', $ward === null ? [] : [$ward]);
    $out = [];
    foreach ($rows as $r) { $c = delivery_counts((int)$r['alert_id']); $out[] = [$r['alert_id'], $r['type'], $r['title'], $r['ward'], $r['status'], $c['submitted'], $c['delivered'], $c['failed'], $c['simulated'], $r['created_at']]; }
    csv_out('alerts', ['ID', 'Type', 'Title', 'Ward', 'Status', 'Submitted', 'Delivered', 'Failed', 'Simulated', 'Created'], $out);
}

$farmersByWard = q('SELECT ward, language, COUNT(*) n, SUM(subscribed) s FROM farmer' . ($ward === null ? '' : ' WHERE ward = ?') . ' GROUP BY ward, language ORDER BY ward, language', $ward === null ? [] : [$ward]);
$alertsSummary = q('SELECT ward, status, COUNT(*) n FROM alert' . ($ward === null ? '' : ' WHERE ward = ?') . ' GROUP BY ward, status ORDER BY ward', $ward === null ? [] : [$ward]);
$pricesSummary = q('SELECT commodity, market, COUNT(*) n, MAX(price_date) latest FROM price GROUP BY commodity, market ORDER BY commodity, market');
if ($ward !== null) { $mk = array_keys(array_filter(market_list(), fn($w) => $w === $ward)); $pricesSummary = array_values(array_filter($pricesSummary, fn($r) => in_array($r['market'], $mk, true))); }
$deliverySummary = q("SELECT status, COUNT(*) n FROM message_log" . ($ward === null ? '' : ' WHERE ward = ?') . ' GROUP BY status', $ward === null ? [] : [$ward]);
admin_top('Reports', 'reports.php');
?>
    <section class="page-intro"><h2>Reports<?= $ward ? ' — ' . e($ward) : '' ?></h2></section>
    <section>
        <h2>Farmers by ward and language</h2>
        <table><thead><tr><th>Ward</th><th>Language</th><th>Farmers</th><th>Subscribed</th></tr></thead><tbody>
<?php foreach ($farmersByWard as $r): ?>
        <tr><td><?= e($r['ward']) ?></td><td><?= e($r['language']) ?></td><td><?= (int)$r['n'] ?></td><td><?= (int)$r['s'] ?></td></tr>
<?php endforeach; ?>
        </tbody></table>
        <p><a href="reports.php?export=farmers">Download farmers as CSV</a></p>
    </section>
    <section>
        <h2>Alerts sent and delivered</h2>
        <table><thead><tr><th>Ward</th><th>Status</th><th>Alerts</th></tr></thead><tbody>
<?php foreach ($alertsSummary as $r): ?>
        <tr><td><?= e($r['ward']) ?></td><td><?= e($r['status']) ?></td><td><?= (int)$r['n'] ?></td></tr>
<?php endforeach; ?>
        </tbody></table>
        <table><thead><tr><th>Message status</th><th>Messages</th></tr></thead><tbody>
<?php foreach ($deliverySummary as $r): ?>
        <tr><td><?= e($r['status']) ?></td><td><?= (int)$r['n'] ?></td></tr>
<?php endforeach; ?>
        </tbody></table>
        <p><a href="reports.php?export=alerts">Download alerts as CSV</a> &middot; <a href="reports.php?export=messages">Download message log as CSV</a></p>
    </section>
    <section>
        <h2>Prices by commodity and market</h2>
        <table><thead><tr><th>Commodity</th><th>Market</th><th>Records</th><th>Latest date</th></tr></thead><tbody>
<?php foreach ($pricesSummary as $r): ?>
        <tr><td><?= e($r['commodity']) ?></td><td><?= e($r['market']) ?></td><td><?= (int)$r['n'] ?></td><td><?= e(date('d/m/Y', strtotime($r['latest']))) ?></td></tr>
<?php endforeach; ?>
        </tbody></table>
        <p><a href="reports.php?export=prices">Download prices as CSV</a></p>
    </section>
<?php admin_bottom();
