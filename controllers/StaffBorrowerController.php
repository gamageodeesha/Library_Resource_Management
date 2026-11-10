<?php
// Staff: borrower records (directory, register, edit)
class StaffBorrowerController extends Controller
{
    // =====================================================
    // The borrower directory
    // =====================================================
    public function index()
    {
        $this->requireRole('staff');

        $borrowers  = new Borrower($this->pdo);
        $loan_model = new Loan($this->pdo);

        // ---------- Search and filter ----------
        $search = trim($_GET['q'] ?? '');
        $filter = $_GET['filter'] ?? 'all';
        if (!in_array($filter, ['all', 'active', 'inactive', 'with_loans'])) {
            $filter = 'all';
        }

        $list = $borrowers->search($search, $filter);

        // ---------- Which borrower is shown in the right-hand panel? ----------
        $selected_id = (int)($_GET['id'] ?? 0);
        if ($selected_id === 0 && count($list) > 0) {
            $selected_id = (int)$list[0]['borrower_id'];   // default: first in the list
        }

        $selected      = null;
        $current_loans = [];
        $overdue = $returned = $pending = 0;

        if ($selected_id > 0) {
            $selected = $borrowers->find($selected_id);

            if ($selected) {
                // Current loans (with a flag if a renewal request is waiting)
                $current_loans = $loan_model->activeForBorrower($selected_id);

                $stats    = $borrowers->stats($selected_id);
                $overdue  = $stats['overdue'];
                $returned = $stats['returned'];
                $pending  = $stats['pending'];
            }
        }

        $this->render('staff/borrowers', [
            'page_title'      => 'Borrowers',
            'active_page'     => 'borrowers',
            'search'          => $search,
            'filter'          => $filter,
            'borrowers'       => $list,
            'total_borrowers' => $borrowers->countAll(),
            'selected_id'     => $selected_id,
            'selected'        => $selected,
            'current_loans'   => $current_loans,
            'overdue'         => $overdue,
            'returned'        => $returned,
            'pending'         => $pending,
        ]);
    }

    // =====================================================
    // Register or edit one borrower
    //   route staff/borrower_form         -> register a new borrower
    //   route staff/borrower_form&id=3    -> edit borrower number 3
    // =====================================================
    public function form()
    {
        $this->requireRole('staff');

        $borrowers = new Borrower($this->pdo);

        // ---------- Add or edit? ----------
        $id       = (int)($_GET['id'] ?? 0);
        $editing  = ($id > 0);
        $borrower = null;

        if ($editing) {
            $borrower = $borrowers->find($id);
            if (!$borrower) {
                set_flash('danger', 'That borrower could not be found.');
                redirect('staff/borrowers');
            }
        }

        // ---------- Starting values for the form ----------
        $fields = ['first_name', 'last_name', 'email', 'phone', 'membership_status', 'address', 'staff_notes'];
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $editing ? (string)($borrower[$field] ?? '') : '';
        }
        if (!$editing) {
            $values['membership_status'] = 'active';
        }
        $errors = [];

        // ---------- The form was submitted ----------
        if ($this->isPost()) {

            if (!csrf_ok()) {
                $errors[] = 'Your session expired. Please try again.';
            }

            foreach ($fields as $field) {
                $values[$field] = trim($_POST[$field] ?? '');
            }

            // 1. Required fields
            if ($values['first_name'] === '') $errors[] = 'First name is required.';
            if ($values['last_name'] === '')  $errors[] = 'Last name is required.';
            if ($values['email'] === '')      $errors[] = 'Email address is required.';

            // 2. Email format and duplicates
            if ($values['email'] !== '') {
                if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Please enter a valid email address.';
                } elseif ($borrowers->emailTaken($values['email'], $id)) {
                    $errors[] = 'Another borrower already uses this email address.';
                }
            }

            // 3. Phone (optional): digits, spaces and + - ( ) only
            if ($values['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{6,30}$/', $values['phone'])) {
                $errors[] = 'Phone number can only contain digits, spaces and + - ( ).';
            }

            // 4. Membership status must be one of the two allowed values
            if (!in_array($values['membership_status'], ['active', 'inactive'])) {
                $errors[] = 'Please choose a membership status.';
            }

            // 5. Everything is fine: save
            if (count($errors) === 0) {
                if ($editing) {
                    $borrowers->update($id, $values);
                    set_flash('success', 'Borrower ' . $borrower['borrower_code'] . ' was updated.');
                    redirect('staff/borrowers', ['id' => $id]);
                } else {
                    $created = $borrowers->create($values);
                    set_flash('success', 'Borrower ' . $created['code'] . ' was registered.');
                    redirect('staff/borrowers', ['id' => $created['id']]);
                }
            }
        }

        // ---------- Numbers for the right-hand panel (edit mode only) ----------
        $stats = ['current' => 0, 'overdue' => 0, 'returned' => 0, 'pending' => 0];
        if ($editing) {
            $stats = $borrowers->stats($id);
        }

        $this->render('staff/borrower_form', [
            'page_title'  => 'Borrowers',
            'active_page' => 'borrowers',
            'editing'     => $editing,
            'borrower'    => $borrower,
            'values'      => $values,
            'errors'      => $errors,
            'current'     => $stats['current'],
            'overdue'     => $stats['overdue'],
            'returned'    => $stats['returned'],
            'pending'     => $stats['pending'],
        ]);
    }
}
