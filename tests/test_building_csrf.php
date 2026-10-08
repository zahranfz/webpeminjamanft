<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Support/Security.php';
require_once __DIR__ . '/../app/Support/Database.php';
require_once __DIR__ . '/../app/Controllers/MasterDataController.php';

use App\Support\Security;

session_start();

// 1. Generate CSRF token
$token = Security::generateCsrfToken();
echo "Generated token: " . substr($token, 0, 10) . "...\n";

// 2. Test verifyCsrfToken with matching token
$isValid = Security::verifyCsrfToken($token);
echo "Verify matching token: " . ($isValid ? "TRUE (PASS)" : "FALSE (FAIL)") . "\n";
assert($isValid === true, "Valid token must return true");

// 3. Test verifyCsrfToken with invalid token
$isInvalid = Security::verifyCsrfToken('bogus_token');
echo "Verify bogus token: " . ($isInvalid ? "TRUE (FAIL)" : "FALSE (PASS)") . "\n";
assert($isInvalid === false, "Bogus token must return false");

// 4. Test verifyCsrfToken with empty token
$isEmpty = Security::verifyCsrfToken('');
echo "Verify empty token: " . ($isEmpty ? "TRUE (FAIL)" : "FALSE (PASS)") . "\n";
assert($isEmpty === false, "Empty token must return false");

echo "\nAll CSRF verification tests PASSED!\n";
