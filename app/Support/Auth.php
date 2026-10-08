<?php
namespace App\Support;

use App\Support\Database;
use PDO;

class Auth {
    /**
     * Start secure session (NFR-015).
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 7200, // 2 hours as per FR-005
                'path' => '/',
                'domain' => '',
                'secure' => false, // Set to true if using HTTPS
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }
        
        // Session expiration logic (FR-005)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
            self::logout();
        }
        $_SESSION['last_activity'] = time();
    }

    /**
     * Attempt login.
     */
    public static function attempt($email, $password) {
        $db = Database::getInstance()->getConnection();
        
        // Check user
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email atau kata sandi salah.'];
        }
        
        if ($user['status'] === 'locked' && strtotime($user['locked_until']) > time()) {
            return ['success' => false, 'message' => 'Akun terkunci karena terlalu banyak percobaan. Coba lagi nanti.'];
        }
        
        if (!in_array($user['status'], ['active', 'pending_approval'])) {
             return ['success' => false, 'message' => 'Akun Anda berstatus: ' . $user['status']];
        }

        if (password_verify($password, $user['password_hash'])) {
            // Success
            $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Session fixation protection
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            
            return ['success' => true];
        } else {
            // Failed
            $attempts = $user['failed_login_attempts'] + 1;
            $lockedUntil = null;
            if ($attempts >= 5) { // FR-003
                $lockedUntil = date('Y-m-d H:i:s', time() + 900); // 15 mins
            }
            
            $stmt = $db->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?");
            $stmt->execute([$attempts, $lockedUntil, $user['id']]);
            
            return ['success' => false, 'message' => 'Email atau kata sandi salah.'];
        }
    }

    /**
     * Logout.
     */
    public static function logout() {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    /**
     * Get current user ID.
     */
    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if logged in.
     */
    public static function check() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user array.
     */
    public static function user() {
        if (!self::check()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['user_role'],
            'name' => $_SESSION['user_name']
        ];
    }

    /**
     * Check user role.
     */
    public static function hasRole($roles) {
        if (!self::check()) return false;
        if (!is_array($roles)) $roles = [$roles];
        return in_array($_SESSION['user_role'], $roles);
    }
}
