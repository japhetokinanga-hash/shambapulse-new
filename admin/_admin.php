<?php
// Staff-only pages. Not linked from the public site — staff sign in at
// login.php (admin / admin123) and land here.
require_once __DIR__ . '/../includes/bootstrap.php';
if (empty($_SESSION['staff'])) { header('Location: ../login.php?next=admin'); exit; }

function admin_top(string $title, string $current): void {
    $u = unread_message_count();
    $role = staff_role();
    $nav = [
        ['index.php', 'Dashboard'], ['messages.php', 'Messages' . ($u > 0 ? " ($u)" : '')],
        ['signins.php', 'Sign-ins'], ['farmers.php', 'Farmers'], ['prices.php', 'Prices'],
        ['alerts.php', 'Alerts'], ['advisories.php', 'Advisories'],
        ['subscriptions.php', 'Subscriptions'], ['delivery.php', 'Delivery'],
        ['reports.php', 'Reports'], ['templates.php', 'SMS templates'],
        ['ussd.php', 'USSD visits'], ['activity.php', 'Website activity'],
    ];
    if ($role === 'admin') {
        $nav[] = ['officers.php', 'Staff and roles'];
        $nav[] = ['config.php', 'Wards, markets, commodities'];
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> - Staff - ShambaPulse</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&amp;family=Source+Serif+4:wght@600&amp;display=swap">
<link rel="stylesheet" href="../css/style.css">
<link rel="icon" type="image/jpeg" href="../img/bcd.jpg">
</head>
<body class="inner">
<header>
    <h1><img class="logo" src="../img/logo.jpg" alt="ShambaPulse logo"> ShambaPulse</h1>
    <p>Where farmers meet quality services.</p>
</header>
<nav><ul>
<?php foreach ($nav as [$href, $label]): ?>
    <li><a href="<?= e($href) ?>"<?= $href === $current ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
<?php endforeach; ?>
    <li><a href="../index.php">View public site</a></li>
    <li><a href="../logout.php">Sign out</a></li>
</ul></nav>
<main>
<?php if ($u > 0 && $current !== 'messages.php'): ?>
    <p role="status"><strong><?= $u ?></strong> — <a href="messages.php">Messages</a></p>
<?php endif; ?>
<?php
}

function admin_bottom(): void {
    ?>
</main>
<footer>
    <p>ShambaPulse, Nairobi</p>
    <p>Site built by Kinanga Japhet and Gideon Francis, DBIT Year 1, 2026.</p>
</footer>
</body>
</html>
<?php
}
