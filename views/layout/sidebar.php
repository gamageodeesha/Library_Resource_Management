<?php
// =====================================================
// Left sidebar. The menu is different for each role.
// Each item is:  [key, label, route, icon]
// =====================================================

if ($role === 'admin') {
    $menu = [
        ['dashboard', 'Dashboard',   'admin/dashboard', 'bi-grid'],
        ['users',     'Users & Roles', 'admin/users',   'bi-shield-check'],
    ];
} elseif ($role === 'staff') {
    $menu = [
        ['dashboard', 'Dashboard',         'staff/dashboard', 'bi-grid'],
        ['resources', 'Resources',         'staff/resources', 'bi-collection'],
        ['borrowers', 'Borrowers',         'staff/borrowers', 'bi-people'],
        ['loans',     'Loans & Returns',   'staff/loans',     'bi-arrow-left-right'],
        ['overdue',   'Overdue Loans',     'staff/overdue',   'bi-clock'],
        ['renewals',  'Renewal Requests',  'staff/renewals',  'bi-arrow-repeat'],
        ['history',   'Borrowing History', 'staff/history',   'bi-clock-history'],
    ];
} else {
    $menu = [
        ['dashboard', 'Dashboard',            'borrower/dashboard', 'bi-grid'],
        ['browse',    'Browse Resources',     'borrower/browse',    'bi-search'],
        ['loans',     'My Current Loans',     'borrower/loans',     'bi-book'],
        ['renewals',  'My Renewal Requests',  'borrower/renewals',  'bi-arrow-repeat'],
        ['history',   'My Borrowing History', 'borrower/history',   'bi-clock-history'],
    ];
}

// Items shown for every role
$bottom_menu = [
    ['notifications', 'Notifications', 'notifications', 'bi-bell'],
    ['profile',       'Profile',       'profile',       'bi-person'],
    ['logout',        'Logout',        'logout',        'bi-box-arrow-right'],
];
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
        <?php foreach ($menu as $item) { partial('menu_item', ['item' => $item, 'active_page' => $active_page, 'badges' => $badges]); } ?>
        <hr class="nav-divider">
        <?php foreach ($bottom_menu as $item) { partial('menu_item', ['item' => $item, 'active_page' => $active_page, 'badges' => $badges]); } ?>
    </nav>

    <div class="sidebar-note">
        <i class="bi bi-question-circle"></i>
        <span>A little order. A lot more possibility.</span>
    </div>
</aside>