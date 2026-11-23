<?php
// Forecast page (doc FR-F-07, NFR-01): 7-day weather forecast for the
// farmer's ward, labelled with its source. Forecasts are fetched from
// Open-Meteo at 06:00 EAT (fetch_forecast.php); until a live fetch
// succeeds, the stored forecast is shown and badged SIMULATED.
require_once __DIR__ . '/includes/bootstrap.php';
$wards = ward_names();
$farmer_ward = '';
if (!empty($_SESSION['farmer_id'])) {
    $f = q('SELECT ward FROM farmer WHERE farmer_id = ?', [(int)$_SESSION['farmer_id']])[0] ?? null;
    $farmer_ward = $f['ward'] ?? '';
}
$ward = trim($_GET['ward'] ?? $farmer_ward);
if (!in_array($ward, $wards, true)) $ward = 'Ahero';
maybe_refresh_forecasts();
$rows = ward_forecast($ward);
$source = $rows ? $rows[0]['source'] : 'SIMULATED';
if (!$_GET) log_activity('forecast', 'view');
layout_top('Forecast', 'forecast.php', 'inner');
?>
    <!-- TEMPORARY: hides the "Search prices" box on this page only.
         Delete this line once you remove the box at its source. -->
    <style>input[placeholder="Search prices"] { display: none; }</style>

    <section class="page-intro">
        <h2>7-day forecast for <?= e($ward) ?></h2>
        <p>Source: <?= e($source) ?><?= $source === 'SIMULATED' ? ' — simulated forecast, replaced by the live Open-Meteo forecast when the site is online.' : ' (Open-Meteo).' ?></p>
    </section>
    <section class="split">
        <div class="content">
            <form method="get" class="filters">
                <label>Ward
                    <select name="ward">
<?php foreach ($wards as $w): ?>
                        <option<?= $w === $ward ? ' selected' : '' ?>><?= e($w) ?></option>
<?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="hero-button">Show forecast</button>
            </form>
<?php if ($rows): ?>
            <table>
                <thead><tr><th>Date</th><th>Forecast</th><th>Rain</th><th>High</th><th>Low</th></tr></thead>
                <tbody>
<?php foreach ($rows as $r): ?>
                    <tr><td><?= e(date('D d/m/Y', strtotime($r['forecast_date']))) ?></td><td><?= e($r['summary']) ?></td><td><?= e((string)$r['rainfall_mm']) ?> mm</td><td><?= e((string)$r['temp_max']) ?>&deg;C</td><td><?= e((string)$r['temp_min']) ?>&deg;C</td></tr>
<?php endforeach; ?>
                </tbody>
            </table>
<?php else: ?>
            <p class="empty">No forecast recorded for this ward yet.</p>
<?php endif; ?>
            <p>Forecasts older than 36 hours carry their latest update date. A forecast is ward-level: rain in Ahero does not mean rain in Kobura.</p>
        </div>
        <aside class="side">
            <h3>What the forecast is for</h3>
            <p>Plan planting, fertilizer and harvest around the rain. Heavy rain days are the days to protect crops and livestock and to delay fertilizer.</p>
        </aside>
    </section>
<?php layout_bottom();