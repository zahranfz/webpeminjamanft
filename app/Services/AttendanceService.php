<?php
namespace App\Services;

use App\Support\Database;
use App\Support\Settings;
use App\Support\AuditHelper;
use PDO;

class AttendanceService {

    /**
     * FR-060: Lecturer Check-in
     * Check-in is allowed from 15 minutes before start_time until end_time.
     */
    public function lecturerCheckin(int $scheduleId, array $currentUser): array {
        $db = Database::getInstance()->getConnection();
        $todayDate = date('Y-m-d');
        $now = date('H:i:s');
        $todayWeekday = (int) date('N');

        // Fetch schedule
        $stmt = $db->prepare("SELECT * FROM class_schedules WHERE id = ? AND is_active = 1");
        $stmt->execute([$scheduleId]);
        $schedule = $stmt->fetch();

        if (!$schedule) {
            return ['success' => false, 'message' => 'Jadwal kelas tidak ditemukan.'];
        }

        // Verify ownership (by lecturer_user_id or lecturer_ref or lecturer_name)
        $isOwner = false;
        if (!empty($schedule['lecturer_user_id']) && (int)$schedule['lecturer_user_id'] === (int)$currentUser['id']) {
            $isOwner = true;
        } elseif (!empty($schedule['lecturer_ref']) && !empty($currentUser['identity_number']) && $schedule['lecturer_ref'] === $currentUser['identity_number']) {
            $isOwner = true;
        } elseif (trim(strtolower($schedule['lecturer_name'])) === trim(strtolower($currentUser['name']))) {
            $isOwner = true;
        }

        if (!$isOwner) {
            return ['success' => false, 'code' => 403, 'message' => 'Anda tidak memiliki akses check-in untuk kelas ini.'];
        }

        if ((int)$schedule['weekday'] !== $todayWeekday) {
            return ['success' => false, 'message' => 'Kelas ini tidak dijadwalkan untuk hari ini.'];
        }

        // Check time window (BR-006: opened 15 minutes before start_time until end_time)
        $minutesBefore = Settings::getInt('checkin_open_minutes_before', 15);
        $openTimestamp = strtotime($todayDate . ' ' . $schedule['start_time']) - ($minutesBefore * 60);
        $endTimestamp = strtotime($todayDate . ' ' . $schedule['end_time']);
        $nowTimestamp = strtotime($todayDate . ' ' . $now);

        if ($nowTimestamp < $openTimestamp) {
            $openTimeStr = date('H:i', $openTimestamp);
            return ['success' => false, 'message' => "Check-in baru dibuka 15 menit sebelum kelas (pukul {$openTimeStr})."];
        }

        if ($nowTimestamp > $endTimestamp) {
            return ['success' => false, 'message' => 'Sesi kelas untuk hari ini sudah berakhir.'];
        }

        // Upsert class_sessions
        $stmtSession = $db->prepare("SELECT id, status FROM class_sessions WHERE schedule_id = ? AND session_date = ?");
        $stmtSession->execute([$scheduleId, $todayDate]);
        $existing = $stmtSession->fetch();

        if ($existing) {
            if ($existing['status'] === 'checked_in') {
                return ['success' => false, 'message' => 'Anda sudah melakukan check-in hari ini.'];
            }
            $stmtUpdate = $db->prepare("
                UPDATE class_sessions 
                SET status = 'checked_in', actual_start_time = ?, checked_in_at = NOW() 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$now, $existing['id']]);
        } else {
            $stmtInsert = $db->prepare("
                INSERT INTO class_sessions (schedule_id, session_date, status, actual_start_time, checked_in_at)
                VALUES (?, ?, 'checked_in', ?, NOW())
            ");
            $stmtInsert->execute([$scheduleId, $todayDate, $now]);
        }

        // Void any absence reports for this session (FR-066)
        $stmtVoid = $db->prepare("UPDATE absence_reports SET status = 'void' WHERE schedule_id = ? AND session_date = ?");
        $stmtVoid->execute([$scheduleId, $todayDate]);

        AuditHelper::log('class_checkin', 'class_schedules', (string)$scheduleId);

        return ['success' => true, 'message' => "Berhasil melakukan check-in untuk kelas {$schedule['course_name']}."];
    }

    /**
     * FR-061: Lecturer cancels class session in advance or today before end_time
     */
    public function lecturerCancelSession(int $scheduleId, array $currentUser, string $sessionDate, ?string $reason = null): array {
        $db = Database::getInstance()->getConnection();
        $todayDate = date('Y-m-d');
        $now = date('H:i:s');

        // Schedule check
        $stmt = $db->prepare("SELECT cs.*, r.name as room_name FROM class_schedules cs JOIN rooms r ON cs.room_id = r.id WHERE cs.id = ? AND cs.is_active = 1");
        $stmt->execute([$scheduleId]);
        $schedule = $stmt->fetch();

        if (!$schedule) {
            return ['success' => false, 'message' => 'Jadwal tidak ditemukan.'];
        }

        // Verify ownership
        $isOwner = false;
        if (!empty($schedule['lecturer_user_id']) && (int)$schedule['lecturer_user_id'] === (int)$currentUser['id']) {
            $isOwner = true;
        } elseif (!empty($schedule['lecturer_ref']) && !empty($currentUser['identity_number']) && $schedule['lecturer_ref'] === $currentUser['identity_number']) {
            $isOwner = true;
        } elseif (trim(strtolower($schedule['lecturer_name'])) === trim(strtolower($currentUser['name']))) {
            $isOwner = true;
        }

        if (!$isOwner) {
            return ['success' => false, 'code' => 403, 'message' => 'Akses ditolak. Bukan kelas milik Anda.'];
        }

        if ($sessionDate < $todayDate) {
            return ['success' => false, 'message' => 'Tidak dapat membatalkan sesi kelas di masa lalu.'];
        }

        if ($sessionDate === $todayDate) {
            $endTimestamp = strtotime($todayDate . ' ' . $schedule['end_time']);
            if (time() > $endTimestamp) {
                return ['success' => false, 'message' => 'Sesi kelas untuk hari ini sudah berakhir, tidak dapat dibatalkan.'];
            }
        }

        // Upsert class_sessions
        $stmtSession = $db->prepare("SELECT id FROM class_sessions WHERE schedule_id = ? AND session_date = ?");
        $stmtSession->execute([$scheduleId, $sessionDate]);
        $existing = $stmtSession->fetch();

        if ($existing) {
            $stmtUpdate = $db->prepare("
                UPDATE class_sessions 
                SET status = 'cancelled_by_lecturer', note = ?, decided_by = ?
                WHERE id = ?
            ");
            $stmtUpdate->execute([$reason, $currentUser['id'], $existing['id']]);
        } else {
            $stmtInsert = $db->prepare("
                INSERT INTO class_sessions (schedule_id, session_date, status, note, decided_by)
                VALUES (?, ?, 'cancelled_by_lecturer', ?, ?)
            ");
            $stmtInsert->execute([$scheduleId, $sessionDate, $reason, $currentUser['id']]);
        }

        // Notify managers (FR-061)
        NotificationService::notifyManagers(
            'class_cancelled_by_lecturer',
            "Dosen Membatalkan Kelas: {$schedule['course_name']}",
            "Dosen {$currentUser['name']} membatalkan sesi kelas {$schedule['course_name']} ({$schedule['class_name']}) pada tanggal {$sessionDate}. Ruang {$schedule['room_name']} kini kosong untuk slot tersebut. Alasan: " . ($reason ?: '-')
        );

        AuditHelper::log('class_cancel_lecturer', 'class_schedules', (string)$scheduleId, null, ['session_date' => $sessionDate, 'reason' => $reason]);

        return ['success' => true, 'message' => "Kelas untuk tanggal {$sessionDate} berhasil dibatalkan. Ruangan kini dapat dipinjam."];
    }

    /**
     * FR-063 & FR-064 & BR-007: Student reports lecturer absent
     */
    public function studentReportAbsent(int $scheduleId, array $currentUser): array {
        if ($currentUser['role'] !== 'student') {
            return ['success' => false, 'code' => 403, 'message' => 'Hanya mahasiswa yang dapat melaporkan kehadiran dosen (BR-007).'];
        }

        $db = Database::getInstance()->getConnection();
        $todayDate = date('Y-m-d');
        $now = date('H:i:s');
        $todayWeekday = (int) date('N');

        // Fetch schedule
        $stmt = $db->prepare("SELECT cs.*, r.name as room_name FROM class_schedules cs JOIN rooms r ON cs.room_id = r.id WHERE cs.id = ? AND cs.is_active = 1");
        $stmt->execute([$scheduleId]);
        $schedule = $stmt->fetch();

        if (!$schedule) {
            return ['success' => false, 'message' => 'Jadwal kelas tidak ditemukan.'];
        }

        if ((int)$schedule['weekday'] !== $todayWeekday) {
            return ['success' => false, 'message' => 'Kelas tidak dijadwalkan pada hari ini.'];
        }

        // Check if session is already checked in or cancelled
        $stmtSession = $db->prepare("SELECT status FROM class_sessions WHERE schedule_id = ? AND session_date = ?");
        $stmtSession->execute([$scheduleId, $todayDate]);
        $session = $stmtSession->fetch();

        if ($session) {
            if ($session['status'] === 'checked_in' || $session['status'] === 'confirmed_held_by_manager' || $session['status'] === 'ongoing') {
                return ['success' => false, 'message' => 'Dosen sudah terkonfirmasi hadir untuk sesi ini.'];
            }
            if ($session['status'] === 'cancelled_by_lecturer' || $session['status'] === 'cancelled_by_manager') {
                return ['success' => false, 'message' => 'Sesi kelas ini sudah dibatalkan sebelumnya.'];
            }
        }

        // BR-006: Check time window [start_time + grace_minutes, end_time)
        $graceMinutes = Settings::getInt('grace_minutes', 15);
        $graceTimestamp = strtotime($todayDate . ' ' . $schedule['start_time']) + ($graceMinutes * 60);
        $endTimestamp = strtotime($todayDate . ' ' . $schedule['end_time']);
        $nowTimestamp = strtotime($todayDate . ' ' . $now);

        if ($nowTimestamp < $graceTimestamp) {
            $minTime = date('H:i', $graceTimestamp);
            return ['success' => false, 'message' => "Laporan ketidakhadiran baru dapat dikirim setelah masa tenggang ({$graceMinutes} menit setelah kelas dimulai, pukul {$minTime})."];
        }

        if ($nowTimestamp >= $endTimestamp) {
            return ['success' => false, 'message' => 'Jam perkuliahan untuk sesi ini telah selesai.'];
        }

        // BR-007: Check daily limit per student (default 5 reports per day)
        $dailyLimit = Settings::getInt('absent_reports_per_user_per_day', 5);
        $stmtDaily = $db->prepare("
            SELECT COUNT(*) FROM absence_reports 
            WHERE reporter_id = ? AND DATE(created_at) = ?
        ");
        $stmtDaily->execute([$currentUser['id'], $todayDate]);
        $dailyCount = (int) $stmtDaily->fetchColumn();

        if ($dailyCount >= $dailyLimit) {
            return ['success' => false, 'message' => "Anda telah mencapai batas maksimal {$dailyLimit} laporan ketidakhadiran per hari."];
        }

        // Check if student already reported this session
        $stmtDuplicate = $db->prepare("
            SELECT id FROM absence_reports 
            WHERE schedule_id = ? AND session_date = ? AND reporter_id = ?
        ");
        $stmtDuplicate->execute([$scheduleId, $todayDate, $currentUser['id']]);
        if ($stmtDuplicate->fetch()) {
            return ['success' => false, 'message' => 'Anda sudah melaporkan ketidakhadiran dosen untuk sesi kelas ini.'];
        }

        // Insert report
        $stmtInsert = $db->prepare("
            INSERT INTO absence_reports (schedule_id, session_date, reporter_id, status, created_at)
            VALUES (?, ?, ?, 'active', NOW())
        ");
        $stmtInsert->execute([$scheduleId, $todayDate, $currentUser['id']]);

        // Count distinct active reporters (FR-064)
        $stmtCount = $db->prepare("
            SELECT COUNT(DISTINCT reporter_id) as cnt 
            FROM absence_reports 
            WHERE schedule_id = ? AND session_date = ? AND status = 'active'
        ");
        $stmtCount->execute([$scheduleId, $todayDate]);
        $distinctCount = (int) $stmtCount->fetchColumn();

        $threshold = Settings::getInt('absent_report_threshold', 3);

        if ($distinctCount >= $threshold) {
            // Threshold reached! Notify managers and lecturer without revealing reporters (BR-008)
            NotificationService::notifyManagers(
                'absent_threshold_reached',
                "Ambang Laporan Dosen Belum Hadir: {$schedule['course_name']}",
                "Sesi mata kuliah {$schedule['course_name']} di Ruang {$schedule['room_name']} telah menerima {$distinctCount} laporan ketidakhadiran mahasiswa. Ruangan kini berstatus Kosong Sementara dan menunggu verifikasi pengelola."
            );

            // Notify lecturer if account exists
            if (!empty($schedule['lecturer_user_id'])) {
                NotificationService::queue(
                    (int)$schedule['lecturer_user_id'],
                    'absent_threshold_reached_lecturer',
                    "Pemberitahuan: Sesi Kelas {$schedule['course_name']} Dilaporkan Kosong Sementara",
                    "Mahasiswa telah melaporkan ketidakhadiran dosen untuk sesi {$schedule['course_name']} hari ini di Ruang {$schedule['room_name']}. Jika Anda hadir, silakan lakukan check-in segera di menu Kelas Saya untuk mengembalikan status ruangan."
                );
            }
        }

        AuditHelper::log('absence_reported', 'class_schedules', (string)$scheduleId, null, [
            'session_date' => $todayDate,
            'reporter_count' => $distinctCount
        ]);

        return [
            'success' => true,
            'message' => "Laporan ketidakhadiran dosen berhasil dicatat (Total: {$distinctCount} laporan).",
            'distinct_count' => $distinctCount,
            'threshold_reached' => ($distinctCount >= $threshold)
        ];
    }

    /**
     * FR-065: Manager verifies reported session
     */
    public function managerVerifySession(int $scheduleId, string $sessionDate, array $currentUser, string $decision, ?string $note = null): array {
        if (!in_array($currentUser['role'], ['manager', 'superadmin'])) {
            return ['success' => false, 'code' => 403, 'message' => 'Akses ditolak. Khusus pengelola.'];
        }

        if (!in_array($decision, ['cancelled_by_manager', 'confirmed_held_by_manager'])) {
            return ['success' => false, 'message' => 'Keputusan verifikasi tidak valid.'];
        }

        $db = Database::getInstance()->getConnection();

        // Upsert class_sessions
        $stmtSession = $db->prepare("SELECT id FROM class_sessions WHERE schedule_id = ? AND session_date = ?");
        $stmtSession->execute([$scheduleId, $sessionDate]);
        $existing = $stmtSession->fetch();

        if ($existing) {
            $stmtUpdate = $db->prepare("
                UPDATE class_sessions 
                SET status = ?, note = ?, decided_by = ? 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$decision, $note, $currentUser['id'], $existing['id']]);
        } else {
            $stmtInsert = $db->prepare("
                INSERT INTO class_sessions (schedule_id, session_date, status, note, decided_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$scheduleId, $sessionDate, $decision, $note, $currentUser['id']]);
        }

        AuditHelper::log('session_manager_verify', 'class_schedules', (string)$scheduleId, null, [
            'session_date' => $sessionDate,
            'decision' => $decision,
            'note' => $note,
            'manager' => $currentUser['name']
        ]);

        $label = ($decision === 'cancelled_by_manager') ? 'Tidak Berlangsung (Dibatalkan Pengelola)' : 'Tetap Berlangsung';
        return ['success' => true, 'message' => "Sesi berhasil diverifikasi sebagai: {$label}."];
    }

    /**
     * Get list of unconfirmed and reported sessions for Manager Dashboard (FR-065 & FR-091)
     */
    public function getPendingSessionsForManager(): array {
        $db = Database::getInstance()->getConnection();
        $todayDate = date('Y-m-d');
        $now = date('H:i:s');
        $todayWeekday = (int) date('N');
        $threshold = Settings::getInt('absent_report_threshold', 3);

        // Find schedules today that have active absence reports or unconfirmed status
        $stmt = $db->prepare("
            SELECT cs.*, r.name as room_name, r.code as room_code,
                   COALESCE(sess.status, 'no_session') as session_status,
                   sess.note as manager_note,
                   COUNT(DISTINCT ar.reporter_id) as reporter_count
            FROM class_schedules cs
            JOIN rooms r ON cs.room_id = r.id
            LEFT JOIN class_sessions sess ON cs.id = sess.schedule_id AND sess.session_date = ?
            LEFT JOIN absence_reports ar ON cs.id = ar.schedule_id AND ar.session_date = ? AND ar.status = 'active'
            WHERE cs.is_active = 1 AND cs.weekday = ?
            GROUP BY cs.id, sess.id
            HAVING reporter_count > 0 OR (session_status = 'no_session' AND cs.start_time <= ?)
            ORDER BY reporter_count DESC, cs.start_time ASC
        ");
        $stmt->execute([$todayDate, $todayDate, $todayWeekday, $now]);
        return $stmt->fetchAll();
    }

    /**
     * FR-068: Lecturer or manager moves class session to an alternative vacant room
     */
    public function moveClassSession(int $scheduleId, string $sessionDate, int $targetRoomId, array $currentUser, ?string $reason = null): array {
        $db = Database::getInstance()->getConnection();
        $todayDate = date('Y-m-d');
        $now = date('H:i:s');

        // 1. Fetch schedule
        $stmt = $db->prepare("SELECT cs.*, r.name as orig_room_name, r.code as orig_room_code FROM class_schedules cs JOIN rooms r ON cs.room_id = r.id WHERE cs.id = ? AND cs.is_active = 1");
        $stmt->execute([$scheduleId]);
        $schedule = $stmt->fetch();

        if (!$schedule) {
            return ['success' => false, 'message' => 'Jadwal kelas tidak ditemukan.'];
        }

        // 2. Authorization check (manager, superadmin, or lecturer owner)
        $isManager = in_array($currentUser['role'] ?? '', ['manager', 'superadmin']);
        $isOwner = false;
        if (!empty($schedule['lecturer_user_id']) && (int)$schedule['lecturer_user_id'] === (int)$currentUser['id']) {
            $isOwner = true;
        } elseif (!empty($schedule['lecturer_ref']) && !empty($currentUser['identity_number']) && $schedule['lecturer_ref'] === $currentUser['identity_number']) {
            $isOwner = true;
        } elseif (trim(strtolower($schedule['lecturer_name'])) === trim(strtolower($currentUser['name'] ?? ''))) {
            $isOwner = true;
        }

        if (!$isManager && !$isOwner) {
            return ['success' => false, 'code' => 403, 'message' => 'Akses ditolak. Hanya dosen pengampu atau pengelola yang dapat memindahkan ruangan kelas.'];
        }

        // Date check
        if ($sessionDate < $todayDate) {
            return ['success' => false, 'message' => 'Tidak dapat memindahkan sesi kelas pada tanggal yang telah lewat.'];
        }

        if ($sessionDate === $todayDate && $now > $schedule['end_time']) {
            return ['success' => false, 'message' => 'Sesi kelas hari ini sudah berakhir.'];
        }

        // Target room check
        if ($targetRoomId === (int)$schedule['room_id']) {
            return ['success' => false, 'message' => 'Ruangan tujuan sama dengan ruangan kelas saat ini.'];
        }

        $stmtTarget = $db->prepare("SELECT * FROM rooms WHERE id = ? AND is_active = 1");
        $stmtTarget->execute([$targetRoomId]);
        $targetRoom = $stmtTarget->fetch();

        if (!$targetRoom) {
            return ['success' => false, 'message' => 'Ruangan tujuan tidak ditemukan atau sedang dinonaktifkan.'];
        }

        // 3. Conflict check on target room (FR-068 AC: Given ruangan tujuan kosong)
        $conflictService = new BookingConflictService();
        $conflict = $conflictService->checkConflicts($targetRoomId, $sessionDate, $schedule['start_time'], $schedule['end_time']);
        if ($conflict['has_conflict']) {
            return ['success' => false, 'message' => "Ruangan tujuan tidak kosong pada slot tersebut: " . $conflict['message']];
        }

        // 4. Upsert class_sessions with status 'moved' and moved_to_room_id
        $stmtSession = $db->prepare("SELECT id FROM class_sessions WHERE schedule_id = ? AND session_date = ?");
        $stmtSession->execute([$scheduleId, $sessionDate]);
        $existing = $stmtSession->fetch();

        if ($existing) {
            $stmtUpdate = $db->prepare("
                UPDATE class_sessions 
                SET status = 'moved', moved_to_room_id = ?, decided_by = ?, note = ? 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$targetRoomId, $currentUser['id'], $reason, $existing['id']]);
        } else {
            $stmtInsert = $db->prepare("
                INSERT INTO class_sessions (schedule_id, session_date, status, moved_to_room_id, decided_by, note) 
                VALUES (?, ?, 'moved', ?, ?, ?)
            ");
            $stmtInsert->execute([$scheduleId, $sessionDate, $targetRoomId, $currentUser['id'], $reason]);
        }

        // 5. Audit Log
        AuditHelper::log('class_moved_room', 'class_schedules', (string)$scheduleId, [
            'session_date' => $sessionDate,
            'from_room_id' => $schedule['room_id'],
            'to_room_id' => $targetRoomId,
            'reason' => $reason
        ]);

        // 6. Notify managers
        NotificationService::notifyManagers(
            'class_moved_room',
            "Pemindahan Ruangan Kelas: {$schedule['course_name']}",
            "Kelas {$schedule['course_name']} ({$schedule['class_name']}) pada tanggal {$sessionDate} dipindahkan dari Ruang {$schedule['orig_room_name']} ke Ruang {$targetRoom['name']}."
        );

        return [
            'success' => true,
            'message' => "Sesi kelas {$schedule['course_name']} berhasil dipindahkan ke Ruang {$targetRoom['name']} ({$targetRoom['code']})."
        ];
    }
}
