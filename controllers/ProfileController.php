<?php
// "My profile": change my name and my password
class ProfileController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $users     = new User($this->pdo);
        $borrowers = new Borrower($this->pdo);

        // ---------- My account ----------
        $user = $users->find($_SESSION['user_id']);

        // Borrowers also have a library membership record (managed by staff)
        $borrower = null;
        if ($_SESSION['role'] === 'borrower') {
            $borrower = $borrowers->find($_SESSION['borrower_id']);
        }

        $name_errors     = [];
        $password_errors = [];
        $new_name        = $user['full_name'];

        if ($this->isPost()) {
            $action = $_POST['action'] ?? '';

            // =====================================================
            // CHANGE MY NAME
            // =====================================================
            if ($action === 'update_name') {
                $new_name = trim($_POST['full_name'] ?? '');

                if (!csrf_ok())              $name_errors[] = 'Your session expired. Please try again.';
                if (strlen($new_name) < 2)   $name_errors[] = 'Please enter your full name.';
                if (strlen($new_name) > 120) $name_errors[] = 'The name must be 120 characters or fewer.';

                if (count($name_errors) === 0) {
                    $users->updateName($_SESSION['user_id'], $new_name);
                    $_SESSION['full_name'] = $new_name;
                    set_flash('success', 'Your name was updated.');
                    redirect('profile');
                }
            }

            // =====================================================
            // CHANGE MY PASSWORD
            // =====================================================
            if ($action === 'change_password') {
                $current = $_POST['current_password'] ?? '';
                $new     = $_POST['new_password'] ?? '';
                $confirm = $_POST['confirm_password'] ?? '';

                if (!csrf_ok()) {
                    $password_errors[] = 'Your session expired. Please try again.';
                }
                if (!password_verify($current, $user['password_hash'])) {
                    $password_errors[] = 'Your current password is not correct.';
                }
                if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
                    $password_errors[] = 'The new password must be at least 8 characters and contain letters and numbers.';
                }
                if ($new !== $confirm) {
                    $password_errors[] = 'The new password and its confirmation do not match.';
                }
                if ($new !== '' && $new === $current) {
                    $password_errors[] = 'The new password must be different from the current one.';
                }

                if (count($password_errors) === 0) {
                    $users->updatePassword($_SESSION['user_id'], password_hash($new, PASSWORD_DEFAULT));

                    $_SESSION['must_change_password'] = 0;
                    session_regenerate_id(true);

                    set_flash('success', 'Your password was changed.');
                    redirect(dashboard_for($_SESSION['role']));
                }
            }
        }

        $this->render('profile/index', [
            'page_title'      => 'Profile',
            'active_page'     => 'profile',
            'user'            => $user,
            'borrower'        => $borrower,
            'name_errors'     => $name_errors,
            'password_errors' => $password_errors,
            'new_name'        => $new_name,
        ]);
    }
}
