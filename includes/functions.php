<?php
// =====================================================
// Small helper functions used by many pages
// =====================================================

// ---------- Safe output ----------
// Always wrap text from the database in e() before printing it in HTML.
function e($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// ---------- Links and redirects ----------
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

// ---------- Login and roles ----------
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// Where each role goes after login
function dashboard_for($role)
{
    if ($role === 'admin')    return 'admin/dashboard.php';
    if ($role === 'staff')    return 'staff/dashboard.php';
    return 'borrower/dashboard.php';
}

// Put this at the top of any page that needs a logged-in user
function require_login()
{
    if (!is_logged_in()) {
        redirect('login.php');
    }

    // A user with a temporary password must choose their own before doing anything else
    if (!empty($_SESSION['must_change_password']) && basename($_SERVER['SCRIPT_NAME']) !== 'profile.php') {
        set_flash('warning', 'Please choose a new password before you continue.');
        redirect('profile.php');
    }
}

// Put this at the top of any page that only one role may open
function require_role($role)
{
    require_login();
    if ($_SESSION['role'] !== $role) {
        // Wrong role: send the user back to their own dashboard
        redirect(dashboard_for($_SESSION['role']));
    }
}

// ---------- Flash messages (one-time messages like "Saved!") ----------
function set_flash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function show_flash()
{
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];      // success, danger, warning, info
        $msg  = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($type) . ' alert-dismissible fade show" role="alert">'
           . e($msg)
           . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// ---------- Formatting ----------
function format_date($date)
{
    if (empty($date)) {
        return '—';
    }
    return date('d M Y', strtotime($date));
}

function initials($name)
{
    $parts = explode(' ', trim($name));
    $letters = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $letters .= strtoupper(substr(end($parts), 0, 1));
    }
    return $letters;
}

function first_name($name)
{
    $parts = explode(' ', trim($name));
    return $parts[0];
}

function greeting()
{
    $hour = (int)date('G');
    if ($hour < 12) return 'Good morning';
    if ($hour < 18) return 'Good afternoon';
    return 'Good evening';
}

// ---------- Coloured status badges ----------
// colour can be: green, blue, amber, red, gray
function badge($label, $colour)
{
    return '<span class="status-badge badge-' . e($colour) . '">' . e($label) . '</span>';
}

// Badge for a loan row (uses the columns from the vw_loans view)
function loan_badge($loan)
{
    if ($loan['display_status'] === 'returned') {
        return badge('Returned', 'green');
    }
    if ($loan['display_status'] === 'overdue') {
        return badge($loan['days_overdue'] . 'd overdue', 'red');
    }
    return badge('Active', 'blue');
}

// Badge for a notification type
function notification_badge($type)
{
    $map = [
        'due_soon'          => ['Due soon', 'amber'],
        'overdue'           => ['Overdue', 'red'],
        'request_received'  => ['Request received', 'blue'],
        'renewal_approved'  => ['Approved', 'green'],
        'renewal_rejected'  => ['Rejected', 'red'],
        'reminder'          => ['Reminder', 'amber'],
        'general'           => ['Notice', 'gray'],
    ];
    $item = $map[$type] ?? ['Notice', 'gray'];
    return badge($item[0], $item[1]);
}

// ---------- Small database helpers ----------
// Read one value from the settings table (example: due_soon_days)
function get_setting($pdo, $key, $default)
{
    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return ($value === false) ? $default : $value;
}

function count_overdue_loans($pdo)
{
    return (int)$pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'active' AND due_date < CURDATE()")->fetchColumn();
}

function count_pending_renewals($pdo)
{
    return (int)$pdo->query("SELECT COUNT(*) FROM renewal_requests WHERE status = 'pending'")->fetchColumn();
}

function count_unread_notifications($pdo, $user_id)
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user_id]);
    return (int)$stmt->fetchColumn();
}

// =====================================================
// Added in Phase 2
// =====================================================

// ---------- Badges ----------
function resource_badge($status)
{
    return ($status === 'available') ? badge('Available', 'green') : badge('On loan', 'blue');
}

function membership_badge($status)
{
    return ($status === 'active') ? badge('Active', 'green') : badge('Inactive', 'gray');
}

// ---------- Create the next code, like R011 or B007 ----------
// $table and $column are written by us in the code, never typed by a user.
function next_code($pdo, $table, $column, $prefix, $digits)
{
    $start = strlen($prefix) + 1;
    $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING($column, $start) AS UNSIGNED)), 0) FROM $table";
    $max = (int)$pdo->query($sql)->fetchColumn();
    return $prefix . str_pad($max + 1, $digits, '0', STR_PAD_LEFT);
}

// ---------- Protect forms from fake submissions (CSRF) ----------
// Call csrf_field() inside every form, and check csrf_ok() when it is submitted.
function csrf_field()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function csrf_ok()
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

// =====================================================
// Added in Phase 3
// =====================================================

// Today's date according to the database (keeps PHP and MySQL in agreement)
function db_today($pdo)
{
    return $pdo->query('SELECT CURDATE()')->fetchColumn();
}

// Is this text a real date written as YYYY-MM-DD?
function valid_date($text)
{
    $d = DateTime::createFromFormat('Y-m-d', $text);
    return $d && $d->format('Y-m-d') === $text;
}

// Save a notification for a user (does nothing if the user has no account)
function add_notification($pdo, $user_id, $type, $title, $message, $loan_id = null, $renewal_id = null)
{
    if (empty($user_id)) {
        return;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, type, title, message, loan_id, renewal_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$user_id, $type, shorten($title, 160), shorten($message, 500), $loan_id, $renewal_id]);
}

// Stop spreadsheet programs from running text that starts with = + - @ as a formula
function csv_safe($value)
{
    $value = (string)$value;
    if ($value !== '' && strpos('=+-@', $value[0]) !== false) {
        return "'" . $value;
    }
    return $value;
}

// Cut text to a maximum length (works even if the PHP "mbstring" add-on is off)
function shorten($text, $max)
{
    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $max);
    }
    return substr($text, 0, $max);
}


// =====================================================
// Added in Phase 5
// =====================================================

// Handles the "Mark as read" buttons. Call it near the top of any page that shows notifications.
function handle_notification_post($pdo, $return_path)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $action = $_POST['action'] ?? '';
    if (!in_array($action, ['mark_read', 'mark_all_read'])) {
        return;
    }
    if (!csrf_ok()) {
        set_flash('danger', 'Your session expired. Please try again.');
        redirect($return_path);
    }

    if ($action === 'mark_read') {
        // "AND user_id = ..." means you can only change your OWN notifications
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ? AND user_id = ?');
        $stmt->execute([(int)($_POST['notification_id'] ?? 0), $_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$_SESSION['user_id']]);
    }
    redirect($return_path);
}

// Where should the "View" link of a notification go?
function notification_link($note, $role)
{
    if ($role === 'staff') {
        if (!empty($note['renewal_id'])) return 'staff/renewals.php?id=' . (int)$note['renewal_id'];
        if (!empty($note['loan_id']))    return 'staff/loans.php?tab=all&loan=' . (int)$note['loan_id'];
    }
    if ($role === 'borrower') {
        if (!empty($note['renewal_id'])) return 'borrower/renewals.php?id=' . (int)$note['renewal_id'];
        if (!empty($note['loan_id']))    return 'borrower/history.php?loan=' . (int)$note['loan_id'];
    }
    return '';
}

// One notification, drawn the same way on every page
function draw_notification($note, $role, $with_button = true)
{
    $unread = ($note['is_read'] == 0);
    $link   = notification_link($note, $role);

    echo '<div class="note-item' . ($unread ? ' unread' : '') . '">';
    echo '<div class="d-flex justify-content-between align-items-center">';
    echo notification_badge($note['type']);
    echo '<span class="text-muted" style="font-size:0.7rem">' . e(format_date($note['created_at'])) . '</span>';
    echo '</div>';
    echo '<div class="note-title">' . e($note['title']) . '</div>';
    echo '<div class="text-muted" style="font-size:0.78rem">' . e($note['message']) . '</div>';

    echo '<div class="mt-1 d-flex gap-3 align-items-center">';
    if ($link !== '') {
        echo '<a href="' . e(url($link)) . '" style="font-size:0.78rem">View</a>';
    }
    if ($unread && $with_button) {
        echo '<form method="post" action="" class="d-inline">' . csrf_field()
           . '<input type="hidden" name="action" value="mark_read">'
           . '<input type="hidden" name="notification_id" value="' . (int)$note['notification_id'] . '">'
           . '<button type="submit" class="btn btn-link p-0" style="font-size:0.78rem">Mark as read</button></form>';
    }
    echo '</div></div>';
}

// "3 days early", "on the due date" or "2 days late"
function return_timing($due_date, $return_date)
{
    $diff = (int)round((strtotime($due_date) - strtotime($return_date)) / 86400);
    if ($diff > 0) return $diff . ' day' . ($diff === 1 ? '' : 's') . ' early';
    if ($diff < 0) return abs($diff) . ' day' . (abs($diff) === 1 ? '' : 's') . ' late';
    return 'on the due date';
}