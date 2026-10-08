<?php
namespace App\Services;

use App\Support\Database;
use PDO;

class NotificationService {

    /**
     * Queue an email notification (FR-090).
     */
    public static function queue(int $userId, string $type, string $subject, string $body): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, type, subject, body, status, attempts, created_at)
                VALUES (?, ?, ?, ?, 'queued', 0, NOW())
            ");
            return $stmt->execute([$userId, $type, $subject, $body]);
        } catch (\Exception $e) {
            error_log("Failed to queue notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify all managers/superadmins.
     */
    public static function notifyManagers(string $type, string $subject, string $body): void {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT id FROM users WHERE role IN ('manager', 'superadmin') AND status = 'active'");
            $managers = $stmt->fetchAll();
            foreach ($managers as $m) {
                self::queue((int)$m['id'], $type, $subject, $body);
            }
        } catch (\Exception $e) {
            error_log("Failed to notify managers: " . $e->getMessage());
        }
    }
}
