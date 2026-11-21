<?php
// Advisories page (doc FR-F-11, FR-O-08): published farming advisories
// for a ward, in English and Kiswahili.
require_once __DIR__ . '/includes/bootstrap.php';
$wards = ward_names();
$ward = trim($_GET['ward'] ?? '');
$lang = ($_GET['lang'] ?? '') === 'sw' ? 'sw' : 'en';
$rows = $ward !== '' && in_array($ward, $wards, true)
    ? q('SELECT * FROM advisory WHERE ward = ? ORDER BY published_at DESC', [$ward])
    : q('SELECT * FROM advisory ORDER BY published_at DESC');
if (!$_GET) log_activity('advisories', 'view');
layout_top('Advisories', 'advisories.php', 'inner');
?>
    <section class="page-intro">
        <h2>Farming advisories</h2>
        <p>Guidance published by extension officers for each ward, in English and Kiswahili. <a href="advisories.php?lang=en">English</a> | <a href="advisories.php?lang=sw">Kiswahili</a></p>
    </section>
    <section class="split">
        <div class="content">
            <form method="get" class="filters">
                <label>Ward
                    <select name="ward">
                        <option value="">All wards</option>
<?php foreach ($wards as $w): ?>
                        <option<?= $w === $ward ? ' selected' : '' ?>><?= e($w) ?></option>
<?php endforeach; ?>
                    </select>
                </label>
                <input type="hidden" name="lang" value="<?= e($lang) ?>">
                <button type="submit" class="hero-button">Show advisories</button>
            </form>
<?php if ($rows): ?>
            <ul class="card-row">
<?php foreach ($rows as $r): ?>
                <li>
                    <h3><?= e($r['title']) ?></h3>
                    <p><?= e($lang === 'sw' ? $r['body_sw'] : $r['body_en']) ?></p>
                    <p><?= e($r['ward']) ?> &middot; <?= e(date('d/m/Y', strtotime($r['published_at']))) ?></p>
                </li>
<?php endforeach; ?>
            </ul>
<?php else: ?>
            <p class="empty">No advisories published yet.</p>
<?php endif; ?>
        </div>
        <aside class="side">
            <h3>Advisories by SMS</h3>
            <p>When an officer publishes an advisory, subscribed farmers in that ward can also receive it by SMS. Dial *384# and choose 3 to hear the latest advisory for your ward.</p>
        </aside>
    </section>
<?php layout_bottom();
