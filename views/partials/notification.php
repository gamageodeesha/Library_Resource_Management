<?php
// One notification, drawn the same way on every page.
// Gets: $note, $role, and (optional) $with_button
$with_button = $with_button ?? true;
$unread = ($note['is_read'] == 0);
$link   = notification_link($note, $role);
?>
<div class="note-item<?= $unread ? ' unread' : '' ?>">
    <div class="d-flex justify-content-between align-items-center">
        <?= notification_badge($note['type']) ?>
        <span class="text-muted" style="font-size:0.7rem"><?= e(format_date($note['created_at'])) ?></span>
    </div>
    <div class="note-title"><?= e($note['title']) ?></div>
    <div class="text-muted" style="font-size:0.78rem"><?= e($note['message']) ?></div>

    <div class="mt-1 d-flex gap-3 align-items-center">
        <?php if ($link !== ''): ?>
            <a href="<?= e($link) ?>" style="font-size:0.78rem">View</a>
        <?php endif; ?>
        <?php if ($unread && $with_button): ?>
            <form method="post" action="" class="d-inline"><?= csrf_field() ?>
                <input type="hidden" name="action" value="mark_read">
                <input type="hidden" name="notification_id" value="<?= (int)$note['notification_id'] ?>">
                <button type="submit" class="btn btn-link p-0" style="font-size:0.78rem">Mark as read</button></form>
        <?php endif; ?>
    </div>
</div>
