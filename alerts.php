<?php
require_once __DIR__ . '/includes/bootstrap.php';
$sent = q("SELECT type, county, message_en, message_sw, created_at FROM alert WHERE status='sent' ORDER BY created_at DESC, alert_id DESC LIMIT 3");
log_activity('alerts', 'view');
layout_top('Alerts', 'alerts.php', 'inner');
?>

    <section class="page-intro">
        <h2>Alerts</h2>
        <p>
            Alerts carry county and regional advisories to farmers in a
            specific county. They are kept short so each one fits one SMS
            message, and they name the region rather than pretending to
            be a ward forecast.
        </p>
    </section>

    <section class="split">
        <div class="content">
            <h2>How an alert is sent</h2>
            <ol>
                <li>An officer creates an alert for a County, in English and Kiswahili.</li>
                <li>The system finds farmers in that county who have consented and subscribed.</li>
                <li>If no farmer is eligible, the alert stays a draft - it is not marked as sent.</li>
                <li>Delivery outcomes are recorded, so the officer can see the totals for that alert and filter them by county.</li>
            </ol>
            <p>
                Separating "submitted" from "confirmed delivered" matters:
                a message that was sent is not the same as a message that
                arrived.
            </p>
        </div>

        <aside class="side">
            <h3>Example topics</h3>
            <ul>
                <li>Short-rains onset and planting advice</li>
                <li>Heavy rain, flood and lightning risk</li>
                <li>Animal disease and crop disease warnings</li>
                <li>Market and advisory updates</li>
            </ul>
        </aside>
    </section>

    <section class="sample-alerts">
        <h2>What an alert looks like</h2>
        <p class="lead">
            Every alert is written in both languages. These are recent
            alerts, short enough to fit a single SMS.
        </p>
        <?php if (!$sent): ?>
        <p class="empty">No alerts have been sent yet.</p>
<?php endif; ?>
<?php foreach ($sent as $a): ?>
        <div class="sms-row">
            <div class="sms">
                <span class="sms-label">English - <?= e($a['type']) ?>, <?= e($a['county']) ?></span>
                <p><?= e($a['message_en']) ?></p>
                <p class="meta"><?= e(date('j M Y', strtotime($a['created_at']))) ?></p>
            </div>
            <div class="sms">
                <span class="sms-label">Kiswahili - <?= e($a['type']) ?>, <?= e($a['county']) ?></span>
                <p><?= e($a['message_sw']) ?></p>
            </div>
        </div>
<?php endforeach; ?>
    </section>

    <section class="types">
        <h2>Types of alert</h2>
        <ul class="card-row">
            <li>
                <h3>Weather</h3>
                <p>Rain, flood and storm warnings, with practical safety advice.</p>
            </li>
            <li>
                <h3>Advisory</h3>
                <p>Seasonal guidance: planting, certified seed, drainage and animal care.</p>
            </li>
            <li>
                <h3>Market</h3>
                <p>Price movements and market notices for the farmer's county.</p>
            </li>
        </ul>
    </section>

    <section class="steps">
        <h2>When you receive an alert</h2>
        <ol>
            <li>
                <h3>Read it fully</h3>
                <p>Check the ward, the date and the advice before acting.</p>
            </li>
            <li>
                <h3>Act early</h3>
                <p>Move animals, protect stored harvest and plan field work around the warning.</p>
            </li>
            <li>
                <h3>Pass it on</h3>
                <p>Tell neighbours who may not have a phone or may have missed the message.</p>
            </li>
        </ol>
    </section>

    <section class="faq">
        <h2>Common questions</h2>

        <details>
            <summary>Which alerts will I get?</summary>
            <p>You receive alerts for the county you registered in. Alerts are sent by county, not for single wards.</p>
        </details>

        <details>
            <summary>Can I stop alerts?</summary>
            <p>Yes. Choose "Opt out of alerts" in the USSD menu at any time.</p>
        </details>

        <details>
            <summary>Why did I not get an alert?</summary>
            <p>Alerts only go to farmers in the county who have consented and subscribed. Check your subscription from the USSD menu.</p>
        </details>
    </section>

    <section class="cta">
        <h2>Not receiving alerts yet?</h2>
        <p>Subscribe from any phone, no internet needed.</p>
        <a href="ussd.php" class="hero-button">See how to subscribe</a>
    </section>

<?php layout_bottom();
