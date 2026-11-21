<?php
require_once __DIR__ . '/_admin.php';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$f = q('SELECT * FROM farmer WHERE farmer_id = ?', [$id])[0] ?? null;
if (!$f) { header('Location: farmers.php'); exit; }
if (staff_ward() !== null && $f['ward'] !== staff_ward()) { header('Location: farmers.php'); exit; } // officer: own ward only
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $ward = trim($_POST['ward'] ?? ''); $lang = trim($_POST['language'] ?? ''); $sub = !empty($_POST['subscribed']);
    if (!in_array($ward, COUNTIES[SCOPE_COUNTY], true)) $err = 'Choose a ward.';
    elseif (!in_array($lang, ['English', 'Kiswahili'], true)) $err = 'Choose a language.';
    else {
        run('UPDATE farmer SET ward = ?, language = ?, subscribed = ? WHERE farmer_id = ?', [$ward, $lang, $sub ? 1 : 0, $id]);
        $pw = trim($_POST['new_password'] ?? '');
        if ($pw !== '') {
            if (strlen($pw) < 8) $err = 'The new password must have at least 8 characters.';
            else run('UPDATE farmer SET password_hash = ? WHERE farmer_id = ?', [password_hash($pw, PASSWORD_DEFAULT), $id]);
        }
        if (!$err) { $msg = 'Saved.'; $f = q('SELECT * FROM farmer WHERE farmer_id = ?', [$id])[0]; }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $err = 'Your session expired. Please try again.';
admin_top('Edit farmer', 'farmers.php');
?>
    <section class="page-intro"><h2><?= e($f['full_name']) ?> — <?= e($f['phone']) ?></h2><p><?= e($f['ward']) ?> &middot; joined <?= e(date('d/m/Y', strtotime($f['created_at']))) ?></p></section>
<?php if ($msg): ?><p role="status"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err): ?><p role="alert"><?= e($err) ?></p><?php endif; ?>
    <section class="contact-form-section"><form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="id" value="<?= $id ?>">
        <label for="ward">Ward</label>
        <select id="ward" name="ward"><?php foreach (COUNTIES[SCOPE_COUNTY] as $w): ?><option<?= $w === $f['ward'] ? ' selected' : '' ?>><?= e($w) ?></option><?php endforeach; ?></select>
        <label for="language">Language</label>
        <select id="language" name="language"><option<?= $f['language'] === 'English' ? ' selected' : '' ?>>English</option><option<?= $f['language'] === 'Kiswahili' ? ' selected' : '' ?>>Kiswahili</option></select>
        <label><input type="checkbox" name="subscribed" value="1" style="width:auto"<?= $f['subscribed'] ? ' checked' : '' ?>> Subscribed to alerts</label>
        <label for="new_password">Set a web password (8+ characters) — leave blank to keep the current one.</label>
        <input id="new_password" name="new_password" type="text" minlength="8" autocomplete="off">
        <button type="submit" class="hero-button">Save</button>
    </form></section>
    <p><a href="farmers.php">Back to farmers</a></p>
<?php admin_bottom();
