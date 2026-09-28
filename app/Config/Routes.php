<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

/* ----------------- OPEN ROUTES ----------------- */
// New user registration (open, no session required)
$routes->get('newuser', 'UserController::newUser');
$routes->post('newuser/submit', 'UserController::submitNewuser');

// Updated forms group using SessionController for custom logout view
$routes->group('forms', ['namespace' => 'App\Controllers'], function ($routes) {
    $routes->get('logout', 'SessionController::logoutPage');

    // Optional: Re-enable these if you still need mock session testing during transition
    $routes->get('session', 'SessionController::index');
    $routes->post('session/set', 'SessionController::set');
});

/* ----------------- DASHBOARD ----------------- */
// Dashboard (requires login)
$routes->get('/', 'DashboardController::index', ['filter' => 'role:1,2,3']);

/* ----------------- MAINTENANCE ----------------- */
$routes->group('maintenance', ['filter' => 'role:2,3'], function ($routes) {
    $routes->get('/', 'MaintenanceController::index');
    $routes->post('updateStatus/(:num)', 'MaintenanceController::updateStatus/$1');
    $routes->post('addContainer', 'MaintenanceController::addContainer');
    $routes->post('deleteContainer', 'MaintenanceController::deleteContainer');
    $routes->get('nextContainerNumber/(:num)', 'MaintenanceController::nextContainerNumber/$1');
    $routes->post('createUnit', 'MaintenanceController::createUnit');
    $routes->post('updateUnit/(:num)', 'MaintenanceController::updateUnit/$1');
    $routes->post('createStorage', 'MaintenanceController::createStorage');
    $routes->post('updateStorage/(:num)', 'MaintenanceController::updateStorage/$1');
    $routes->post('createContainer', 'MaintenanceController::createContainer');
    $routes->post('updateContainer/(:num)', 'MaintenanceController::updateContainer/$1');
});

/* ----------------- USERS & ROLES ----------------- */
$routes->group('', ['filter' => 'role:2,3'], function ($routes) {
    $routes->get('users', 'UserController::users');

    $routes->get('role', 'RoleController::index');
    $routes->get('role/approve/(:num)', 'RoleController::approve/$1');
    $routes->post('role/reject/(:num)', 'RoleController::reject/$1');
    $routes->get('role/restore/(:num)', 'RoleController::restore/$1');
    $routes->get('role/changeRole/(:num)/(:num)', 'RoleController::changeRole/$1/$2');
    $routes->get('role/changeStatus/(:num)/(:num)', 'RoleController::changeStatus/$1/$2');
    $routes->get('role/changeUnit/(:num)/(:num)', 'RoleController::changeUnit/$1/$2');
    $routes->post('role/changeUnitMultiple', 'RoleController::changeUnitMultiple');
});

/* ----------------- SPECIMEN ----------------- */
$routes->group('specimen', ['filter' => 'role:1,2,3'], function ($routes) {
    $routes->get('/', 'SpecimenController::index');
    $routes->get('storages/(:any)', 'SpecimenController::getStorages/$1');
    $routes->get('containers/numbers/(:num)', 'SpecimenController::getContainerNumbers/$1');
    $routes->get('containers/today/(:num)/(:num)', 'SpecimenController::getTodayContainers/$1/$2');
    $routes->get('specimens/(:num)', 'SpecimenController::getSpecimens/$1');
    $routes->post('save', 'SpecimenController::save');
    $routes->post('cancel', 'SpecimenController::cancel');
    $routes->get('all_list', 'SpecimenController::all_list');
    $routes->post('allSpecimensAjax', 'SpecimenController::allSpecimensAjax');

    // NEW ISOLATED ROUTES FOR BULK OPERATION WORKFLOWS
    $routes->get('bulk', 'SpecimenController::bulk'); // Opens the new view
    $routes->post('saveBulk', 'SpecimenController::saveBulk'); // Form destination endpoint

});

$routes->group('specimen', ['filter' => 'role:1,2,3'], function ($routes) {
    $routes->get('disposal', 'SpecimenController::disposal');
    $routes->post('disposeSelectedAjax', 'SpecimenController::disposeSelectedAjax');
    $routes->post('disposalAjax', 'SpecimenController::disposalAjax');
    $routes->get('requestDisposeAll', 'SpecimenController::requestDisposeAll');
    $routes->get('disposal/details', 'SpecimenController::getReadyToDispose');
});

// /* ----------------- REPORTS ----------------- */
// $routes->get('reports', 'ReportController::index', ['filter' => 'role:1,2,3']);
// $routes->get('reports/disposal_report', 'ReportController::disposal_report', ['filter' => 'role:1,2,3']);
// $routes->get('get-storage-by-unit', 'ReportController::getStorageByUnit');

/* ----------------- REPORTS ----------------- */
// Menyisihkan role:1 (User biasa) daripada hak akses melayari modul laporan
$routes->get('reports', 'ReportController::index', ['filter' => 'role:2,3']);
$routes->get('get-storage-by-unit', 'ReportController::getStorageByUnit', ['filter' => 'role:2,3']);
$routes->post('reports/disposalAuditAjax', 'ReportController::disposalAuditAjax', ['filter' => 'role:2,3']);
$routes->post('reports/cancelledAuditAjax', 'ReportController::cancelledAuditAjax', ['filter' => 'role:2,3']);
$routes->post('reports/exportAuditAjax', 'ReportController::exportAuditAjax', ['filter' => 'role:2,3']);

// Laluan untuk memaparkan borang suis maintenance
$routes->get('admin/settings', 'AdminController::settings');
// Laluan untuk memproses borang simpanan (POST)
$routes->post('admin/saveSettings', 'AdminController::saveSettings');