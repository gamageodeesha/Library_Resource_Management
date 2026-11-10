<?php
// =====================================================
// CONTROLLER (base class)
// A Controller receives the request, asks the Models for
// data, and then picks a View to show.
// Every controller in the controllers/ folder "extends"
// this class so it can use the helpers below.
// =====================================================
class Controller
{
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // ---------- Showing a View ----------
    // $view  = the file inside views/ (without .php), for example 'staff/loans'
    // $data  = the variables the View needs, for example ['loans' => $loans]
    // Pages that need the sidebar and top bar also get $data['page_title'] and $data['active_page'].
    protected function render($view, $data = [], $with_layout = true)
    {
        if ($with_layout) {
            $this->show('layout/header', $this->layoutData($data));
        }

        $this->show($view, $data);

        if ($with_layout) {
            $this->show('layout/footer', []);
        }
    }

    // Loads a view file. Having it in its own function keeps the View's variables separate.
    private function show($file, $vars)
    {
        extract($vars);
        require BASE_PATH . '/views/' . $file . '.php';
    }

    // The information the sidebar and top bar need on every logged-in page
    private function layoutData($data)
    {
        $notifications = new Notification($this->pdo);

        $badges = [];
        if ($_SESSION['role'] === 'staff') {
            $loans    = new Loan($this->pdo);
            $renewals = new Renewal($this->pdo);
            $overdue_count = $loans->countOverdue();
            $renewal_count = $renewals->countPending();
            if ($overdue_count > 0) $badges['overdue']  = ['red',   $overdue_count];
            if ($renewal_count > 0) $badges['renewals'] = ['amber', $renewal_count];
        }

        return [
            'page_title'  => $data['page_title'] ?? '',
            'active_page' => $data['active_page'] ?? '',
            'role'        => $_SESSION['role'],
            'user_name'   => $_SESSION['full_name'],
            'workspace'   => ucfirst($_SESSION['role']) . ' workspace',
            'unread'      => $notifications->countUnread($_SESSION['user_id']),
            'badges'      => $badges,
        ];
    }

    // ---------- Who may open this page? ----------
    // Put this at the top of any action that needs a logged-in user
    protected function requireLogin()
    {
        if (!is_logged_in()) {
            redirect('login');
        }

        // A user with a temporary password must choose their own before doing anything else
        if (!empty($_SESSION['must_change_password']) && CURRENT_ROUTE !== 'profile') {
            set_flash('warning', 'Please choose a new password before you continue.');
            redirect('profile');
        }
    }

    // Put this at the top of any action that only one role may open
    protected function requireRole($role)
    {
        $this->requireLogin();
        if ($_SESSION['role'] !== $role) {
            // Wrong role: send the user back to their own dashboard
            redirect(dashboard_for($_SESSION['role']));
        }
    }

    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    // ---------- Shared by two pages ----------
    // Handles the "Mark as read" buttons. Call it near the top of any action that shows notifications.
    protected function handleNotificationPost($return_route, $return_params = [])
    {
        if (!$this->isPost()) {
            return;
        }
        $action = $_POST['action'] ?? '';
        if (!in_array($action, ['mark_read', 'mark_all_read'])) {
            return;
        }
        if (!csrf_ok()) {
            set_flash('danger', 'Your session expired. Please try again.');
            redirect($return_route, $return_params);
        }

        $notifications = new Notification($this->pdo);
        if ($action === 'mark_read') {
            // The user id is added so you can only change your OWN notifications
            $notifications->markRead((int)($_POST['notification_id'] ?? 0), $_SESSION['user_id']);
        } else {
            $notifications->markAllRead($_SESSION['user_id']);
        }
        redirect($return_route, $return_params);
    }
}
