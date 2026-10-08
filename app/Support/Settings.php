<?php
namespace App\Support;

use PDO;

class Settings {
    private static array $cache = [];

    /**
     * Get setting value by key, with optional default fallback.
     */
    public static function get(string $key, $default = null) {
        if (empty(self::$cache)) {
            self::loadAll();
        }

        return self::$cache[$key] ?? $default;
    }

    /**
     * Get integer setting value.
     */
    public static function getInt(string $key, int $default = 0): int {
        return (int) self::get($key, $default);
    }

    /**
     * Get string setting value.
     */
    public static function getString(string $key, string $default = ''): string {
        return (string) self::get($key, $default);
    }

    /**
     * Set/update setting value in database and update cache.
     */
    public static function set(string $key, string $value): bool {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $success = $stmt->execute([$value, $key]);
        if ($success) {
            self::$cache[$key] = $value;
        }
        return $success;
    }

    /**
     * Load all settings into static cache.
     */
    public static function loadAll(): void {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                self::$cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (\Exception $e) {
            self::$cache = [];
        }
    }

    /**
     * Get all settings with descriptions.
     */
    public static function all(): array {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT setting_key, setting_value, description FROM settings ORDER BY setting_key ASC");
        return $stmt->fetchAll();
    }
}
