<?php
// Market prices (doc Module 3, FR-O-05/06, FR-A-08). Officers see
// only their ward's markets. Delete is allowed for a price in the
// officer's ward. "Refresh feed" imports the prototype price feed
// (source "feed", badged SIMULATED); the unique key makes the
// refresh idempotent — a second run adds nothing.
require_once __DIR__ . '/_admin.php';
$ward = staff_ward();
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    if (isset($_POST['delete'])) {
        $id = (int)$_POST['delete'];
        $r = q('SELECT * FROM price WHERE price_id = ?', [$id])[0] ?? null;
        $mw = $r ? (market_list()[$r['market']] ?? '') : '';
        if (!$r || ($ward !== null && $mw !== $ward)) $err = 'Price not found.';
        else { run('DELETE FROM price WHERE price_id = ?', [$id]); $msg = 'Price deleted.'; }
    } elseif (isset($_POST['feed'])) {
        $added = 0;
        $feed = [
            ['Maize', 'Ahero', 5200, 0], ['Maize', 'Awasi', 5100, 0], ['Maize', 'Rabuor', 5300, 1],
            ['Beans', 'Ahero', 9800, 0], ['Beans', 'Pap Onditi', 9600, 1], ['Rice', 'Ahero', 11500, 0],
            ['Sorghum', 'Nyando', 4200, 0], ['Finger Millet', 'Ahero', 6800, 1], ['Groundnuts', 'Awasi', 180, 0],
            ['Tomatoes', 'Ahero', 90, 0], ['Tomatoes', 'Katito', 85, 0], ['Onions', 'Ahero', 120, 0],
            ['Sukuma Wiki', 'Rabuor', 20, 0], ['Milk', 'Ahero', 65, 0], ['Milk', 'Awasi', 60, 1],
            ['Eggs', 'Ahero', 420, 0], ['Live Chicken', 'Pap Onditi', 750, 1], ['Goat', 'Nyando', 8500, 2],
            ['Cattle', 'Ahero', 42000, 2], ['Beans', 'Nyando', 9700, 2],
        ];
        $units = commodity_list();
        foreach ($feed as [$c, $m, $price, $ago]) {
            $date = date('Y-m-d', strtotime("-$ago days"));
            $n = run("INSERT IGNORE INTO price (commodity, unit, market, county, price_date, price, source) VALUES (?,?,?, ?,?,?, 'feed')",
                [$c, $units[$c] ?? 'kg', $m, SCOPE_COUNTY, $date, $price]);
            $added += $n;
        }
        $msg = $added > 0 ? "Feed refreshed: $added new price(s) added." : 'Feed refreshed: no new prices (already imported).';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $err = 'Your session expired. Please try again.';
$rows = q('SELECT * FROM price WHERE county = ? ORDER BY price_date DESC, commodity, market', [SCOPE_COUNTY]);
if ($ward !== null) {
    $markets = array_keys(array_filter(market_list(), fn($w) => $w === $ward));
    $rows = array_values(array_filter($rows, fn($r) => in_array($r['market'], $markets, true)));
}
admin_top('Prices', 'prices.php');
?>
    <section class="page-intro"><h2>Prices (<?= count($rows) ?>)<?= $ward ? ' — ' . e($ward) : '' ?></h2>
    <p><a href="add-price.php">Add a price</a></p>
    <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button class="hero-button" type="submit" name="feed" value="1">Refresh price feed</button></form>
    </section>
<?php if ($msg): ?><p role="status"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err): ?><p role="alert"><?= e($err) ?></p><?php endif; ?>
    <table><thead><tr><th>Commodity</th><th>Unit</th><th>Market</th><th>Ward</th><th>Date</th><th>Price</th><th>Source</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
        <tr><td><?= e($r['commodity']) ?></td><td><?= e($r['unit']) ?></td><td><?= e($r['market']) ?></td><td><?= e(market_list()[$r['market']] ?? '') ?></td><td><?= e(date('d/m/Y', strtotime($r['price_date']))) ?></td><td>KSh <?= number_format((float)$r['price']) ?></td><td><?= e($r['source']) ?><?= $r['source'] === 'feed' ? ' (SIMULATED)' : '' ?></td><td><a href="edit-price.php?id=<?= (int)$r['price_id'] ?>">Edit</a> <form method="post" style="display:inline" onsubmit="return confirm('Delete this price?')"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button type="submit" name="delete" value="<?= (int)$r['price_id'] ?>">Delete</button></form></td></tr>
<?php endforeach; ?>
    </tbody></table>
<?php admin_bottom();
