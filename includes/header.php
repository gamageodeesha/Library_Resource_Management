<?php
// =====================================================
// Top part of every logged-in page.
// Before including this file, a page sets:
//   $page_title  -> text in the browser tab and the page breadcrumb
//   $active_page -> which sidebar item is highlighted
// =====================================================
require_login();

$role        = $_SESSION['role'];
$user_name   = $_SESSION['full_name'];
$workspace   = ucfirst($role) . ' workspace';
$unread      = count_unread_notifications($pdo, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | <?= APP_NAME ?></title>

    <!-- Inter font, Bootstrap and Bootstrap Icons (loaded from the internet) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Our own styles -->
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<?php require __DIR__ . '/sidebar.php'; ?>

<div class="main-area">

    <!-- Top bar -->
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm d-lg-none" id="menuToggle" type="button" aria-label="Open menu">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div class="breadcrumb-line">
                <?= e($workspace) ?> / <strong><?= e($page_title) ?></strong>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <span class="topbar-date d-none d-md-inline"><?= date('D, d M Y') ?></span>

            <a href="<?= url('notifications.php') ?>" class="bell-link" title="Notifications">
                <i class="bi bi-bell"></i>
                <?php if ($unread > 0): ?>
                    <span class="bell-dot"></span>
                <?php endif; ?>
            </a>

            <div class="user-box">
                <span class="avatar"><?= e(initials($user_name)) ?></span>
                <span class="d-none d-sm-block">
                    <span class="user-name"><?= e($user_name) ?></span>
                    <span class="user-role"><?= e(ucfirst($role)) ?></span>
                </span>
            </div>
        </div>
    </header>

    <!-- Page content starts here -->
    <main class="content">
        <?php show_flash(); ?>
