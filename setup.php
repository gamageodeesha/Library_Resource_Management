<?php
// =====================================================
// RUN THIS PAGE ONCE, THEN DELETE THIS FILE.
// It replaces the placeholder passwords in the sample
// data with a real hashed password: Password@123
// =====================================================
require_once __DIR__ . '/includes/init.php';

$new_password = 'Password@123';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('UPDATE users SET password_hash = ?');
$stmt->execute([$hash]);
$count = $stmt->rowCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <h3>Setup finished</h3>
    <p><?= (int)$count ?> user passwords were set to <strong><?= e($new_password) ?></strong>.</p>
    <ul>
        <li>Admin: avery.wilson@example.org</li>
        <li>Staff: taylor.reed@example.org</li>
        <li>Borrower: alex.morgan@example.org</li>
    </ul>
    <p class="text-danger"><strong>Now delete setup.php from your project folder.</strong></p>
    <a class="btn btn-primary" href="<?= url('login.php') ?>">Go to login</a>
</body>
</html>
