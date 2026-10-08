<?php
namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Security;
use App\Support\AuditHelper;
use App\Support\Settings;
use App\Services\BookingConflictService;
use App\Services\FacilityReportService;
use App\Services\AttendanceService;
use PDO;

class AdminController {
    
    private BookingConflictService $bookingService;
    private FacilityReportService $reportService;
    private AttendanceService $attendanceService;

    public function __construct() {
        $this->bookingService = new BookingConflictService();
        $this->reportService = new FacilityReportService();
        $this->attendanceService = new AttendanceService();
    }

    private function checkAdmin() {
        if (!Auth::check() || !Auth::hasRole(['manager', 'superadmin'])) {
            $_SESSION['flash_error'] = 'Akses ditolak. Khusus pengelola / administrator.';
            header("Location: /webpinjamft/public/");
            exit;
        }
    }

    private function checkSuperAdmin() {
        if (!Auth::check() || Auth::user()['role'] !== 'superadmin') {
            $_SESSION['flash_error'] = 'Akses ditolak. Khusus Super Admin (FR-092, FR-093).';
            header("Location: /webpinjamft/public/admin");
            exit;
        }
    }

    /**
     * FR-091: Dashboard Pengelola dengan 6 Kartu Metrik Utama
     */
    public function dashboard() {
        $this->checkAdmin();
        $title = "Dashboard Pengelola";
        $activeMenu = "admin_dashboard";
        $baseUrl = '/webpinjamft/public';
        
        $db = Database::getInstance()->getConnection();
        
        $stats = [];
        
        // 1. Permohonan Menunggu (submitted)
        $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'submitted'");
        $stats['pending_bookings'] = (int) $stmt->fetchColumn();
        
        // 2. Permohonan Bersaing (competing submitted bookings)
        $stmtComp = $db->query("
            SELECT COUNT(DISTINCT b1.id) 
            FROM bookings b1
            JOIN bookings b2 ON b1.room_id = b2.room_id 
                            AND b1.booking_date = b2.booking_date 
                            AND b1.id != b2.id
                            AND b1.status = 'submitted' 
                            AND b2.status = 'submitted'
                            AND b1.start_time < b2.end_time 
                            AND b1.end_time > b2.start_time
        ");
        $stats['competing_bookings'] = (int) $stmtComp->fetchColumn();

        // 3. Laporan Fasilitas Baru (status = 'new')
        $stmt = $db->query("SELECT COUNT(*) FROM facility_reports WHERE status = 'new'");
        $stats['new_reports'] = (int) $stmt->fetchColumn();
        
        // 4. Sesi Terlapor Belum Diverifikasi (sesi dengan laporan ketidakhadiran)
        $todayDate = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT schedule_id) 
            FROM absence_reports 
            WHERE session_date = ? AND status = 'active'
        ");
        $stmt->execute([$todayDate]);
        $stats['unverified_sessions'] = (int) $stmt->fetchColumn();

        // 5. Peminjaman Perlu Ditinjau (needs_review = 1)
        $stmt = $db->query("SELECT COUNT(*) FROM bookings WHERE needs_review = 1");
        $stats['review_bookings'] = (int) $stmt->fetchColumn();

        // 6. Ruangan Nonaktif (is_active = 0)
        $stmt = $db->query("SELECT COUNT(*) FROM rooms WHERE is_active = 0");
        $stats['inactive_rooms'] = (int) $stmt->fetchColumn();

        // Dosen pending approval
        $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'lecturer' AND status = 'pending_approval'");
        $stats['pending_lecturers'] = (int) $stmt->fetchColumn();

        // Stitch Screen 5: Fetch active pending bookings with user info
        $stmtBookings = $db->query("
            SELECT b.*, r.name as room_name, r.code as room_code, u.name as user_name, u.role as user_role, u.identity_number 
            FROM bookings b 
            JOIN rooms r ON b.room_id = r.id 
            JOIN users u ON b.user_id = u.id 
            WHERE b.status = 'submitted'
            ORDER BY CASE WHEN u.role = 'lecturer' THEN 0 ELSE 1 END, b.created_at ASC
            LIMIT 10
        ");
        $pendingBookings = $stmtBookings->fetchAll();

        // Stitch Screen 5: Fetch recent facility reports
        $stmtReports = $db->query("
            SELECT fr.*, r.name as room_name, r.code as room_code, u.name as reporter_name 
            FROM facility_reports fr
            JOIN rooms r ON fr.room_id = r.id
            JOIN users u ON fr.user_id = u.id
            ORDER BY fr.created_at DESC
            LIMIT 5
        ");
        $recentReports = $stmtReports->fetchAll();
        
        ob_start();
        require __DIR__ . '/../Views/admin/dashboard.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout/main.php';
    }


    /**
     * FR-072: Antrean Peminjaman dengan Prioritas Dosen & Penanda Bersaing
     */
    public function bookings() {
        $this->checkAdmin();
        $title = "Antrean & Kelola Peminjaman (FR-072)";
        $activeMenu = "admin_bookings";
        $baseUrl = '/webpinjamft/public';
        
        $db = Database::getInstance()->getConnection();
        
        // Query bookings: submitted first (lecturer before student, then FIFO), then others
        $stmt = $db->query("
            SELECT b.*, r.name as room_name, r.code as room_code, u.name as user_name, u.role as user_role, u.identity_number 
            FROM bookings b 
            JOIN rooms r ON b.room_id = r.id 
            JOIN users u ON b.user_id = u.id 
            ORDER BY 
                FIELD(b.status, 'submitted', 'approved', 'rejected', 'cancelled', 'displaced'),
                CASE WHEN b.status = 'submitted' AND u.role = 'lecturer' THEN 0 ELSE 1 END,
                b.booking_date ASC,
                b.start_time ASC
        ");
        $rawBookings = $stmt->fetchAll();

        // Check competing bookings in PHP for fast cross-comparison
        $bookings = [];
        $submittedBookings = array_filter($rawBookings, fn($item) => $item['status'] === 'submitted');

        foreach ($rawBookings as $b) {
            $isCompeting = false;
            if ($b['status'] === 'submitted') {
                foreach ($submittedBookings as $other) {
                    if ((int)$other['id'] !== (int)$b['id'] && (int)$other['room_id'] === (int)$b['room_id'] && $other['booking_date'] === $b['booking_date']) {
                        if ($b['start_time'] < $other['end_time'] && $b['end_time'] > $other['start_time']) {
                            $isCompeting = true;
                            break;
                        }
                    }
                }
            }
            $b['is_competing'] = $isCompeting;
            $bookings[] = $b;
        }
        
        ob_start();
        require __DIR__ . '/../Views/admin/bookings.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout/main.php';
    }

    public function processBooking() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/bookings");
            exit;
        }
        
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $notes = trim($_POST['decision_notes'] ?? '');
        $managerId = Auth::id();
        
        if ($action === 'approved') {
            $result = $this->bookingService->approveBooking($bookingId, $managerId, $notes, true);
        } elseif ($action === 'rejected') {
            $result = $this->bookingService->rejectBooking($bookingId, $managerId, $notes ?: 'Ditolak oleh pengelola');
        } elseif ($action === 'displaced') {
            $result = $this->bookingService->displaceBooking($bookingId, $managerId, $notes ?: 'Digeser demi kebutuhan prioritas dosen (BR-011)');
        } else {
            $_SESSION['flash_error'] = 'Aksi tidak valid.';
            header("Location: /webpinjamft/public/admin/bookings");
            exit;
        }
        
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        
        header("Location: /webpinjamft/public/admin/bookings");
        exit;
    }

    /**
     * FR-084: Kelola Laporan Fasilitas
     */
    public function reports() {
        $this->checkAdmin();
        $title = "Kelola Laporan Fasilitas (FR-084)";
        $activeMenu = "admin_reports";
        $baseUrl = '/webpinjamft/public';
        
        $db = Database::getInstance()->getConnection();
        
        $statusFilter = $_GET['status'] ?? '';
        $sql = "
            SELECT fr.*, r.name as room_name, r.code as room_code, u.name as reporter_name,
                   (SELECT COUNT(*) FROM facility_report_supports WHERE report_id = fr.id) as supports_count
            FROM facility_reports fr 
            JOIN rooms r ON fr.room_id = r.id 
            JOIN users u ON fr.user_id = u.id 
        ";
        $params = [];
        if (!empty($statusFilter)) {
            $sql .= " WHERE fr.status = ?";
            $params[] = $statusFilter;
        }
        $sql .= " ORDER BY FIELD(fr.status, 'new', 'verified', 'in_progress', 'resolved', 'rejected'), FIELD(fr.urgency, 'urgent', 'normal'), fr.created_at ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll();
        
        ob_start();
        require __DIR__ . '/../Views/admin/reports.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout/main.php';
    }

    public function processReport() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/reports");
            exit;
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $note = trim($_POST['manager_notes'] ?? '');
        
        $result = $this->reportService->updateStatus($reportId, Auth::id(), $status, $note);

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        
        header("Location: /webpinjamft/public/admin/reports");
        exit;
    }

    /**
     * FR-086: Ekspor Laporan Fasilitas Terfilter ke CSV
     * AC 1: Given filter aktif, Then CSV hanya berisi baris terfilter;
     * sel yang diawali = + - @ diawali tanda kutip untuk mencegah injeksi rumus.
     */
    public function exportReports() {
        $this->checkAdmin();
        $db = Database::getInstance()->getConnection();

        $statusFilter = $_GET['status'] ?? '';
        $sql = "
            SELECT fr.*, r.name as room_name, r.code as room_code, b.name as building_name, u.name as reporter_name, u.email as reporter_email,
                   (SELECT COUNT(*) FROM facility_report_supports WHERE report_id = fr.id) as supports_count
            FROM facility_reports fr 
            JOIN rooms r ON fr.room_id = r.id 
            JOIN buildings b ON r.building_id = b.id
            JOIN users u ON fr.user_id = u.id 
        ";
        $params = [];
        if (!empty($statusFilter)) {
            $sql .= " WHERE fr.status = ?";
            $params[] = $statusFilter;
        }
        $sql .= " ORDER BY fr.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=laporan_fasilitas_' . date('Ymd_His') . '.csv');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM
        fputs($output, "\xEF\xBB\xBF");

        // CSV Header
        fputcsv($output, [
            'ID Laporan', 'Tanggal Lapor', 'Ruangan', 'Gedung', 'Kategori',
            'Fasilitas', 'Deskripsi Masalah', 'Tingkat Urgensi', 'Status',
            'Pelapor', 'Email Pelapor', 'Jumlah Dukungan', 'Catatan Pengelola'
        ]);

        $sanitize = function($val) {
            $str = (string)($val ?? '');
            if (preg_match('/^[=\+\-@\t\r]/', $str)) {
                $str = "'" . $str;
            }
            return $str;
        };

        foreach ($reports as $r) {
            fputcsv($output, [
                $r['id'],
                $sanitize($r['created_at']),
                $sanitize($r['room_name'] . ' (' . $r['room_code'] . ')'),
                $sanitize($r['building_name']),
                $sanitize($r['category']),
                $sanitize($r['facility_type'] ?: 'Lainnya: ' . $r['facility_custom']),
                $sanitize($r['description']),
                $sanitize($r['urgency']),
                $sanitize($r['status']),
                $sanitize($r['reporter_name']),
                $sanitize($r['reporter_email']),
                (int)$r['supports_count'],
                $sanitize($r['manager_notes'])
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * FR-065: Verifikasi Sesi Terlapor & Kehadiran Kelas
     */
    public function sessions() {
        $this->checkAdmin();
        $title = "Verifikasi Kehadiran Kelas (FR-065)";
        $activeMenu = "admin_sessions";
        $baseUrl = '/webpinjamft/public';

        $sessions = $this->attendanceService->getPendingSessionsForManager();

        ob_start();
        require __DIR__ . '/../Views/admin/sessions.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    public function verifySession() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/sessions");
            exit;
        }

        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        $sessionDate = trim($_POST['session_date'] ?? date('Y-m-d'));
        $decision = $_POST['decision'] ?? '';
        $note = trim($_POST['note'] ?? '');

        $result = $this->attendanceService->managerVerifySession($scheduleId, $sessionDate, Auth::user(), $decision, $note);

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header("Location: /webpinjamft/public/admin/sessions");
        exit;
    }

    /**
     * FR-006: Verifikasi Dosen & Kelola Pengguna
     */
    public function lecturers() {
        $this->checkAdmin();
        $title = "Verifikasi Akun Dosen (FR-006)";
        $activeMenu = "admin_lecturers";
        $baseUrl = '/webpinjamft/public';
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM users WHERE role = 'lecturer' ORDER BY FIELD(status, 'pending_approval', 'active', 'disabled'), created_at DESC");
        $lecturers = $stmt->fetchAll();
        
        ob_start();
        require __DIR__ . '/../Views/admin/lecturers.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout/main.php';
    }

    public function processLecturer() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/lecturers");
            exit;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $identityNumber = trim($_POST['identity_number'] ?? '');
        
        $db = Database::getInstance()->getConnection();
        if ($action === 'active') {
            $stmt = $db->prepare("UPDATE users SET status = 'active', identity_number = COALESCE(NULLIF(?, ''), identity_number) WHERE id = ? AND role = 'lecturer'");
            $stmt->execute([$identityNumber, $userId]);
            
            // Link schedules matching identity_number or name
            $stmtLink = $db->prepare("UPDATE class_schedules SET lecturer_user_id = ? WHERE lecturer_ref = ? AND lecturer_ref != ''");
            $stmtLink->execute([$userId, $identityNumber]);

            AuditHelper::log('lecturer_approved', 'users', (string)$userId);
            \App\Services\NotificationService::queue($userId, 'lecturer_approved', 'Akun Dosen Disetujui', 'Akun dosen Anda telah diverifikasi dan aktif. Anda kini dapat melakukan check-in kelas dan peminjaman dengan prioritas.');

            $_SESSION['flash_message'] = "Akun dosen berhasil diverifikasi dan diaktifkan.";
        } elseif ($action === 'disabled') {
            $stmt = $db->prepare("UPDATE users SET status = 'disabled' WHERE id = ?");
            $stmt->execute([$userId]);
            AuditHelper::log('user_disabled', 'users', (string)$userId);
            $_SESSION['flash_message'] = "Akun berhasil dinonaktifkan.";
        }
        
        header("Location: /webpinjamft/public/admin/lecturers");
        exit;
    }

    /**
     * FR-092: Audit Log Viewer (Super Admin)
     */
    public function audit() {
        $this->checkSuperAdmin();
        $title = "Audit Logs (FR-092)";
        $activeMenu = "admin_audit";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();
        
        $entityFilter = $_GET['entity'] ?? '';
        $actionFilter = $_GET['action'] ?? '';

        $sql = "
            SELECT a.*, u.name as actor_name, u.email as actor_email, u.role as actor_role 
            FROM audit_logs a 
            LEFT JOIN users u ON a.user_id = u.id 
            WHERE 1=1
        ";
        $params = [];
        if (!empty($entityFilter)) {
            $sql .= " AND a.entity_type = ?";
            $params[] = $entityFilter;
        }
        if (!empty($actionFilter)) {
            $sql .= " AND a.action = ?";
            $params[] = $actionFilter;
        }
        $sql .= " ORDER BY a.created_at DESC LIMIT 100";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        ob_start();
        require __DIR__ . '/../Views/admin/audit.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-093: Pengaturan Sistem (Super Admin)
     */
    public function settings() {
        $this->checkSuperAdmin();
        $title = "Pengaturan Sistem (FR-093)";
        $activeMenu = "admin_settings";
        $baseUrl = '/webpinjamft/public';

        $settings = Settings::all();

        ob_start();
        require __DIR__ . '/../Views/admin/settings.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    public function updateSettings() {
        $this->checkSuperAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/settings");
            exit;
        }

        $postedSettings = $_POST['settings'] ?? [];
        $db = Database::getInstance()->getConnection();

        $updatedCount = 0;
        foreach ($postedSettings as $key => $val) {
            $oldVal = Settings::get($key);
            if ($oldVal !== $val) {
                Settings::set($key, trim($val));
                AuditHelper::log('setting_updated', 'settings', $key, $oldVal, trim($val));
                $updatedCount++;
            }
        }

        $_SESSION['flash_message'] = "Berhasil memperbarui {$updatedCount} parameter sistem.";
        header("Location: /webpinjamft/public/admin/settings");
        exit;
    }
}
