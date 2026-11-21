<?php
require_once __DIR__ . '/includes/bootstrap.php';
$snap = q("SELECT p.commodity, p.unit, p.market, p.price, p.price_date FROM price p
          WHERE p.price_id IN (SELECT MIN(price_id) FROM price WHERE price_date = (SELECT MAX(price_date) FROM price) GROUP BY commodity)
          ORDER BY p.commodity LIMIT 4");
log_activity('index', 'view');
layout_top('Home', 'index.php', 'home inner');
?>

    <section class="hero">
        <div class="hero-text">
            <h2>Welcome to ShambaPulse</h2>
            <h3>Smart Farming for a Better Future</h3>

            <p>Discover modern farming solutions.</p>
            <p>Get better harvests with smart agriculture.</p>
            <p>Find the latest farming tips and market prices.</p>

            <a href="about.php" class="register-btn">Learn More</a>
        </div>
    </section>

    <section class="features">
        <h2>Everything your farm needs</h2>
        <ul class="card-row">
            <li>
                <h3>Farm Alerts</h3>
                <p>Weather warnings, advisories and planting reminders, sent when they matter.</p>
                <a href="alerts.php">See alerts</a>
            </li>
            <li>
                <h3>Market Prices</h3>
                <p>Check current prices for maize, beans, vegetables and more before you sell.</p>
                <a href="prices.php">View prices</a>
            </li>
            <li>
                <h3>USSD Access</h3>
                <p>No smartphone? Dial a short code from any phone to get farm information.</p>
                <a href="ussd.php">How it works</a>
            </li>
            <li>
                <h3>Advisories</h3>
                <p>Seasonal farming guidance in English and Kiswahili for your county.</p>
                <a href="contact.php">Ask a question</a>
            </li>
        </ul>
    </section>

    <section class="steps">
        <h2>How it works</h2>
        <ol>
            <li>
                <h3>Sign up</h3>
                <p>Register with your name, phone number and county, on the <a href="login.php#register">web</a> or by dialling *384#.</p>
            </li>
            <li>
                <h3>Get updates</h3>
                <p>Receive alerts and prices for your area on your phone.</p>
            </li>
            <li>
                <h3>Grow with confidence</h3>
                <p>Plan planting, spraying and selling using real information.</p>
            </li>
        </ol>
    </section>

    <section class="price-preview">
        <h2>Today's market snapshot</h2>
        <?php if ($snap): ?><p class="lead">Latest recorded prices, as of <?= e(date('j M Y', strtotime($snap[0]['price_date']))) ?>.</p><?php endif; ?>
        <?php if ($snap): ?>
        <table>
            <thead>
                <tr>
                    <th>Crop</th>
                    <th>Market</th>
                    <th>Price (KES)</th>
                </tr>
            </thead>
            <tbody>
<?php foreach ($snap as $r): ?>
                <tr><td><?= e($r['commodity']) ?> (<?= e($r['unit']) ?>)</td><td><?= e($r['market']) ?></td><td><?= number_format($r['price']) ?></td></tr>
<?php endforeach; ?>
            </tbody>
        </table>
        <p class="more"><a href="prices.php">See all prices</a></p>
<?php else: ?>
        <p class="empty">No prices recorded yet.</p>
<?php endif; ?>
</section>

    <section class="cta">
        <h2>Ready to grow with us?</h2>
        <p>Talk to us about how ShambaPulse can support your farm.</p>
        <a href="contact.php" class="hero-button">Get in Touch</a>
    </section>

<?php layout_bottom();
