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

    // ---------- Sign up (new borrowers create their own account) ----------
    public function signup()
    {
        // Already logged in? Go straight to the dashboard.
        if (is_logged_in()) {
            redirect(dashboard_for($_SESSION['role']));
        }

        $users     = new User($this->pdo);
        $borrowers = new Borrower($this->pdo);

        $errors = [];
        $form   = ['first_name' => '', 'last_name' => '', 'email' => ''];

        // Runs only when the form is submitted
        if ($this->isPost()) {
            $form = [
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name'  => trim($_POST['last_name'] ?? ''),
                'email'      => trim($_POST['email'] ?? ''),
            ];
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            // --- 1. Check what the user typed ---
            if (!csrf_ok()) {
                $errors[] = 'Your session expired. Please try again.';
            }
            if ($form['first_name'] === '' || strlen($form['first_name']) > 60) {
                $errors[] = 'Please enter your first name (up to 60 characters).';
            }
            if ($form['last_name'] === '' || strlen($form['last_name']) > 60) {
                $errors[] = 'Please enter your last name (up to 60 characters).';
            }

            if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            } elseif ($users->emailTaken($form['email'], 0) || $borrowers->emailTaken($form['email'], 0)) {
                $errors[] = 'This email address is already registered. Please sign in, or contact library staff.';
            }

            if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
                $errors[] = 'The password must be at least 8 characters and contain letters and numbers.';
            }
            if ($password !== $confirm) {
                $errors[] = 'The password and its confirmation do not match.';
            }

            // --- 2. Everything is fine: save the account and the borrower record ---
            if (count($errors) === 0) {
                $new = $borrowers->register(
                    $form['first_name'], $form['last_name'], $form['email'],
                    password_hash($password, PASSWORD_DEFAULT)
                );

                if (!$new) {
                    $errors[] = 'Your account could not be created. Please try again.';
                } else {
                    // --- 3. Sign the new user in straight away ---
                    session_regenerate_id(true);
                    $_SESSION['user_id']     = $new['user_id'];
                    $_SESSION['full_name']   = $form['first_name'] . ' ' . $form['last_name'];
                    $_SESSION['role']        = 'borrower';
                    $_SESSION['borrower_id'] = $new['borrower_id'];

                    $users->touchLogin($new['user_id']);

                    set_flash('success', 'Welcome to ' . APP_NAME . '! Your account was created.');
                    redirect('borrower/dashboard');
                }
            }
        }

        // false = this page does not use the sidebar / top bar layout
        $this->render('auth/signup', ['errors' => $errors, 'form' => $form], false);
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
