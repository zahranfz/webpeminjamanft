<?php
require_once __DIR__ . '/../app/Support/Database.php';

use App\Support\Database;

$db = Database::getInstance()->getConnection();

// Create or verify an admin user session
$stmt = $db->query("SELECT id, name, email, role FROM users WHERE role = 'manager' LIMIT 1");
$adminUser = $stmt->fetch();

if (!$adminUser) {
    echo "No manager user found in database.\n";
    exit(1);
}

// Generate a session by calling login or setting session cookie
$sessionFile = __DIR__ . '/admin_cookie.txt';
if (file_exists($sessionFile)) unlink($sessionFile);

// Login as manager
$postFields = http_build_query([
    'email' => $adminUser['email'],
    'password' => 'password123',
    'csrf_token' => '' // We can get csrf token first
]);

// Step 1: GET /login to get cookie and CSRF token
$ch = curl_init("http://localhost:8000/webpinjamft/public/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $sessionFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $sessionFile);
$loginPage = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage, $matches);
$csrfToken = $matches[1] ?? '';

// Step 2: POST /login
$ch = curl_init("http://localhost:8000/webpinjamft/public/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'email' => $adminUser['email'],
    'password' => 'password',
    'csrf_token' => $csrfToken
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, $sessionFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $sessionFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$afterLogin = curl_exec($ch);
curl_close($ch);

// Step 3: Test Admin Master Data endpoints
$adminUrls = [
    '/admin/rooms',
    '/admin/rooms/create',
    '/admin/buildings',
    '/admin/terms',
    '/admin/bookings',
    '/admin/reports',
    '/admin/sessions',
    '/admin/lecturers'
];

echo "=== PENGUJIAN AKSES HALAMAN ADMIN / PENGELOLA ===\n";
foreach ($adminUrls as $path) {
    $url = "http://localhost:8000/webpinjamft/public" . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $sessionFile);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $status = ($httpCode === 200) ? "[PASS]" : "[FAIL - HTTP $httpCode]";
    echo "{$status} {$path} (HTTP {$httpCode})\n";
}

if (file_exists($sessionFile)) unlink($sessionFile);
