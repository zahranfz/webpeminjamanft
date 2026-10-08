<?php
namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Services\RoomStatusService;

class RoomController {
    
    public function show($id) {
        $baseUrl = '/webpinjamft/public';
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT r.*, b.name as building_name 
            FROM rooms r 
            JOIN buildings b ON r.building_id = b.id 
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $room = $stmt->fetch();
        
        if (!$room) {
            $_SESSION['flash_error'] = 'Ruangan tidak ditemukan.';
            header("Location: /webpinjamft/public/");
            exit;
        }

        $title = "Detail Ruang " . $room['name'] . " (" . $room['code'] . ")";
        $activeMenu = "rooms";
        
        // 1. Get real-time status via centralized RoomStatusService
        $roomStatusService = new RoomStatusService();
        $statusInfo = $roomStatusService->getRoomStatus((int)$id);
        
        // 2. Get room facilities (FR-012, FR-051)
        $stmtFac = $db->prepare("SELECT * FROM room_facilities WHERE room_id = ? ORDER BY type ASC");
        $stmtFac->execute([$id]);
        $facilities = $stmtFac->fetchAll();

        // 3. Get active facility reports (FR-085: new, verified, in_progress - without reporter name)
        $stmtReports = $db->prepare("
            SELECT id, category, facility_name, description, urgency, status, created_at 
            FROM facility_reports 
            WHERE room_id = ? AND status IN ('new', 'verified', 'in_progress')
            ORDER BY urgency DESC, created_at DESC
        ");
        $stmtReports->execute([$id]);
        $activeReports = $stmtReports->fetchAll();
        
        // 4. Get today's classes and bookings
        $todayDate = date('Y-m-d');
        $todayWeekday = (int) date('N');
        $now = date('H:i:s');
        
        $stmtClasses = $db->prepare("
            SELECT cs.*, sess.status as session_status, sess.note as session_note, sess.actual_start_time,
                   r_target.name as target_room_name, r_target.code as target_room_code
            FROM class_schedules cs 
            LEFT JOIN class_sessions sess ON cs.id = sess.schedule_id AND sess.session_date = ?
            LEFT JOIN rooms r_target ON sess.moved_to_room_id = r_target.id
            WHERE cs.room_id = ? AND cs.weekday = ? AND cs.is_active = 1 
            ORDER BY cs.start_time ASC
        ");
        $stmtClasses->execute([$todayDate, $id, $todayWeekday]);
        $classes = $stmtClasses->fetchAll();

        // FR-068: Fetch classes moved into this room today
        $stmtMovedIn = $db->prepare("
            SELECT cs.*, sess.status as session_status, sess.note as session_note, sess.actual_start_time,
                   r_orig.name as orig_room_name, r_orig.code as orig_room_code
            FROM class_sessions sess
            JOIN class_schedules cs ON sess.schedule_id = cs.id
            JOIN rooms r_orig ON cs.room_id = r_orig.id
            WHERE sess.moved_to_room_id = ? AND sess.session_date = ? AND sess.status = 'moved'
            ORDER BY cs.start_time ASC
        ");
        $stmtMovedIn->execute([$id, $todayDate]);
        $movedInClasses = $stmtMovedIn->fetchAll();
        
        $stmtBookings = $db->prepare("
            SELECT * FROM bookings 
            WHERE room_id = ? AND booking_date = ? AND status = 'approved' 
            ORDER BY start_time ASC
        ");
        $stmtBookings->execute([$id, $todayDate]);
        $bookings = $stmtBookings->fetchAll();
        
        // Merge and sort schedules
        $schedules = [];
        foreach ($classes as $c) {
            $isOngoing = ($now >= $c['start_time'] && $now < $c['end_time']);
            $subtitle = 'Dosen: ' . $c['lecturer_name'] . ' | Kelas ' . $c['class_name'];
            if ($c['session_status'] === 'moved') {
                $subtitle = '⇄ Dipindahkan ke Ruang ' . ($c['target_room_name'] ?? 'Lain') . ' | ' . $subtitle;
            }
            $schedules[] = [
                'type' => 'class',
                'schedule_id' => $c['id'],
                'start_time' => $c['start_time'],
                'end_time' => $c['end_time'],
                'title' => $c['course_name'] . ' (' . $c['course_code'] . ')',
                'subtitle' => $subtitle,
                'session_status' => $c['session_status'],
                'session_note' => $c['session_note'],
                'is_ongoing' => $isOngoing
            ];
        }

        foreach ($movedInClasses as $mc) {
            $isOngoing = ($now >= $mc['start_time'] && $now < $mc['end_time']);
            $schedules[] = [
                'type' => 'class',
                'schedule_id' => $mc['id'],
                'start_time' => $mc['start_time'],
                'end_time' => $mc['end_time'],
                'title' => '[Pindahan] ' . $mc['course_name'] . ' (' . $mc['course_code'] . ')',
                'subtitle' => 'Pindahan dari ' . $mc['orig_room_name'] . ' (' . $mc['orig_room_code'] . ') | Dosen: ' . $mc['lecturer_name'],
                'session_status' => 'moved_in',
                'session_note' => $mc['session_note'],
                'is_ongoing' => $isOngoing
            ];
        }

        foreach ($bookings as $b) {
            $isOngoing = ($now >= $b['start_time'] && $now < $b['end_time']);
            $schedules[] = [
                'type' => 'booking',
                'start_time' => $b['start_time'],
                'end_time' => $b['end_time'],
                'title' => $b['activity_name'],
                'subtitle' => 'Peminjaman: ' . ucfirst($b['activity_type']),
                'is_ongoing' => $isOngoing
            ];
        }
        
        usort($schedules, function($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });

        // 5. Weekly schedules for Grid View (FR-023)
        $stmtWeeklyClasses = $db->prepare("
            SELECT cs.*, sem.name as semester_name 
            FROM class_schedules cs 
            JOIN semesters sem ON cs.semester_id = sem.id 
            WHERE cs.room_id = ? AND cs.is_active = 1
            ORDER BY cs.weekday ASC, cs.start_time ASC
        ");
        $stmtWeeklyClasses->execute([$id]);
        $weeklyClasses = $stmtWeeklyClasses->fetchAll();

        $weeklyGrid = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => []];
        foreach ($weeklyClasses as $wc) {
            $w = (int)$wc['weekday'];
            if (isset($weeklyGrid[$w])) {
                $weeklyGrid[$w][] = [
                    'type' => 'class',
                    'title' => $wc['course_name'],
                    'code' => $wc['course_code'],
                    'class_name' => $wc['class_name'],
                    'lecturer' => $wc['lecturer_name'],
                    'start_time' => $wc['start_time'],
                    'end_time' => $wc['end_time']
                ];
            }
        }

        // Current active user for report absent check
        $user = Auth::user();
        
        ob_start();
        require __DIR__ . '/../Views/room/show.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout/main.php';
    }
}
