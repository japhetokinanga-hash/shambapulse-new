<?php
// SMS templates (doc Appendix C.2, FR-O-09): the six bilingual
// templates with placeholders and segment counts, as used by the
// dispatch engine. 1 segment = 160 GSM characters.
require_once __DIR__ . '/_admin.php';
$sample = ['rainfall' => '25', 'ward' => 'Ahero', 'date' => date('Y-m-d'), 'days' => '7', 'commodity' => 'Maize', 'price' => '5000', 'unit' => '90 kg bag', 'market' => 'Ahero', 'title' => 'Planting after the rains', 'name' => 'Mary'];
admin_top('SMS templates', 'templates.php');
?>
    <section class="page-intro"><h2>SMS templates</h2>
    <p>Six templates, English and Kiswahili. {placeholders} are filled in for each farmer. The segment count is for the sample text shown.</p></section>
<?php foreach (SMS_TEMPLATES as $key => $t): ?>
    <section>
        <h2><?= e($key) ?></h2>
        <p><strong>English</strong> (<?= sms_segments(sms_render($key, 'en', $sample)) ?> segment(s)): <?= e(sms_render($key, 'en', $sample)) ?></p>
        <p><strong>Kiswahili</strong> (<?= sms_segments(sms_render($key, 'sw', $sample)) ?> segment(s)): <?= e(sms_render($key, 'sw', $sample)) ?></p>
    </section>
<?php endforeach; ?>
<?php admin_bottom();
