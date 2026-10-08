<?php
namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Security;
use App\Services\AttendanceService;

class ClassController {
    
    private AttendanceService $attendanceService;

    public function __construct() {
        $this->attendanceService = new AttendanceService();
    }

    /**
     * Halaman Kelas Saya (Dosen) - FR-060, FR-061
     */
    public function index() {
        if (!Auth::check() || Auth::user()['role'] !== 'lecturer') {
            $_SESSION['flash_error'] = 'Akses ditolak. Halaman khusus dosen.';
            header("Location: /webpinjamft/public/");
            exit;
        }
        
        $title = "Jadwal Kelas Saya";
        $activeMenu = "classes";
        $baseUrl = '/webpinjamft/public';
        
        $db = Database::getInstance()->getConnection();
        $user = Auth::user();
        
        $today = (int) date('N');
        $todayDate = date('Y-m-d');
        
        // Find lecturer's classes for today
        $stmt = $db->prepare("
            SELECT cs.*, r.name as room_name, r.code as room_code 
            FROM class_schedules cs 
            JOIN rooms r ON cs.room_id = r.id 
            WHERE (cs.lecturer_user_id = ? OR cs.lecturer_ref = ? OR LOWER(cs.lecturer_name) = LOWER(?))
              AND cs.weekday = ? AND cs.is_active = 1
            ORDER BY cs.start_time ASC
        ");
        $stmt->execute([$user['id'], $user['identity_number'] ?? '', $user['name'], $today]);
        $classes = $stmt->fetchAll();
        
        // Fetch sessions for today
        $sessions = [];
        if (!empty($classes)) {
            $classIds = array_column($classes, 'id');
            $inClause = implode(',', array_fill(0, count($classIds), '?'));
            
            $stmtSess = $db->prepare("
                SELECT sess.schedule_id, sess.actual_start_time, sess.status, sess.note, sess.moved_to_room_id,
                       r_target.name as target_room_name, r_target.code as target_room_code
                FROM class_sessions sess
                LEFT JOIN rooms r_target ON sess.moved_to_room_id = r_target.id
                WHERE sess.schedule_id IN ($inClause) AND sess.session_date = ?
            ");
            $params = $classIds;
            $params[] = $todayDate;
            $stmtSess->execute($params);
            
            while ($row = $stmtSess->fetch()) {
                $sessions[$row['schedule_id']] = $row;
            }
        }

        // Fetch active bookable rooms for class room change (FR-068)
        $stmtAllRooms = $db->query("SELECT id, name, code, capacity FROM rooms WHERE is_active = 1 AND can_be_booked = 1 ORDER BY code ASC");
        $allRooms = $stmtAllRooms->fetchAll();
        
        ob_start();
        require __DIR__ . '/../Views/class/index.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * Check-in Dosen (FR-060)
     */
    public function checkin() {
        if (!Auth::check() || Auth::user()['role'] !== 'lecturer') {
            header("Location: /webpinjamft/public/");
            exit;
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/class");
            exit;
        }
        
        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        $result = $this->attendanceService->lecturerCheckin($scheduleId, Auth::user());
        
        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        
        header("Location: /webpinjamft/public/class");
        exit;
    }

    /**
     * Dosen Membatalkan Kelas (FR-061)
     */
    public function cancel() {
        if (!Auth::check() || Auth::user()['role'] !== 'lecturer') {
            header("Location: /webpinjamft/public/");
            exit;
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/class");
            exit;
        }

        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        $sessionDate = trim($_POST['session_date'] ?? date('Y-m-d'));
        $reason = trim($_POST['reason'] ?? '');

        $result = $this->attendanceService->lecturerCancelSession($scheduleId, Auth::user(), $sessionDate, $reason);

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header("Location: /webpinjamft/public/class");
        exit;
    }

    /**
     * Mahasiswa Melaporkan Dosen Belum Hadir (FR-063, FR-064)
     * POST /rooms/{id}/report-absent
     */
    public function reportAbsent($roomId) {
        if (!Auth::check() || Auth::user()['role'] !== 'student') {
            $_SESSION['flash_error'] = 'Hanya mahasiswa terdaftar yang dapat melaporkan kehadiran dosen.';
            header("Location: /webpinjamft/public/room/{$roomId}");
            exit;
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/room/{$roomId}");
            exit;
        }

        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        $result = $this->attendanceService->studentReportAbsent($scheduleId, Auth::user());

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header("Location: /webpinjamft/public/room/{$roomId}");
        exit;
    }

    /**
     * Dosen / Pengelola Memindahkan Ruangan Kelas (FR-068)
     */
    public function move() {
        if (!Auth::check()) {
            header("Location: /webpinjamft/public/login");
            exit;
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/class");
            exit;
        }

        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        $sessionDate = trim($_POST['session_date'] ?? date('Y-m-d'));
        $targetRoomId = (int) ($_POST['target_room_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        $result = $this->attendanceService->moveClassSession($scheduleId, $sessionDate, $targetRoomId, Auth::user(), $reason);

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header("Location: /webpinjamft/public/class");
        exit;
    }
}
