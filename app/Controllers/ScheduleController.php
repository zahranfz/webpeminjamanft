<?php
namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Security;
use App\Support\AuditHelper;
use App\Services\NotificationService;
use PDO;

class ScheduleController {

    private function checkAdmin(): void {
        if (!Auth::check() || !Auth::hasRole(['manager', 'superadmin'])) {
            $_SESSION['flash_error'] = 'Akses ditolak. Khusus pengelola.';
            header("Location: /webpinjamft/public/");
            exit;
        }
    }

    /**
     * FR-054: Daftar Jadwal Kuliah Manual
     */
    public function index() {
        $this->checkAdmin();
        $title = "Kelola Jadwal Kuliah Manual (FR-054)";
        $activeMenu = "admin_schedules";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();

        $semFilter = (int)($_GET['semester_id'] ?? 0);
        $roomFilter = (int)($_GET['room_id'] ?? 0);
        $dayFilter = (int)($_GET['weekday'] ?? 0);
        $search = trim($_GET['search'] ?? '');

        // Dapatkan semester aktif sebagai default jika tidak dipilih
        if ($semFilter <= 0) {
            $stmtDefSem = $db->query("SELECT id FROM semesters WHERE is_active = 1 LIMIT 1");
            $semFilter = (int)$stmtDefSem->fetchColumn();
            if ($semFilter <= 0) {
                $semFilter = (int)$db->query("SELECT id FROM semesters ORDER BY id DESC LIMIT 1")->fetchColumn();
            }
        }

        $semesters = $db->query("SELECT * FROM semesters ORDER BY is_active DESC, start_date DESC")->fetchAll();
        $rooms = $db->query("SELECT r.*, b.name as building_name FROM rooms r JOIN buildings b ON r.building_id = b.id ORDER BY b.name ASC, r.code ASC")->fetchAll();

        $sql = "
            SELECT cs.*, r.code as room_code, r.name as room_name, b.name as building_name, sem.name as semester_name,
                   (SELECT COUNT(*) FROM class_sessions WHERE schedule_id = cs.id) as session_count
            FROM class_schedules cs
            JOIN rooms r ON cs.room_id = r.id
            JOIN buildings b ON r.building_id = b.id
            JOIN semesters sem ON cs.semester_id = sem.id
            WHERE 1=1
        ";
        $params = [];

        if ($semFilter > 0) {
            $sql .= " AND cs.semester_id = ?";
            $params[] = $semFilter;
        }

        if ($roomFilter > 0) {
            $sql .= " AND cs.room_id = ?";
            $params[] = $roomFilter;
        }

        if ($dayFilter > 0) {
            $sql .= " AND cs.weekday = ?";
            $params[] = $dayFilter;
        }

        if ($search !== '') {
            $sql .= " AND (cs.course_name LIKE ? OR cs.course_code LIKE ? OR cs.lecturer_name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY cs.weekday ASC, cs.start_time ASC, r.code ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $schedules = $stmt->fetchAll();

        ob_start();
        require __DIR__ . '/../Views/admin/schedules/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-054: Form Tambah Jadwal Manual
     */
    public function create() {
        $this->checkAdmin();
        $title = "Tambah Jadwal Kuliah Manual (FR-054)";
        $activeMenu = "admin_schedules";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();
        $semesters = $db->query("SELECT * FROM semesters ORDER BY is_active DESC, start_date DESC")->fetchAll();
        $rooms = $db->query("SELECT r.*, b.name as building_name FROM rooms r JOIN buildings b ON r.building_id = b.id WHERE r.is_active = 1 ORDER BY b.name ASC, r.code ASC")->fetchAll();
        $lecturers = $db->query("SELECT id, name, identity_number, email FROM users WHERE role = 'lecturer' AND status = 'active' ORDER BY name ASC")->fetchAll();

        $schedule = null;

        ob_start();
        require __DIR__ . '/../Views/admin/schedules/form.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-054: Form Edit Jadwal Manual
     */
    public function edit($id) {
        $this->checkAdmin();
        $title = "Edit Jadwal Kuliah (FR-054)";
        $activeMenu = "admin_schedules";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM class_schedules WHERE id = ?");
        $stmt->execute([(int)$id]);
        $schedule = $stmt->fetch();

        if (!$schedule) {
            $_SESSION['flash_error'] = 'Jadwal tidak ditemukan.';
            header("Location: /webpinjamft/public/admin/schedules");
            exit;
        }

        $semesters = $db->query("SELECT * FROM semesters ORDER BY is_active DESC, start_date DESC")->fetchAll();
        $rooms = $db->query("SELECT r.*, b.name as building_name FROM rooms r JOIN buildings b ON r.building_id = b.id ORDER BY b.name ASC, r.code ASC")->fetchAll();
        $lecturers = $db->query("SELECT id, name, identity_number, email FROM users WHERE role = 'lecturer' AND status = 'active' ORDER BY name ASC")->fetchAll();

        ob_start();
        require __DIR__ . '/../Views/admin/schedules/form.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-054: Simpan Jadwal Manual dengan Validasi Bentrok (BR-004)
     * AC 1: Given entri baru valid, Then tersimpan dan divalidasi bentrok seperti pada impor.
     */
    public function save() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/schedules");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $semesterId = (int)($_POST['semester_id'] ?? 0);
        $roomId = (int)($_POST['room_id'] ?? 0);
        $courseCode = trim($_POST['course_code'] ?? '');
        $courseName = trim($_POST['course_name'] ?? '');
        $className = trim($_POST['class_name'] ?? '');
        $lecturerName = trim($_POST['lecturer_name'] ?? '');
        $lecturerUserId = !empty($_POST['lecturer_user_id']) ? (int)$_POST['lecturer_user_id'] : null;
        $weekday = (int)($_POST['weekday'] ?? 1);
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        $attendees = (int)($_POST['attendees'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($semesterId <= 0 || $roomId <= 0 || $courseCode === '' || $courseName === '' || $className === '' || $lecturerName === '' || $startTime === '' || $endTime === '') {
            $_SESSION['flash_error'] = 'Seluruh kolom bertanda bintang (*) wajib diisi.';
            $redirect = $id > 0 ? "/webpinjamft/public/admin/schedules/{$id}/edit" : "/webpinjamft/public/admin/schedules/create";
            header("Location: {$redirect}");
            exit;
        }

        if ($startTime >= $endTime) {
            $_SESSION['flash_error'] = 'Jam selesai harus lebih akhir dari jam mulai.';
            $redirect = $id > 0 ? "/webpinjamft/public/admin/schedules/{$id}/edit" : "/webpinjamft/public/admin/schedules/create";
            header("Location: {$redirect}");
            exit;
        }

        $db = Database::getInstance()->getConnection();

        // Validasi bentrok BR-004 (ruangan sama, hari sama, semester sama, jam beririsan)
        // Setengah terbuka: start < other_end AND end > other_start
        $sqlConflict = "
            SELECT cs.*, r.code as room_code 
            FROM class_schedules cs
            JOIN rooms r ON cs.room_id = r.id
            WHERE cs.semester_id = ? AND cs.room_id = ? AND cs.weekday = ? AND cs.is_active = 1
              AND cs.start_time < ? AND cs.end_time > ?
        ";
        $conflictParams = [$semesterId, $roomId, $weekday, $endTime, $startTime];

        if ($id > 0) {
            $sqlConflict .= " AND cs.id != ?";
            $conflictParams[] = $id;
        }

        $stmtConf = $db->prepare($sqlConflict);
        $stmtConf->execute($conflictParams);
        $conflicts = $stmtConf->fetchAll();

        if (!empty($conflicts)) {
            // Cek apakah kelas gabungan (BR-004: mata kuliah sama dan jam sama persis)
            $isCombinedClass = false;
            foreach ($conflicts as $c) {
                if ($c['course_code'] === $courseCode && $c['start_time'] === $startTime && $c['end_time'] === $endTime) {
                    $isCombinedClass = true;
                    break;
                }
            }

            if (!$isCombinedClass) {
                $c = $conflicts[0];
                $_SESSION['flash_error'] = "Bentrok jadwal (BR-004): Ruangan {$c['room_code']} sudah digunakan oleh mata kuliah '{$c['course_name']}' ({$c['class_name']}) pada jam {$c['start_time']}-{$c['end_time']}.";
                $redirect = $id > 0 ? "/webpinjamft/public/admin/schedules/{$id}/edit" : "/webpinjamft/public/admin/schedules/create";
                header("Location: {$redirect}");
                exit;
            }
        }

        // Simpan
        if ($id > 0) {
            $stmtOld = $db->prepare("SELECT * FROM class_schedules WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldData = $stmtOld->fetch();

            $stmt = $db->prepare("
                UPDATE class_schedules 
                SET semester_id = ?, room_id = ?, course_code = ?, course_name = ?, class_name = ?,
                    lecturer_name = ?, lecturer_user_id = ?, weekday = ?, start_time = ?, end_time = ?,
                    attendees = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $semesterId, $roomId, $courseCode, $courseName, $className,
                $lecturerName, $lecturerUserId, $weekday, $startTime, $endTime,
                $attendees, $isActive, $id
            ]);

            AuditHelper::log('schedule_updated', 'class_schedules', $id, $oldData, [
                'course' => "{$courseCode} - {$courseName} ({$className})",
                'room_id' => $roomId, 'weekday' => $weekday, 'time' => "{$startTime}-{$endTime}"
            ]);

            $_SESSION['flash_message'] = "Jadwal '{$courseName} ({$className})' berhasil diperbarui.";
        } else {
            $stmt = $db->prepare("
                INSERT INTO class_schedules (semester_id, room_id, course_code, course_name, class_name, lecturer_name, lecturer_user_id, weekday, start_time, end_time, attendees, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $semesterId, $roomId, $courseCode, $courseName, $className,
                $lecturerName, $lecturerUserId, $weekday, $startTime, $endTime,
                $attendees, $isActive
            ]);
            $newId = (int)$db->lastInsertId();

            AuditHelper::log('schedule_created', 'class_schedules', $newId, null, [
                'course' => "{$courseCode} - {$courseName} ({$className})",
                'room_id' => $roomId, 'weekday' => $weekday, 'time' => "{$startTime}-{$endTime}"
            ]);

            $_SESSION['flash_message'] = "Jadwal baru '{$courseName} ({$className})' berhasil ditambahkan (FR-054).";
        }

        // Cek konflik pasca-penambahan dengan peminjaman approved (FR-045)
        $today = date('Y-m-d');
        $stmtBookConflict = $db->prepare("
            UPDATE bookings 
            SET needs_review = 1 
            WHERE room_id = ? AND status = 'approved' AND booking_date >= ?
              AND WEEKDAY(booking_date) + 1 = ?
              AND start_time < ? AND end_time > ?
        ");
        $stmtBookConflict->execute([$roomId, $today, $weekday, $endTime, $startTime]);
        $affected = $stmtBookConflict->rowCount();

        if ($affected > 0) {
            $_SESSION['flash_message'] .= " Ditemukan {$affected} peminjaman disetujui yang beririsan dan ditandai 'Perlu Ditinjau' (FR-045).";
        }

        header("Location: /webpinjamft/public/admin/schedules?semester_id={$semesterId}");
        exit;
    }

    /**
     * FR-054: Hapus atau Nonaktifkan Jadwal
     */
    public function delete($id) {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/schedules");
            exit;
        }

        $id = (int)$id;
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM class_schedules WHERE id = ?");
        $stmt->execute([$id]);
        $sched = $stmt->fetch();

        if (!$sched) {
            $_SESSION['flash_error'] = 'Jadwal tidak ditemukan.';
            header("Location: /webpinjamft/public/admin/schedules");
            exit;
        }

        // Cek riwayat sesi kehadiran
        $stmtSess = $db->prepare("SELECT COUNT(*) FROM class_sessions WHERE schedule_id = ?");
        $stmtSess->execute([$id]);
        $sessionCount = (int)$stmtSess->fetchColumn();

        if ($sessionCount > 0) {
            // Ada riwayat kehadiran, nonaktifkan saja (BR-016)
            $db->prepare("UPDATE class_schedules SET is_active = 0 WHERE id = ?")->execute([$id]);
            AuditHelper::log('schedule_deactivated', 'class_schedules', $id, $sched, ['is_active' => 0]);
            $_SESSION['flash_message'] = "Jadwal '{$sched['course_name']}' memiliki riwayat presensi ({$sessionCount} sesi) sehingga dinonaktifkan (bukan dihapus permanen, BR-016).";
        } else {
            // Hapus permanen
            $db->prepare("DELETE FROM class_schedules WHERE id = ?")->execute([$id]);
            AuditHelper::log('schedule_deleted', 'class_schedules', $id, $sched, null);
            $_SESSION['flash_message'] = "Jadwal '{$sched['course_name']} ({$sched['class_name']})' berhasil dihapus.";
        }

        header("Location: /webpinjamft/public/admin/schedules?semester_id={$sched['semester_id']}");
        exit;
    }

    /**
     * FR-054: Toggle Status Aktif / Nonaktif
     */
    public function toggle($id) {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/schedules");
            exit;
        }

        $id = (int)$id;
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, course_name, is_active, semester_id FROM class_schedules WHERE id = ?");
        $stmt->execute([$id]);
        $sched = $stmt->fetch();

        if (!$sched) {
            $_SESSION['flash_error'] = 'Jadwal tidak ditemukan.';
            header("Location: /webpinjamft/public/admin/schedules");
            exit;
        }

        $newActive = ((int)$sched['is_active'] === 1) ? 0 : 1;
        $db->prepare("UPDATE class_schedules SET is_active = ? WHERE id = ?")->execute([$newActive, $id]);

        AuditHelper::log('schedule_toggled', 'class_schedules', $id, ['is_active' => $sched['is_active']], ['is_active' => $newActive]);

        $statusText = ($newActive === 1) ? 'diaktifkan' : 'dinonaktifkan';
        $_SESSION['flash_message'] = "Jadwal '{$sched['course_name']}' berhasil {$statusText}.";

        header("Location: /webpinjamft/public/admin/schedules?semester_id={$sched['semester_id']}");
        exit;
    }
}
