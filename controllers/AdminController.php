<?php
// Everything the administrator can do: the dashboard and "Users & Roles"
class AdminController extends Controller
{
    // =====================================================
    // Admin dashboard
    // =====================================================
    public function dashboard()
    {
        $this->requireRole('admin');

        $users     = new User($this->pdo);
        $resources = new Resource($this->pdo);
        $loans     = new Loan($this->pdo);

        $resource_counts = $resources->counts();

        $this->render('admin/dashboard', [
            'page_title'      => 'Dashboard',
            'active_page'     => 'dashboard',
            'total_users'     => $users->countAll(),
            'active_users'    => $users->countActive(),
            'total_resources' => $resource_counts['total'],
            'available'       => $resource_counts['available'],
            'active_loans'    => $loans->countActive(),
            'overdue_count'   => $loans->countOverdue(),
            'role_counts'     => $users->countByRole(),
            'recent_users'    => $users->newest(5),    // newest accounts
            'recent_loans'    => $loans->recent(5),    // recent activity (latest 5 loans)
        ]);
    }

    // =====================================================
    // Users & Roles (list, add and edit on one page)
    // =====================================================
    public function users()
    {
        $this->requireRole('admin');

        $users     = new User($this->pdo);
        $borrowers = new Borrower($this->pdo);

        $per_page = 10;

        // ---------- What did the user ask for? (search, role filter, page) ----------
        $search      = trim($_GET['q'] ?? '');
        $role_filter = $_GET['role'] ?? '';
        if (!in_array($role_filter, ['admin', 'staff', 'borrower'])) {
            $role_filter = '';
        }
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $edit_id = (int)($_GET['edit'] ?? 0);

        // The current filters, so links can keep them
        $base = ['q' => $search, 'role' => $role_filter, 'page' => $page];

        $errors = [];

        // ---------- Who is being edited? ----------
        $edit_user = null;
        if ($edit_id > 0) {
            $edit_user = $users->find($edit_id);
            if (!$edit_user) {
                set_flash('danger', 'That user could not be found.');
                redirect('admin/users');
            }
        }

        // Borrower record linked to the user being edited (if any)
        $linked_borrower = null;
        if ($edit_user) {
            $linked_borrower = $borrowers->linkedToUser($edit_user['user_id']);
        }

        // Starting values for the form
        $form = [
            'full_name'   => $edit_user['full_name'] ?? '',
            'email'       => $edit_user['email'] ?? '',
            'role'        => $edit_user['role'] ?? 'staff',
            'status'      => $edit_user['status'] ?? 'active',
            'borrower_id' => 0,
        ];

        // =====================================================
        // SAVE (add a new user, or save changes to one)
        // =====================================================
        if ($this->isPost() && ($_POST['action'] ?? '') === 'save_user') {

            $uid     = (int)($_POST['user_id'] ?? 0);
            $editing = ($uid > 0);
            $temp_pw = $_POST['temp_password'] ?? '';

            $form = [
                'full_name'   => trim($_POST['full_name'] ?? ''),
                'email'       => trim($_POST['email'] ?? ''),
                'role'        => $_POST['role'] ?? '',
                'status'      => $editing ? ($_POST['status'] ?? '') : 'active',
                'borrower_id' => (int)($_POST['borrower_id'] ?? 0),
            ];

            // The user being changed (re-read from the database, never trusted from the form)
            $existing     = null;
            $current_link = null;
            if ($editing) {
                $existing     = $users->find($uid);
                $current_link = $borrowers->idForUser($uid);
            }

            // --- 1. Basic checks ---
            if (!csrf_ok())                                               $errors[] = 'Your session expired. Please try again.';
            if ($editing && !$existing)                                   $errors[] = 'That user could not be found.';
            if (strlen($form['full_name']) < 2)                           $errors[] = 'Please enter the full name.';
            if (strlen($form['full_name']) > 120)                         $errors[] = 'The name must be 120 characters or fewer.';
            if (!in_array($form['role'], ['admin', 'staff', 'borrower'])) $errors[] = 'Please choose a role.';
            if (!in_array($form['status'], ['active', 'inactive']))       $errors[] = 'Please choose a status.';

            if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            } elseif ($users->emailTaken($form['email'], $uid)) {
                $errors[] = 'Another account already uses this email address.';
            }

            // --- 2. Password (required for a new user, optional reset when editing) ---
            if (!$editing && $temp_pw === '') {
                $errors[] = 'Please set a temporary password.';
            }
            if ($temp_pw !== '' && (strlen($temp_pw) < 8 || !preg_match('/[A-Za-z]/', $temp_pw) || !preg_match('/[0-9]/', $temp_pw))) {
                $errors[] = 'The temporary password must be at least 8 characters with letters and numbers.';
            }

            // --- 3. Protect the administrator from locking themselves out ---
            if ($existing) {
                if ($uid === (int)$_SESSION['user_id'] && ($form['role'] !== $existing['role'] || $form['status'] !== $existing['status'])) {
                    $errors[] = 'You cannot change your own role or status. Ask another administrator.';
                }
                if ($existing['role'] === 'admin' && $existing['status'] === 'active'
                    && ($form['role'] !== 'admin' || $form['status'] !== 'active')) {
                    if ($users->countOtherActiveAdmins($uid) == 0) {
                        $errors[] = 'There must always be at least one active administrator.';
                    }
                }
            }

            // --- 4. Borrower accounts must be linked to a borrower record ---
            if ($form['role'] === 'borrower') {
                if (!$current_link) {
                    if ($form['borrower_id'] <= 0 || !$borrowers->isUnlinked($form['borrower_id'])) {
                        $errors[] = 'Please choose the borrower record this account belongs to.';
                    }
                }
            } elseif ($current_link) {
                $errors[] = 'This account is linked to a borrower record, so its role cannot be changed.';
            }

            // --- 5. Save everything together ---
            $saved = false;
            if (count($errors) === 0) {
                $link_id = ($form['role'] === 'borrower' && !$current_link) ? $form['borrower_id'] : 0;
                $saved   = $users->saveAccount($uid, $form, $temp_pw, $link_id);
                if (!$saved) {
                    $errors[] = 'The user could not be saved. Please try again.';
                }
            }

            if ($saved) {
                set_flash('success', $editing
                    ? 'Changes to ' . $form['full_name'] . ' were saved.'
                    : 'User ' . $form['full_name'] . ' was added. They must choose a new password when they first sign in.');
                redirect('admin/users', ['q' => $search, 'role' => $role_filter, 'page' => $page]);
            }

            // Something was wrong: show the form again for the same user
            $edit_user       = $existing ?: null;
            $linked_borrower = null;
            if ($existing && $current_link) {
                $linked_borrower = $borrowers->summary($current_link);
            }
        }

        // =====================================================
        // THE LIST OF USERS
        // =====================================================
        $matching = $users->countMatching($search, $role_filter);

        $pages = max(1, (int)ceil($matching / $per_page));
        $page  = min($page, $pages);
        $base['page'] = $page;
        $offset = ($page - 1) * $per_page;

        $user_list = $users->search($search, $role_filter, $per_page, $offset);

        $this->render('admin/users', [
            'page_title'      => 'Users & Roles',
            'active_page'     => 'users',
            'search'          => $search,
            'role_filter'     => $role_filter,
            'page'            => $page,
            'pages'           => $pages,
            'offset'          => $offset,
            'matching'        => $matching,
            'base'            => $base,
            'errors'          => $errors,
            'edit_user'       => $edit_user,
            'editing_now'     => ($edit_user !== null),
            'linked_borrower' => $linked_borrower,
            'form'            => $form,
            'users'           => $user_list,
            'total_users'     => $users->countAll(),
            'active_users'    => $users->countActive(),
            'free_borrowers'  => $borrowers->unlinked(),
        ]);
    }
}
