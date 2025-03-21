<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Dashboard');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */
// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');

// Auth routes
$routes->group('auth', function ($routes) {
    $routes->get('register', 'Auth::register');
    $routes->post('register', 'Auth::register');
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::login');
    $routes->get('logout', 'Auth::logout');
});

// Home/Landing page
$routes->get('/', 'Home::index');

// Dashboard (requires login)
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);

// User routes
// User routes
$routes->group('', ['filter' => 'auth'], function ($routes) {
    $routes->get('profile', 'Profile::index');  // This route already exists
    $routes->post('profile/update', 'Profile::update');
    $routes->post('profile/change-password', 'Profile::changePassword');
    $routes->post('profile/upload-signature', 'Profile::uploadSignature');
    $routes->get('forms/sign/(:num)', 'Forms::signForm/$1');
    $routes->get('forms', 'Forms::index');
    $routes->get('forms/view/(:segment)', 'Forms::view/$1');
    $routes->post('forms/submit', 'Forms::submit');
    $routes->get('forms/my-submissions', 'Forms::mySubmissions');
    $routes->get('forms/submission/(:num)', 'Forms::viewSubmission/$1');
    $routes->get('forms/submission/(:num)/(:alpha)', 'Forms::export/$1/$2');
});


// Admin routes
$routes->group('admin', ['filter' => 'auth:admin,superuser'], function ($routes) {
    $routes->get('dashboard', 'Admin\Dashboard::index');
    
    // Configurations - Consolidated lookup tables
    $routes->get('configurations', 'Admin\Configurations::index');
    $routes->get('configurations/new', 'Admin\Configurations::new');
    $routes->post('configurations/create', 'Admin\Configurations::create');
    $routes->get('configurations/edit/(:num)', 'Admin\Configurations::edit/$1');
    $routes->post('configurations/update/(:num)', 'Admin\Configurations::update/$1');
    $routes->get('configurations/delete/(:num)', 'Admin\Configurations::delete/$1');

    // Dynamic Forms routes
    $routes->get('dynamicforms', 'Admin\DynamicForms::index');
    $routes->get('dynamicforms/panel', 'Admin\DynamicForms::panel');
    $routes->get('dynamicforms/panel-config', 'Admin\DynamicForms::panelConfig');
    $routes->get('dynamicforms/edit-panel/(:segment)', 'Admin\DynamicForms::editPanel/$1');
    $routes->post('dynamicforms/add-panel-field', 'Admin\DynamicForms::addPanelField');
    $routes->get('dynamicforms/delete-field/(:num)', 'Admin\DynamicForms::deleteField/$1');
    $routes->post('dynamicforms/update-panel-field', 'Admin\DynamicForms::updatePanelField');
    $routes->post('dynamicforms/submit', 'Admin\DynamicForms::submit');
    $routes->get('dynamicforms/submissions', 'Admin\DynamicForms::submissions');
    $routes->get('dynamicforms/view-submission/(:num)', 'Admin\DynamicForms::viewSubmission/$1');
    $routes->post('dynamicforms/update-status', 'Admin\DynamicForms::updateStatus');
    $routes->get('dynamicforms/export-submission/(:num)/(:alpha)', 'Admin\DynamicForms::exportSubmission/$1/$2');
    $routes->post('dynamicforms/bulk-action', 'Admin\DynamicForms::bulkAction');
});

// Superuser-only routes
$routes->group('admin', ['filter' => 'auth:superuser'], function ($routes) {
    // User management
    $routes->get('users', 'Admin\Users::index');
    $routes->get('users/new', 'Admin\Users::new');
    $routes->post('users/create', 'Admin\Users::create');
    $routes->get('users/edit/(:num)', 'Admin\Users::edit/$1');
    $routes->post('users/update/(:num)', 'Admin\Users::update/$1');
    $routes->get('users/delete/(:num)', 'Admin\Users::delete/$1');
});

/*
  * --------------------------------------------------------------------
  * Additional Routing
  * --------------------------------------------------------------------
  */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
