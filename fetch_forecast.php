<?php
// Fetch the 7-day Open-Meteo forecast for every ward (doc 1.7, NFR-01).
// Run daily at 06:00 EAT — in XAMPP, schedule it with Windows Task
// Scheduler (php C:\xampp\htdocs\shambapulse\fetch_forecast.php), or
// open this page in a browser. A failed fetch keeps the stored
// forecast; it never breaks the site.
require_once __DIR__ . '/includes/bootstrap.php';
$r = fetch_forecasts();
if (PHP_SAPI === 'cli') {
    echo "Fetched {$r['fetched']} forecast rows.\n";
    exit;
}
layout_top('Fetch forecast', 'forecast.php', 'inner');
?>
    <section class="page-intro"><h2>Forecast fetch</h2>
    <p><?= (int)$r['fetched'] ?> forecast rows fetched from Open-Meteo. <a href="forecast.php">See the forecast</a></p></section>
<?php layout_bottom();
