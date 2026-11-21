<?php
require_once __DIR__ . '/_admin.php';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$m = q('SELECT * FROM message WHERE message_id = ?', [$id])[0] ?? null;
if (!$m) { header('Location: messages.php'); exit; }
$err = ''; $saved = false;
// Opening the message marks it read — that is what clears the NEW badge.
run('UPDATE message SET is_read = 1 WHERE message_id = ?', [$id]);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $reply = trim($_POST['reply'] ?? '');
    if ($reply === '') $err = 'Write a reply first.';
    else {
        run('UPDATE message SET reply = ?, replied_at = NOW() WHERE message_id = ?', [$reply, $id]);
        log_activity('messages', 'reply_message', $m['full_name'] . ', ' . $m['email']);
        $saved = true;
        $m = q('SELECT * FROM message WHERE message_id = ?', [$id])[0];
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') $err = 'Your session expired. Please try again.';
admin_top('Reply to message', 'messages.php');
?>
    <section class="page-intro">
        <h2>Message from <?= e($m['full_name']) ?></h2>
        <p><?= e($m['email']) ?> &middot; <?= e($m['type']) ?> &middot; <?= e(date('j M Y, H:i', strtotime($m['created_at']))) ?></p>
        <p><?= e($m['message']) ?></p>
    </section>
<?php if ($saved): ?><p role="status">Reply saved.</p><?php endif; ?>
<?php if ($err): ?><p role="alert"><?= e($err) ?></p><?php endif; ?>
<?php if ($m['reply']): ?><p><strong>Your saved reply (<?= e(date('j M Y, H:i', strtotime($m['replied_at']))) ?>):</strong><br><?= e($m['reply']) ?></p><?php endif; ?>
    <section class="contact-form-section">
        <h2>Reply</h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <label for="reply">Your reply</label>
            <textarea id="reply" name="reply" rows="5" maxlength="2000" required><?= e($m['reply'] ?? '') ?></textarea>
            <button type="submit" class="hero-button">Save reply</button>
        </form>
        <p><a href="messages.php">Back to all messages</a></p>
    </section>
<?php admin_bottom();
