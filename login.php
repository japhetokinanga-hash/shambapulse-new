<?php
require_once __DIR__ . '/includes/bootstrap.php';

function go_home(): void { header('Location: ' . (!empty($_SESSION['staff']) ? 'admin/index.php' : 'account.php')); exit; }
if (!empty($_SESSION['staff']) || !empty($_SESSION['farmer_id'])) go_home();

$err = ''; $regErr = ''; $old = ['identifier' => '', 'full_name' => '', 'phone' => '', 'county' => 'Kisumu', 'ward' => '', 'language' => 'English'];
$mode = $_POST['mode'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { $err = $regErr = 'Your session expired. Please try again.'; }

    elseif ($mode === 'login') {
        $old['identifier'] = trim($_POST['identifier'] ?? '');
        if (($_SESSION['lock_until'] ?? 0) > time()) { $err = 'Too many attempts. Please wait a few minutes and try again.'; }
        else {
            usleep(400000); // slows down password guessing
            $id = $old['identifier']; $pw = $_POST['password'] ?? ''; $ok = false;
            // Staff (doc 3.4.5, FR-O-01/FR-A-01): email + password, role
            // from the staff table. The old admin username still works.
            $s = q('SELECT * FROM staff WHERE (email = ? OR username = ?) AND active = 1', [$id, $id])[0] ?? null;
            if ($s && password_verify($pw, $s['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['staff'] = true; $_SESSION['staff_id'] = (int)$s['staff_id'];
                $_SESSION['staff_role'] = $s['role']; $_SESSION['staff_ward'] = $s['ward']; $_SESSION['staff_name'] = $s['name'];
                $ok = true; log_activity('login', 'staff_login', 'Staff: ' . $s['name'] . ' (' . $s['role'] . ($s['ward'] ? ', ' . $s['ward'] : '') . ')');
            } elseif (strcasecmp($id, ADMIN_USER) === 0 && password_verify($pw, ADMIN_HASH)) {
                session_regenerate_id(true); $_SESSION['staff'] = true; $_SESSION['staff_role'] = 'admin'; $_SESSION['staff_ward'] = ''; $_SESSION['staff_name'] = 'Admin';
                $ok = true; log_activity('login', 'staff_login', 'Staff: ' . ADMIN_USER);
            } else {                                                       // farmer, by phone number
                $f = q('SELECT farmer_id, full_name, phone, ward, password_hash FROM farmer WHERE phone = ?', [norm_phone($id)])[0] ?? null;
                if ($f && $f['password_hash'] && password_verify($pw, $f['password_hash'])) { session_regenerate_id(true); $_SESSION['farmer_id'] = (int)$f['farmer_id']; $ok = true; run('UPDATE farmer SET last_login_at = NOW() WHERE farmer_id = ?', [(int)$f['farmer_id']]); log_activity('login', 'farmer_login', $f['full_name'] . ', ' . $f['phone'] . ', ' . $f['ward'] . ', Nyando'); }
            }
            if ($ok) { unset($_SESSION['fails']); go_home(); }
            $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
            if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = time() + 300; $_SESSION['fails'] = 0; }
            $err = 'Wrong phone number, username or password.';
        }
    }

    elseif ($mode === 'register') {
        foreach (['full_name', 'phone', 'county', 'ward', 'language'] as $k) $old[$k] = trim($_POST[$k] ?? '');
        $phone = norm_phone($old['phone']); $pw = $_POST['password'] ?? '';
        if ($old['full_name'] === '' || mb_strlen($old['full_name']) > 80) $regErr = 'Please enter your name.';
        elseif (!preg_match('/^\+254[17]\d{8}$/', $phone)) $regErr = 'Please enter a valid Kenyan phone number, for example 0712 345 678.';
        elseif (!isset(COUNTIES[$old['county']]) || !in_array($old['ward'], COUNTIES[$old['county']], true)) $regErr = 'Please choose your county and ward.';
        elseif (!in_array($old['language'], ['English', 'Kiswahili'], true)) $regErr = 'Please choose a language.';
        elseif (strlen($pw) < 8) $regErr = 'Your password must have at least 8 characters.';
        elseif ($pw !== ($_POST['password2'] ?? '')) $regErr = 'The two passwords do not match.';
        elseif (empty($_POST['consent'])) $regErr = 'We need your consent under the Data Protection Act, 2019 to register you.';
        else {
            $ex = q('SELECT password_hash FROM farmer WHERE phone = ?', [$phone])[0] ?? null;
            if ($ex) $regErr = $ex['password_hash'] ? 'This number already has an account. Please sign in.' : 'This number was registered by USSD. For safety, ask staff (see Contact) to set up your web access.';
            else {
                run('INSERT INTO farmer (full_name, phone, password_hash, county, ward, language, consent, consent_at, subscribed, last_login_at) VALUES (?,?,?,?,?,?,1,NOW(),1,NOW())',
                    [$old['full_name'], $phone, password_hash($pw, PASSWORD_DEFAULT), $old['county'], $old['ward'], $old['language']]);
                $fid = (int)db()->lastInsertId();
                subscribe_farmer($fid, $old['ward']); // home ward subscription (FR-F-04)
                session_regenerate_id(true); $_SESSION['farmer_id'] = $fid;
                log_activity('login', 'farmer_register', $old['full_name'] . ', ' . $phone . ', ' . $old['ward'] . ', Nyando, ' . $old['language']); go_home();
            }
        }
    }
} else { log_activity('login', 'view'); }

layout_top('Sign in', 'login.php', 'contact');
?>
    <section class="contact-intro">
        <h2>Sign in</h2>
        <p>Farmers and ShambaPulse staff sign in here. Farmers use their phone number; staff use their email address.</p>
    </section>

    <section class="contact-form-section" id="signin">
        <h2>Sign in</h2>
<?php if (isset($_GET['next']) && !$err): ?><p class="flash">Please sign in to continue.</p><?php endif; ?>
<?php if ($err): ?><p class="flash error" role="alert"><?= e($err) ?></p><?php endif; ?>
        <form method="post" action="login.php#signin">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="mode" value="login">
            <label for="identifier">Phone number or staff email</label>
            <input id="identifier" name="identifier" required autocomplete="username" value="<?= e($old['identifier']) ?>">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            <button type="submit" class="hero-button">Sign in</button>
        </form>
    </section>

    <section class="contact-form-section" id="register">
        <h2>New farmer? Create an account</h2>
        <p class="lead">Already subscribed by USSD? Ask staff to set up your web access (see <a href="contact.php">Contact</a>).</p>
<?php if ($regErr): ?><p class="flash error" role="alert"><?= e($regErr) ?></p><?php endif; ?>
        <form method="post" action="login.php#register">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="mode" value="register">
            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" maxlength="80" required value="<?= e($old['full_name']) ?>">
            <label for="phone">Phone number</label>
            <input id="phone" name="phone" inputmode="tel" placeholder="0712 345 678" required value="<?= e($old['phone']) ?>">
            <label for="county">County</label>
            <select id="county" name="county">
<?php foreach (COUNTIES as $c => $_): ?><option<?= $c === $old['county'] ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
            </select>
            <label for="ward">Ward</label>
            <select id="ward" name="ward" required>
<?php foreach (COUNTIES as $c => $ws): foreach ($ws as $w): ?><option data-county="<?= e($c) ?>"<?= $w === $old['ward'] ? ' selected' : '' ?>><?= e($w) ?></option><?php endforeach; endforeach; ?>
            </select>
            <label for="language">Language</label>
            <select id="language" name="language"><option<?= $old['language'] === 'English' ? ' selected' : '' ?>>English</option><option<?= $old['language'] === 'Kiswahili' ? ' selected' : '' ?>>Kiswahili</option></select>
            <label for="rpw">Password (at least 8 characters)</label>
            <input id="rpw" name="password" type="password" minlength="8" required autocomplete="new-password">
            <label for="rpw2">Repeat password</label>
            <input id="rpw2" name="password2" type="password" minlength="8" required autocomplete="new-password">
            <label><input type="checkbox" name="consent" value="1" style="width:auto"> I agree to receive alerts and to ShambaPulse storing my phone number and ward (Data Protection Act, 2019).</label>
            <button type="submit" class="hero-button">Create account</button>
        </form>
    </section>
    <script>
    // Show only the wards of the chosen county (the server re-checks the choice).
    const c = document.getElementById('county'), w = document.getElementById('ward');
    function sync() { let first = null; [...w.options].forEach(o => { const on = o.dataset.county === c.value; o.hidden = o.disabled = !on; if (on && !first) first = o; }); if (w.selectedOptions[0]?.disabled) w.value = first.value; }
    c.addEventListener('change', sync); sync();
    </script>
<?php layout_bottom();
