<?php
/**
 * Front Controller
 */

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Simple Autoloader
spl_autoload_register(function ($class) {
    // App\Support\Router => app/Support/Router.php
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Start secure session
\App\Support\Auth::startSession();

// Initialize Router
$router = new \App\Support\Router();

// Define Routes - Public & Home
$router->get('/', ['App\Controllers\HomeController', 'index']);
$router->get('/rooms', ['App\Controllers\HomeController', 'rooms']);
$router->get('/free', ['App\Controllers\HomeController', 'freeRooms']);
$router->get('/search', ['App\Controllers\HomeController', 'search']);

// Auth Routes (FR-001 - FR-005, FR-023)
$router->get('/login', ['App\Controllers\AuthController', 'showLoginForm']);
$router->post('/login', ['App\Controllers\AuthController', 'login']);
$router->post('/logout', ['App\Controllers\AuthController', 'logout']);
$router->get('/register', ['App\Controllers\AuthController', 'showRegisterForm']);
$router->post('/register', ['App\Controllers\AuthController', 'register']);
$router->get('/verify', ['App\Controllers\AuthController', 'verifyEmail']);
$router->get('/forgot-password', ['App\Controllers\AuthController', 'forgotPassword']);
$router->post('/forgot-password', ['App\Controllers\AuthController', 'sendResetLink']);
$router->get('/reset-password', ['App\Controllers\AuthController', 'resetPasswordForm']);
$router->post('/reset-password', ['App\Controllers\AuthController', 'resetPassword']);
$router->get('/profile', ['App\Controllers\AuthController', 'profile']);
$router->post('/profile/password', ['App\Controllers\AuthController', 'updatePassword']);
$router->get('/auth/sso', ['App\Controllers\AuthController', 'showSsoPage']);
$router->post('/auth/sso/process', ['App\Controllers\AuthController', 'processSso']);

// API Routes
$router->get('/api/rooms', ['App\Controllers\ApiController', 'rooms']);
$router->get('/api/search', ['App\Controllers\ApiController', 'search']);
$router->get('/api/rooms/alternatives', ['App\Controllers\BookingController', 'alternativesApi']);

// Room Routes & Absent Reporting (FR-012, FR-063)
$router->get('/room/{id}', ['App\Controllers\RoomController', 'show']);
$router->get('/rooms/{id}', ['App\Controllers\RoomController', 'show']);
$router->post('/rooms/{id}/report-absent', ['App\Controllers\ClassController', 'reportAbsent']);

// Booking Routes (FR-070, FR-074, FR-077)
$router->get('/booking/create', ['App\Controllers\BookingController', 'create']);
$router->get('/bookings/new', ['App\Controllers\BookingController', 'create']);
$router->post('/booking/store', ['App\Controllers\BookingController', 'store']);
$router->get('/me/bookings', ['App\Controllers\BookingController', 'myBookings']);
$router->post('/booking/cancel', ['App\Controllers\BookingController', 'cancel']);

// Facility Report Routes (FR-080, FR-081, FR-083)
$router->get('/report/create', ['App\Controllers\ReportController', 'create']);
$router->get('/reports/new', ['App\Controllers\ReportController', 'create']);
$router->post('/report/store', ['App\Controllers\ReportController', 'store']);
$router->get('/me/reports', ['App\Controllers\ReportController', 'myReports']);
$router->post('/report/support', ['App\Controllers\ReportController', 'support']);
$router->post('/report/reopen', ['App\Controllers\ReportController', 'reopen']);

// Lecturer Routes (FR-060, FR-061, FR-068)
$router->get('/class', ['App\Controllers\ClassController', 'index']);
$router->get('/me/classes', ['App\Controllers\ClassController', 'index']);
$router->post('/class/checkin', ['App\Controllers\ClassController', 'checkin']);
$router->post('/class/cancel', ['App\Controllers\ClassController', 'cancel']);
$router->post('/class/move', ['App\Controllers\ClassController', 'move']);

// Admin / Manager Routes (FR-006, FR-065, FR-072, FR-084, FR-091)
$router->get('/admin', ['App\Controllers\AdminController', 'dashboard']);
$router->get('/manage', ['App\Controllers\AdminController', 'dashboard']);

$router->get('/admin/bookings', ['App\Controllers\AdminController', 'bookings']);
$router->get('/manage/bookings', ['App\Controllers\AdminController', 'bookings']);
$router->post('/admin/bookings/process', ['App\Controllers\AdminController', 'processBooking']);

$router->get('/admin/reports', ['App\Controllers\AdminController', 'reports']);
$router->get('/manage/reports', ['App\Controllers\AdminController', 'reports']);
$router->get('/admin/reports/export', ['App\Controllers\AdminController', 'exportReports']);
$router->post('/admin/reports/process', ['App\Controllers\AdminController', 'processReport']);

$router->get('/admin/sessions', ['App\Controllers\AdminController', 'sessions']);
$router->get('/manage/sessions', ['App\Controllers\AdminController', 'sessions']);
$router->post('/admin/sessions/verify', ['App\Controllers\AdminController', 'verifySession']);

$router->get('/admin/lecturers', ['App\Controllers\AdminController', 'lecturers']);
$router->get('/manage/users', ['App\Controllers\AdminController', 'lecturers']);
$router->post('/admin/lecturers/process', ['App\Controllers\AdminController', 'processLecturer']);

// Schedule Import Routes (FR-040 - FR-045)
$router->get('/admin/import', ['App\Controllers\ImportController', 'index']);
$router->get('/manage/imports', ['App\Controllers\ImportController', 'index']);
$router->get('/admin/import/template', ['App\Controllers\ImportController', 'downloadTemplate']);
$router->post('/admin/import/preview', ['App\Controllers\ImportController', 'preview']);
$router->post('/admin/import/commit', ['App\Controllers\ImportController', 'commit']);
$router->post('/admin/import/cancel', ['App\Controllers\ImportController', 'cancelPreview']);
$router->post('/admin/import/rollback', ['App\Controllers\ImportController', 'rollback']);

// Superadmin Routes (FR-092, FR-093)
$router->get('/admin/audit', ['App\Controllers\AdminController', 'audit']);
$router->get('/admin/settings', ['App\Controllers\AdminController', 'settings']);
$router->post('/admin/settings/update', ['App\Controllers\AdminController', 'updateSettings']);

// Master Data Management Routes (FR-050 - FR-053)
$router->get('/admin/rooms', ['App\Controllers\MasterDataController', 'rooms']);
$router->get('/manage/rooms', ['App\Controllers\MasterDataController', 'rooms']);
$router->get('/admin/rooms/create', ['App\Controllers\MasterDataController', 'createRoom']);
$router->get('/admin/rooms/{id}/edit', ['App\Controllers\MasterDataController', 'editRoom']);
$router->post('/admin/rooms/save', ['App\Controllers\MasterDataController', 'saveRoom']);
$router->post('/admin/rooms/deactivate', ['App\Controllers\MasterDataController', 'deactivateRoom']);
$router->post('/admin/rooms/activate', ['App\Controllers\MasterDataController', 'activateRoom']);
$router->post('/admin/rooms/delete', ['App\Controllers\MasterDataController', 'deleteRoom']);

$router->get('/admin/rooms/{id}/facilities', ['App\Controllers\MasterDataController', 'facilities']);
$router->post('/admin/rooms/{id}/facilities/save', ['App\Controllers\MasterDataController', 'saveFacility']);
$router->post('/admin/facilities/{id}/delete', ['App\Controllers\MasterDataController', 'deleteFacility']);

$router->get('/admin/buildings', ['App\Controllers\MasterDataController', 'buildings']);
$router->get('/manage/buildings', ['App\Controllers\MasterDataController', 'buildings']);
$router->post('/admin/buildings/save', ['App\Controllers\MasterDataController', 'saveBuilding']);
$router->post('/admin/buildings/delete', ['App\Controllers\MasterDataController', 'deleteBuilding']);

$router->get('/admin/terms', ['App\Controllers\MasterDataController', 'terms']);
$router->get('/manage/terms', ['App\Controllers\MasterDataController', 'terms']);
$router->post('/admin/terms/save', ['App\Controllers\MasterDataController', 'saveSemester']);
$router->post('/admin/terms/activate', ['App\Controllers\MasterDataController', 'activateSemester']);
$router->post('/admin/no-class/save', ['App\Controllers\MasterDataController', 'saveNoClassPeriod']);
$router->post('/admin/no-class/delete', ['App\Controllers\MasterDataController', 'deleteNoClassPeriod']);

// Manual Schedule Entry Routes (FR-054)
$router->get('/admin/schedules', ['App\Controllers\ScheduleController', 'index']);
$router->get('/manage/schedules', ['App\Controllers\ScheduleController', 'index']);
$router->get('/admin/schedules/create', ['App\Controllers\ScheduleController', 'create']);
$router->get('/admin/schedules/{id}/edit', ['App\Controllers\ScheduleController', 'edit']);
$router->post('/admin/schedules/save', ['App\Controllers\ScheduleController', 'save']);
$router->post('/admin/schedules/{id}/delete', ['App\Controllers\ScheduleController', 'delete']);
$router->post('/admin/schedules/{id}/toggle', ['App\Controllers\ScheduleController', 'toggle']);

// Run the application
$router->run();
