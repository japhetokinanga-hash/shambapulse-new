<?php
// Advisory publishing (doc Module 4, FR-O-08, FR-F-11): advisories in
// English and Kiswahili for a ward. Officers publish to their own
// ward only. Ticking "also send by SMS" sends the advisory template
// to the ward's subscribed farmers (simulated in the demo).
require_once __DIR__ . '/_admin.php';
$ward = staff_ward();
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    if (isset($_POST['publish'])) {
        $title = trim($_POST['title'] ?? ''); $en = trim($_POST['body_en'] ?? ''); $sw = trim($_POST['body_sw'] ?? '');
        $w = $ward ?? trim($_POST['ward'] ?? '');
        if ($title === '' || $en === '' || $sw === '') $err = 'Write a title and the advisory in both languages.';
        elseif (!in_array($w, ward_names(), true)) $err = 'Choose a ward.';
        else {
            run('INSERT INTO advisory (title, body_en, body_sw, ward, published_by) VALUES (?,?,?,?,?)',
                [$title, $en, $sw, $w, (int)($_SESSION['staff_id'] ?? 0)]);
            $id = (int)db()->lastInsertId();
            $sent = 0;
            if (!empty($_POST['sms'])) {
                $farmers = q("SELECT f.* FROM farmer f JOIN subscription s ON s.farmer_id = f.farmer_id AND s.ward = ? AND s.channel = 'sms' AND s.active = 1 WHERE f.consent = 1 AND f.consent_at IS NOT NULL", [$w]);
                foreach ($farmers as $f) {
                    $lang = $f['language'] === 'Kiswahili' ? 'sw' : 'en';
                    send_sms(['phone' => $f['phone'], 'ward_name' => $w, 'alert_title' => $title,
                        'message' => sms_render('advisory', $lang, ['ward' => $w, 'title' => $title]),
                        'farmer_id' => (int)$f['farmer_id'], 'ward' => $w]);
                    $sent++;
                }
            }
            $msg = 'Advisory published.' . ($sent ? " SMS sent to $sent farmer(s)." : '');
        }
    } elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['delete'];
        $a = q('SELECT * FROM advisory WHERE advisory_id = ?', [$id])[0] ?? null;
        if (!$a || ($ward !== null && $a['ward'] !== $ward)) $err = 'Advisory not found.';
        else { run('DELETE FROM advisory WHERE advisory_id = ?', [$id]); $msg = 'Advisory deleted.'; }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $err = 'Your session expired. Please try again.';
$rows = $ward === null ? q('SELECT * FROM advisory ORDER BY published_at DESC, advisory_id DESC') : q('SELECT * FROM advisory WHERE ward = ? ORDER BY published_at DESC, advisory_id DESC', [$ward]);
admin_top('Advisories', 'advisories.php');
?>
    <section class="page-intro"><h2>Advisories<?= $ward ? ' — ' . e($ward) : '' ?></h2></section>
<?php if ($msg): ?><p role="status"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err): ?><p role="alert"><?= e($err) ?></p><?php endif; ?>
    <section class="contact-form-section"><h2>Publish an advisory</h2><form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <label for="title">Title</label><input id="title" name="title" maxlength="120" required>
<?php if ($ward === null): ?>
        <label for="ward">Ward</label><select id="ward" name="ward"><?php foreach (ward_names() as $w): ?><option><?= e($w) ?></option><?php endforeach; ?></select>
<?php else: ?>
        <p>Ward: <?= e($ward) ?></p>
<?php endif; ?>
        <label for="body_en">Advisory (English)</label><textarea id="body_en" name="body_en" rows="4" required></textarea>
        <label for="body_sw">Advisory (Kiswahili)</label><textarea id="body_sw" name="body_sw" rows="4" required></textarea>
        <label><input type="checkbox" name="sms" value="1" style="width:auto"> Also send by SMS to subscribed farmers in this ward</label>
        <button type="submit" class="hero-button" name="publish" value="1">Publish</button>
    </form></section>
    <table><thead><tr><th>Title</th><th>Ward</th><th>Published</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $a): ?>
        <tr><td><?= e($a['title']) ?><br><?= e(mb_substr($a['body_en'], 0, 120)) ?></td><td><?= e($a['ward']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($a['published_at']))) ?></td>
        <td><form method="post" style="display:inline" onsubmit="return confirm('Delete this advisory?')"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button type="submit" name="delete" value="<?= (int)$a['advisory_id'] ?>">Delete</button></form></td></tr>
<?php endforeach; ?>
    </tbody></table>
<?php admin_bottom();
