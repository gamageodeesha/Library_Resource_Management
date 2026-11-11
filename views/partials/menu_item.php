<?php
// One item of the sidebar menu.
// Gets: $item = [key, label, route, icon], $active_page, $badges
$is_active = ($item[0] === $active_page) ? ' active' : '';

// The Logout link opens the confirmation popup (see layout/footer.php).
// If JavaScript is off, the link still works and signs the user out.
$popup = ($item[0] === 'logout') ? ' data-bs-toggle="modal" data-bs-target="#logoutModal"' : '';
?>
<a class="nav-item-link<?= $is_active ?>" href="<?= e(route($item[2])) ?>"<?= $popup ?>>
    <i class="bi <?= $item[3] ?>"></i><span><?= e($item[1]) ?></span>
    <?php if (isset($badges[$item[0]])): ?>
        <span class="nav-count count-<?= $badges[$item[0]][0] ?>"><?= (int)$badges[$item[0]][1] ?></span>
    <?php endif; ?>
</a>
