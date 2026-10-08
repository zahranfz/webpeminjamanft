<?php
namespace App\Services\Auth;

use App\Support\Database;
use App\Support\AuditHelper;
use PDO;

class UnsoedSSOProvider implements SSOProviderInterface {

    public function getLoginUrl(): string {
        return '/webpinjamft/public/auth/sso/login';
    }

    /**
     * FR-007: Autentikasi identitas SSO Unsoed.
     * AC 1: Given SSO mengembalikan identitas dengan email terdaftar, When login, Then akun lokal tertaut dan sesi dibuat.
     * AC 2: Given identitas SSO belum punya akun, Then akun dibuat otomatis dengan peran sesuai BR-013.
     * AC 3: Given SSO tidak tersedia, Then login lokal tetap berfungsi.
     */
    public function authenticate(array $params): array {
        $email = strtolower(trim($params['email'] ?? ''));
        $name = trim($params['name'] ?? '');
        $identityNumber = trim($params['identity_number'] ?? '');

        if (empty($email)) {
            return ['success' => false, 'message' => 'Email dari SSO tidak valid.'];
        }

        // BR-013: Domain validation
        if (!str_ends_with($email, 'unsoed.ac.id')) {
            return ['success' => false, 'message' => 'Email SSO bukan domain resmi Unsoed (unsoed.ac.id).'];
        }

        $db = Database::getInstance()->getConnection();

        // 1. Cek apakah user sudah terdaftar di database lokal
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // AC 1: Akun lokal tertaut, perbarui jika perlu
            if ($user['status'] === 'disabled' || $user['status'] === 'locked') {
                return ['success' => false, 'message' => "Akun berstatus {$user['status']}. Hubungi administrator."];
            }

            // Bersihkan failed login
            $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, email_verified_at = COALESCE(email_verified_at, NOW()) WHERE id = ?")
               ->execute([$user['id']]);

            $this->setSession($user);
            AuditHelper::log('sso_login', 'users', (string)$user['id'], null, ['email' => $email, 'provider' => 'unsoed_sso']);

            return ['success' => true, 'user' => $user, 'message' => 'Berhasil masuk melalui SSO Unsoed.'];
        }

        // AC 2: User belum punya akun, buat otomatis dengan peran sesuai BR-013
        $role = 'student';
        if (str_ends_with($email, '@mhs.unsoed.ac.id')) {
            $role = 'student';
        } elseif (str_ends_with($email, '@unsoed.ac.id')) {
            $role = 'lecturer';
        }

        if (empty($name)) {
            $name = ucwords(str_replace('.', ' ', explode('@', $email)[0]));
        }

        $randomPasswordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

        $stmtInsert = $db->prepare("
            INSERT INTO users (name, email, password_hash, role, identity_number, status, email_verified_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW())
        ");
        $stmtInsert->execute([$name, $email, $randomPasswordHash, $role, $identityNumber ?: null]);
        $newUserId = (int)$db->lastInsertId();

        $newUser = [
            'id' => $newUserId,
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'status' => 'active'
        ];

        $this->setSession($newUser);
        AuditHelper::log('sso_registered', 'users', (string)$newUserId, null, [
            'email' => $email, 'name' => $name, 'role' => $role, 'provider' => 'unsoed_sso'
        ]);

        return ['success' => true, 'user' => $newUser, 'message' => 'Akun baru berhasil dibuat otomatis melalui SSO Unsoed (BR-013).'];
    }

    private function setSession(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['last_activity'] = time();
    }
}
