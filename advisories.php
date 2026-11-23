<?php
// Advisories page (doc FR-F-11, FR-O-08): published farming advisories
// for a ward, in English and Kiswahili.
require_once __DIR__ . '/includes/bootstrap.php';

const ADVISORIES_LIMIT = 50;

$wards     = ward_names();
$ward      = trim($_GET['ward'] ?? '');
$lang      = ($_GET['lang'] ?? '') === 'sw' ? 'sw' : 'en';
$validWard = $ward !== '' && in_array($ward, $wards, true);

// Literal integer LIMIT (constant, not user input) to avoid driver issues with bound LIMIT params.
$rows = $validWard
    ? q('SELECT * FROM advisory WHERE ward = ? ORDER BY published_at DESC LIMIT ' . ADVISORIES_LIMIT, [$ward])
    : q('SELECT * FROM advisory ORDER BY published_at DESC LIMIT ' . ADVISORIES_LIMIT);

// Log every view (including filtered and language-switched views).
log_activity('advisories', 'view');

// Build a link for a given language that keeps the current ward filter.
$langUrl = function (string $l) use ($validWard, $ward): string {
    $params = ['lang' => $l];
    if ($validWard) {
        $params = ['ward' => $ward] + $params;
    }
    return 'advisories.php?' . http_build_query($params);
};

layout_top('Advisories', 'advisories.php', 'inner');
?>
    <section class="page-intro">
        <h2>Farming advisories</h2>
        <p>Guidance published by extension officers for each ward, in English and Kiswahili.
            <a href="<?= e($langUrl('en')) ?>" hreflang="en"<?= $lang === 'en' ? ' aria-current="true"' : '' ?>>English</a> |
            <a href="<?= e($langUrl('sw')) ?>" hreflang="sw" lang="sw"<?= $lang === 'sw' ? ' aria-current="true"' : '' ?>>Kiswahili</a></p>
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
<?php foreach ($rows as $r):
    // Fall back to English if the Kiswahili text is missing.
    $useSw     = $lang === 'sw' && trim((string)($r['body_sw'] ?? '')) !== '';
    $body      = $useSw ? $r['body_sw'] : $r['body_en'];
    $timestamp = strtotime($r['published_at']);
?>
                <li>
                    <h3><?= e($r['title']) ?></h3>
                    <p lang="<?= $useSw ? 'sw' : 'en' ?>"><?= e($body) ?></p>
<?php if ($lang === 'sw' && !$useSw): ?>
                    <p class="note"><small>Kiswahili version not available yet; showing English.</small></p>
<?php endif; ?>
                    <p><?= e($r['ward']) ?> &middot; <time datetime="<?= e(date('c', $timestamp)) ?>"><?= e(date('d/m/Y', $timestamp)) ?></time></p>
                </li>
<?php endforeach; ?>
            </ul>
<?php else: ?>
            <p class="empty"><?= $validWard
                ? 'No advisories published for ' . e($ward) . ' yet.'
                : 'No advisories published yet.' ?></p>
<?php endif; ?>
        </div>
        <aside class="side">
            <h3>Advisories by SMS and USSD</h3>
            <p>When an officer publishes an advisory, subscribed farmers in that ward can also receive it by SMS. You can also dial *384# (USSD) and choose 3 to hear the latest advisory for your ward.</p>
        </aside>
    </section>
<?php layout_bottom();