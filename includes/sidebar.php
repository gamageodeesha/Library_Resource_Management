<?php
// =====================================================
// Left sidebar. The menu is different for each role.
// Each item is:  [key, label, link, icon]
// =====================================================

if ($role === 'admin') {
    $menu = [
        ['dashboard', 'Dashboard',   'admin/dashboard.php', 'bi-grid'],
        ['users',     'Users & Roles', 'admin/users.php',   'bi-shield-check'],
    ];
} elseif ($role === 'staff') {
    $menu = [
        ['dashboard', 'Dashboard',         'staff/dashboard.php', 'bi-grid'],
        ['resources', 'Resources',         'staff/resources.php', 'bi-collection'],
        ['borrowers', 'Borrowers',         'staff/borrowers.php', 'bi-people'],
        ['loans',     'Loans & Returns',   'staff/loans.php',     'bi-arrow-left-right'],
        ['overdue',   'Overdue Loans',     'staff/overdue.php',   'bi-clock'],
        ['renewals',  'Renewal Requests',  'staff/renewals.php',  'bi-arrow-repeat'],
        ['history',   'Borrowing History', 'staff/history.php',   'bi-clock-history'],
    ];
} else {
    $menu = [
        ['dashboard', 'Dashboard',            'borrower/dashboard.php', 'bi-grid'],
        ['browse',    'Browse Resources',     'borrower/browse.php',    'bi-search'],
        ['loans',     'My Current Loans',     'borrower/loans.php',     'bi-book'],
        ['renewals',  'My Renewal Requests',  'borrower/renewals.php',  'bi-arrow-repeat'],
        ['history',   'My Borrowing History', 'borrower/history.php',   'bi-clock-history'],
    ];
}

// Items shown for every role
$bottom_menu = [
    ['notifications', 'Notifications', 'notifications.php', 'bi-bell'],
    ['profile',       'Profile',       'profile.php',       'bi-person'],
    ['logout',        'Logout',        'logout.php',        'bi-box-arrow-right'],
];

// Small number badges for staff (overdue count and pending renewals)
$badges = [];
if ($role === 'staff') {
    $overdue_count = count_overdue_loans($pdo);
    $renewal_count = count_pending_renewals($pdo);
    if ($overdue_count > 0) $badges['overdue']  = ['red',   $overdue_count];
    if ($renewal_count > 0) $badges['renewals'] = ['amber', $renewal_count];
}

// Draw one menu item
function draw_menu_item($item, $active_page, $badges)
{
    $is_active = ($item[0] === $active_page) ? ' active' : '';

    // The Logout link opens the confirmation popup (see footer.php).
    // If JavaScript is off, the link still works and signs the user out.
    $popup = ($item[0] === 'logout') ? ' data-bs-toggle="modal" data-bs-target="#logoutModal"' : '';

    echo '<a class="nav-item-link' . $is_active . '" href="' . url($item[2]) . '"' . $popup . '>';
    echo '<i class="bi ' . $item[3] . '"></i><span>' . e($item[1]) . '</span>';
    if (isset($badges[$item[0]])) {
        echo '<span class="nav-count count-' . $badges[$item[0]][0] . '">' . (int)$badges[$item[0]][1] . '</span>';
    }
    echo '</a>';
}
?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <span class="brand-icon"><i class="bi bi-book"></i></span>
        <span>
            <span class="brand-name"><?= APP_NAME ?></span>
            <span class="brand-sub">LIBRARY &amp; RESOURCES</span>
        </span>
    </div>

    <div class="nav-title"><?= strtoupper(e($role)) ?> WORKSPACE</div>

    <nav>
        <?php foreach ($menu as $item) { draw_menu_item($item, $active_page, $badges); } ?>
        <hr class="nav-divider">
        <?php foreach ($bottom_menu as $item) { draw_menu_item($item, $active_page, $badges); } ?>
    </nav>

    <div class="sidebar-note">
        <i class="bi bi-question-circle"></i>
        <span>A little order. A lot more possibility.</span>
    </div>
</aside>