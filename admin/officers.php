<?php
// Staff and roles (doc Table 3.8, FR-A-02): administrators manage user
// accounts and roles. One phone number per user is enforced by the
// unique key; staff sign in with email and password. Admin only.
require_once __DIR__ . '/_admin.php';
require_admin();
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    if (isset($_POST['create'])) {
        $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $pw = $_POST['password'] ?? '';
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'officer';
        $w = $role === 'officer' ? trim($_POST['ward'] ?? '') : '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter a name and a valid email address.';
        elseif (strlen($pw) < 8) $err = 'The password must have at least 8 characters.';
        elseif ($role === 'officer' && !in_array($w, ward_names(), true)) $err = 'Choose the officer’s ward.';
        else {
            try {
                run('INSERT INTO staff (name, username, email, password_hash, role, ward) VALUES (?,?,?,?,?,?)',
                    [$name, explode('@', $email)[0], $email, password_hash($pw, PASSWORD_DEFAULT), $role, $w]);
                $msg = 'Staff account created.';
            } catch (Throwable $e) { $err = 'That email address is already in use.'; }
        }
    } elseif (isset($_POST['deactivate'])) {
        $id = (int)$_POST['deactivate'];
        if ($id === (int)$_SESSION['staff_id']) $err = 'You cannot deactivate your own account.';
        else { run('UPDATE staff SET active = 0 WHERE staff_id = ?', [$id]); $msg = 'Staff account deactivated.'; }
    } elseif (isset($_POST['activate'])) {
        run('UPDATE staff SET active = 1 WHERE staff_id = ?', [(int)$_POST['activate']]);
        $msg = 'Staff account reactivated.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $err = 'Your session expired. Please try again.';
$rows = q('SELECT * FROM staff ORDER BY role, name');
admin_top('Staff and roles', 'officers.php');
?>
    <section class="page-intro"><h2>Staff and roles</h2></section>
<?php if ($msg): ?><p role="status"><?= e($msg) ?></p><?php endif; ?>
<?php if ($err): ?><p role="alert"><?= e($err) ?></p><?php endif; ?>
    <section class="contact-form-section"><h2>Add staff</h2><form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <label for="name">Full name</label><input id="name" name="name" maxlength="80" required>
        <label for="email">Email</label><input id="email" name="email" type="email" maxlength="100" required>
        <label for="password">Password (at least 8 characters)</label><input id="password" name="password" type="password" minlength="8" required>
        <label for="role">Role</label><select id="role" name="role"><option value="officer">Extension officer (one ward)</option><option value="admin">Administrator (all wards)</option></select>
        <label for="ward">Ward (officers only)</label><select id="ward" name="ward"><?php foreach (ward_names() as $w): ?><option><?= e($w) ?></option><?php endforeach; ?></select>
        <button type="submit" class="hero-button" name="create" value="1">Create account</button>
    </form></section>
    <table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Ward</th><th>Active</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $s): ?>
        <tr><td><?= e($s['name']) ?></td><td><?= e($s['email']) ?></td><td><?= e($s['role']) ?></td><td><?= e($s['ward']) ?></td><td><?= $s['active'] ? 'Yes' : 'No' ?></td>
        <td><?php if ($s['active']): ?><form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button type="submit" name="deactivate" value="<?= (int)$s['staff_id'] ?>">Deactivate</button></form><?php else: ?><form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button type="submit" name="activate" value="<?= (int)$s['staff_id'] ?>">Reactivate</button></form><?php endif; ?></td></tr>
<?php endforeach; ?>
    </tbody></table>
<?php admin_bottom();
