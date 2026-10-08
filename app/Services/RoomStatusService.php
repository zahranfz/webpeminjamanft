<?php
namespace App\Services;

use App\Support\Database;
use App\Support\Settings;
use PDO;

class RoomStatusService {
    
    /**
     * Get all active rooms with their calculated status at current or specified time.
     * Implements FR-010, BR-003, BR-005, and PRD Section 10.1 algorithm.
     */
    public function getAllRoomsStatus(?string $nowTime = null, ?string $currentDate = null): array {
        $db = Database::getInstance()->getConnection();
        
        // Base rooms query
        $stmt = $db->query("
            SELECT r.*, b.name as building_name 
            FROM rooms r 
            JOIN buildings b ON r.building_id = b.id 
            ORDER BY b.name ASC, r.code ASC
        ");
        $rooms = $stmt->fetchAll();
        
        $now = $nowTime ?: date('H:i:s');
        $todayDate = $currentDate ?: date('Y-m-d');
        $todayWeekday = (int) date('N', strtotime($todayDate)); // 1 = Monday, 7 = Sunday
        
        // Check active term and no-class periods
        $termActive = $this->isTermActiveOnDate($todayDate);
        $graceMinutes = Settings::getInt('grace_minutes', 15);
        $absentThreshold = Settings::getInt('absent_report_threshold', 3);
        
        $result = [];
        
        foreach ($rooms as $room) {
            $result[] = $this->evaluateRoom($room, $now, $todayDate, $todayWeekday, $termActive, $graceMinutes, $absentThreshold);
        }
        
        return $result;
    }

    /**
     * Get status of a single room by ID.
     */
    public function getRoomStatus(int $roomId, ?string $nowTime = null, ?string $currentDate = null): ?array {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT r.*, b.name as building_name 
            FROM rooms r 
            JOIN buildings b ON r.building_id = b.id 
            WHERE r.id = ?
        ");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();
        if (!$room) {
            return null;
        }

        $now = $nowTime ?: date('H:i:s');
        $todayDate = $currentDate ?: date('Y-m-d');
        $todayWeekday = (int) date('N', strtotime($todayDate));
        $termActive = $this->isTermActiveOnDate($todayDate);
        $graceMinutes = Settings::getInt('grace_minutes', 15);
        $absentThreshold = Settings::getInt('absent_report_threshold', 3);

        return $this->evaluateRoom($room, $now, $todayDate, $todayWeekday, $termActive, $graceMinutes, $absentThreshold);
    }

    /**
     * Core Algorithm for Room Status (PRD Section 10.1 & BR-005)
     */
    private function evaluateRoom(array $room, string $now, string $todayDate, int $todayWeekday, bool $termActive, int $graceMinutes, int $absentThreshold): array {
        $db = Database::getInstance()->getConnection();

        // 1. Inactive room check
        if ((int)$room['is_active'] === 0) {
            return [
                'id' => $room['id'],
                'code' => $room['code'],
                'name' => $room['name'],
                'building' => $room['building_name'],
                'floor' => $room['floor'],
                'capacity' => $room['capacity'],
                'can_be_booked' => (bool)$room['can_be_booked'],
                'status' => 'inactive',
                'statusText' => 'Tidak tersedia',
                'badgeClass' => 'badge-danger',
                'until' => null,
                'timeKeterangan' => 'Tidak tersedia',
                'details' => $room['inactive_reason'] ?: 'Ruangan sedang dinonaktifkan',
                'current_class' => null,
                'can_report_absent' => false
            ];
        }

        $statusCode = 'vacant';
        $statusText = 'Kosong';
        $badgeClass = 'badge-success';
        $until = '23:59';
        $details = 'Tersedia untuk dipinjam';
        $currentClassData = null;
        $canReportAbsent = false;
        $isClassCancelled = false;
        $isClassAbandoned = false;

        // 2. Check active class session if term is active
        if ($termActive) {
            $stmtClass = $db->prepare("
                SELECT cs.*, sem.name as semester_name 
                FROM class_schedules cs 
                JOIN semesters sem ON cs.semester_id = sem.id 
                WHERE cs.room_id = ? AND cs.weekday = ? AND cs.is_active = 1 
                  AND cs.start_time <= ? AND cs.end_time > ?
                LIMIT 1
            ");
            $stmtClass->execute([$room['id'], $todayWeekday, $now, $now]);
            $currentClass = $stmtClass->fetch();

            if ($currentClass) {
                $currentClassData = $currentClass;
                // Check class_sessions table
                $stmtSession = $db->prepare("
                    SELECT * FROM class_sessions 
                    WHERE schedule_id = ? AND session_date = ?
                ");
                $stmtSession->execute([$currentClass['id'], $todayDate]);
                $session = $stmtSession->fetch();

                $sessionStatus = $session ? $session['status'] : null;

                if ($sessionStatus === 'cancelled_by_lecturer' || $sessionStatus === 'cancelled_by_manager' || $sessionStatus === 'moved') {
                    $isClassCancelled = true;
                } elseif ($sessionStatus === 'checked_in' || $sessionStatus === 'confirmed_held_by_manager' || $sessionStatus === 'ongoing') {
                    $statusCode = 'occupied_class';
                    $statusText = 'Terpakai (kelas)';
                    $badgeClass = 'badge-primary';
                    $until = date('H:i', strtotime($currentClass['end_time']));
                    $details = 'Mata Kuliah: ' . $currentClass['course_name'] . ' (' . $currentClass['class_name'] . ') - Dosen: ' . $currentClass['lecturer_name'];
                } else {
                    // Check grace period: start_time + graceMinutes
                    $startTimestamp = strtotime($todayDate . ' ' . $currentClass['start_time']);
                    $graceTimestamp = $startTimestamp + ($graceMinutes * 60);
                    $currentTimestamp = strtotime($todayDate . ' ' . $now);

                    if ($currentTimestamp < $graceTimestamp) {
                        // Still within grace period
                        $statusCode = 'occupied_class';
                        $statusText = 'Terpakai (kelas)';
                        $badgeClass = 'badge-primary';
                        $until = date('H:i', strtotime($currentClass['end_time']));
                        $details = 'Mata Kuliah: ' . $currentClass['course_name'] . ' (' . $currentClass['class_name'] . ')';
                    } else {
                        // Grace period elapsed, check absence reports
                        $stmtReports = $db->prepare("
                            SELECT COUNT(DISTINCT reporter_id) as cnt 
                            FROM absence_reports 
                            WHERE schedule_id = ? AND session_date = ? AND status = 'active'
                        ");
                        $stmtReports->execute([$currentClass['id'], $todayDate]);
                        $reporterCount = (int) $stmtReports->fetchColumn();

                        if ($reporterCount >= $absentThreshold) {
                            $isClassAbandoned = true;
                        } else {
                            $statusCode = 'occupied_class_unconfirmed';
                            $statusText = 'Terpakai (belum terkonfirmasi)';
                            $badgeClass = 'badge-warning';
                            $until = date('H:i', strtotime($currentClass['end_time']));
                            $details = 'Mata Kuliah: ' . $currentClass['course_name'] . ' (Belum check-in, ' . $reporterCount . ' laporan)';
                            $canReportAbsent = true;
                        }
                    }
                }
            }

            // FR-068: Check if any class was moved TO this room right now
            if ($statusCode === 'vacant') {
                $stmtMovedTo = $db->prepare("
                    SELECT cs.*, r_orig.code as orig_code, r_orig.name as orig_name
                    FROM class_sessions sess
                    JOIN class_schedules cs ON sess.schedule_id = cs.id
                    JOIN rooms r_orig ON cs.room_id = r_orig.id
                    WHERE sess.moved_to_room_id = ? AND sess.session_date = ? AND sess.status = 'moved'
                      AND cs.start_time <= ? AND cs.end_time > ?
                    LIMIT 1
                ");
                $stmtMovedTo->execute([$room['id'], $todayDate, $now, $now]);
                $movedClass = $stmtMovedTo->fetch();

                if ($movedClass) {
                    $statusCode = 'occupied_class';
                    $statusText = 'Terpakai (kelas pindahan)';
                    $badgeClass = 'badge-primary';
                    $until = date('H:i', strtotime($movedClass['end_time']));
                    $details = 'Mata Kuliah: ' . $movedClass['course_name'] . ' (' . $movedClass['class_name'] . ') - Pindahan dari Ruang ' . $movedClass['orig_code'];
                }
            }
        }

        // 3. If class is not occupying room (no class, or cancelled, or reporters >= N)
        if ($statusCode === 'vacant') {
            // Check approved bookings on this room right now
            $stmtBooking = $db->prepare("
                SELECT b.*, u.name as requester_name 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.room_id = ? AND b.status = 'approved' AND b.booking_date = ? 
                  AND b.start_time <= ? AND b.end_time > ?
                LIMIT 1
            ");
            $stmtBooking->execute([$room['id'], $todayDate, $now, $now]);
            $currentBooking = $stmtBooking->fetch();

            if ($currentBooking) {
                $statusCode = 'occupied_booking';
                $statusText = 'Terpakai (peminjaman)';
                $badgeClass = 'badge-info';
                $until = date('H:i', strtotime($currentBooking['end_time']));
                $details = 'Kegiatan: ' . $currentBooking['activity_name'];
            } elseif ($isClassCancelled || $isClassAbandoned) {
                $statusCode = 'vacant_temp';
                $statusText = 'Kosong sementara';
                $badgeClass = 'badge-warning';
                $until = $currentClassData ? date('H:i', strtotime($currentClassData['end_time'])) : '23:59';
                $details = $isClassCancelled ? 'Kelas dibatalkan, ruangan kosong sementara' : 'Dosen terlapor tidak hadir, ruangan kosong sementara';
            } else {
                $statusCode = 'vacant';
                $statusText = 'Kosong';
                $badgeClass = 'badge-success';
                $until = $this->findNextOccupiedTime($room['id'], $todayDate, $todayWeekday, $now, $termActive);
                $details = 'Tersedia untuk dipinjam';
            }
        }

        // Format time string
        $timeKeterangan = '';
        if (str_starts_with($statusCode, 'occupied')) {
            $timeKeterangan = "Terpakai hingga " . $until;
        } else {
            $timeKeterangan = "Kosong hingga " . $until;
        }

        return [
            'id' => $room['id'],
            'code' => $room['code'],
            'name' => $room['name'],
            'building' => $room['building_name'],
            'floor' => $room['floor'],
            'capacity' => $room['capacity'],
            'can_be_booked' => (bool)$room['can_be_booked'],
            'status' => $statusCode,
            'statusText' => $statusText,
            'badgeClass' => $badgeClass,
            'until' => $until,
            'timeKeterangan' => $timeKeterangan,
            'details' => $details,
            'current_class' => $currentClassData,
            'can_report_absent' => $canReportAbsent
        ];
    }

    /**
     * Find next occupied time (class or booking) for a room today after $now.
     */
    private function findNextOccupiedTime(int $roomId, string $todayDate, int $todayWeekday, string $now, bool $termActive): string {
        $db = Database::getInstance()->getConnection();
        $nextTimes = [];

        if ($termActive) {
            $stmt = $db->prepare("
                SELECT start_time FROM class_schedules 
                WHERE room_id = ? AND weekday = ? AND is_active = 1 AND start_time > ?
                ORDER BY start_time ASC LIMIT 1
            ");
            $stmt->execute([$roomId, $todayWeekday, $now]);
            $time = $stmt->fetchColumn();
            if ($time) {
                $nextTimes[] = $time;
            }
        }

        $stmtBooking = $db->prepare("
            SELECT start_time FROM bookings 
            WHERE room_id = ? AND status = 'approved' AND booking_date = ? AND start_time > ?
            ORDER BY start_time ASC LIMIT 1
        ");
        $stmtBooking->execute([$roomId, $todayDate, $now]);
        $bookingTime = $stmtBooking->fetchColumn();
        if ($bookingTime) {
            $nextTimes[] = $bookingTime;
        }

        if (empty($nextTimes)) {
            return '23:59';
        }

        sort($nextTimes);
        return date('H:i', strtotime($nextTimes[0]));
    }

    /**
     * Check if active term exists and date is not within a no_class_period (BR-003).
     */
    public function isTermActiveOnDate(string $date): bool {
        $db = Database::getInstance()->getConnection();
        
        // Find active semester covering date
        $stmtTerm = $db->prepare("
            SELECT id FROM semesters 
            WHERE is_active = 1 AND start_date <= ? AND end_date >= ?
            LIMIT 1
        ");
        $stmtTerm->execute([$date, $date]);
        $termId = $stmtTerm->fetchColumn();

        if (!$termId) {
            return false;
        }

        // Check if date is in no_class_periods
        $stmtPeriod = $db->prepare("
            SELECT id FROM no_class_periods 
            WHERE semester_id = ? AND start_date <= ? AND end_date >= ?
            LIMIT 1
        ");
        $stmtPeriod->execute([$termId, $date, $date]);
        if ($stmtPeriod->fetchColumn()) {
            return false;
        }

        return true;
    }
}
