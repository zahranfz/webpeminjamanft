<?php
namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Security;
use App\Support\AuditHelper;
use App\Services\NotificationService;
use PDO;

class MasterDataController {

    private function checkAdmin(): void {
        if (!Auth::check() || !Auth::hasRole(['manager', 'superadmin'])) {
            $_SESSION['flash_error'] = 'Akses ditolak. Khusus pengelola / administrator.';
            header("Location: /webpinjamft/public/");
            exit;
        }
    }

    /* =========================================================================
     * RUANGAN (FR-050, FR-052, BR-016, BR-019)
     * ========================================================================= */

    /**
     * FR-050: Daftar Ruangan
     */
    public function rooms() {
        $this->checkAdmin();
        $title = "Kelola Ruangan (FR-050)";
        $activeMenu = "admin_rooms";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();

        $buildingFilter = (int)($_GET['building_id'] ?? 0);
        $statusFilter = $_GET['status'] ?? '';
        $search = trim($_GET['search'] ?? '');

        // Fetch buildings for filter
        $stmtBldgs = $db->query("SELECT * FROM buildings ORDER BY name ASC");
        $buildings = $stmtBldgs->fetchAll();

        $sql = "
            SELECT r.*, b.name as building_name, b.code as building_code,
                   (SELECT COUNT(*) FROM room_facilities WHERE room_id = r.id) as facility_count,
                   (SELECT COUNT(*) FROM class_schedules WHERE room_id = r.id) as schedule_count,
                   (SELECT COUNT(*) FROM bookings WHERE room_id = r.id) as booking_count
            FROM rooms r
            JOIN buildings b ON r.building_id = b.id
            WHERE 1=1
        ";
        $params = [];

        if ($buildingFilter > 0) {
            $sql .= " AND r.building_id = ?";
            $params[] = $buildingFilter;
        }

        if ($statusFilter === 'active') {
            $sql .= " AND r.is_active = 1";
        } elseif ($statusFilter === 'inactive') {
            $sql .= " AND r.is_active = 0";
        }

        if ($search !== '') {
            $sql .= " AND (r.name LIKE ? OR r.code LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY b.name ASC, r.code ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll();

        ob_start();
        require __DIR__ . '/../Views/admin/rooms/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-050: Form Tambah Ruangan
     */
    public function createRoom() {
        $this->checkAdmin();
        $title = "Tambah Ruangan Baru (FR-050)";
        $activeMenu = "admin_rooms";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();
        $buildings = $db->query("SELECT * FROM buildings WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

        $room = null;

        ob_start();
        require __DIR__ . '/../Views/admin/rooms/form.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-050: Form Edit Ruangan
     */
    public function editRoom($id) {
        $this->checkAdmin();
        $title = "Edit Ruangan (FR-050)";
        $activeMenu = "admin_rooms";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->execute([(int)$id]);
        $room = $stmt->fetch();

        if (!$room) {
            $_SESSION['flash_error'] = 'Ruangan tidak ditemukan.';
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        $buildings = $db->query("SELECT * FROM buildings ORDER BY name ASC")->fetchAll();

        ob_start();
        require __DIR__ . '/../Views/admin/rooms/form.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-050: Simpan Ruangan (Create / Update)
     * AC 1: Given kode ruangan sudah ada, When membuat ruangan, Then ditolak.
     */
    public function saveRoom() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $buildingId = (int)($_POST['building_id'] ?? 0);
        $floor = (int)($_POST['floor'] ?? 1);
        $capacity = (int)($_POST['capacity'] ?? 0);
        $type = $_POST['type'] ?? 'classroom';
        $canBeBooked = isset($_POST['can_be_booked']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($code === '' || $name === '' || $buildingId <= 0 || $capacity <= 0) {
            $_SESSION['flash_error'] = 'Semua field wajib diisi dan kapasitas minimal 1.';
            $redirect = $id > 0 ? "/webpinjamft/public/admin/rooms/{$id}/edit" : "/webpinjamft/public/admin/rooms/create";
            header("Location: {$redirect}");
            exit;
        }

        $db = Database::getInstance()->getConnection();

        // Cek duplikasi kode ruangan (AC 1)
        if ($id > 0) {
            $stmtCheck = $db->prepare("SELECT id FROM rooms WHERE code = ? AND id != ?");
            $stmtCheck->execute([$code, $id]);
        } else {
            $stmtCheck = $db->prepare("SELECT id FROM rooms WHERE code = ?");
            $stmtCheck->execute([$code]);
        }

        if ($stmtCheck->fetchColumn()) {
            $_SESSION['flash_error'] = "Kode ruangan '{$code}' sudah ada dalam sistem. Gunakan kode yang unik (FR-050 AC 1).";
            $redirect = $id > 0 ? "/webpinjamft/public/admin/rooms/{$id}/edit" : "/webpinjamft/public/admin/rooms/create";
            header("Location: {$redirect}");
            exit;
        }

        if ($id > 0) {
            // Update
            $stmtOld = $db->prepare("SELECT * FROM rooms WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldRoom = $stmtOld->fetch();

            $stmt = $db->prepare("
                UPDATE rooms 
                SET code = ?, name = ?, building_id = ?, floor = ?, capacity = ?, type = ?, can_be_booked = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$code, $name, $buildingId, $floor, $capacity, $type, $canBeBooked, $isActive, $id]);

            AuditHelper::log('room_updated', 'rooms', $id, $oldRoom, [
                'code' => $code, 'name' => $name, 'building_id' => $buildingId, 'floor' => $floor,
                'capacity' => $capacity, 'type' => $type, 'can_be_booked' => $canBeBooked, 'is_active' => $isActive
            ]);

            $_SESSION['flash_message'] = "Ruangan '{$name}' ({$code}) berhasil diperbarui.";
        } else {
            // Insert
            $stmt = $db->prepare("
                INSERT INTO rooms (code, name, building_id, floor, capacity, type, can_be_booked, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$code, $name, $buildingId, $floor, $capacity, $type, $canBeBooked, $isActive]);
            $newId = (int)$db->lastInsertId();

            AuditHelper::log('room_created', 'rooms', $newId, null, [
                'code' => $code, 'name' => $name, 'building_id' => $buildingId, 'floor' => $floor,
                'capacity' => $capacity, 'type' => $type, 'can_be_booked' => $canBeBooked, 'is_active' => $isActive
            ]);

            $_SESSION['flash_message'] = "Ruangan baru '{$name}' ({$code}) berhasil ditambahkan.";
        }

        header("Location: /webpinjamft/public/admin/rooms");
        exit;
    }

    /**
     * FR-052: Nonaktifkan Ruangan Sementara
     * AC 1: Given ruangan dinonaktifkan, Then status Tidak tersedia dan pengajuan baru ditolak; 
     *       peminjaman approved ke depan ditandai perlu ditinjau.
     */
    public function deactivateRoom() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        $roomId = (int)($_POST['room_id'] ?? 0);
        $reason = trim($_POST['inactive_reason'] ?? '');
        $inactiveUntil = !empty($_POST['inactive_until']) ? $_POST['inactive_until'] : null;

        if ($roomId <= 0 || $reason === '') {
            $_SESSION['flash_error'] = 'Alasan penonaktifan ruangan wajib diisi (FR-052).';
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmtRoom = $db->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmtRoom->execute([$roomId]);
        $room = $stmtRoom->fetch();

        if (!$room) {
            $_SESSION['flash_error'] = 'Ruangan tidak ditemukan.';
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        // 1. Update ruangan is_active = 0, catat alasan & inactive_until
        $stmt = $db->prepare("
            UPDATE rooms 
            SET is_active = 0, inactive_reason = ?, inactive_until = ?
            WHERE id = ?
        ");
        $stmt->execute([$reason, $inactiveUntil, $roomId]);

        // 2. Tandai peminjaman approved ke depan sebagai perlu ditinjau (needs_review = 1)
        $today = date('Y-m-d');
        $stmtReview = $db->prepare("
            UPDATE bookings 
            SET needs_review = 1 
            WHERE room_id = ? AND status = 'approved' AND booking_date >= ?
        ");
        $stmtReview->execute([$roomId, $today]);
        $affectedBookings = $stmtReview->rowCount();

        // 3. Notifikasi pengelola jika ada peminjaman terdampak
        if ($affectedBookings > 0) {
            $untilText = $inactiveUntil ? " sampai " . date('d/m/Y', strtotime($inactiveUntil)) : "";
            NotificationService::notifyManagers(
                'room_deactivated_conflict',
                "Peminjaman Perlu Ditinjau: Ruangan {$room['code']} Dinonaktifkan",
                "Ruangan {$room['name']} ({$room['code']}) telah dinonaktifkan sementara{$untilText} karena: '{$reason}'. Sebanyak {$affectedBookings} peminjaman disetujui mendatang terdampak dan ditandai perlu ditinjau di antrean pengelola."
            );
        }

        // 4. Audit Log
        AuditHelper::log('room_deactivated', 'rooms', $roomId, [
            'is_active' => $room['is_active'],
            'inactive_reason' => $room['inactive_reason'],
            'inactive_until' => $room['inactive_until']
        ], [
            'is_active' => 0,
            'inactive_reason' => $reason,
            'inactive_until' => $inactiveUntil,
            'affected_bookings' => $affectedBookings
        ]);

        $_SESSION['flash_message'] = "Ruangan {$room['name']} ({$room['code']}) berhasil dinonaktifkan sementara. {$affectedBookings} peminjaman mendatang ditandai perlu ditinjau.";
        header("Location: /webpinjamft/public/admin/rooms");
        exit;
    }

    /**
     * FR-050 / FR-052: Aktifkan Kembali Ruangan
     */
    public function activateRoom() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        $roomId = (int)($_POST['room_id'] ?? 0);
        $db = Database::getInstance()->getConnection();

        $stmtRoom = $db->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmtRoom->execute([$roomId]);
        $room = $stmtRoom->fetch();

        if (!$room) {
            $_SESSION['flash_error'] = 'Ruangan tidak ditemukan.';
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        $stmt = $db->prepare("
            UPDATE rooms 
            SET is_active = 1, inactive_reason = NULL, inactive_until = NULL
            WHERE id = ?
        ");
        $stmt->execute([$roomId]);

        AuditHelper::log('room_activated', 'rooms', $roomId, [
            'is_active' => 0,
            'inactive_reason' => $room['inactive_reason']
        ], [
            'is_active' => 1,
            'inactive_reason' => null
        ]);

        $_SESSION['flash_message'] = "Ruangan {$room['name']} ({$room['code']}) berhasil diaktifkan kembali.";
        header("Location: /webpinjamft/public/admin/rooms");
        exit;
    }

    /**
     * FR-050 AC 2 & BR-016: Hapus Ruangan (dengan proteksi integritas historis)
     * Given ruangan punya jadwal atau peminjaman, When dihapus, Then hanya dapat dinonaktifkan.
     */
    public function deleteRoom() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        $roomId = (int)($_POST['room_id'] ?? 0);
        $db = Database::getInstance()->getConnection();

        $stmtRoom = $db->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmtRoom->execute([$roomId]);
        $room = $stmtRoom->fetch();

        if (!$room) {
            $_SESSION['flash_error'] = 'Ruangan tidak ditemukan.';
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        // Cek riwayat jadwal kelas
        $stmtSchedules = $db->prepare("SELECT COUNT(*) FROM class_schedules WHERE room_id = ?");
        $stmtSchedules->execute([$roomId]);
        $scheduleCount = (int)$stmtSchedules->fetchColumn();

        // Cek riwayat peminjaman
        $stmtBookings = $db->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ?");
        $stmtBookings->execute([$roomId]);
        $bookingCount = (int)$stmtBookings->fetchColumn();

        if ($scheduleCount > 0 || $bookingCount > 0) {
            // BR-016: Tolak hapus permanen
            $_SESSION['flash_error'] = "Sesuai aturan BR-016, ruangan '{$room['name']}' ({$room['code']}) memiliki riwayat ({$scheduleCount} jadwal, {$bookingCount} peminjaman) sehingga TIDAK DAPAT dihapus permanen. Silakan gunakan fitur 'Nonaktifkan' sebagai gantinya.";
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        // Hapus permanen jika tidak ada riwayat
        $stmtDel = $db->prepare("DELETE FROM rooms WHERE id = ?");
        $stmtDel->execute([$roomId]);

        AuditHelper::log('room_deleted', 'rooms', $roomId, $room, null);

        $_SESSION['flash_message'] = "Ruangan '{$room['name']}' ({$room['code']}) berhasil dihapus permanen.";
        header("Location: /webpinjamft/public/admin/rooms");
        exit;
    }

    /* =========================================================================
     * FASILITAS RUANGAN (FR-051)
     * ========================================================================= */

    /**
     * FR-051: Kelola Fasilitas Ruangan
     */
    public function facilities($roomId) {
        $this->checkAdmin();
        $roomId = (int)$roomId;
        $baseUrl = '/webpinjamft/public';
        $activeMenu = "admin_rooms";

        $db = Database::getInstance()->getConnection();
        $stmtRoom = $db->prepare("
            SELECT r.*, b.name as building_name 
            FROM rooms r 
            JOIN buildings b ON r.building_id = b.id 
            WHERE r.id = ?
        ");
        $stmtRoom->execute([$roomId]);
        $room = $stmtRoom->fetch();

        if (!$room) {
            $_SESSION['flash_error'] = 'Ruangan tidak ditemukan.';
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        $title = "Kelola Fasilitas - " . $room['name'] . " (" . $room['code'] . ")";

        $stmtFac = $db->prepare("SELECT * FROM room_facilities WHERE room_id = ? ORDER BY type ASC");
        $stmtFac->execute([$roomId]);
        $facilities = $stmtFac->fetchAll();

        ob_start();
        require __DIR__ . '/../Views/admin/rooms/facilities.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-051: Simpan Fasilitas Ruangan (Tambah / Update)
     */
    public function saveFacility($roomId) {
        $this->checkAdmin();
        $roomId = (int)$roomId;

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/rooms/{$roomId}/facilities");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $type = trim($_POST['type'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 1);
        $condition = $_POST['condition'] ?? 'good';

        if ($type === '' || $quantity < 1) {
            $_SESSION['flash_error'] = 'Nama fasilitas wajib diisi dan jumlah minimal 1.';
            header("Location: /webpinjamft/public/admin/rooms/{$roomId}/facilities");
            exit;
        }

        $db = Database::getInstance()->getConnection();

        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE room_facilities 
                SET type = ?, quantity = ?, `condition` = ? 
                WHERE id = ? AND room_id = ?
            ");
            $stmt->execute([$type, $quantity, $condition, $id, $roomId]);
            AuditHelper::log('facility_updated', 'room_facilities', $id, null, ['type' => $type, 'qty' => $quantity, 'cond' => $condition]);
            $_SESSION['flash_message'] = "Fasilitas '{$type}' berhasil diperbarui.";
        } else {
            $stmt = $db->prepare("
                INSERT INTO room_facilities (room_id, type, quantity, `condition`) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$roomId, $type, $quantity, $condition]);
            $newId = (int)$db->lastInsertId();
            AuditHelper::log('facility_created', 'room_facilities', $newId, null, ['room_id' => $roomId, 'type' => $type, 'qty' => $quantity, 'cond' => $condition]);
            $_SESSION['flash_message'] = "Fasilitas '{$type}' ({$quantity} unit) berhasil ditambahkan.";
        }

        header("Location: /webpinjamft/public/admin/rooms/{$roomId}/facilities");
        exit;
    }

    /**
     * FR-051: Hapus Fasilitas Ruangan
     */
    public function deleteFacility($facilityId) {
        $this->checkAdmin();
        $facilityId = (int)$facilityId;

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM room_facilities WHERE id = ?");
        $stmt->execute([$facilityId]);
        $fac = $stmt->fetch();

        if (!$fac) {
            $_SESSION['flash_error'] = 'Fasilitas tidak ditemukan.';
            header("Location: /webpinjamft/public/admin/rooms");
            exit;
        }

        $roomId = (int)$fac['room_id'];
        $stmtDel = $db->prepare("DELETE FROM room_facilities WHERE id = ?");
        $stmtDel->execute([$facilityId]);

        AuditHelper::log('facility_deleted', 'room_facilities', $facilityId, $fac, null);

        $_SESSION['flash_message'] = "Fasilitas '{$fac['type']}' berhasil dihapus.";
        header("Location: /webpinjamft/public/admin/rooms/{$roomId}/facilities");
        exit;
    }

    /* =========================================================================
     * GEDUNG (FR-050, BR-016)
     * ========================================================================= */

    /**
     * FR-050: Daftar & Kelola Gedung
     */
    public function buildings() {
        $this->checkAdmin();
        $title = "Kelola Gedung (FR-050)";
        $activeMenu = "admin_buildings";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT b.*, 
                   COUNT(r.id) as total_rooms,
                   SUM(CASE WHEN r.is_active = 1 THEN 1 ELSE 0 END) as active_rooms
            FROM buildings b
            LEFT JOIN rooms r ON b.id = r.building_id
            GROUP BY b.id
            ORDER BY b.name ASC
        ");
        $buildings = $stmt->fetchAll();

        ob_start();
        require __DIR__ . '/../Views/admin/buildings/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-050: Simpan Gedung (Tambah / Update)
     */
    public function saveBuilding() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/buildings");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($code === '' || $name === '') {
            $_SESSION['flash_error'] = 'Kode dan nama gedung wajib diisi.';
            header("Location: /webpinjamft/public/admin/buildings");
            exit;
        }

        $db = Database::getInstance()->getConnection();

        // Check unique code
        if ($id > 0) {
            $stmtCheck = $db->prepare("SELECT id FROM buildings WHERE code = ? AND id != ?");
            $stmtCheck->execute([$code, $id]);
        } else {
            $stmtCheck = $db->prepare("SELECT id FROM buildings WHERE code = ?");
            $stmtCheck->execute([$code]);
        }

        if ($stmtCheck->fetchColumn()) {
            $_SESSION['flash_error'] = "Kode gedung '{$code}' sudah ada. Gunakan kode unik.";
            header("Location: /webpinjamft/public/admin/buildings");
            exit;
        }

        if ($id > 0) {
            $stmtOld = $db->prepare("SELECT * FROM buildings WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldBldg = $stmtOld->fetch();

            $stmt = $db->prepare("UPDATE buildings SET code = ?, name = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$code, $name, $isActive, $id]);

            AuditHelper::log('building_updated', 'buildings', $id, $oldBldg, ['code' => $code, 'name' => $name, 'is_active' => $isActive]);
            $_SESSION['flash_message'] = "Gedung '{$name}' ({$code}) berhasil diperbarui.";
        } else {
            $stmt = $db->prepare("INSERT INTO buildings (code, name, is_active) VALUES (?, ?, ?)");
            $stmt->execute([$code, $name, $isActive]);
            $newId = (int)$db->lastInsertId();

            AuditHelper::log('building_created', 'buildings', $newId, null, ['code' => $code, 'name' => $name, 'is_active' => $isActive]);
            $_SESSION['flash_message'] = "Gedung baru '{$name}' ({$code}) berhasil ditambahkan.";
        }

        header("Location: /webpinjamft/public/admin/buildings");
        exit;
    }

    /**
     * FR-050 / BR-016: Hapus Gedung
     */
    public function deleteBuilding() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/buildings");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $db = Database::getInstance()->getConnection();

        $stmtBldg = $db->prepare("SELECT * FROM buildings WHERE id = ?");
        $stmtBldg->execute([$id]);
        $bldg = $stmtBldg->fetch();

        if (!$bldg) {
            $_SESSION['flash_error'] = 'Gedung tidak ditemukan.';
            header("Location: /webpinjamft/public/admin/buildings");
            exit;
        }

        // Cek jika ada ruangan di bawah gedung ini
        $stmtRooms = $db->prepare("SELECT COUNT(*) FROM rooms WHERE building_id = ?");
        $stmtRooms->execute([$id]);
        $roomCount = (int)$stmtRooms->fetchColumn();

        if ($roomCount > 0) {
            $_SESSION['flash_error'] = "Sesuai aturan BR-016, gedung '{$bldg['name']}' memiliki {$roomCount} ruangan sehingga TIDAK DAPAT dihapus langsung. Anda dapat menonaktifkan status gedung ini.";
            header("Location: /webpinjamft/public/admin/buildings");
            exit;
        }

        $stmtDel = $db->prepare("DELETE FROM buildings WHERE id = ?");
        $stmtDel->execute([$id]);

        AuditHelper::log('building_deleted', 'buildings', $id, $bldg, null);

        $_SESSION['flash_message'] = "Gedung '{$bldg['name']}' berhasil dihapus.";
        header("Location: /webpinjamft/public/admin/buildings");
        exit;
    }

    /* =========================================================================
     * SEMESTER & PERIODE TANPA KELAS (FR-053)
     * ========================================================================= */

    /**
     * FR-053: Kelola Semester dan Periode Libur/Tanpa Kelas
     */
    public function terms() {
        $this->checkAdmin();
        $title = "Kelola Semester & Periode Tanpa Kelas (FR-053)";
        $activeMenu = "admin_terms";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();

        // Semesters
        $stmtSem = $db->query("
            SELECT s.*, 
                   (SELECT COUNT(*) FROM class_schedules WHERE semester_id = s.id) as schedule_count,
                   (SELECT COUNT(*) FROM no_class_periods WHERE semester_id = s.id) as period_count
            FROM semesters s 
            ORDER BY s.start_date DESC
        ");
        $semesters = $stmtSem->fetchAll();

        // No Class Periods
        $stmtPeriods = $db->query("
            SELECT ncp.*, s.name as semester_name 
            FROM no_class_periods ncp 
            JOIN semesters s ON ncp.semester_id = s.id 
            ORDER BY ncp.start_date DESC
        ");
        $noClassPeriods = $stmtPeriods->fetchAll();

        ob_start();
        require __DIR__ . '/../Views/admin/terms/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-053: Simpan Semester
     * AC 2: Hanya satu semester boleh aktif pada satu waktu.
     */
    public function saveSemester() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/terms");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '' || $startDate === '' || $endDate === '') {
            $_SESSION['flash_error'] = 'Nama semester, tanggal mulai, dan tanggal selesai wajib diisi.';
            header("Location: /webpinjamft/public/admin/terms");
            exit;
        }

        if ($startDate >= $endDate) {
            $_SESSION['flash_error'] = 'Tanggal selesai harus lebih akhir dari tanggal mulai.';
            header("Location: /webpinjamft/public/admin/terms");
            exit;
        }

        $db = Database::getInstance()->getConnection();

        // Jika diset aktif, nonaktifkan semester lainnya terlebih dahulu (AC 2)
        if ($isActive === 1) {
            $db->exec("UPDATE semesters SET is_active = 0");
        }

        if ($id > 0) {
            $stmtOld = $db->prepare("SELECT * FROM semesters WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldSem = $stmtOld->fetch();

            $stmt = $db->prepare("
                UPDATE semesters 
                SET name = ?, start_date = ?, end_date = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $startDate, $endDate, $isActive, $id]);

            AuditHelper::log('semester_updated', 'semesters', $id, $oldSem, [
                'name' => $name, 'start_date' => $startDate, 'end_date' => $endDate, 'is_active' => $isActive
            ]);

            $_SESSION['flash_message'] = "Semester '{$name}' berhasil diperbarui.";
        } else {
            $stmt = $db->prepare("
                INSERT INTO semesters (name, start_date, end_date, is_active) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $startDate, $endDate, $isActive]);
            $newId = (int)$db->lastInsertId();

            AuditHelper::log('semester_created', 'semesters', $newId, null, [
                'name' => $name, 'start_date' => $startDate, 'end_date' => $endDate, 'is_active' => $isActive
            ]);

            $_SESSION['flash_message'] = "Semester '{$name}' berhasil ditambahkan.";
        }

        header("Location: /webpinjamft/public/admin/terms");
        exit;
    }

    /**
     * FR-053 AC 2: Aktifkan Semester
     */
    public function activateSemester() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/terms");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $db = Database::getInstance()->getConnection();

        // 1. Matikan semua semester
        $db->exec("UPDATE semesters SET is_active = 0");

        // 2. Aktifkan semester terpilih
        $stmt = $db->prepare("UPDATE semesters SET is_active = 1 WHERE id = ?");
        $stmt->execute([$id]);

        $stmtName = $db->prepare("SELECT name FROM semesters WHERE id = ?");
        $stmtName->execute([$id]);
        $semName = $stmtName->fetchColumn();

        AuditHelper::log('semester_activated', 'semesters', $id, null, ['is_active' => 1]);

        $_SESSION['flash_message'] = "Semester '{$semName}' kini aktif sebagai semester utama (FR-053 AC 2).";
        header("Location: /webpinjamft/public/admin/terms");
        exit;
    }

    /**
     * FR-053: Simpan Periode Tanpa Kelas (Libur / Minggu Tenang / UTS / UAS)
     * AC 1: Given periode tanpa kelas, Then jadwal kelas tidak berlaku dan ruangan terdaftar Kosong pada rentang itu.
     */
    public function saveNoClassPeriod() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/terms");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $semesterId = (int)($_POST['semester_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';

        if ($semesterId <= 0 || $description === '' || $startDate === '' || $endDate === '') {
            $_SESSION['flash_error'] = 'Semester, keterangan periode, dan rentang tanggal wajib diisi.';
            header("Location: /webpinjamft/public/admin/terms");
            exit;
        }

        if ($startDate > $endDate) {
            $_SESSION['flash_error'] = 'Tanggal selesai harus sama atau setelah tanggal mulai.';
            header("Location: /webpinjamft/public/admin/terms");
            exit;
        }

        $db = Database::getInstance()->getConnection();

        if ($id > 0) {
            $stmtOld = $db->prepare("SELECT * FROM no_class_periods WHERE id = ?");
            $stmtOld->execute([$id]);
            $oldPeriod = $stmtOld->fetch();

            $stmt = $db->prepare("
                UPDATE no_class_periods 
                SET semester_id = ?, description = ?, start_date = ?, end_date = ?
                WHERE id = ?
            ");
            $stmt->execute([$semesterId, $description, $startDate, $endDate, $id]);

            AuditHelper::log('no_class_period_updated', 'no_class_periods', $id, $oldPeriod, [
                'semester_id' => $semesterId, 'description' => $description, 'start_date' => $startDate, 'end_date' => $endDate
            ]);

            $_SESSION['flash_message'] = "Periode tanpa kelas '{$description}' berhasil diperbarui.";
        } else {
            $stmt = $db->prepare("
                INSERT INTO no_class_periods (semester_id, description, start_date, end_date)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$semesterId, $description, $startDate, $endDate]);
            $newId = (int)$db->lastInsertId();

            AuditHelper::log('no_class_period_created', 'no_class_periods', $newId, null, [
                'semester_id' => $semesterId, 'description' => $description, 'start_date' => $startDate, 'end_date' => $endDate
            ]);

            $_SESSION['flash_message'] = "Periode tanpa kelas '{$description}' ({$startDate} s.d. {$endDate}) berhasil ditambahkan (FR-053 AC 1).";
        }

        header("Location: /webpinjamft/public/admin/terms");
        exit;
    }

    /**
     * FR-053: Hapus Periode Tanpa Kelas
     */
    public function deleteNoClassPeriod() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/terms");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM no_class_periods WHERE id = ?");
        $stmt->execute([$id]);
        $period = $stmt->fetch();

        if (!$period) {
            $_SESSION['flash_error'] = 'Periode tidak ditemukan.';
            header("Location: /webpinjamft/public/admin/terms");
            exit;
        }

        $stmtDel = $db->prepare("DELETE FROM no_class_periods WHERE id = ?");
        $stmtDel->execute([$id]);

        AuditHelper::log('no_class_period_deleted', 'no_class_periods', $id, $period, null);

        $_SESSION['flash_message'] = "Periode tanpa kelas '{$period['description']}' berhasil dihapus.";
        header("Location: /webpinjamft/public/admin/terms");
        exit;
    }
}
