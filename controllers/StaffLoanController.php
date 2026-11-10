<?php
// Staff: loans and returns (list + process a return, register a new loan)
class StaffLoanController extends Controller
{
    // =====================================================
    // Loans & Returns
    // =====================================================
    public function index()
    {
        $this->requireRole('staff');

        $loans = new Loan($this->pdo);

        $today = $loans->today();

        // ---------- What did the user ask for? ----------
        $tab = $_GET['tab'] ?? 'active';
        if (!in_array($tab, ['all', 'active', 'returned', 'overdue'])) {
            $tab = 'active';
        }
        $search = trim($_GET['q'] ?? '');
        $sort   = ($_GET['sort'] ?? 'due_asc') === 'due_desc' ? 'due_desc' : 'due_asc';

        $selected_id       = (int)($_GET['loan'] ?? 0);
        $return_errors     = [];
        $return_date_value = $today;      // what the date box shows

        // =====================================================
        // PROCESS A RETURN (the form on the right was submitted)
        // =====================================================
        if ($this->isPost() && ($_POST['action'] ?? '') === 'return') {

            $selected_id       = (int)($_POST['loan_id'] ?? 0);
            $return_date       = trim($_POST['return_date'] ?? '');
            $return_date_value = $_POST['return_date'] ?? $today;
            $done_message      = '';

            if (!csrf_ok()) {
                $return_errors[] = 'Your session expired. Please try again.';
            }
            if (!valid_date($return_date)) {
                $return_errors[] = 'Please enter a valid return date.';
            } elseif ($return_date > $today) {
                $return_errors[] = 'The return date cannot be in the future.';
            }

            if (count($return_errors) === 0) {
                // The model saves all the steps together
                $result          = $loans->processReturn($selected_id, $return_date, $_SESSION['user_id']);
                $return_errors   = $result['errors'];
                $done_message    = $result['message'];
            }

            if (count($return_errors) === 0 && $done_message !== '') {
                set_flash('success', $done_message);
                redirect('staff/loans', ['tab' => $tab]);
            }
        }

        // ---------- The loan shown in the right-hand panel ----------
        $selected = null;
        if ($selected_id > 0) {
            $selected = $loans->find($selected_id);
        }

        $this->render('staff/loans', [
            'page_title'        => 'Loans & Returns',
            'active_page'       => 'loans',
            'today'             => $today,
            'tab'               => $tab,
            'search'            => $search,
            'sort'              => $sort,
            'selected_id'       => $selected_id,
            'return_errors'     => $return_errors,
            'return_date_value' => $return_date_value,
            'loans'             => $loans->listForStaff($tab, $search, $sort),
            'counts'            => $loans->counts(),
            'selected'          => $selected,
        ]);
    }

    // =====================================================
    // Register a loan
    // =====================================================
    public function register()
    {
        $this->requireRole('staff');

        $loans     = new Loan($this->pdo);
        $borrowers = new Borrower($this->pdo);
        $resources = new Resource($this->pdo);
        $settings  = new Setting($this->pdo);

        $today  = $loans->today();
        $period = (int)$settings->get('loan_period_days', 14);

        // ---------- Starting values (borrower/resource can come from the link) ----------
        $values = [
            'borrower_id' => (int)($_GET['borrower_id'] ?? 0),
            'resource_id' => (int)($_GET['resource_id'] ?? 0),
            'loan_date'   => $today,
            'due_date'    => date('Y-m-d', strtotime($today . ' +' . $period . ' days')),
        ];
        $errors = [];

        // ---------- The form was submitted ----------
        if ($this->isPost()) {

            $values['borrower_id'] = (int)($_POST['borrower_id'] ?? 0);
            $values['resource_id'] = (int)($_POST['resource_id'] ?? 0);
            $values['loan_date']   = trim($_POST['loan_date'] ?? '');
            $values['due_date']    = trim($_POST['due_date'] ?? '');

            if (!csrf_ok())                  $errors[] = 'Your session expired. Please try again.';
            if ($values['borrower_id'] <= 0) $errors[] = 'Please select a borrower.';
            if ($values['resource_id'] <= 0) $errors[] = 'Please select a resource.';

            if (!valid_date($values['loan_date'])) {
                $errors[] = 'Please enter a valid loan date.';
            }
            if (!valid_date($values['due_date'])) {
                $errors[] = 'Please enter a valid due date.';
            }
            if (valid_date($values['loan_date']) && valid_date($values['due_date'])) {
                if ($values['loan_date'] > $today) {
                    $errors[] = 'The loan date cannot be in the future.';
                }
                if ($values['due_date'] <= $values['loan_date']) {
                    $errors[] = 'The due date must be after the loan date.';
                }
            }

            $saved_message = '';

            if (count($errors) === 0) {
                // The model checks the rules and saves everything together
                $result = $loans->register(
                    $values['borrower_id'], $values['resource_id'],
                    $values['loan_date'], $values['due_date'], $_SESSION['user_id']
                );
                $errors        = $result['errors'];
                $saved_message = $result['message'];
            }

            if (count($errors) === 0 && $saved_message !== '') {
                set_flash('success', $saved_message);
                redirect('staff/loans');
            }
        }

        // ---------- Lists for the two drop-downs ----------
        // Only ACTIVE members can borrow, and only AVAILABLE resources can be loaned.
        $this->render('staff/loan_register', [
            'page_title'  => 'Loans & Returns',
            'active_page' => 'loans',
            'today'       => $today,
            'period'      => $period,
            'borrowers'   => $borrowers->activeMembers(),
            'resources'   => $resources->availableForLoan(),
            'values'      => $values,
            'errors'      => $errors,
        ]);
    }
}
