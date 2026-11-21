<?php
require_once __DIR__ . '/includes/bootstrap.php';
$types = ['Farmer', 'Buyer', 'Partner or organisation', 'Other'];
$err = ''; $old = ['full_name' => '', 'email' => '', 'type' => 'Farmer', 'message' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');
    if (!csrf_ok()) $err = 'Your session expired. Please try again.';
    elseif (($_POST['website'] ?? '') !== '') $err = 'Could not send your message.'; // honeypot
    elseif ($old['full_name'] === '' || mb_strlen($old['full_name']) > 80) $err = 'Please enter your name (80 characters or fewer).';
    elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 100) $err = 'Please enter a valid email address.';
    elseif (!in_array($old['type'], $types, true)) $err = 'Please choose who you are.';
    elseif ($old['message'] === '' || mb_strlen($old['message']) > 2000) $err = 'Please write a message (up to 2000 characters).';
    else {
        run('INSERT INTO message (full_name, email, type, message, is_read) VALUES (?,?,?,?,0)', [$old['full_name'], $old['email'], $old['type'], $old['message']]);
        log_activity('contact', 'contact_message', $old['full_name'] . ', ' . $old['email'] . ' - ' . $old['type'] . ': ' . mb_substr($old['message'], 0, 120));
        header('Location: contact.php?sent=1#form'); exit;
    }
} else { log_activity('contact', 'view'); }
layout_top('Contact', 'contact.php', 'contact');
?>
    <section class="contact-intro">
        <h2>Contact Us</h2>
        <p>Farmer, buyer or partner, we would like to hear from you. Send us a message and our team will reply.</p>
    </section>

    <section class="contact-grid" aria-label="Ways to reach us">
        <div class="contact-card">
            <h3>Where we are</h3>
            <p>Strathmore University, Nairobi, Kenya</p>
            <p class="note">School of Computing and Engineering Sciences</p>
        </div>
        <div class="contact-card">
            <h3>From any phone</h3>
            <ul>
                <li>Dial <strong>*384#</strong></li>
                <li>English and Kiswahili</li>
                <li>No internet needed</li>
            </ul>
        </div>
        <div class="contact-card">
            <h3>Who can write to us</h3>
            <ul>
<?php foreach ($types as $t): ?>
                <li><?= e($t) ?></li>
<?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="contact-form-section" id="form">
        <h2>Send a message</h2>
<?php if (isset($_GET['sent'])): ?>
        <p class="flash" role="status">Thank you. Your message has been received.</p>
<?php endif; ?>
<?php if ($err): ?>
        <p class="flash error" role="alert"><?= e($err) ?></p>
<?php endif; ?>
        <form method="post" action="contact.php#form">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" maxlength="80" required value="<?= e($old['full_name']) ?>">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" maxlength="100" required value="<?= e($old['email']) ?>">
            <label for="type">I am a</label>
            <select id="type" name="type">
<?php foreach ($types as $t): ?>
                <option<?= $t === $old['type'] ? ' selected' : '' ?>><?= e($t) ?></option>
<?php endforeach; ?>
            </select>
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="5" maxlength="2000" required><?= e($old['message']) ?></textarea>
            <button type="submit" class="hero-button">Send message</button>
        </form>
    </section>

    <section class="faq">
        <h2>Common questions</h2>
        <details>
            <summary>How quickly will I get a reply?</summary>
            <p>Our team reads every message. Replies are sent to the email address you give us.</p>
        </details>
        <details>
            <summary>I am a farmer without internet. How do I reach you?</summary>
            <p>Dial *384# from any phone to check prices and alerts, or ask a neighbour or extension officer to send a message for you.</p>
        </details>
        <details>
            <summary>Is my information safe?</summary>
            <p>We store only what we need and follow the Data Protection Act, 2019. You can opt out of alerts at any time.</p>
        </details>
    </section>
<?php layout_bottom();
