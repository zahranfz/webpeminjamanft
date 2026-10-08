<?php
namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Security;
use App\Support\AuditHelper;
use PDO;

class AuthController {
    
    public function showLoginForm() {
        if (Auth::check()) {
            header("Location: /webpinjamft/public/");
            exit;
        }
        $title = "Login";
        $baseUrl = '/webpinjamft/public';
        
        ob_start();
        require __DIR__ . '/../Views/auth/login.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout/main.php';
    }

    public function login() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $result = Auth::attempt($email, $password);
        
        if ($result['success']) {
            AuditHelper::log('login', 'user', Auth::id());
            header("Location: /webpinjamft/public/");
            exit;
        } else {
            $_SESSION['flash_error'] = $result['message'];
            header("Location: /webpinjamft/public/login");
            exit;
        }
    }

    public function logout() {
        AuditHelper::log('logout', 'user', Auth::id());
        Auth::logout();
        header("Location: /webpinjamft/public/login");
        exit;
    }

    /**
     * FR-007: Halaman Portal SSO Unsoed (Pluggable SSO)
     */
    public function showSsoPage() {
        if (Auth::check()) {
            header("Location: /webpinjamft/public/");
            exit;
        }
        $title = "Login SSO Unsoed (FR-007)";
        $baseUrl = '/webpinjamft/public';

        ob_start();
        require __DIR__ . '/../Views/auth/sso.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-007: Proses Autentikasi SSO Unsoed
     */
    public function processSso() {
        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/auth/sso");
            exit;
        }

        $email = strtolower(trim($_POST['email'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $identityNumber = trim($_POST['identity_number'] ?? '');

        $provider = new \App\Services\Auth\UnsoedSSOProvider();
        $result = $provider->authenticate([
            'email' => $email,
            'name' => $name,
            'identity_number' => $identityNumber
        ]);

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            header("Location: /webpinjamft/public/");
            exit;
        } else {
            $_SESSION['flash_error'] = $result['message'];
            header("Location: /webpinjamft/public/auth/sso");
            exit;
        }
    }

    public function showRegisterForm() {
        if (Auth::check()) {
            header("Location: /webpinjamft/public/");
            exit;
        }
        $title = "Daftar Akun";
        $baseUrl = '/webpinjamft/public';
        
        ob_start();
        require __DIR__ . '/../Views/auth/register.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout/main.php';
    }

    public function register() {
        $name = $_POST['name'] ?? '';
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $identity = $_POST['identity_number'] ?? '';
        $role = $_POST['role'] ?? 'student'; // Assume student default
        
        if (!in_array($role, ['student', 'lecturer'])) {
            $role = 'student';
        }
        
        // Validation (FR-001)
        if (!preg_match('/@([a-z0-9]+\.)?unsoed\.ac\.id$/i', $email)) {
            $_SESSION['flash_error'] = 'Gunakan email resmi Unsoed (@unsoed.ac.id atau subdomainnya).';
            header("Location: /webpinjamft/public/register");
            exit;
        }
        
        $db = Database::getInstance()->getConnection();
        
        // Check existing
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['flash_error'] = 'Pendaftaran gagal.'; // Tidak membocorkan peran akun yang ada
            header("Location: /webpinjamft/public/register");
            exit;
        }
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role, identity_number, status) VALUES (?, ?, ?, ?, ?, 'pending_email')");
            $stmt->execute([$name, $email, $hash, $role, $identity]);
            
            $userId = $db->lastInsertId();
            
            // Create verify token (FR-002)
            $token = bin2hex(random_bytes(32));
            $tokenHash = password_hash($token, PASSWORD_DEFAULT);
            $expires = date('Y-m-d H:i:s', time() + 86400); // 24 hours
            
            $stmt = $db->prepare("INSERT INTO user_tokens (user_id, token_hash, type, expires_at) VALUES (?, ?, 'email_verify', ?)");
            $stmt->execute([$userId, $tokenHash, $expires]);
            
            // Mock email sending: Queue it (M1 antrean email)
            // For now, we will just simulate it by putting the link in flash message for development
            
            $_SESSION['flash_message'] = "Pendaftaran berhasil! Cek email Anda untuk link verifikasi. (DEV: <a href='/webpinjamft/public/verify?t={$token}&u={$userId}'>Link Verifikasi</a>)";
            header("Location: /webpinjamft/public/login");
            exit;
            
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'Terjadi kesalahan sistem.';
            header("Location: /webpinjamft/public/register");
            exit;
        }
    }

    public function verifyEmail() {
        $token = $_GET['t'] ?? '';
        $userId = $_GET['u'] ?? '';
        
        $db = Database::getInstance()->getConnection();
        
        // Find valid token
        $stmt = $db->prepare("SELECT * FROM user_tokens WHERE user_id = ? AND type = 'email_verify' AND expires_at > NOW()");
        $stmt->execute([$userId]);
        $tokens = $stmt->fetchAll();
        
        $validTokenFound = false;
        foreach ($tokens as $t) {
            if (password_verify($token, $t['token_hash'])) {
                $validTokenFound = true;
                // Delete used token
                $db->prepare("DELETE FROM user_tokens WHERE id = ?")->execute([$t['id']]);
                break;
            }
        }
        
        if ($validTokenFound) {
            // Update user status
            $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            $newStatus = ($user['role'] === 'student') ? 'active' : 'pending_approval';
            
            $stmt = $db->prepare("UPDATE users SET status = ?, email_verified_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            
            AuditHelper::log('email_verified', 'user', $userId);
            
            $_SESSION['flash_message'] = "Email berhasil diverifikasi! " . ($newStatus === 'pending_approval' ? "Akun dosen Anda menunggu persetujuan pengelola." : "Silakan login.");
        } else {
            $_SESSION['flash_error'] = "Link verifikasi tidak valid atau sudah kedaluwarsa.";
        }
        
        header("Location: /webpinjamft/public/login");
        exit;
    }

    public function forgotPassword() {
        $title = "Lupa Kata Sandi";
        $baseUrl = '/webpinjamft/public';
        
        ob_start();
        require __DIR__ . '/../Views/auth/forgot_password.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout/main.php';
    }

    public function sendResetLink() {
        $email = $_POST['email'] ?? '';
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $hash = password_hash($token, PASSWORD_DEFAULT);
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $db->prepare("INSERT INTO user_tokens (user_id, token_hash, type, expires_at) VALUES (?, ?, 'password_reset', ?)");
            $stmt->execute([$user['id'], $hash, $expires]);
            
            // In MVP, we just display the link
            $resetLink = "/webpinjamft/public/reset-password?t=" . $token . "&u=" . $user['id'];
            $_SESSION['flash_message'] = "Link reset password (SIMULASI): <a href='{$resetLink}'>Klik di sini</a>";
        } else {
            $_SESSION['flash_message'] = "Jika email terdaftar, instruksi reset akan dikirim.";
        }
        
        header("Location: /webpinjamft/public/forgot-password");
        exit;
    }

    public function resetPasswordForm() {
        $token = $_GET['t'] ?? '';
        $userId = $_GET['u'] ?? '';
        $title = "Reset Kata Sandi";
        $baseUrl = '/webpinjamft/public';
        
        ob_start();
        require __DIR__ . '/../Views/auth/reset_password.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout/main.php';
    }

    public function resetPassword() {
        $token = $_POST['token'] ?? '';
        $userId = $_POST['user_id'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM user_tokens WHERE user_id = ? AND type = 'password_reset' AND expires_at > NOW()");
        $stmt->execute([$userId]);
        $tokens = $stmt->fetchAll();
        
        $validTokenId = null;
        foreach ($tokens as $t) {
            if (password_verify($token, $t['token_hash'])) {
                $validTokenId = $t['id'];
                break;
            }
        }
        
        if ($validTokenId) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $userId]);
            $db->prepare("DELETE FROM user_tokens WHERE id = ?")->execute([$validTokenId]);
            
            AuditHelper::log('password_reset', 'user', $userId);
            $_SESSION['flash_message'] = "Kata sandi berhasil direset. Silakan login.";
            header("Location: /webpinjamft/public/login");
        } else {
            $_SESSION['flash_error'] = "Token reset tidak valid atau kedaluwarsa.";
            header("Location: /webpinjamft/public/forgot-password");
        }
        exit;
    }

    /**
     * Profil Pengguna & Ubah Kata Sandi (FR-023 / Akun)
     */
    public function profile() {
        if (!Auth::check()) {
            header("Location: /webpinjamft/public/login");
            exit;
        }

        $title = "Profil Pengguna";
        $activeMenu = "profile";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch();

        ob_start();
        require __DIR__ . '/../Views/auth/profile.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    public function updatePassword() {
        if (!Auth::check()) {
            header("Location: /webpinjamft/public/login");
            exit;
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/profile");
            exit;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $userId = Auth::id();
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $_SESSION['flash_error'] = 'Kata sandi saat ini tidak cocok.';
            header("Location: /webpinjamft/public/profile");
            exit;
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['flash_error'] = 'Kata sandi baru minimal 8 karakter (BR-014).';
            header("Location: /webpinjamft/public/profile");
            exit;
        }

        if ($newPassword === $user['email']) {
            $_SESSION['flash_error'] = 'Kata sandi baru tidak boleh sama dengan alamat email (BR-014).';
            header("Location: /webpinjamft/public/profile");
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['flash_error'] = 'Konfirmasi kata sandi baru tidak cocok.';
            header("Location: /webpinjamft/public/profile");
            exit;
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmtUp = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmtUp->execute([$newHash, $userId]);

        AuditHelper::log('password_changed', 'users', (string)$userId);
        $_SESSION['flash_message'] = 'Kata sandi berhasil diperbarui.';
        header("Location: /webpinjamft/public/profile");
        exit;
    }
}
