<?php
namespace App\Services;

use App\Support\Database;
use App\Support\Settings;
use App\Support\AuditHelper;
use PDO;

class BookingConflictService {

    /**
     * Validate booking eligibility rules (BR-009, BR-012).
     */
    public function validateEligibility(array $data, array $user, array $room): array {
        $db = Database::getInstance()->getConnection();
        $todayDate = date('Y-m-d');
        $now = date('H:i:s');

        $bookingDate = $data['booking_date'];
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];
        $participants = (int) $data['participants_count'];

        // 1. Room active and bookable check
        if (!(bool)$room['is_active'] || !(bool)$room['can_be_booked']) {
            return ['valid' => false, 'message' => 'Ruangan ini sedang tidak dapat dipinjam.'];
        }

        // 2. Capacity check
        if ($participants > (int)$room['capacity']) {
            return ['valid' => false, 'message' => "Jumlah peserta ({$participants}) melebihi kapasitas ruangan ({$room['capacity']} orang)."];
        }

        // 3. Time comparison
        if ($startTime >= $endTime) {
            return ['valid' => false, 'message' => 'Jam selesai harus lebih akhir dari jam mulai.'];
        }

        // 4. Max duration check (default 8 hours)
        $maxHours = Settings::getInt('booking_max_duration_hours', 8);
        $durationSeconds = strtotime($endTime) - strtotime($startTime);
        if ($durationSeconds > ($maxHours * 3600)) {
            return ['valid' => false, 'message' => "Durasi peminjaman maksimal adalah {$maxHours} jam."];
        }

        // 5. Operating hours and days check (default 07:00 - 21:00, Senin - Sabtu)
        $operatingStart = Settings::getString('operating_start', '07:00');
        $operatingEnd = Settings::getString('operating_end', '21:00');
        $operatingDays = explode(',', Settings::getString('operating_days', '1,2,3,4,5,6'));
        $bookingWeekday = (string) date('N', strtotime($bookingDate));

        if (!in_array($bookingWeekday, $operatingDays)) {
            return ['valid' => false, 'message' => 'Peminjaman ruangan tidak diizinkan pada hari tersebut (di luar hari operasional).'];
        }

        if ($startTime < $operatingStart || $endTime > $operatingEnd) {
            return ['valid' => false, 'message' => "Jam peminjaman harus berada dalam jam operasional ({$operatingStart} - {$operatingEnd} WIB)."];
        }

        // 6. Lead time check (minimum lead time from start time, default 120 minutes)
        $minLeadMinutes = Settings::getInt('booking_min_lead_minutes', 120);
        $bookingStartTimestamp = strtotime("{$bookingDate} {$startTime}");
        $currentTimestamp = time();

        if ($bookingStartTimestamp < ($currentTimestamp + ($minLeadMinutes * 60))) {
            return ['valid' => false, 'message' => "Pengajuan peminjaman minimal diajukan {$minLeadMinutes} menit sebelum jam mulai kegiatan."];
        }

        // 7. Max advance days check (default 90 days)
        $maxAdvanceDays = Settings::getInt('booking_max_advance_days', 90);
        $maxAdvanceTimestamp = strtotime("+{$maxAdvanceDays} days", strtotime($todayDate));
        if (strtotime($bookingDate) > $maxAdvanceTimestamp) {
            return ['valid' => false, 'message' => "Pengajuan peminjaman maksimal dilakukan {$maxAdvanceDays} hari ke depan."];
        }

        // 8. BR-012: Concurrent submitted bookings limit per user
        $limitKey = ($user['role'] === 'lecturer') ? 'max_submitted_bookings_lecturer' : 'max_submitted_bookings_student';
        $defaultLimit = ($user['role'] === 'lecturer') ? 10 : 3;
        $maxSubmitted = Settings::getInt($limitKey, $defaultLimit);

        $stmtCount = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'submitted'");
        $stmtCount->execute([$user['id']]);
        $submittedCount = (int) $stmtCount->fetchColumn();

        if ($submittedCount >= $maxSubmitted) {
            return ['valid' => false, 'message' => "Anda telah mencapai batas maksimal ({$maxSubmitted}) pengajuan peminjaman yang masih berstatus menunggu."];
        }

        return ['valid' => true];
    }

    /**
     * Check conflicts against active class schedules and approved bookings (BR-002, BR-010).
     */
    public function checkConflicts(int $roomId, string $bookingDate, string $startTime, string $endTime): array {
        $db = Database::getInstance()->getConnection();
        $weekday = (int) date('N', strtotime($bookingDate));

        // 1. Check class schedules on that weekday
        // Note: class session cancelled by lecturer or manager does NOT conflict (FR-061, BR-010)
        $stmtClasses = $db->prepare("
            SELECT cs.*, sess.status as session_status
            FROM class_schedules cs
            LEFT JOIN class_sessions sess ON cs.id = sess.schedule_id AND sess.session_date = ?
            WHERE cs.room_id = ? AND cs.weekday = ? AND cs.is_active = 1
              AND cs.start_time < ? AND cs.end_time > ?
        ");
        $stmtClasses->execute([$bookingDate, $roomId, $weekday, $endTime, $startTime]);
        $conflictedClasses = $stmtClasses->fetchAll();

        foreach ($conflictedClasses as $cc) {
            if ($cc['session_status'] === 'cancelled_by_lecturer' || $cc['session_status'] === 'cancelled_by_manager' || $cc['session_status'] === 'moved') {
                // Class is cancelled or moved for this date, slot is free to book! (BR-010, FR-068)
                continue;
            }
            return [
                'has_conflict' => true,
                'type' => 'class',
                'message' => "Bentrok dengan jadwal perkuliahan: {$cc['course_name']} ({$cc['start_time']} - {$cc['end_time']})."
            ];
        }

        // Check classes moved INTO this room on this date (FR-068)
        $stmtMovedClasses = $db->prepare("
            SELECT cs.*, sess.status as session_status
            FROM class_sessions sess
            JOIN class_schedules cs ON sess.schedule_id = cs.id
            WHERE sess.moved_to_room_id = ? AND sess.session_date = ? AND sess.status = 'moved'
              AND cs.start_time < ? AND cs.end_time > ?
            LIMIT 1
        ");
        $stmtMovedClasses->execute([$roomId, $bookingDate, $endTime, $startTime]);
        $movedIn = $stmtMovedClasses->fetch();
        if ($movedIn) {
            return [
                'has_conflict' => true,
                'type' => 'class',
                'message' => "Bentrok dengan perkuliahan pindahan: {$movedIn['course_name']} ({$movedIn['start_time']} - {$movedIn['end_time']})."
            ];
        }

        // 2. Check approved bookings on this date (BR-002: start < end2 AND end > start2)
        $stmtBookings = $db->prepare("
            SELECT b.*, u.name as user_name 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            WHERE b.room_id = ? AND b.booking_date = ? AND b.status = 'approved'
              AND b.start_time < ? AND b.end_time > ?
            LIMIT 1
        ");
        $stmtBookings->execute([$roomId, $bookingDate, $endTime, $startTime]);
        $conflictedBooking = $stmtBookings->fetch();

        if ($conflictedBooking) {
            return [
                'has_conflict' => true,
                'type' => 'booking',
                'message' => "Bentrok dengan peminjaman yang telah disetujui: '{$conflictedBooking['activity_name']}' ({$conflictedBooking['start_time']} - {$conflictedBooking['end_time']})."
            ];
        }

        return ['has_conflict' => false];
    }

    /**
     * FR-078: Suggest up to 5 alternative vacant rooms if conflict occurs.
     */
    public function getAlternativeRooms(int $requestedRoomId, string $bookingDate, string $startTime, string $endTime, int $minCapacity = 1): array {
        $db = Database::getInstance()->getConnection();

        // Get requested room details (building_id)
        $stmtReq = $db->prepare("SELECT building_id, capacity FROM rooms WHERE id = ?");
        $stmtReq->execute([$requestedRoomId]);
        $reqRoom = $stmtReq->fetch();
        $targetBuildingId = $reqRoom['building_id'] ?? 0;
        $capacityNeeded = max($minCapacity, 1);

        // Fetch candidate active, bookable rooms with capacity >= capacityNeeded
        // Order by: same building first, then closest capacity match, then room code
        $stmtCandidates = $db->prepare("
            SELECT r.*, b.name as building_name
            FROM rooms r
            JOIN buildings b ON r.building_id = b.id
            WHERE r.id != ?
              AND r.is_active = 1
              AND r.can_be_booked = 1
              AND r.capacity >= ?
            ORDER BY 
              (r.building_id = ?) DESC,
              r.capacity ASC,
              r.name ASC
        ");
        $stmtCandidates->execute([$requestedRoomId, $capacityNeeded, $targetBuildingId]);
        $candidates = $stmtCandidates->fetchAll();

        $alternatives = [];
        foreach ($candidates as $cand) {
            // Check if candidate room has any conflict during requested date and time
            $conflict = $this->checkConflicts((int)$cand['id'], $bookingDate, $startTime, $endTime);
            if (!$conflict['has_conflict']) {
                $alternatives[] = [
                    'id' => (int)$cand['id'],
                    'code' => $cand['code'],
                    'name' => $cand['name'],
                    'capacity' => (int)$cand['capacity'],
                    'building_id' => (int)$cand['building_id'],
                    'building_name' => $cand['building_name'],
                    'is_same_building' => ((int)$cand['building_id'] === (int)$targetBuildingId)
                ];
                if (count($alternatives) >= 5) {
                    break;
                }
            }
        }

        return $alternatives;
    }

    /**
     * FR-071 & NFR-008: Approve booking with row-level locking and priority conflict resolution.
     */
    public function approveBooking(int $bookingId, int $managerId, ?string $notes = null, bool $rejectCompeting = true): array {
        $db = Database::getInstance()->getConnection();

        $db->beginTransaction();
        try {
            // Row-level lock on the booking
            $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ? FOR UPDATE");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();

            if (!$booking || $booking['status'] !== 'submitted') {
                $db->rollBack();
                return ['success' => false, 'message' => 'Peminjaman tidak ditemukan atau sudah diproses oleh pengelola lain.'];
            }

            // Lock and re-check conflicts with approved bookings (PRD line 970-978, NFR-008)
            $stmtCheck = $db->prepare("
                SELECT id, activity_name FROM bookings 
                WHERE room_id = ? AND booking_date = ? AND status = 'approved'
                  AND start_time < ? AND end_time > ?
                FOR UPDATE
            ");
            $stmtCheck->execute([$booking['room_id'], $booking['booking_date'], $booking['end_time'], $booking['start_time']]);
            $conflict = $stmtCheck->fetch();

            if ($conflict) {
                $db->rollBack();
                return ['success' => false, 'message' => "Bentrok dengan peminjaman '{$conflict['activity_name']}' yang baru saja disetujui."];
            }

            // Also check active class conflict
            $weekday = (int) date('N', strtotime($booking['booking_date']));
            $stmtClass = $db->prepare("
                SELECT cs.course_name, sess.status as session_status 
                FROM class_schedules cs 
                LEFT JOIN class_sessions sess ON cs.id = sess.schedule_id AND sess.session_date = ?
                WHERE cs.room_id = ? AND cs.weekday = ? AND cs.is_active = 1
                  AND cs.start_time < ? AND cs.end_time > ?
            ");
            $stmtClass->execute([$booking['booking_date'], $booking['room_id'], $weekday, $booking['end_time'], $booking['start_time']]);
            $classConflict = $stmtClass->fetch();

            if ($classConflict && $classConflict['session_status'] !== 'cancelled_by_lecturer' && $classConflict['session_status'] !== 'cancelled_by_manager') {
                $db->rollBack();
                return ['success' => false, 'message' => "Bentrok dengan jadwal perkuliahan: {$classConflict['course_name']}."];
            }

            // Approve booking
            $stmtApprove = $db->prepare("
                UPDATE bookings 
                SET status = 'approved', manager_id = ?, decision_notes = ?, decided_at = NOW(), needs_review = 0 
                WHERE id = ?
            ");
            $stmtApprove->execute([$managerId, $notes, $bookingId]);

            // FR-072: If requested, reject competing submitted student bookings
            $rejectedCompetingCount = 0;
            if ($rejectCompeting) {
                $stmtCompeting = $db->prepare("
                    SELECT b.id, b.user_id 
                    FROM bookings b 
                    WHERE b.room_id = ? AND b.booking_date = ? AND b.status = 'submitted' AND b.id != ?
                      AND b.start_time < ? AND b.end_time > ?
                ");
                $stmtCompeting->execute([$booking['room_id'], $booking['booking_date'], $bookingId, $booking['end_time'], $booking['start_time']]);
                $competing = $stmtCompeting->fetchAll();

                foreach ($competing as $cmp) {
                    $stmtRej = $db->prepare("
                        UPDATE bookings 
                        SET status = 'rejected', manager_id = ?, decision_notes = 'Diprioritaskan untuk kegiatan lain (FR-072)', decided_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmtRej->execute([$managerId, $cmp['id']]);
                    $rejectedCompetingCount++;

                    NotificationService::queue(
                        (int)$cmp['user_id'],
                        'booking_rejected',
                        'Permohonan Peminjaman Ruangan Ditolak',
                        'Permohonan peminjaman ruangan Anda ditolak dengan alasan: Diprioritaskan untuk kegiatan lain.'
                    );
                }
            }

            $db->commit();

            // Notify approved user
            NotificationService::queue(
                (int)$booking['user_id'],
                'booking_approved',
                'Permohonan Peminjaman Ruangan Disetujui',
                "Permohonan peminjaman Anda untuk kegiatan '{$booking['activity_name']}' pada tanggal {$booking['booking_date']} telah DISETUJUI oleh pengelola."
            );

            AuditHelper::log('booking_approved', 'bookings', (string)$bookingId, null, [
                'manager_id' => $managerId,
                'rejected_competing_count' => $rejectedCompetingCount
            ]);

            return [
                'success' => true,
                'message' => 'Peminjaman berhasil disetujui.' . ($rejectedCompetingCount > 0 ? " ({$rejectedCompetingCount} permohonan bersaing otomatis ditolak)." : '')
            ];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()];
        }
    }

    /**
     * FR-073: Reject booking with mandatory reason.
     */
    public function rejectBooking(int $bookingId, int $managerId, string $reason): array {
        if (empty(trim($reason))) {
            return ['success' => false, 'message' => 'Alasan penolakan wajib diisi (FR-073).'];
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            return ['success' => false, 'message' => 'Peminjaman tidak ditemukan.'];
        }

        $stmtUpdate = $db->prepare("
            UPDATE bookings 
            SET status = 'rejected', manager_id = ?, decision_notes = ?, decided_at = NOW() 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$managerId, $reason, $bookingId]);

        NotificationService::queue(
            (int)$booking['user_id'],
            'booking_rejected',
            'Permohonan Peminjaman Ruangan Ditolak',
            "Permohonan peminjaman ruangan Anda untuk '{$booking['activity_name']}' pada tanggal {$booking['booking_date']} ditolak oleh pengelola. Alasan: {$reason}"
        );

        AuditHelper::log('booking_rejected', 'bookings', (string)$bookingId, null, [
            'reason' => $reason,
            'manager_id' => $managerId
        ]);

        return ['success' => true, 'message' => 'Peminjaman berhasil ditolak.'];
    }

    /**
     * FR-074: Requester cancels booking before start time.
     */
    public function cancelBooking(int $bookingId, int $userId): array {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->execute([$bookingId, $userId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            return ['success' => false, 'code' => 403, 'message' => 'Peminjaman tidak ditemukan atau bukan milik Anda.'];
        }

        if (in_array($booking['status'], ['cancelled', 'rejected', 'displaced'])) {
            return ['success' => false, 'message' => 'Peminjaman ini sudah tidak aktif.'];
        }

        $startTimestamp = strtotime("{$booking['booking_date']} {$booking['start_time']}");
        if (time() >= $startTimestamp) {
            return ['success' => false, 'message' => 'Tidak dapat membatalkan peminjaman karena waktu kegiatan sudah lewat/sedang berlangsung (FR-074).'];
        }

        $stmtUpdate = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmtUpdate->execute([$bookingId]);

        AuditHelper::log('booking_cancelled_by_user', 'bookings', (string)$bookingId);

        return ['success' => true, 'message' => 'Peminjaman berhasil dibatalkan.'];
    }

    /**
     * FR-075: Geser peminjaman mahasiswa yang sudah disetujui demi kebutuhan dosen (BR-011).
     * AC 1: Given peminjaman mahasiswa approved, When digeser, Then status displaced,
     * alasan wajib tersimpan, pemohon dinotifikasi, audit log terisi.
     */
    public function displaceBooking(int $bookingId, int $managerId, string $reason): array {
        if (empty(trim($reason))) {
            return ['success' => false, 'message' => 'Alasan penggeseran peminjaman wajib diisi (FR-075 & BR-011).'];
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT b.*, u.role as user_role, u.name as user_name, u.email as user_email 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            return ['success' => false, 'message' => 'Peminjaman tidak ditemukan.'];
        }

        if ($booking['status'] !== 'approved') {
            return ['success' => false, 'message' => 'Hanya peminjaman dengan status Approved yang dapat digeser.'];
        }

        $stmtUpdate = $db->prepare("
            UPDATE bookings 
            SET status = 'displaced', manager_id = ?, decision_notes = ?, decided_at = NOW() 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$managerId, $reason, $bookingId]);

        // Notifikasi pemohon (FR-075 AC 1)
        NotificationService::queue(
            (int)$booking['user_id'],
            'booking_displaced',
            'Pemberitahuan Penggeseran Peminjaman Ruangan (Prioritas Dosen)',
            "Mohon maaf, peminjaman ruangan Anda untuk '{$booking['activity_name']}' pada tanggal {$booking['booking_date']} digeser/dibatalkan oleh pengelola demi kebutuhan prioritas kegiatan dosen (BR-011). Alasan: {$reason}."
        );

        // Audit log (FR-075 AC 1)
        AuditHelper::log('booking_displaced', 'bookings', (string)$bookingId, [
            'status' => 'approved',
            'user_id' => $booking['user_id'],
            'user_role' => $booking['user_role']
        ], [
            'status' => 'displaced',
            'manager_id' => $managerId,
            'reason' => $reason
        ]);

        return ['success' => true, 'message' => 'Peminjaman berhasil digeser (displaced) dan pemohon telah dinotifikasi.'];
    }
}
