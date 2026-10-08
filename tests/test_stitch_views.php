<?php
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// Simulate student login
\App\Support\Auth::startSession();
$db = \App\Support\Database::getInstance()->getConnection();
$user = $db->query("SELECT * FROM users WHERE role = 'student' LIMIT 1")->fetch();
if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'];
}

// Test booking/create view
ob_start();
$rooms = $db->query("SELECT r.*, b.name as building_name FROM rooms r JOIN buildings b ON r.building_id = b.id WHERE r.is_active = 1")->fetchAll();
$alternatives = [];
$baseUrl = '/webpinjamft/public';
require __DIR__ . '/../app/Views/booking/create.php';
$bookingHtml = ob_get_clean();
echo "Booking View Render: OK (" . strlen($bookingHtml) . " bytes)\n";

// Simulate manager login
$mgr = $db->query("SELECT * FROM users WHERE role = 'manager' LIMIT 1")->fetch();
if ($mgr) {
    $_SESSION['user_id'] = $mgr['id'];
    $_SESSION['user_name'] = $mgr['name'];
    $_SESSION['user_role'] = $mgr['role'];
    $_SESSION['user_email'] = $mgr['email'];
}

// Test admin/dashboard view
ob_start();
$stats = [
    'pending_bookings' => 8,
    'competing_bookings' => 2,
    'new_reports' => 3,
    'unverified_sessions' => 2,
    'review_bookings' => 1,
    'inactive_rooms' => 2,
    'pending_lecturers' => 0
];
$pendingBookings = [];
$recentReports = [];
require __DIR__ . '/../app/Views/admin/dashboard.php';
$adminHtml = ob_get_clean();
echo "Admin Dashboard Render: OK (" . strlen($adminHtml) . " bytes)\n";

// Test room/show view
ob_start();
$room = $db->query("SELECT r.*, b.name as building_name FROM rooms r JOIN buildings b ON r.building_id = b.id WHERE r.id = 1")->fetch();
$statusInfo = [
    'statusText' => 'Tersedia',
    'badgeClass' => 'badge-success',
    'timeKeterangan' => 'Kosong hingga 13.00 WIB',
    'details' => 'Siap untuk peminjaman',
    'can_report_absent' => false
];
$schedules = [];
require __DIR__ . '/../app/Views/room/show.php';
$roomShowHtml = ob_get_clean();
echo "Room Show Render: OK (" . strlen($roomShowHtml) . " bytes)\n";

echo "ALL STITCH VIEWS RENDERED SUCCESSFULLY WITHOUT ERROR!\n";
