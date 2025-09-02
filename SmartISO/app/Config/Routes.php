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

// Allow upload-docx endpoint to be reachable for all methods at top-level
// This ensures method routing won't return 405 when called via fetch multipart POST.
$routes->match(['GET','POST','OPTIONS'], 'forms/upload-docx/(:segment)', 'Forms::uploadDocx/$1');

// Auth routes
$routes->group('auth', function ($routes) {
    $routes->get('register', 'Auth::register');
    $routes->post('register', 'Auth::register');
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::login');
    $routes->get('logout', 'Auth::logout');
    $routes->post('extend-session', 'Auth::extendSession');
});

// Home/Landing page

// API routes (no auth required)
$routes->get('api/current-time', 'Api::currentTime');

// Dashboard (requires login)
$routes->get('/dashboard', 'Dashboard::index', ['filter' => 'auth']);

// Analytics routes
$routes->group('analytics', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Analytics::index');
    $routes->get('api/(:segment)', 'Analytics::api/$1');
    $routes->post('export', 'Analytics::exportReport');
});

// User routes
$routes->group('', ['filter' => 'auth'], function ($routes) {
    // Profile and signature routes
    $routes->get('profile', 'Profile::index');
    $routes->post('profile/update', 'Profile::update');
    $routes->post('profile/change-password', 'Profile::changePassword');
    $routes->post('profile/upload-signature', 'Profile::uploadSignature');
    $routes->post('profile/upload-profile-image', 'Profile::uploadProfileImage');

    // Form routes for all users
    $routes->get('forms', 'Forms::index');
    $routes->get('forms/view/(:segment)', 'Forms::view/$1');
    $routes->post('forms/submit', 'Forms::submit');
    $routes->get('forms/my-submissions', 'Forms::mySubmissions');
    $routes->get('forms/submission/(:num)', 'Forms::viewSubmission/$1');
    $routes->get('forms/submission/(:num)/(:alpha)', 'Forms::export/$1/$2');
    $routes->get('forms/completed', 'Forms::completedForms');

    // For requestors
    $routes->get('forms/pending-signature', 'Forms::pendingRequestorSignature');
    
    // For approving authority
    $routes->get('forms/pending-approval', 'Forms::pendingApproval');
    $routes->get('forms/approved-by-me', 'Forms::approvedByMe');
    $routes->get('forms/rejected-by-me', 'Forms::rejectedByMe');
    
    // For service staff
    $routes->get('forms/pending-service', 'Forms::pendingService');
    $routes->get('forms/serviced-by-me', 'Forms::servicedByMe');
    
    // Form actions
    $routes->get('forms/sign/(:num)', 'Forms::signForm/$1');
    $routes->get('forms/approve/(:num)', 'Forms::approveForm/$1');
    $routes->post('forms/approve', 'Forms::submitApproval');
    $routes->get('forms/reject/(:num)', 'Forms::rejectForm/$1');
    $routes->post('forms/reject', 'Forms::submitRejection');
    $routes->post('forms/approve-all', 'Forms::approveAll');
    $routes->post('forms/assign-service-staff', 'Forms::assignServiceStaff');
    $routes->get('forms/service/(:num)', 'Forms::serviceForm/$1');
    // Backwards-compatible URL used in some views
    $routes->get('forms/service-form/(:num)', 'Forms::serviceForm/$1');
    $routes->post('forms/service', 'Forms::submitService');
    $routes->post('forms/sign/(:num)', 'Forms::signForm/$1');
    
    // Form completion routes
    $routes->get('forms/final-sign/(:num)', 'Forms::finalSignForm/$1');
    $routes->post('forms/confirm-service', 'Forms::confirmService');

    // PDF viewer
    $routes->get('pdfgenerator/generateFormPdf/(:num)', 'PdfGenerator::generateFormPdf/$1');
    
    // Form download routes (fillable forms without placeholders)
    $routes->get('forms/download/pdf/(:segment)', 'FormDownload::downloadPDF/$1');
    $routes->get('forms/download/word/(:segment)', 'FormDownload::downloadWord/$1');
    // Download the uploaded raw template (PDF or DOCX)
    $routes->get('forms/download/uploaded/(:segment)', 'FormDownload::downloadUploaded/$1');
    // Requestor DOCX prefill upload - accept GET/POST/OPTIONS to avoid method issues
    $routes->match(['GET','POST','OPTIONS'], 'forms/upload-docx/(:segment)', 'Forms::uploadDocx/$1');
    
    // Scheduling routes
    $routes->get('schedule', 'Schedule::index');
    $routes->get('schedule/create/(:num)', 'Schedule::create/$1');
    $routes->post('schedule/store', 'Schedule::store');
    $routes->get('schedule/edit/(:num)', 'Schedule::edit/$1');
    $routes->post('schedule/update/(:num)', 'Schedule::update/$1');
    $routes->post('schedule/update-priority/(:num)', 'Schedule::updatePriority/$1');
    $routes->get('schedule/delete/(:num)', 'Schedule::delete/$1');
    $routes->get('schedule/calendar', 'Schedule::calendar');
    $routes->post('schedule/mark-complete/(:num)', 'Schedule::markComplete/$1');
    $routes->post('schedule/toggle-priority/(:num)', 'Schedule::togglePriority/$1');
    $routes->get('schedule/priorities', 'Schedule::priorities');
    $routes->post('schedule/priorities/clear', 'Schedule::bulkUnmarkPriorities');
    
    // Feedback routes
    $routes->get('feedback', 'Feedback::index');
    $routes->get('feedback/create', 'Feedback::create');
    $routes->post('feedback/store', 'Feedback::store');
    $routes->get('feedback/view/(:num)', 'Feedback::view/$1');
    $routes->post('feedback/mark-reviewed/(:num)', 'Feedback::markReviewed/$1');
    $routes->get('feedback/analytics', 'Feedback::analytics');
    $routes->get('feedback/export', 'Feedback::export');
    $routes->get('feedback/create/(:num)', 'Feedback::create/$1');
    
    // Notification routes
    $routes->get('notifications', 'Notifications::index');
    $routes->get('notifications/unread', 'Notifications::getUnread');
    $routes->get('notifications/view/(:num)', 'Notifications::view/$1');
    $routes->post('notifications/mark-read/(:num)', 'Notifications::markAsRead/$1');
    $routes->post('notifications/mark-all-read', 'Notifications::markAsRead');
    $routes->post('notifications/delete/(:num)', 'Notifications::delete/$1');
});

// Admin routes
$routes->group('admin', ['filter' => 'auth:admin,superuser'], function ($routes) {
    // Configurations - Consolidated lookup tables
    $routes->get('configurations', 'Admin\Configurations::index');
    $routes->get('configurations/new', 'Admin\Configurations::new');
    $routes->post('configurations/create', 'Admin\Configurations::create');
    $routes->get('configurations/edit/(:num)', 'Admin\Configurations::edit/$1');
    $routes->post('configurations/update/(:num)', 'Admin\Configurations::update/$1');
    $routes->get('configurations/delete/(:num)', 'Admin\Configurations::delete/$1');
    
    // Form signatories management
    $routes->get('configurations/form-signatories/(:num)', 'Admin\Configurations::formSignatories/$1');
    $routes->post('configurations/add-form-signatory', 'Admin\Configurations::addFormSignatory');
    $routes->get('configurations/remove-form-signatory/(:num)', 'Admin\Configurations::removeFormSignatory/$1');
    $routes->get('configurations/user-form-signatories/(:num)', 'Admin\Configurations::userFormSignatories/$1');

    // Dynamic Forms routes
    $routes->get('dynamicforms', 'Admin\DynamicForms::index');
    $routes->get('dynamicforms/guide', 'Admin\DynamicForms::guide');
    $routes->get('dynamicforms/panel', 'Admin\DynamicForms::panel');
    $routes->get('dynamicforms/panel-config', 'Admin\DynamicForms::panelConfig');
    $routes->get('dynamicforms/edit-panel/(:segment)', 'Admin\DynamicForms::editPanel/$1');
    $routes->get('dynamicforms/form-builder/(:segment)', 'Admin\DynamicForms::formBuilder/$1');
    $routes->post('dynamicforms/save-form-builder', 'Admin\DynamicForms::saveFormBuilder');
    $routes->post('dynamicforms/reorder-fields', 'Admin\DynamicForms::reorderFields');
    $routes->post('dynamicforms/add-panel-field', 'Admin\DynamicForms::addPanelField');
    $routes->post('dynamicforms/create-panel', 'Admin\DynamicForms::createPanel');
    $routes->post('dynamicforms/copy-panel', 'Admin\DynamicForms::copyPanel');
    $routes->post('dynamicforms/create-form', 'Admin\DynamicForms::createForm');
    $routes->post('dynamicforms/update-form', 'Admin\DynamicForms::updateForm');
    $routes->post('dynamicforms/delete-form', 'Admin\DynamicForms::deleteForm');
    $routes->post('dynamicforms/delete-panel', 'Admin\DynamicForms::deletePanel');
    $routes->get('dynamicforms/delete-field/(:num)', 'Admin\DynamicForms::deleteField/$1');
    $routes->post('dynamicforms/update-panel-field', 'Admin\DynamicForms::updatePanelField');
    $routes->post('dynamicforms/submit', 'Admin\DynamicForms::submit');
    $routes->get('dynamicforms/submissions', 'Admin\DynamicForms::submissions');
    $routes->get('dynamicforms/view-submission/(:num)', 'Admin\DynamicForms::viewSubmission/$1');
    $routes->post('dynamicforms/update-status', 'Admin\DynamicForms::updateStatus');
    $routes->post('dynamicforms/update-priority', 'Admin\DynamicForms::updatePriority');
    $routes->get('dynamicforms/export-submission/(:num)/(:alpha)', 'Admin\DynamicForms::exportSubmission/$1/$2');
    $routes->post('dynamicforms/bulk-action', 'Admin\DynamicForms::bulkAction');
    $routes->post('dynamicforms/rename-panel', 'Admin\DynamicForms::renamePanel');
    
    // User management (accessible to both admin and superuser)
    $routes->get('users', 'Admin\Users::index');
    $routes->get('users/new', 'Admin\Users::new');
    $routes->post('users/create', 'Admin\Users::create');
    $routes->get('users/edit/(:num)', 'Admin\Users::edit/$1');
    $routes->post('users/update/(:num)', 'Admin\Users::update/$1');

    // Approval Form routes
    $routes->get('dynamicforms/approval-form/(:num)', 'Admin\DynamicForms::approvalForm/$1');
    $routes->post('dynamicforms/approve-submission', 'Admin\DynamicForms::approveSubmission');

    $routes->get('configurations/download-template/(:num)', 'Admin\Configurations::downloadTemplate/$1');
    $routes->get('configurations/delete-template/(:num)', 'Admin\Configurations::deleteTemplate/$1');
    $routes->post('configurations/upload-template/(:num)', 'Admin\Configurations::uploadTemplate/$1');
    $routes->post('configurations/update-system-config', 'Admin\Configurations::updateSystemConfig');
    // Database backup (download SQL dump)
    $routes->get('configurations/backup-db', 'Admin\Configurations::exportDatabase');
    
    // Office management routes (replaces departments)
    $routes->get('office', 'Admin\Office::index');
    $routes->get('office/create', 'Admin\Office::create');
    $routes->post('office/store', 'Admin\Office::store');
    $routes->get('office/edit/(:num)', 'Admin\Office::edit/$1');
    $routes->post('office/update/(:num)', 'Admin\Office::update/$1');
    $routes->get('office/delete/(:num)', 'Admin\Office::delete/$1');
    $routes->get('office/active', 'Admin\Office::getActiveOffices');
    
    // Admin notification cleanup
    $routes->post('notifications/cleanup', 'Notifications::cleanup');
});

// Superuser-only routes
$routes->group('admin', ['filter' => 'auth:superuser'], function ($routes) {
    // Restricted user management operations (superuser only)
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
