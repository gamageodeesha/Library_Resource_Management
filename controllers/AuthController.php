<?php
// Login, logout and the one-time setup page
class AuthController extends Controller
{
    // ---------- Sign in ----------
    public function login()
    {
        // Already logged in? Go straight to the dashboard.
        if (is_logged_in()) {
            redirect(dashboard_for($_SESSION['role']));
        }

        $users     = new User($this->pdo);
        $borrowers = new Borrower($this->pdo);

        $error = '';
        $email = '';

        // Runs only when the form is submitted
        if ($this->isPost()) {
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($email === '' || $password === '') {
                $error = 'Please enter your email address and password.';
            } else {
                // 1. Find the user by email
                $user = $users->findByEmail($email);

                // 2. Check the password against the saved hash
                if (!$user || !password_verify($password, $user['password_hash'])) {
                    $error = 'Invalid email or password.';
                } elseif ($user['status'] !== 'active') {
                    $error = 'Your account is inactive. Please contact library staff.';
                } else {
                    // 3. If this is a borrower, find their borrower record
                    $borrower_id = null;
                    if ($user['role'] === 'borrower') {
                        $borrower_id = $borrowers->idForUser($user['user_id']);
                        if (!$borrower_id) {
                            $error = 'No borrower record is linked to this account. Please contact library staff.';
                        }
                    }

                    // 4. Everything is fine: save the user in the session
                    if ($error === '') {
                        session_regenerate_id(true);
                        $_SESSION['user_id']     = $user['user_id'];
                        $_SESSION['full_name']   = $user['full_name'];
                        $_SESSION['role']        = $user['role'];
                        $_SESSION['borrower_id'] = $borrower_id;

                        $users->touchLogin($user['user_id']);

                        redirect(dashboard_for($user['role']));
                    }
                }
            }
        }

        // false = this page does not use the sidebar / top bar layout
        $this->render('auth/login', ['error' => $error, 'email' => $email], false);
    }

    // ---------- Sign out ----------
    public function logout()
    {
        // Remove everything saved in the session, then go back to login
        $_SESSION = [];
        session_destroy();

        redirect('login');
    }

    // ---------- RUN ONCE, THEN DELETE ----------
    // Replaces the placeholder passwords in the sample data with: Password@123
    public function setup()
    {
        $users = new User($this->pdo);

        $new_password = 'Password@123';
        $count = $users->setAllPasswords(password_hash($new_password, PASSWORD_DEFAULT));

        $this->render('auth/setup', ['count' => $count, 'new_password' => $new_password], false);
    }
}
