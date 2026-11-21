<?php
// Climate alerts and dispatch (doc Module 2, FR-O-03/04, FR-F-10).
// Compose a bilingual alert and dispatch it by SMS to the subscribed
// farmers of a ward who recorded consent. Each farmer gets the
// version in their own language. Delivery counts come from the
// message log: submitted, delivered, failed, simulated.
require_once __DIR__ . '/_admin.php';
$ward = staff_ward(); // null = admin (any ward); officer = own ward
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    if (isset($_POST['create'])) {
        $type = trim($_POST['type'] ?? ''); $title = trim($_POST['title'] ?? '');
        $en = trim($_POST['message_en'] ?? ''); $sw = trim($_POST['message_sw'] ?? '');
        $w = $ward ?? trim($_POST['ward'] ?? '');
        $sev = in_array($_POST['severity'] ?? '', ['info', 'high'], true) ? $_POST['severity'] : 'info';
        if (!isset(SMS_TEMPLATES[$type])) $err = 'Choose a type.';
        elseif ($title === '') $err = 'Give the alert a title.';
        elseif (!in_array($w, ward_names(), true)) $err = 'Choose a ward.';
        elseif ($en === '' || mb_strlen($en) > 160 || $sw === '' || mb_strlen($sw) > 160) $err = 'Write both messages, 160 characters or fewer each.';
        else {
            run("INSERT INTO alert (type, title, severity, county, ward, message_en, message_sw, status, created_by) VALUES (?,?,?,?,?,?,?,?,?)",
                [$type, $title, $sev, SCOPE_COUNTY, $w, $en, $sw, 'draft', (int)($_SESSION['staff_id'] ?? 0)]);
            $msg = 'Alert saved as a draft. Use Send to dispatch it.';
        }
    } elseif (isset($_POST['send'])) {
        $id = (int)$_POST['send'];
        $a = q('SELECT * FROM alert WHERE alert_id = ?', [$id])[0] ?? null;
        if (!$a || !can_see_ward($a['ward'])) $err = 'Alert not found.';
        else {
            $r = dispatch_alert($id);
            if ($r['sent'] === 0) $err = 'No eligible farmers. The alert stays a draft.';
            else $msg = 'Sent to ' . $r['sent'] . ' farmer(s). See Delivery for the outcome.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $err = 'Your session expired. Please try again.';
$rows = $ward === null
    ? q('SELECT * FROM alert ORDER BY alert_id DESC')
    : q('SELECT * FROM alert WHERE ward = ? ORDER BY alert_id DESC', [$ward]);
admin_top('Alerts', 'alerts.php');
?>
    <section class="page-intro"><h2>Alerts<?= $ward ? ' — ' . e($ward) : '' ?></h2></section>
<?php if ($msg): ?><p role="status"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err): ?><p role="alert"><?= e($err) ?></p><?php endif; ?>
    <section class="contact-form-section"><h2>New alert</h2><form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <label for="type">Type (fills the messages from the SMS template)</label>
        <select id="type" name="type">
<?php foreach (SMS_TEMPLATES as $k => $t): ?>
            <option value="<?= e($k) ?>"><?= e($k) ?></option>
<?php endforeach; ?>
        </select>
        <label for="title">Title</label><input id="title" name="title" maxlength="120" required placeholder="Heavy rain expected in Ahero">
        <label for="severity">Severity</label><select id="severity" name="severity"><option value="info">Info</option><option value="high">High</option></select>
<?php if ($ward === null): ?>
        <label for="ward">Ward</label><select id="ward" name="ward"><?php foreach (ward_names() as $w): ?><option><?= e($w) ?></option><?php endforeach; ?></select>
<?php else: ?>
        <p>Ward: <?= e($ward) ?></p>
<?php endif; ?>
        <label for="message_en">Message (English, 160 characters or fewer)</label><textarea id="message_en" name="message_en" rows="3" maxlength="160" required></textarea>
        <label for="message_sw">Message (Kiswahili, 160 characters or fewer)</label><textarea id="message_sw" name="message_sw" rows="3" maxlength="160" required></textarea>
        <button type="submit" class="hero-button" name="create" value="1">Save as draft</button>
    </form></section>
    <table><thead><tr><th>Title</th><th>Type</th><th>Ward</th><th>Status</th><th>Submitted</th><th>Delivered</th><th>Failed</th><th>Simulated</th><th>Created</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $a): $c = delivery_counts((int)$a['alert_id']); ?>
        <tr><td><?= e($a['title'] ?: $a['type']) ?></td><td><?= e($a['type']) ?></td><td><?= e($a['ward']) ?></td><td><?= e($a['status']) ?></td>
        <td><?= $c['submitted'] ?></td><td><?= $c['delivered'] ?></td><td><?= $c['failed'] ?></td><td><?= $c['simulated'] ?></td>
        <td><?= e(date('d/m/Y H:i', strtotime($a['created_at']))) ?></td>
        <td><?php if ($a['status'] !== 'sent'): ?><form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button type="submit" name="send" value="<?= (int)$a['alert_id'] ?>">Send</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
    </tbody></table>
    <p><a href="delivery.php">Delivery report</a> &middot; <a href="templates.php">SMS templates</a></p>
    <script>
    // Fill both messages from the chosen SMS template (sample values).
    const T = <?= json_encode(SMS_TEMPLATES) ?>;
    const type = document.getElementById('type'), en = document.getElementById('message_en'), sw = document.getElementById('message_sw');
    function fill() { const t = T[type.value] || {}; en.value = (t.en || '').replace('{rainfall}','25').replace('{ward}','Ahero').replace('{date}','<?= date('Y-m-d') ?>').replace('{days}','7').replace('{commodity}','Maize').replace('{price}','5000').replace('{unit}','90 kg bag').replace('{market}','Ahero').replace('{title}','advisory'); sw.value = (t.sw || '').replace('{rainfall}','25').replace('{ward}','Ahero').replace('{date}','<?= date('Y-m-d') ?>').replace('{days}','7').replace('{commodity}','Mahindi').replace('{price}','5000').replace('{unit}','gunia la 90kg').replace('{market}','Ahero').replace('{title}','ushauri'); }
    type.addEventListener('change', fill); fill();
    </script>
<?php admin_bottom();
