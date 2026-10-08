<?php
namespace App\Support;

use App\Support\Database;
use App\Support\Auth;
use PDO;

class AuditHelper {
    /**
     * Log an action to the audit_logs table (FR-092).
     */
    public static function log($action, $entityType = null, $entityId = null, $oldValues = null, $newValues = null) {
        $db = Database::getInstance()->getConnection();
        
        $userId = Auth::id(); // null if guest
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $oldJson = $oldValues ? json_encode($oldValues) : null;
        $newJson = $newValues ? json_encode($newValues) : null;
        
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $action, $entityType, $entityId, $oldJson, $newJson, $ip]);
    }
}
