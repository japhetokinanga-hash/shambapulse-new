<?php
require_once __DIR__ . '/includes/bootstrap.php';
$st = q("SELECT (SELECT COUNT(DISTINCT county) FROM price) counties, (SELECT COUNT(DISTINCT commodity) FROM price) commodities,
            (SELECT COUNT(*) FROM farmer WHERE consent = 1) farmers, (SELECT COUNT(*) FROM alert WHERE status = 'sent') alerts")[0];
log_activity('about', 'view');
layout_top('About', 'about.php', 'about');
?>

    <section class="intro">
        <h2>About ShambaPulse</h2>
        <p>
            ShambaPulse brings weather alerts, farming advisories and
            market prices to smallholder farmers, on a smartphone or a
            basic phone, in English and Kiswahili.
        </p>
    </section>

    <section class="stats" aria-label="ShambaPulse at a glance">
        <ul>
            <li><strong><?= (int)$st['counties'] ?></strong><span>Counties covered</span></li>
            <li><strong><?= (int)$st['commodities'] ?></strong><span>Commodities tracked</span></li>
            <li><strong><?= (int)$st['farmers'] ?></strong><span>Farmers registered</span></li>
            <li><strong><?= (int)$st['alerts'] ?></strong><span>Alerts sent</span></li>
        </ul>
    </section>

    <section class="detail">
        <div class="detail-head">
            <h2>Plan With Better Information</h2>
            <h3>Grow More Every Season</h3>
        </div>

        <div class="detail-body">
            <p>
                ShambaPulse gives farmers weather alerts, farming
                advisories and market prices in one place, so planting and
                selling decisions are made with current information.
            </p>
            <p>
                Make informed decisions and grow your farming business
                with confidence.
            </p>
            <a href="contact.php" class="hero-button">Contact Us</a>
        </div>
    </section>

    <section class="detail">
        <div class="detail-head">
            <h2>Our Story</h2>
            <h3>Rooted in Kenyan soil</h3>
        </div>

        <div class="detail-body">
            <p>
                ShambaPulse started with a simple observation: many
                farmers still make big decisions without timely
                information. Weather changes, market prices and pest
                outbreaks move fast, and a farmer who hears too late
                can lose a whole season.
            </p>
            <p>
                We are a team of students and developers in Strathmore University, Nairobi,
                building a platform that puts that information in
                every farmer's hands, whether through a smartphone or
                a basic phone using USSD.
            </p>
        </div>
    </section>

    <section class="detail">
        <div class="detail-head">
            <h2>Our Mission</h2>
            <h3>Information that reaches the farm</h3>
        </div>

        <div class="detail-body">
            <p>
                To help farmers increase their harvests and income by
                giving them reliable, timely and easy-to-use farming
                information.
            </p>
            <p>
                We believe good decisions grow good harvests, and
                every farmer deserves the information to make them.
            </p>
        </div>
    </section>

    <section class="services">
        <h2>What We Offer</h2>
        <ul class="card-row">
            <li>
                <h3>Farm Alerts</h3>
                <p>Weather warnings, farming advisories and planting reminders sent when they matter.</p>
            </li>
            <li>
                <h3>Market Prices</h3>
                <p>Up-to-date prices for maize, beans, vegetables and more, so you can sell at the right time.</p>
            </li>
            <li>
                <h3>USSD Access</h3>
                <p>No smartphone or internet needed. Dial a short code from any phone to get farm information.</p>
            </li>
            <li>
                <h3>Advisories</h3>
                <p>Seasonal farming guidance, written in English and Kiswahili for your county.</p>
            </li>
        </ul>
    </section>

    <section class="values">
        <h2>Our Values</h2>
        <ul class="card-row">
            <li>
                <h3>Accessible</h3>
                <p>Built for every farmer, on any phone, in English and Kiswahili.</p>
            </li>
            <li>
                <h3>Reliable</h3>
                <p>Information you can trust enough to plan a season around.</p>
            </li>
            <li>
                <h3>Sustainable</h3>
                <p>Encouraging farming practices that protect the land for the next generation.</p>
            </li>
        </ul>
    </section>

    <section class="team">
        <h2>Meet the Team</h2>
        <ul class="card-row">
            <li>
                <h3>Kinanga Japhet</h3>
                <p>Co-founder and web developer</p>
            </li>
            <li>
                <h3>Gideon Francis</h3>
                <p>Co-founder and web developer</p>
            </li>
        </ul>
    </section>

    <section class="cta">
        <h2>Ready to grow with us?</h2>
        <p>Talk to us about how ShambaPulse can support your farm.</p>
        <a href="contact.php" class="hero-button">Get in Touch</a>
    </section>

<?php layout_bottom();
