<?php
require_once __DIR__ . '/includes/bootstrap.php';
$fc = trim($_GET['commodity'] ?? ''); $fk = trim($_GET['county'] ?? '');
$commodities = array_column(q('SELECT DISTINCT commodity FROM price ORDER BY commodity'), 'commodity');
$counties = array_column(q('SELECT DISTINCT county FROM price ORDER BY county'), 'county');
$rows = q('SELECT p.* FROM price p JOIN (SELECT commodity, unit, market, MAX(price_date) d FROM price GROUP BY commodity, unit, market) x
           ON x.commodity=p.commodity AND x.unit=p.unit AND x.market=p.market AND x.d=p.price_date
           WHERE (LENGTH(?) = 0 OR p.commodity = ?) AND (LENGTH(?) = 0 OR p.county = ?) ORDER BY p.commodity, p.market', [$fc, $fc, $fk, $fk]);
if ($fc !== '' || $fk !== '') log_activity('prices', 'price_search', trim("$fc $fk"));
if (!$_GET) log_activity('prices', 'view');
layout_top('Prices', 'prices.php', 'inner');
?>

    <section class="page-intro">
        <h2>Market prices</h2>
        <p>
            Price information is kept distinct from demand signals.
            Every price on this page shows the commodity, the unit, the
            market and the date, so a farmer can tell what the number
            actually means.
        </p>
    </section>

    <section class="split">
        <div class="content">
            <h2>Latest prices</h2>
            <p>
                The most recent price for each commodity, unit and market,
                with the source recorded beside each record.
            </p>

            <form method="get" class="filters">
                <label>Commodity
                    <select name="commodity">
                        <option value="">All</option>
<?php foreach ($commodities as $c): ?>
                        <option<?= $c === $fc ? ' selected' : '' ?>><?= e($c) ?></option>
<?php endforeach; ?>
                    </select>
                </label>
                <label>County
                    <select name="county">
                        <option value="">All</option>
<?php foreach ($counties as $c): ?>
                        <option<?= $c === $fk ? ' selected' : '' ?>><?= e($c) ?></option>
<?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="hero-button">Filter</button>
            </form>

<?php if ($rows): ?>
            <table>
                <thead>
                    <tr>
                        <th>Commodity</th>
                        <th>Unit</th>
                        <th>Market</th>
                        <th>Date</th>
                        <th>Price</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
<?php foreach ($rows as $r): ?>
                    <tr><td><?= e($r['commodity']) ?></td><td><?= e($r['unit']) ?></td><td><?= e($r['market']) ?></td><td><?= e(date('d/m/Y', strtotime($r['price_date']))) ?></td><td>Ksh <?= number_format($r['price']) ?></td><td><?= e($r['source']) ?><?= $r['source'] === 'feed' ? ' (SIMULATED)' : '' ?></td></tr>
<?php endforeach; ?>
                </tbody>
            </table>
<?php else: ?>
            <p class="empty">No prices match that search.</p>
<?php endif; ?>

            <p>
                Prices are shown with their unit, source and date. A price without a unit is not useful, so the
                unit is never left out.
            </p>
        </div>

        <aside class="side">
            <h3>Why the unit matters</h3>
            <p>
                A price for "maize" means nothing until you know whether
                it is per kilogram, per 90 kg bag, or per debe.
                ShambaPulse records the unit with every price, and
                duplicate feed imports are blocked by a stable key so the
                same price is not counted twice.
            </p>
        </aside>
    </section>

<?php
// Price history and trend (doc Module 3): past prices for one
// commodity in one market, newest first, with the change from the
// previous record.
$hc = trim($_GET['history_commodity'] ?? ''); $hm = trim($_GET['history_market'] ?? '');
$history = [];
if ($hc !== '' && $hm !== '') {
    $history = q('SELECT price_date, price, source FROM price WHERE commodity = ? AND market = ? ORDER BY price_date DESC, price_id DESC LIMIT 12', [$hc, $hm]);
}
?>
    <section class="reading">
        <h2>Price history and trend</h2>
        <form method="get" class="filters">
            <label>Commodity
                <select name="history_commodity">
                    <option value="">Choose</option>
<?php foreach ($commodities as $c): ?>
                    <option<?= $c === $hc ? ' selected' : '' ?>><?= e($c) ?></option>
<?php endforeach; ?>
                </select>
            </label>
            <label>Market
                <select name="history_market">
                    <option value="">Choose</option>
<?php foreach (array_keys(market_list()) as $m): ?>
                    <option<?= $m === $hm ? ' selected' : '' ?>><?= e($m) ?></option>
<?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="hero-button">Show history</button>
        </form>
<?php if ($history): ?>
        <table>
            <thead><tr><th>Date</th><th>Price</th><th>Change</th><th>Source</th></tr></thead>
            <tbody>
<?php $prev = null; foreach ($history as $r): $chg = $prev === null ? '—' : ((float)$r['price'] > $prev ? 'Up' : ((float)$r['price'] < $prev ? 'Down' : 'Same')); $prev = (float)$r['price']; ?>
                <tr><td><?= e(date('d/m/Y', strtotime($r['price_date']))) ?></td><td>Ksh <?= number_format($r['price']) ?></td><td><?= e($chg) ?></td><td><?= e($r['source']) ?><?= $r['source'] === 'feed' ? ' (SIMULATED)' : '' ?></td></tr>
<?php endforeach; ?>
            </tbody>
        </table>
<?php elseif ($hc !== '' && $hm !== ''): ?>
        <p class="empty">No prices recorded for <?= e($hc) ?> in <?= e($hm) ?> yet.</p>
<?php endif; ?>
    </section>

    <section class="reading">
        <h2>How to read a price</h2>
        <ul class="card-row">
            <li>
                <h3>Commodity</h3>
                <p>What is being sold, such as maize, beans or milk.</p>
            </li>
            <li>
                <h3>Unit</h3>
                <p>How it is measured: a 90 kg bag, a litre, a debe.</p>
            </li>
            <li>
                <h3>Market</h3>
                <p>Where the price was recorded. Prices differ from market to market.</p>
            </li>
            <li>
                <h3>Date</h3>
                <p>When it was recorded. An old price may no longer hold.</p>
            </li>
        </ul>
    </section>

    <section class="compare">
        <h2>Compare before you sell</h2>
        <ul class="card-row">
            <li>
                <h3>By market</h3>
                <p>Farmers compare prices across nearby markets before deciding where to sell.</p>
            </li>
            <li>
                <h3>By commodity</h3>
                <p>Each commodity has its own unit, so prices are compared like with like.</p>
            </li>
            <li>
                <h3>By date</h3>
                <p>The date beside every price shows how fresh the information is.</p>
            </li>
        </ul>
    </section>

    <section class="steps">
        <h2>Tips before you sell</h2>
        <ol>
            <li>
                <h3>Check the unit</h3>
                <p>Make sure you are comparing the same bag, litre or debe.</p>
            </li>
            <li>
                <h3>Check two markets</h3>
                <p>A short trip can sometimes mean a better price, but count transport costs.</p>
            </li>
            <li>
                <h3>Check the date</h3>
                <p>Use the freshest price you can find before agreeing to sell.</p>
            </li>
        </ol>
    </section>

    <section class="cta">
        <h2>Check prices from any phone</h2>
        <p>Choose "Market prices" in the USSD menu, no internet needed.</p>
        <a href="ussd.php" class="hero-button">See the USSD menu</a>
    </section>

<?php layout_bottom();
