<?php
require_once __DIR__ . '/includes/init.php';

// Remove everything saved in the session, then go back to login
$_SESSION = [];
session_destroy();

header('Location: ' . url('login.php'));
exit;
