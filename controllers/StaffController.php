<?php
// Staff dashboard, overdue loans and borrowing history
class StaffController extends Controller
{
    // =====================================================
    // Staff dashboard
    // =====================================================
    public function dashboard()
    {
        $this->requireRole('staff');

        $resources = new Resource($this->pdo);
        $borrowers = new Borrower($this->pdo);
        $loans     = new Loan($this->pdo);
        $renewals  = new Renewal($this->pdo);
        $settings  = new Setting($this->pdo);

        // ---------- Numbers for the four cards ----------
        $resource_counts = $resources->counts();
        $total_resources = $resource_counts['total'];
        $available       = $resource_counts['available'];

        $overdue_count = $loans->countOverdue();

        // ---------- Loans needing attention (overdue or due very soon) ----------
        $due_soon_days = (int)$settings->get('due_soon_days', 2);

        $this->render('staff/dashboard', [
            'page_title'      => 'Dashboard',
            'active_page'     => 'dashboard',
            'total_resources' => $total_resources,
            'available'       => $available,
            'on_loan'         => $total_resources - $available,
            'total_borrowers' => $borrowers->countAll(),
            'with_loans'      => $loans->countActiveBorrowers(),
            'active_loans'    => $loans->countActive(),
            'next_due'        => $loans->nextDueDate(),
            'overdue_count'   => $overdue_count,
            'oldest_overdue'  => $loans->oldestOverdueDays(),
            'attention'       => $loans->needingAttention($due_soon_days, 5),
            // Names of borrowers with overdue loans (for the yellow warning box)
            'overdue_names'   => $loans->overdueBorrowerNames(3),
            // Oldest pending renewal request
            'pending_total'   => $renewals->countPending(),
            'renewal'         => $renewals->oldestPending(),
        ]);
    }

    // =====================================================
    // Overdue loans (+ send a reminder, + CSV download)
    // =====================================================
    public function overdue()
    {
        $this->requireRole('staff');

        $loans         = new Loan($this->pdo);
        $notifications = new Notification($this->pdo);

        $today = $loans->today();

        // =====================================================
        // SEND A REMINDER (button in the table)
        // =====================================================
        if ($this->isPost() && ($_POST['action'] ?? '') === 'reminder') {

            if (!csrf_ok()) {
                set_flash('danger', 'Your session expired. Please try again.');
                redirect('staff/overdue');
            }

            $loan = $loans->overdueForReminder((int)($_POST['loan_id'] ?? 0));

            if (!$loan) {
                set_flash('warning', 'That loan is no longer overdue.');
            } elseif (empty($loan['user_id'])) {
                set_flash('warning', $loan['first_name'] . ' ' . $loan['last_name'] . ' has no sign-in account, so please contact them at ' . $loan['email'] . '.');
            } else {
                $notifications->add(
                    $loan['user_id'], 'reminder',
                    'Reminder: ' . $loan['title'] . ' is overdue',
                    $loan['title'] . ' was due on ' . format_date($loan['due_date']) . '. Please return it to the library desk.',
                    $loan['loan_id']
                );
                set_flash('success', 'Reminder sent to ' . $loan['first_name'] . ' ' . $loan['last_name'] . '. The due date is unchanged.');
            }
            redirect('staff/overdue');
        }

        // =====================================================
        // ALL OVERDUE LOANS (used for the cards, contacts and export)
        // =====================================================
        $all_overdue = $loans->allOverdue();

        // ---------- Download as a CSV file (opens in Excel) ----------
        if (($_GET['export'] ?? '') === '1') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="overdue-loans-' . $today . '.csv"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // helps Excel read special characters
            fputcsv($out, ['Loan', 'Resource', 'Borrower', 'Email', 'Borrowed', 'Due date', 'Days overdue']);
            foreach ($all_overdue as $row) {
                fputcsv($out, [
                    $row['loan_code'], csv_safe($row['title']), csv_safe($row['borrower_name']),
                    csv_safe($row['borrower_email']), $row['loan_date'], $row['due_date'], $row['days_late'],
                ]);
            }
            fclose($out);
            exit;
        }

        // ---------- Search and sort for the table ----------
        $search = trim($_GET['q'] ?? '');
        $sort   = ($_GET['sort'] ?? 'most') === 'least' ? 'least' : 'most';

        $list = [];
        foreach ($all_overdue as $row) {
            if ($search !== '') {
                $haystack = strtolower($row['title'] . ' ' . $row['borrower_name'] . ' ' . $row['loan_code']);
                if (strpos($haystack, strtolower($search)) === false) {
                    continue;
                }
            }
            $list[] = $row;
        }
        if ($sort === 'least') {
            $list = array_reverse($list);
        }

        // ---------- Numbers for the three cards ----------
        $overdue_total = count($all_overdue);

        $contacts = [];   // one entry per borrower
        foreach ($all_overdue as $row) {
            $contacts[$row['borrower_id']] = ['name' => $row['borrower_name'], 'email' => $row['borrower_email']];
        }
        $longest = $overdue_total > 0 ? $all_overdue[0] : null;

        $this->render('staff/overdue', [
            'page_title'    => 'Overdue Loans',
            'active_page'   => 'overdue',
            'today'         => $today,
            'search'        => $search,
            'sort'          => $sort,
            'loans'         => $list,
            'overdue_total' => $overdue_total,
            'active_total'  => $loans->countActive(),
            'contacts'      => $contacts,
            'longest'       => $longest,
        ]);
    }

    // =====================================================
    // Borrowing history (+ CSV download)
    // =====================================================
    public function history()
    {
        $this->requireRole('staff');

        $loans     = new Loan($this->pdo);
        $borrowers = new Borrower($this->pdo);

        // ---------- What did the user ask for? ----------
        $tab = $_GET['tab'] ?? 'all';
        if (!in_array($tab, ['all', 'current', 'returned'])) {
            $tab = 'all';
        }
        $search      = trim($_GET['q'] ?? '');
        $borrower_id = (int)($_GET['borrower_id'] ?? 0);
        $dates       = $_GET['dates'] ?? 'all';
        if (!in_array($dates, ['all', '30', '90'])) {
            $dates = 'all';
        }

        $records = $loans->history($tab, $search, $borrower_id, $dates);

        // ---------- Download as CSV ----------
        if (($_GET['export'] ?? '') === '1') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="borrowing-history-' . date('Y-m-d') . '.csv"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Loan', 'Resource', 'Borrower', 'Borrowed', 'Due date', 'Returned', 'Status', 'Renewals used']);
            foreach ($records as $row) {
                fputcsv($out, [
                    $row['loan_code'], csv_safe($row['title']), csv_safe($row['borrower_name']),
                    $row['loan_date'], $row['due_date'], $row['return_date'], ucfirst($row['display_status']), $row['renewal_count'],
                ]);
            }
            fclose($out);
            exit;
        }

        // The link of the "Export history" button
        $export_url = self_url(['tab' => $tab, 'q' => $search, 'borrower_id' => $borrower_id, 'dates' => $dates, 'export' => 1]);

        $this->render('staff/history', [
            'page_title'    => 'Borrowing History',
            'active_page'   => 'history',
            'tab'           => $tab,
            'search'        => $search,
            'borrower_id'   => $borrower_id,
            'dates'         => $dates,
            'records'       => $records,
            'counts'        => $loans->counts(),
            'borrower_list' => $borrowers->names(),
            'has_filter'    => ($search !== '' || $borrower_id > 0 || $dates !== 'all'),
            'export_url'    => $export_url,
        ]);
    }
}
