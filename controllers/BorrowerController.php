<?php
// Everything a borrower can do: dashboard, browse, my loans, my renewals, my history.
// Every action uses the borrower from the SESSION, never a number typed in the URL.
class BorrowerController extends Controller
{
    // =====================================================
    // Borrower dashboard
    // =====================================================
    public function dashboard()
    {
        $this->requireRole('borrower');

        $loan_model    = new Loan($this->pdo);
        $resources     = new Resource($this->pdo);
        $notifications = new Notification($this->pdo);

        // Only this borrower's own records (taken from the session, never from the URL)
        $borrower_id = $_SESSION['borrower_id'];

        // ---------- My current loans ----------
        $loans = $loan_model->activeForBorrower($borrower_id);

        // ---------- Numbers for the cards ----------
        $current_count = count($loans);
        $next_return   = $current_count > 0 ? $loans[0]['due_date'] : null;

        $overdue_count = 0;
        $pending_count = 0;
        foreach ($loans as $loan) {
            if ($loan['display_status'] === 'overdue') $overdue_count++;
            if ($loan['pending_renewal'] > 0)          $pending_count++;
        }

        $resource_counts = $resources->counts();

        $this->render('borrower/dashboard', [
            'page_title'     => 'Dashboard',
            'active_page'    => 'dashboard',
            'loans'          => $loans,
            'current_count'  => $current_count,
            'next_return'    => $next_return,
            'overdue_count'  => $overdue_count,
            'pending_count'  => $pending_count,
            'returned_count' => $loan_model->countReturnedByBorrower($borrower_id),
            'updates'        => $notifications->latest($_SESSION['user_id'], 3),   // latest 3 notifications
            'available'      => $resource_counts['available'],
        ]);
    }

    // =====================================================
    // Browse resources
    // =====================================================
    public function browse()
    {
        $this->requireRole('borrower');

        $resources  = new Resource($this->pdo);
        $categories = new Category($this->pdo);
        $loan_model = new Loan($this->pdo);

        $borrower_id = (int)$_SESSION['borrower_id'];

        // ---------- What did the user ask for? ----------
        $tab = $_GET['tab'] ?? 'all';
        if (!in_array($tab, ['all', 'available', 'on_loan'])) {
            $tab = 'all';
        }
        $search      = trim($_GET['q'] ?? '');
        $category_id = (int)($_GET['category'] ?? 0);
        $selected_id = (int)($_GET['id'] ?? 0);

        $list = $resources->browse($tab, $search, $category_id);

        // ---------- Numbers for the tabs ----------
        $counts    = $resources->counts();
        $total     = $counts['total'];
        $available = $counts['available'];
        $on_loan   = $total - $available;

        // ---------- Which resource is shown on the right? ----------
        $selected = null;
        if ($selected_id > 0) {
            foreach ($list as $r) {
                if ($r['resource_id'] == $selected_id) {
                    $selected = $r;
                }
            }
        }
        if (!$selected && count($list) > 0) {
            $selected = $list[0];          // default: the first one in the list
        }

        // ---------- This borrower's own history with that resource ----------
        $my_current  = null;
        $my_previous = null;
        if ($selected) {
            $my_current  = $loan_model->currentOfBorrowerForResource($borrower_id, $selected['resource_id']);
            $my_previous = $loan_model->previousOfBorrowerForResource($borrower_id, $selected['resource_id']);
        }

        $this->render('borrower/browse', [
            'page_title'  => 'Browse Resources',
            'active_page' => 'browse',
            'tab'         => $tab,
            'search'      => $search,
            'category_id' => $category_id,
            'resources'   => $list,
            'total'       => $total,
            'available'   => $available,
            'on_loan'     => $on_loan,
            'categories'  => $categories->all(),
            'selected'    => $selected,
            'my_current'  => $my_current,
            'my_previous' => $my_previous,
        ]);
    }

    // =====================================================
    // My current loans (and "Request renewal")
    // =====================================================
    public function loans()
    {
        $this->requireRole('borrower');

        $loan_model    = new Loan($this->pdo);
        $renewal_model = new Renewal($this->pdo);
        $settings      = new Setting($this->pdo);

        // Always use the borrower from the session, never a number typed in the URL
        $borrower_id = (int)$_SESSION['borrower_id'];

        $max_renewals   = (int)$settings->get('max_renewals_per_loan', 1);
        $extension_days = (int)$settings->get('renewal_extension_days', 7);
        $max_extension  = 30;   // a borrower may ask for at most 30 days beyond the current due date

        $errors = [];

        // =====================================================
        // REQUEST A RENEWAL (a form on one of the cards was submitted)
        // =====================================================
        if ($this->isPost() && ($_POST['action'] ?? '') === 'request_renewal') {

            $loan_id        = (int)($_POST['loan_id'] ?? 0);
            $requested_date = trim($_POST['requested_due_date'] ?? '');
            $reason         = trim($_POST['reason'] ?? '');
            $new_id         = 0;

            if (!csrf_ok()) {
                $errors[] = 'Your session expired. Please try again.';
            }
            if (!valid_date($requested_date)) {
                $errors[] = 'Please choose a valid date for the new due date.';
            }
            if (strlen($reason) > 500) {
                $errors[] = 'The reason must be 500 characters or fewer.';
            }

            if (count($errors) === 0) {
                // The model checks the rules and saves everything together
                $result = $renewal_model->submit(
                    $loan_id, $borrower_id, $_SESSION['user_id'],
                    $requested_date, $reason, $max_renewals, $max_extension
                );
                $errors = $result['errors'];
                $new_id = $result['id'];
            }

            if ($new_id > 0 && count($errors) === 0) {
                set_flash('success', 'Renewal request RN' . $new_id . ' was submitted. Staff will review it soon.');
                redirect('borrower/renewals', ['id' => $new_id]);
            }
        }

        // =====================================================
        // MY CURRENT LOANS
        // =====================================================
        $loans = $loan_model->activeForBorrower($borrower_id);

        // Renewal requests for each loan (newest first)
        foreach ($loans as $i => $loan) {
            $loans[$i]['requests'] = $renewal_model->forLoan($loan['loan_id'], true);
        }

        $overdue_count = 0;
        foreach ($loans as $loan) {
            if ($loan['display_status'] === 'overdue') $overdue_count++;
        }

        $this->render('borrower/loans', [
            'page_title'     => 'My Current Loans',
            'active_page'    => 'loans',
            'errors'         => $errors,
            'loans'          => $loans,
            'overdue_count'  => $overdue_count,
            'max_renewals'   => $max_renewals,
            'extension_days' => $extension_days,
            'max_extension'  => $max_extension,
        ]);
    }

    // =====================================================
    // My renewal requests
    // =====================================================
    public function renewals()
    {
        $this->requireRole('borrower');

        $renewal_model = new Renewal($this->pdo);
        $settings      = new Setting($this->pdo);

        // Always use the borrower from the session, never a number typed in the URL
        $borrower_id  = (int)$_SESSION['borrower_id'];
        $max_renewals = (int)$settings->get('max_renewals_per_loan', 1);

        $tab = $_GET['tab'] ?? 'all';
        if (!in_array($tab, ['all', 'pending', 'approved', 'rejected'])) {
            $tab = 'all';
        }
        $selected_id = (int)($_GET['id'] ?? 0);

        // ---------- The request shown on the right (must also be mine) ----------
        $selected = null;
        if ($selected_id > 0) {
            $selected = $renewal_model->findForBorrower($selected_id, $borrower_id);
        }

        $this->render('borrower/renewals', [
            'page_title'   => 'My Renewal Requests',
            'active_page'  => 'renewals',
            'tab'          => $tab,
            'selected_id'  => $selected_id,
            'max_renewals' => $max_renewals,
            'requests'     => $renewal_model->listForBorrower($borrower_id, $tab),
            'counts'       => $renewal_model->counts($borrower_id),
            'selected'     => $selected,
        ]);
    }

    // =====================================================
    // My borrowing history (+ my notifications)
    // =====================================================
    public function history()
    {
        $this->requireRole('borrower');

        $loan_model    = new Loan($this->pdo);
        $renewal_model = new Renewal($this->pdo);
        $notifications = new Notification($this->pdo);
        $users         = new User($this->pdo);

        $borrower_id = (int)$_SESSION['borrower_id'];

        // "Mark as read" buttons on this page
        $this->handleNotificationPost('borrower/history');

        // ---------- What did the user ask for? ----------
        $tab = $_GET['tab'] ?? 'all';
        if (!in_array($tab, ['all', 'current', 'returned'])) {
            $tab = 'all';
        }
        $search = trim($_GET['q'] ?? '');
        $dates  = $_GET['dates'] ?? 'all';
        if (!in_array($dates, ['all', '30', '90'])) {
            $dates = 'all';
        }
        $selected_id = (int)($_GET['loan'] ?? 0);

        // ---------- The loan whose story is shown below the table (must be mine) ----------
        $selected = null;
        $requests = [];
        if ($selected_id > 0) {
            $selected = $loan_model->findForBorrower($selected_id, $borrower_id);
            if ($selected) {
                $requests = $renewal_model->forLoan($selected_id, false);
            }
        }

        $account = $users->find($_SESSION['user_id']);

        $this->render('borrower/history', [
            'page_title'    => 'My Borrowing History',
            'active_page'   => 'history',
            'tab'           => $tab,
            'search'        => $search,
            'dates'         => $dates,
            'selected_id'   => $selected_id,
            'loans'         => $loan_model->historyForBorrower($borrower_id, $tab, $search, $dates),
            'counts'        => $loan_model->counts($borrower_id),
            'selected'      => $selected,
            'requests'      => $requests,
            'notes'         => $notifications->latest($_SESSION['user_id'], 5),   // latest 5
            'unread'        => $notifications->countUnread($_SESSION['user_id']),
            'account_email' => $account['email'],
        ]);
    }
}
