<div class="page-head">
    <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-sub">Updates about your loans, renewals and library activity.</p>
    </div>
    <?php if ($unread > 0): ?>
        <form method="post" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn btn-soft"><i class="bi bi-check2-all"></i> Mark all as read</button>
        </form>
    <?php endif; ?>
</div>

<div class="card-box" style="height:auto">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $tab === 'all' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'all'])) ?>">All (<?= $total ?>)</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'unread' ? 'active' : '' ?>" href="<?= e(self_url(['tab' => 'unread'])) ?>">Unread (<?= $unread ?>)</a></li>
    </ul>

    <?php if (count($notes) === 0): ?>
        <p class="text-muted mb-0">
            <?= $tab === 'unread' ? 'You are all caught up. There are no unread notifications.' : 'You have no notifications yet.' ?>
        </p>
    <?php else: ?>
        <?php foreach ($notes as $note) { partial('notification', ['note' => $note, 'role' => $_SESSION['role']]); } ?>
        <?php if (count($notes) === 100): ?>
            <p class="text-muted mt-3 mb-0" style="font-size:0.75rem">Showing the latest 100 notifications.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

