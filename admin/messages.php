<?php
require_once __DIR__ . '/_admin.php';
// Mark one message read, or all of them.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    if (isset($_POST['mark_all'])) run('UPDATE message SET is_read = 1');
    elseif (!empty($_POST['mark_read'])) run('UPDATE message SET is_read = 1 WHERE message_id = ?', [(int)$_POST['mark_read']]);
    header('Location: messages.php'); exit;
}
$rows = q('SELECT message_id, full_name, email, type, message, reply, replied_at, is_read, created_at FROM message ORDER BY message_id DESC');
admin_top('Messages', 'messages.php');
?>
    <section class="page-intro">
        <h2>Contact messages</h2>
        <p><?= count($rows) ?> messages. <?= unread_message_count() ?> unread.</p>
        <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button class="hero-button" type="submit" name="mark_all" value="1">Mark all as read</button></form>
    </section>
    <table><thead><tr><th></th><th>Name</th><th>Email</th><th>Type</th><th>Message</th><th>When</th><th>Reply</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
        <tr>
            <td><?php if (!$r['is_read']): ?><span class="badge">NEW</span><?php endif; ?></td>
            <td><?= e($r['full_name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['type']) ?></td>
            <td><?= e($r['message']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
            <td><?= $r['replied_at'] ? 'Replied' : 'Not replied'; ?><?php if ($r['reply']) echo '<br>' . e($r['reply']); ?></td>
            <td><a href="reply-message.php?id=<?= (int)$r['message_id'] ?>">Read / reply</a>
            <?php if (!$r['is_read']): ?><form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button type="submit" name="mark_read" value="<?= (int)$r['message_id'] ?>">Mark read</button></form><?php endif; ?></td>
        </tr>
<?php endforeach; ?>
    </tbody></table>
<?php admin_bottom();
