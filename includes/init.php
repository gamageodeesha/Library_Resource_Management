<?php
// =====================================================
// Every page includes this ONE file first.
// It starts the session safely, loads the settings,
// connects to the database and loads our helper functions.
// =====================================================

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

require_once __DIR__ . '/../config/config.php';

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

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';