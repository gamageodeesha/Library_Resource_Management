<?php
// =====================================================
// Small helper functions, mostly used inside the Views.
// (Anything that talks to the database now lives in a
//  Model instead - see the models/ folder.)
// =====================================================

// ---------- Safe output ----------
// Always wrap text from the database in e() before printing it in HTML.
function e($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// ---------- Links and redirects ----------
// A file inside the project, like a CSS or JS file:  url('assets/css/style.css')
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

// A page of the app:  route('staff/loans', ['tab' => 'all'])
// gives:  /lendwise/index.php?route=staff/loans&tab=all
function route($page, $params = [])
{
    $link = BASE_URL . '/index.php?route=' . $page;
    if (count($params) > 0) {
        $link .= '&' . http_build_query($params);
    }
    return $link;
}

// The page we are on right now, with different extra settings (used for tabs, filters, pagination)
function self_url($params = [])
{
    return route(CURRENT_ROUTE, $params);
}

// Put this inside every <form method="get"> so the form remembers which page it belongs to
function route_field()
{
    return '<input type="hidden" name="route" value="' . e(CURRENT_ROUTE) . '">';
}

function redirect($page, $params = [])
{
    header('Location: ' . route($page, $params));
    exit;
}

// ---------- Login and roles ----------
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// Where each role goes after login (these are route names, see index.php)
function dashboard_for($role)
{
    if ($role === 'admin')    return 'admin/dashboard';
    if ($role === 'staff')    return 'staff/dashboard';
    return 'borrower/dashboard';
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

// ---------- Small pieces of a View ----------
// Draws a file from views/partials/ (a small reusable piece of HTML)
function partial($name, $vars = [])
{
    extract($vars);
    include BASE_PATH . '/views/partials/' . $name . '.php';
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

// Cut text to a maximum length (works even if the PHP "mbstring" add-on is off)
function shorten($text, $max)
{
    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $max);
    }
    return substr($text, 0, $max);
}

// Is this text a real date written as YYYY-MM-DD?
function valid_date($text)
{
    $d = DateTime::createFromFormat('Y-m-d', $text);
    return $d && $d->format('Y-m-d') === $text;
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

// "3 days early", "on the due date" or "2 days late"
function return_timing($due_date, $return_date)
{
    $diff = (int)round((strtotime($due_date) - strtotime($return_date)) / 86400);
    if ($diff > 0) return $diff . ' day' . ($diff === 1 ? '' : 's') . ' early';
    if ($diff < 0) return abs($diff) . ' day' . (abs($diff) === 1 ? '' : 's') . ' late';
    return 'on the due date';
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

function resource_badge($status)
{
    return ($status === 'available') ? badge('Available', 'green') : badge('On loan', 'blue');
}

function membership_badge($status)
{
    return ($status === 'active') ? badge('Active', 'green') : badge('Inactive', 'gray');
}

// Where should the "View" link of a notification go?
function notification_link($note, $role)
{
    if ($role === 'staff') {
        if (!empty($note['renewal_id'])) return route('staff/renewals', ['id' => (int)$note['renewal_id']]);
        if (!empty($note['loan_id']))    return route('staff/loans', ['tab' => 'all', 'loan' => (int)$note['loan_id']]);
    }
    if ($role === 'borrower') {
        if (!empty($note['renewal_id'])) return route('borrower/renewals', ['id' => (int)$note['renewal_id']]);
        if (!empty($note['loan_id']))    return route('borrower/history', ['loan' => (int)$note['loan_id']]);
    }
    return '';
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
