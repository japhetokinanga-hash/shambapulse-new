<?php
// Reference data (doc FR-A-03/04/05): administrators configure wards,
// markets and commodities. A ward, market or commodity that is in
// use cannot be deleted. Wards stay inside Nyando Sub-County.
// Admin only.
require_once __DIR__ . '/_admin.php';
require_admin();
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $kind = $_POST['kind'] ?? '';
    if (isset($_POST['add'])) {
        if ($kind === 'market') {
            $name = trim($_POST['name'] ?? ''); $w = trim($_POST['ward'] ?? '');
            if ($name === '' || !in_array($w, ward_names(), true)) $err = 'Enter a market name and choose its ward.';
            else try { run('INSERT INTO market (name, ward) VALUES (?,?)', [$name, $w]); $msg = 'Market added.'; } catch (Throwable $e) { $err = 'That market already exists.'; }
        } elseif ($kind === 'commodity') {
            $name = trim($_POST['name'] ?? ''); $unit = trim($_POST['unit'] ?? '');
            if ($name === '' || $unit === '') $err = 'Enter a commodity name and its unit.';
            else try { run('INSERT INTO commodity (name, unit) VALUES (?,?)', [$name, $unit]); $msg = 'Commodity added.'; } catch (Throwable $e) { $err = 'That commodity already exists.'; }
        }
    } elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['delete'];
        if ($kind === 'market') {
            $m = q('SELECT * FROM market WHERE market_id = ?', [$id])[0] ?? null;
            if (!$m) $err = 'Market not found.';
            elseif (q('SELECT 1 FROM price WHERE market = ? LIMIT 1', [$m['name']])) $err = 'That market has prices recorded. It cannot be deleted.';
            else { run('DELETE FROM market WHERE market_id = ?', [$id]); $msg = 'Market deleted.'; }
        } elseif ($kind === 'commodity') {
            $c = q('SELECT * FROM commodity WHERE commodity_id = ?', [$id])[0] ?? null;
            if (!$c) $err = 'Commodity not found.';
            elseif (q('SELECT 1 FROM price WHERE commodity = ? LIMIT 1', [$c['name']])) $err = 'That commodity has prices recorded. It cannot be deleted.';
            else { run('DELETE FROM commodity WHERE commodity_id = ?', [$id]); $msg = 'Commodity deleted.'; }
        }
    } elseif (isset($_POST['save_ward'])) {
        $id = (int)$_POST['save_ward'];
        $lat = (float)($_POST['lat'] ?? 0); $lon = (float)($_POST['lon'] ?? 0);
        run('UPDATE ward SET lat = ?, lon = ? WHERE ward_id = ?', [$lat, $lon, $id]);
        $msg = 'Ward coordinates saved. Forecasts are fetched for these coordinates.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $err = 'Your session expired. Please try again.';
$wards = q('SELECT * FROM ward ORDER BY ward_id');
$markets = q('SELECT * FROM market ORDER BY market_id');
$commodities = q('SELECT * FROM commodity ORDER BY name');
admin_top('Wards, markets, commodities', 'config.php');
?>
    <section class="page-intro"><h2>Wards, markets, commodities</h2></section>
<?php if ($msg): ?><p role="status"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err): ?><p role="alert"><?= e($err) ?></p><?php endif; ?>
    <section>
        <h2>Wards — Nyando Sub-County, Kisumu County</h2>
        <table><thead><tr><th>Ward</th><th>Latitude</th><th>Longitude</th><th></th></tr></thead><tbody>
<?php foreach ($wards as $w): ?>
        <tr><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><td><?= e($w['name']) ?></td><td><input name="lat" type="number" step="0.0001" value="<?= e((string)$w['lat']) ?>"></td><td><input name="lon" type="number" step="0.0001" value="<?= e((string)$w['lon']) ?>"></td><td><button type="submit" name="save_ward" value="<?= (int)$w['ward_id'] ?>">Save</button></td></form></tr>
<?php endforeach; ?>
        </tbody></table>
    </section>
    <section>
        <h2>Markets</h2>
        <form method="post" class="form-stack"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="kind" value="market">
            <label for="mname">Market name</label><input id="mname" name="name" required>
            <label for="mward">Ward</label><select id="mward" name="ward"><?php foreach ($wards as $w): ?><option><?= e($w['name']) ?></option><?php endforeach; ?></select>
            <button type="submit" class="hero-button" name="add" value="1">Add market</button></form>
        <table><thead><tr><th>Market</th><th>Ward</th><th></th></tr></thead><tbody>
<?php foreach ($markets as $m): ?>
        <tr><td><?= e($m['name']) ?></td><td><?= e($m['ward']) ?></td><td><form method="post" style="display:inline" onsubmit="return confirm('Delete this market?')"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="kind" value="market"><button type="submit" name="delete" value="<?= (int)$m['market_id'] ?>">Delete</button></form></td></tr>
<?php endforeach; ?>
        </tbody></table>
    </section>
    <section>
        <h2>Commodities</h2>
        <form method="post" class="form-stack"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="kind" value="commodity">
            <label for="cname">Commodity</label><input id="cname" name="name" required>
            <label for="cunit">Unit</label><input id="cunit" name="unit" required placeholder="90kg bag">
            <button type="submit" class="hero-button" name="add" value="1">Add commodity</button></form>
        <table><thead><tr><th>Commodity</th><th>Unit</th><th></th></tr></thead><tbody>
<?php foreach ($commodities as $c): ?>
        <tr><td><?= e($c['name']) ?></td><td><?= e($c['unit']) ?></td><td><form method="post" style="display:inline" onsubmit="return confirm('Delete this commodity?')"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="kind" value="commodity"><button type="submit" name="delete" value="<?= (int)$c['commodity_id'] ?>">Delete</button></form></td></tr>
<?php endforeach; ?>
        </tbody></table>
    </section>
<?php admin_bottom();
