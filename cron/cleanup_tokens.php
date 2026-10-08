<?php
/**
 * Cron: Cleanup Expired Tokens (BR-018)
 * Run daily or hourly via scheduler
 */

require_once __DIR__ . '/../app/Support/Database.php';

use App\Support\Database;

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("DELETE FROM user_tokens WHERE expires_at < NOW()");
$stmt->execute();
$deleted = $stmt->rowCount();

echo "[" . date('Y-m-d H:i:s') . "] Berhasil membersihkan {$deleted} token kedaluwarsa.\n";
