<?php
namespace App\Controllers;

use App\Services\RoomStatusService;
use App\Support\Database;
use PDO;

class ApiController {
    
    /**
     * Return JSON for room statuses (FR-013).
     */
    public function rooms() {
        header('Content-Type: application/json');
        
        try {
            $service = new RoomStatusService();
            $rooms = $service->getAllRoomsStatus();
            
            echo json_encode([
                'success' => true,
                'data' => $rooms,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan server'
            ]);
        }
    }

    /**
     * FR-031: Saran Otomatis (Autocomplete)
     * Maksimal 8 item gabungan Ruangan, Gedung, Jadwal
     */
    public function search() {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');

        if (mb_strlen($q) < 2) {
            echo json_encode(['success' => true, 'count' => 0, 'data' => []]);
            exit;
        }

        try {
            $db = Database::getInstance()->getConnection();
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
            $param = "%{$escaped}%";
            $results = [];

            // 1. Ruangan
            $stmtRooms = $db->prepare("
                SELECT r.id, r.code, r.name, b.name as building_name 
                FROM rooms r 
                JOIN buildings b ON r.building_id = b.id 
                WHERE (r.name LIKE ? ESCAPE '\\\\' OR r.code LIKE ? ESCAPE '\\\\')
                ORDER BY r.code ASC LIMIT 4
            ");
            $stmtRooms->execute([$param, $param]);
            while ($row = $stmtRooms->fetch()) {
                $results[] = [
                    'type' => 'Ruangan',
                    'title' => $row['name'] . ' (' . $row['code'] . ')',
                    'subtitle' => $row['building_name'],
                    'url' => '/webpinjamft/public/rooms/' . $row['id']
                ];
            }

            // 2. Gedung
            $stmtBldg = $db->prepare("
                SELECT id, code, name 
                FROM buildings 
                WHERE (name LIKE ? ESCAPE '\\\\' OR code LIKE ? ESCAPE '\\\\')
                ORDER BY name ASC LIMIT 3
            ");
            $stmtBldg->execute([$param, $param]);
            while ($row = $stmtBldg->fetch()) {
                $results[] = [
                    'type' => 'Gedung',
                    'title' => $row['name'] . ' (' . $row['code'] . ')',
                    'subtitle' => 'Gedung Fakultas Teknik',
                    'url' => '/webpinjamft/public/search?q=' . urlencode($row['name'])
                ];
            }

            // 3. Jadwal (Mata Kuliah / Dosen)
            $weekdayMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
            $stmtSched = $db->prepare("
                SELECT cs.id, cs.course_name, cs.course_code, cs.lecturer_name, cs.weekday, cs.start_time, cs.end_time, r.id as room_id, r.code as room_code 
                FROM class_schedules cs 
                JOIN rooms r ON cs.room_id = r.id 
                WHERE cs.is_active = 1 
                  AND (cs.course_name LIKE ? ESCAPE '\\\\' 
                       OR cs.course_code LIKE ? ESCAPE '\\\\' 
                       OR cs.lecturer_name LIKE ? ESCAPE '\\\\')
                LIMIT 5
            ");
            $stmtSched->execute([$param, $param, $param]);
            while ($row = $stmtSched->fetch()) {
                $day = $weekdayMap[(int)$row['weekday']] ?? '';
                $time = substr($row['start_time'], 0, 5) . '-' . substr($row['end_time'], 0, 5);
                $results[] = [
                    'type' => 'Jadwal',
                    'title' => $row['course_name'] . ' (' . $row['course_code'] . ')',
                    'subtitle' => $row['lecturer_name'] . ' • ' . $day . ' ' . $time . ' @ ' . $row['room_code'],
                    'url' => '/webpinjamft/public/rooms/' . $row['room_id']
                ];
            }

            // Batasi tepat maksimal 8 item (FR-031)
            $results = array_slice($results, 0, 8);

            echo json_encode([
                'success' => true,
                'count' => count($results),
                'data' => $results
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem'
            ]);
        }
    }
}
