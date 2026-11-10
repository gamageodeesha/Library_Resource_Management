<?php
// =====================================================
// This file runs at the start of EVERY request.
// (It replaces the old includes/init.php)
// It starts the session, loads the settings, connects to
// the database, loads the helper functions, and sets up
// "autoloading" so PHP finds our Model / Controller classes
// by itself.
// =====================================================

// The folder that contains this project on your computer
define('BASE_PATH', dirname(__DIR__));

// ---------- Session (the "remember who is logged in" box) ----------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,                          // ends when the browser closes
        'path'     => '/',
        'httponly' => true,                       // JavaScript cannot read the cookie
        'samesite' => 'Lax',                      // not sent from other websites' forms
        'secure'   => !empty($_SERVER['HTTPS']),  // only over https when the site uses it
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

require_once BASE_PATH . '/config/config.php';

// ---------- Error display ----------
// While you build and test: APP_DEBUG = true  (errors are shown on the page).
// For the final demo or a real server, add this line to config/config.php:
//     define('APP_DEBUG', false);
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');

// Any error that nobody catches ends up here
set_exception_handler(function ($error) {
    error_log('Lendwise error: ' . $error->getMessage() . ' in ' . $error->getFile() . ':' . $error->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (APP_DEBUG) {
        echo '<pre style="padding:1rem;white-space:pre-wrap">' . htmlspecialchars($error->getMessage() . "\n" . $error->getFile() . ':' . $error->getLine()) . '</pre>';
    } else {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Something went wrong</title></head>'
           . '<body style="font-family:sans-serif;max-width:480px;margin:15vh auto;text-align:center">'
           . '<h2>Something went wrong</h2><p>Please try again. If the problem continues, contact the library staff.</p>'
           . '<a href="' . htmlspecialchars(BASE_URL) . '/">Back to the home page</a></body></html>';
    }
    exit;
});

require_once BASE_PATH . '/config/database.php';   // creates $pdo
require_once BASE_PATH . '/core/helpers.php';

// ---------- Autoload ----------
// When the code says "new Loan($pdo)", PHP looks for Loan.php in these folders.
spl_autoload_register(function ($class) {
    foreach (['core', 'models', 'controllers'] as $folder) {
        $file = BASE_PATH . '/' . $folder . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});
