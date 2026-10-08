<?php
$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$baseUrl = 'http://localhost:8000/webpinjamft/public';

// 1. Get Login Page & CSRF Token
$ch = curl_init("$baseUrl/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m);
$loginCsrf = $m[1] ?? '';

// 2. Perform Login as Manager
$ch = curl_init("$baseUrl/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $loginCsrf,
    'email' => 'bapendik@unsoed.ac.id',
    'password' => 'password'
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);
curl_close($ch);

// 3. Get /admin/buildings Page to retrieve Form CSRF Token
$ch = curl_init("$baseUrl/admin/buildings");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$bldgHtml = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', $bldgHtml, $m);
$bldgCsrf = $m[1] ?? '';
echo "Buildings Form CSRF Token: " . substr($bldgCsrf, 0, 10) . "...\n";

// 4. Submit Add Building Form
$testCode = 'TEST_BLD_' . rand(100, 999);
$testName = 'Gedung Uji Coba ' . rand(100, 999);

$ch = curl_init("$baseUrl/admin/buildings/save");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $bldgCsrf,
    'id' => 0,
    'code' => $testCode,
    'name' => $testName,
    'is_active' => '1'
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$resultHtml = curl_exec($ch);
curl_close($ch);

if (file_exists($cookieFile)) unlink($cookieFile);

// Check if flash error appears or if building was added
if (strpos($resultHtml, 'Token CSRF tidak valid') !== false) {
    echo "FAILED: 'Token CSRF tidak valid' error still encountered!\n";
    exit(1);
}

if (strpos($resultHtml, $testName) !== false || strpos($resultHtml, 'Gedung berhasil disimpan') !== false) {
    echo "SUCCESS: Building '$testName' ($testCode) successfully added via HTTP POST!\n";
    exit(0);
}

echo "Response preview:\n" . substr(strip_tags($resultHtml), 0, 300) . "\n";
