<?php
// =====================================================
// THE FRONT CONTROLLER - the only page the browser opens.
//
// Every link in the site looks like this:
//     index.php?route=staff/loans
// This file reads the "route", finds the matching
// Controller + method in the table below, and runs it.
//
//   Request  ->  index.php  ->  Controller  ->  Model (database)
//                                    |
//                                    +------->  View (HTML)
// =====================================================
require_once __DIR__ . '/core/bootstrap.php';

// route name           => [Controller class,         method]
$routes = [
    // Everybody
    'login'               => ['AuthController',         'login'],
    'signup'              => ['AuthController',         'signup'],
    'logout'              => ['AuthController',         'logout'],
    'setup'               => ['AuthController',         'setup'],
    'profile'             => ['ProfileController',      'index'],
    'notifications'       => ['NotificationController', 'index'],

    // Admin
    'admin/dashboard'     => ['AdminController',        'dashboard'],
    'admin/users'         => ['AdminController',        'users'],

    // Borrower
    'borrower/dashboard'  => ['BorrowerController',     'dashboard'],
    'borrower/browse'     => ['BorrowerController',     'browse'],
    'borrower/loans'      => ['BorrowerController',     'loans'],
    'borrower/renewals'   => ['BorrowerController',     'renewals'],
    'borrower/history'    => ['BorrowerController',     'history'],

    // Staff
    'staff/dashboard'     => ['StaffController',          'dashboard'],
    'staff/overdue'       => ['StaffController',          'overdue'],
    'staff/history'       => ['StaffController',          'history'],
    'staff/resources'     => ['StaffResourceController',  'index'],
    'staff/resource_form' => ['StaffResourceController',  'form'],
    'staff/borrowers'     => ['StaffBorrowerController',  'index'],
    'staff/borrower_form' => ['StaffBorrowerController',  'form'],
    'staff/loans'         => ['StaffLoanController',      'index'],
    'staff/loan_register' => ['StaffLoanController',      'register'],
    'staff/renewals'      => ['StaffRenewalController',   'index'],
];

$route = $_GET['route'] ?? '';

// No route given (for example the visitor typed just /lendwise/): go to the right start page
if ($route === '') {
    redirect(is_logged_in() ? dashboard_for($_SESSION['role']) : 'login');
}

// Unknown route: show a simple "not found" message
if (!isset($routes[$route])) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Page not found</title></head>'
       . '<body style="font-family:sans-serif;max-width:480px;margin:15vh auto;text-align:center">'
       . '<h2>Page not found</h2><a href="' . e(BASE_URL) . '/">Back to the home page</a></body></html>';
    exit;
}

// The views use this to build links that point back to the same page
define('CURRENT_ROUTE', $route);

// Run it:  new StaffLoanController($pdo)  then  ->index()
list($controller_name, $method) = $routes[$route];
$controller = new $controller_name($pdo);
$controller->$method();
