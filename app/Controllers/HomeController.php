<?php
namespace App\Controllers;

use App\Support\Database;
use App\Support\Settings;

class HomeController {
    
    public function index() {
        $title = "Dashboard Ketersediaan Ruangan";
        $activeMenu = "dashboard";
        $baseUrl = '/webpinjamft/public';
        
        // Ambil status ruangan secara real-time untuk KPI
        $service = new \App\Services\RoomStatusService();
        $roomsStatus = $service->getAllRoomsStatus();
        
        $totalRooms = count($roomsStatus);
        $availableRooms = 0;
        $ongoingSessions = 0;
        
        foreach ($roomsStatus as $r) {
            if ($r['status'] === 'vacant' || $r['status'] === 'vacant_temp') {
                $availableRooms++;
            } elseif (strpos($r['status'], 'occupied') === 0) {
                $ongoingSessions++;
            }
        }
        
        $kpi = [
            'total' => $totalRooms,
            'available' => $availableRooms,
            'ongoing' => $ongoingSessions
        ];
        
        ob_start();
        require __DIR__ . '/../Views/home/index.php';
        $content = ob_get_clean();
        
        $extraJs = '<script src="' . $baseUrl . '/js/app.js"></script>';
        
        require __DIR__ . '/../Views/layout/main.php';
    }

    public function rooms() {
        $title = "Katalog & Ketersediaan Ruangan FT Unsoed";
        $activeMenu = "rooms";
        $baseUrl = '/webpinjamft/public';
        
        ob_start();
        require __DIR__ . '/../Views/room/index.php';
        $content = ob_get_clean();
        
        $extraJs = '<script src="' . $baseUrl . '/js/app.js"></script>';
        
        require __DIR__ . '/../Views/layout/main.php';
    }


    /**
     * FR-022: Cari ruangan kosong pada tanggal dan rentang jam tertentu
     */
    public function freeRooms() {
        $title = "Cari Ruangan Kosong (FR-022)";
        $activeMenu = "free";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();

        // Get filter params
        $searchDate = $_GET['date'] ?? date('Y-m-d');
        $startTime = $_GET['start_time'] ?? date('H:00', strtotime('+1 hour'));
        $endTime = $_GET['end_time'] ?? date('H:00', strtotime('+3 hours'));
        $minCapacity = (int)($_GET['capacity'] ?? 0);
        $buildingId = (int)($_GET['building_id'] ?? 0);

        // Fetch buildings for filter
        $stmtBldg = $db->query("SELECT id, name FROM buildings ORDER BY name ASC");
        $buildings = $stmtBldg->fetchAll();

        $rooms = [];
        $searched = isset($_GET['date']);

        if ($searched) {
            $weekday = (int) date('N', strtotime($searchDate));
            $formattedStart = date('H:i:s', strtotime($startTime));
            $formattedEnd = date('H:i:s', strtotime($endTime));

            // Find rooms with neither class nor approved booking on that interval (BR-002)
            $sql = "
                SELECT r.*, b.name as building_name 
                FROM rooms r
                JOIN buildings b ON r.building_id = b.id
                WHERE r.is_active = 1
            ";
            $params = [];

            if ($minCapacity > 0) {
                $sql .= " AND r.capacity >= ?";
                $params[] = $minCapacity;
            }

            if ($buildingId > 0) {
                $sql .= " AND r.building_id = ?";
                $params[] = $buildingId;
            }

            // Exclude rooms with active class schedules during that interval
            $sql .= "
                AND NOT EXISTS (
                    SELECT 1 FROM class_schedules cs
                    LEFT JOIN class_sessions sess ON cs.id = sess.schedule_id AND sess.session_date = ?
                    WHERE cs.room_id = r.id AND cs.weekday = ? AND cs.is_active = 1
                      AND cs.start_time < ? AND cs.end_time > ?
                      AND COALESCE(sess.status, '') NOT IN ('cancelled_by_lecturer', 'cancelled_by_manager')
                )
            ";
            $params[] = $searchDate;
            $params[] = $weekday;
            $params[] = $formattedEnd;
            $params[] = $formattedStart;

            // Exclude rooms with approved bookings during that interval
            $sql .= "
                AND NOT EXISTS (
                    SELECT 1 FROM bookings bk
                    WHERE bk.room_id = r.id AND bk.booking_date = ? AND bk.status = 'approved'
                      AND bk.start_time < ? AND bk.end_time > ?
                )
            ";
            $params[] = $searchDate;
            $params[] = $formattedEnd;
            $params[] = $formattedStart;

            $sql .= " ORDER BY b.name ASC, r.code ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rooms = $stmt->fetchAll();
        }

        ob_start();
        require __DIR__ . '/../Views/home/free.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-030 & FR-032: Pencarian Terpadu
     * Hasil dikelompokkan: Ruangan, Gedung, Jadwal.
     * Minimal 2 karakter, prepared statement dengan karakter % dan _ diperlakukan literal.
     */
    public function search() {
        $title = "Hasil Pencarian Terpadu (FR-030)";
        $activeMenu = "search";
        $baseUrl = '/webpinjamft/public';

        $q = trim($_GET['q'] ?? '');
        $isQueryValid = mb_strlen($q) >= 2;

        $rooms = [];
        $buildings = [];
        $schedules = [];
        $totalResults = 0;

        if ($isQueryValid) {
            $db = Database::getInstance()->getConnection();

            // AC 4: Escape literal % and _
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
            $param = "%{$escaped}%";

            // 1. Grup Ruangan (nama, kode)
            $stmtRooms = $db->prepare("
                SELECT r.*, b.name as building_name 
                FROM rooms r 
                JOIN buildings b ON r.building_id = b.id 
                WHERE (r.name LIKE ? ESCAPE '\\\\' OR r.code LIKE ? ESCAPE '\\\\')
                ORDER BY r.code ASC LIMIT 20
            ");
            $stmtRooms->execute([$param, $param]);
            $rooms = $stmtRooms->fetchAll();

            // 2. Grup Gedung (nama, kode)
            $stmtBldg = $db->prepare("
                SELECT b.*, COUNT(r.id) as room_count 
                FROM buildings b 
                LEFT JOIN rooms r ON b.id = r.building_id 
                WHERE (b.name LIKE ? ESCAPE '\\\\' OR b.code LIKE ? ESCAPE '\\\\')
                GROUP BY b.id 
                ORDER BY b.name ASC LIMIT 10
            ");
            $stmtBldg->execute([$param, $param]);
            $buildings = $stmtBldg->fetchAll();

            // 3. Grup Jadwal (mata kuliah, kode mk, nama dosen)
            $stmtSched = $db->prepare("
                SELECT cs.*, r.code as room_code, r.name as room_name, sem.name as semester_name 
                FROM class_schedules cs 
                JOIN rooms r ON cs.room_id = r.id 
                JOIN semesters sem ON cs.semester_id = sem.id 
                WHERE cs.is_active = 1 
                  AND (cs.course_name LIKE ? ESCAPE '\\\\' 
                       OR cs.course_code LIKE ? ESCAPE '\\\\' 
                       OR cs.lecturer_name LIKE ? ESCAPE '\\\\')
                ORDER BY cs.weekday ASC, cs.start_time ASC LIMIT 25
            ");
            $stmtSched->execute([$param, $param, $param]);
            $schedules = $stmtSched->fetchAll();

            $totalResults = count($rooms) + count($buildings) + count($schedules);
        }

        ob_start();
        require __DIR__ . '/../Views/home/search.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }
}

