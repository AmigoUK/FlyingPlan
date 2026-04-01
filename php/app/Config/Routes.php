<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Redirect to installer if not yet set up
if (!file_exists(WRITEPATH . '.installed') && file_exists(FCPATH . 'install.php')) {
    $routes->get('/', static fn() => redirect()->to('/install.php'));
    $routes->get('(:any)', static fn() => redirect()->to('/install.php'));
    return;
}

// Public routes (no auth required)
$routes->get('/', 'PublicForm::form');
$routes->post('/submit', 'PublicForm::submit');
$routes->get('/confirmation', 'PublicForm::confirmation');
$routes->get('/login', 'Auth::login');
$routes->post('/login', 'Auth::login');
$routes->post('/logout', 'Auth::logout');

// Shared mission view (no auth required)
$routes->get('/shared/(:segment)', 'Shared::view/$1');

// Help (no auth required)
$routes->get('/help', 'Help::index');

// Admin routes (manager role required)
$routes->group('admin', ['filter' => 'auth:manager'], static function ($routes) {
    $routes->get('/', 'Admin::dashboard');
    $routes->get('(:num)', 'Admin::detail/$1');
    $routes->post('(:num)/waypoints', 'Admin::saveWaypoints/$1');
    $routes->post('(:num)/status', 'Admin::updateStatus/$1');
    $routes->post('(:num)/notes', 'Admin::saveNotes/$1');
    $routes->post('(:num)/gsd', 'Admin::calculateGsd/$1');
    $routes->post('(:num)/generate-pattern', 'Admin::generatePattern/$1');
    $routes->get('(:num)/airspace', 'Admin::getAirspace/$1');
    $routes->get('(:num)/weather', 'Admin::getWeather/$1');
    $routes->post('(:num)/terrain-follow', 'Admin::terrainFollow/$1');
    $routes->post('(:num)/generate-grid', 'Admin::generateGrid/$1');
    $routes->post('(:num)/elevation', 'Admin::getElevation/$1');
    $routes->post('(:num)/import-kmz', 'Admin::importKmz/$1');
    $routes->post('(:num)/polygon', 'Admin::savePolygon/$1');
    $routes->post('(:num)/drone-model', 'Admin::saveDroneModel/$1');
    $routes->post('(:num)/duplicate', 'Admin::duplicate/$1');
    $routes->get('(:num)/export-kmz', 'Admin::exportKmz/$1');
    $routes->get('(:num)/export-kml', 'Admin::exportKml/$1');
    $routes->get('(:num)/export-geojson', 'Admin::exportGeojson/$1');
    $routes->get('(:num)/export-csv', 'Admin::exportCsv/$1');
    $routes->get('(:num)/export-gpx', 'Admin::exportGpx/$1');
    $routes->post('(:num)/generate-oblique-grid', 'Admin::generateObliqueGrid/$1');
    $routes->post('(:num)/generate-facade-scan', 'Admin::generateFacadeScan/$1');
    $routes->post('(:num)/generate-multi-orbit', 'Admin::generateMultiOrbit/$1');
    $routes->post('(:num)/coverage-analysis', 'Admin::coverageAnalysis/$1');
    $routes->get('(:num)/terrain-mesh', 'Admin::terrainMesh/$1');
    $routes->post('(:num)/quality-report', 'Admin::qualityReport/$1');
    $routes->get('(:num)/export-litchi', 'Admin::exportLitchi/$1');
    $routes->get('(:num)/export-photo-positions', 'Admin::exportPhotoPositions/$1');
    $routes->get('(:num)/export-enhanced-geojson', 'Admin::exportEnhancedGeojson/$1');
    $routes->post('(:num)/share', 'Admin::share/$1');
});

// Pilot routes (pilot role required)
$routes->group('pilot', ['filter' => 'auth:pilot'], static function ($routes) {
    $routes->get('/', 'Pilot::dashboard');
    $routes->match(['get', 'post'], 'profile', 'Pilot::profile');
    $routes->get('orders/(:num)', 'Pilot::orderDetail/$1');
    $routes->post('orders/(:num)/accept', 'Pilot::acceptOrder/$1');
    $routes->post('orders/(:num)/decline', 'Pilot::declineOrder/$1');
    $routes->post('orders/(:num)/status', 'Pilot::updateStatus/$1');
    $routes->post('orders/(:num)/notes', 'Pilot::saveNotes/$1');
    $routes->post('orders/(:num)/deliverables', 'Pilot::uploadDeliverable/$1');
    $routes->post('orders/(:num)/deliverables/(:num)/delete', 'Pilot::deleteDeliverable/$1/$2');
    $routes->match(['get', 'post'], 'orders/(:num)/flight-params', 'Pilot::flightParams/$1');
    $routes->post('orders/(:num)/category-check', 'Pilot::categoryCheck/$1');
    $routes->match(['get', 'post'], 'orders/(:num)/risk-assessment', 'Pilot::riskAssessment/$1');
    $routes->post('certifications/add', 'Pilot::addCertification');
    $routes->post('certifications/(:num)/delete', 'Pilot::deleteCertification/$1');
    $routes->post('memberships/add', 'Pilot::addMembership');
    $routes->post('memberships/(:num)/delete', 'Pilot::deleteMembership/$1');
    $routes->post('equipment/add', 'Pilot::addEquipment');
    $routes->post('equipment/(:num)/delete', 'Pilot::deleteEquipment/$1');
    $routes->post('documents/upload', 'Pilot::uploadDocument');
    $routes->post('documents/(:num)/delete', 'Pilot::deleteDocument/$1');
    $routes->get('documents/(:num)/download', 'Pilot::downloadDocument/$1');
    $routes->get('orders/(:num)/weather', 'Pilot::getWeather/$1');
    $routes->post('orders/(:num)/elevation', 'Pilot::getElevation/$1');
    $routes->post('orders/(:num)/import-kmz', 'Pilot::importKmz/$1');
    $routes->post('orders/(:num)/waypoints', 'Pilot::saveWaypoints/$1');
    $routes->get('orders/(:num)/export-kmz', 'Pilot::exportKmz/$1');
    $routes->get('orders/(:num)/report-pdf', 'Pilot::reportPdf/$1');
});

// Orders routes (manager role required)
$routes->group('orders', ['filter' => 'auth:manager'], static function ($routes) {
    $routes->get('/', 'Orders::index');
    $routes->post('create/(:num)', 'Orders::create/$1');
    $routes->get('(:num)', 'Orders::detail/$1');
    $routes->post('(:num)/assign', 'Orders::assign/$1');
    $routes->post('(:num)/status', 'Orders::updateStatus/$1');
    $routes->post('(:num)/notes', 'Orders::saveNotes/$1');
    $routes->get('(:num)/deliverables/(:num)/download', 'Orders::downloadDeliverable/$1/$2');
    $routes->get('(:num)/report-pdf', 'Orders::reportPdf/$1');
});

// Pilots management routes (manager role required)
$routes->group('pilots', ['filter' => 'auth:manager'], static function ($routes) {
    $routes->get('/', 'Pilots::index');
    $routes->match(['get', 'post'], 'new', 'Pilots::create');
    $routes->get('(:num)', 'Pilots::view/$1');
    $routes->post('(:num)/edit', 'Pilots::edit/$1');
    $routes->post('(:num)/toggle-active', 'Pilots::toggleActive/$1');
    $routes->post('(:num)/availability', 'Pilots::setAvailability/$1');
    $routes->post('(:num)/certifications/add', 'Pilots::addCertification/$1');
    $routes->post('(:num)/certifications/(:num)/delete', 'Pilots::deleteCertification/$1/$2');
    $routes->post('(:num)/memberships/add', 'Pilots::addMembership/$1');
    $routes->post('(:num)/memberships/(:num)/delete', 'Pilots::deleteMembership/$1/$2');
    $routes->post('(:num)/equipment/add', 'Pilots::addEquipment/$1');
    $routes->post('(:num)/equipment/(:num)/delete', 'Pilots::deleteEquipment/$1/$2');
    $routes->post('(:num)/documents/upload', 'Pilots::uploadDocument/$1');
    $routes->get('(:num)/documents/(:num)/download', 'Pilots::downloadDocument/$1/$2');
    $routes->post('(:num)/documents/(:num)/delete', 'Pilots::deleteDocument/$1/$2');
});

// Settings routes (admin role required)
$routes->group('settings', ['filter' => 'auth:admin'], static function ($routes) {
    $routes->get('/', 'Settings::index');
    $routes->post('branding', 'Settings::branding');
    $routes->post('form-visibility', 'Settings::formVisibility');
    $routes->post('job-types/new', 'Settings::createJobType');
    $routes->post('job-types/(:num)/edit', 'Settings::editJobType/$1');
    $routes->post('job-types/(:num)/toggle', 'Settings::toggleJobType/$1');
    $routes->post('job-types/(:num)/delete', 'Settings::deleteJobType/$1');
    $routes->post('purposes/new', 'Settings::createPurpose');
    $routes->post('purposes/(:num)/edit', 'Settings::editPurpose/$1');
    $routes->post('purposes/(:num)/toggle', 'Settings::togglePurpose/$1');
    $routes->post('purposes/(:num)/delete', 'Settings::deletePurpose/$1');
    $routes->post('heard-about/new', 'Settings::createHeardAbout');
    $routes->post('heard-about/(:num)/edit', 'Settings::editHeardAbout/$1');
    $routes->post('heard-about/(:num)/toggle', 'Settings::toggleHeardAbout/$1');
    $routes->post('heard-about/(:num)/delete', 'Settings::deleteHeardAbout/$1');
});
