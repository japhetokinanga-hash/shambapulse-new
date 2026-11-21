<?php
require_once __DIR__ . '/_admin.php';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$r = q('SELECT * FROM price WHERE price_id = ?', [$id])[0] ?? null;
if (!$r) { header('Location: prices.php'); exit; }
if (staff_ward() !== null && (market_list()[$r['market']] ?? '') !== staff_ward()) { header('Location: prices.php'); exit; } // officer: own ward only (FR-O-06)
$err = ''; $saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $price = (float)($_POST['price'] ?? 0); $date = trim($_POST['price_date'] ?? '');
    $market = trim($_POST['market'] ?? '');
    if (!isset(market_list()[$market])) $err = 'Choose a market.';
    elseif ($price <= 0) $err = 'Enter a price above 0.';
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $err = 'Choose a date.';
    else {
        try {
            run('UPDATE price SET market = ?, price_date = ?, price = ? WHERE price_id = ?', [$market, $date, $price, $id]);
            $saved = true; $r = q('SELECT * FROM price WHERE price_id = ?', [$id])[0];
        } catch (Throwable $e) { $err = 'That price is already recorded for that market and date.'; }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $err = 'Your session expired. Please try again.';
admin_top('Edit price', 'prices.php');
?>
    <section class="page-intro"><h2><?= e($r['commodity']) ?> — <?= e($r['unit']) ?></h2></section>
<?php if ($saved): ?><p role="status">Saved.</p><?php endif; ?>
<?php if ($err): ?><p role="alert"><?= e($err) ?></p><?php endif; ?>
    <section class="contact-form-section"><form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="id" value="<?= $id ?>">
        <label for="market">Market</label>
        <select id="market" name="market"><?php $markets = market_list(); if (staff_ward() !== null) $markets = array_filter($markets, fn($w) => $w === staff_ward()); foreach ($markets as $m => $w): ?><option value="<?= e($m) ?>"<?= $m === $r['market'] ? ' selected' : '' ?>><?= e($m) ?> — <?= e($w) ?> ward</option><?php endforeach; ?></select>
        <label for="price_date">Date</label><input id="price_date" name="price_date" type="date" required value="<?= e($r['price_date']) ?>">
        <label for="price">Price (KSh)</label><input id="price" name="price" type="number" step="0.01" min="0" required value="<?= e((string)$r['price']) ?>">
        <button type="submit" class="hero-button">Save</button>
    </form></section>
    <p><a href="prices.php">Back to prices</a></p>
<?php admin_bottom();
