<?php
require_once __DIR__ . '/_admin.php';
$err = ''; $saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $commodity = trim($_POST['commodity'] ?? ''); $unit = trim($_POST['unit'] ?? '');
    $market = trim($_POST['market'] ?? ''); $date = trim($_POST['price_date'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    if ($commodity === '' || $unit === '') $err = 'Enter the commodity and unit.';
    elseif (!isset(market_list()[$market])) $err = 'Choose a market.';
    elseif ($price <= 0) $err = 'Enter a price above 0.';
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $err = 'Choose a date.';
    else {
        // Officer price entry (FR-O-05): stamped source "officer" and date.
        $mw = market_list()[$market] ?? '';
        if (staff_ward() !== null && $mw !== staff_ward()) $err = 'That market is not in your ward.';
        else try {
            run('INSERT INTO price (commodity, unit, market, county, price_date, price, source, recorded_by) VALUES (?,?,?, ?,?,?,?, ?)',
                [$commodity, $unit, $market, SCOPE_COUNTY, $date, $price, 'officer', (int)($_SESSION['staff_id'] ?? 0)]);
            $saved = true;
        } catch (Throwable $e) { $err = 'That price is already recorded for that market and date.'; }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $err = 'Your session expired. Please try again.';
admin_top('Add a price', 'prices.php');
?>
    <section class="page-intro"><h2>Add a price</h2></section>
<?php if ($saved): ?><p role="status">Price saved. <a href="prices.php">See all prices</a></p><?php endif; ?>
<?php if ($err): ?><p role="alert"><?= e($err) ?></p><?php endif; ?>
    <section class="contact-form-section"><form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <label for="commodity">Commodity</label><input id="commodity" name="commodity" required maxlength="50" placeholder="Maize">
        <label for="unit">Unit</label><input id="unit" name="unit" required maxlength="30" placeholder="90 kg bag">
        <label for="market">Market</label>
        <select id="market" name="market"><?php $markets = market_list(); if (staff_ward() !== null) $markets = array_filter($markets, fn($w) => $w === staff_ward()); foreach ($markets as $m => $w): ?><option value="<?= e($m) ?>"><?= e($m) ?> — <?= e($w) ?> ward</option><?php endforeach; ?></select>
        <label for="price_date">Date</label><input id="price_date" name="price_date" type="date" required value="<?= date('Y-m-d') ?>">
        <label for="price">Price (KSh)</label><input id="price" name="price" type="number" step="0.01" min="0" required>
        <button type="submit" class="hero-button">Save price</button>
    </form></section>
<?php admin_bottom();
