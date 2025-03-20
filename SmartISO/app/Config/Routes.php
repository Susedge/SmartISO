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
$routes->group('', ['filter' => 'auth'], function ($routes) {
    $routes->get('profile', 'Profile::index');
    $routes->post('profile/update', 'Profile::update');
    $routes->post('profile/change-password', 'Profile::changePassword');
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
    
    // Keep the existing individual controllers if needed or remove if only using the consolidated approach
    // Department management
    $routes->get('departments', 'Admin\Departments::index');
    $routes->get('departments/new', 'Admin\Departments::new');
    $routes->post('departments/create', 'Admin\Departments::create');
    $routes->get('departments/edit/(:num)', 'Admin\Departments::edit/$1');
    $routes->post('departments/update/(:num)', 'Admin\Departments::update/$1');
    $routes->get('departments/delete/(:num)', 'Admin\Departments::delete/$1');
    
    // Forms management
    $routes->get('forms', 'Admin\Forms::index');
    $routes->get('forms/new', 'Admin\Forms::new');
    $routes->post('forms/create', 'Admin\Forms::create');
    $routes->get('forms/edit/(:num)', 'Admin\Forms::edit/$1');
    $routes->post('forms/update/(:num)', 'Admin\Forms::update/$1');
    $routes->get('forms/delete/(:num)', 'Admin\Forms::delete/$1');
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
