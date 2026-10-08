<?php
namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Security;
use App\Support\AuditHelper;
use App\Support\Settings;
use App\Services\BookingConflictService;

class BookingController {
    
    private BookingConflictService $conflictService;

    public function __construct() {
        $this->conflictService = new BookingConflictService();
    }

    public function create() {
        if (!Auth::check()) {
            $_SESSION['flash_error'] = 'Anda harus login untuk meminjam ruangan.';
            header("Location: /webpinjamft/public/login");
            exit;
        }
        
        $selectedRoomId = $_GET['room_id'] ?? null;
        
        $db = Database::getInstance()->getConnection();
        
        // Get all active bookable rooms
        $stmt = $db->query("SELECT id, name, code, capacity FROM rooms WHERE is_active = 1 AND can_be_booked = 1 ORDER BY code ASC");
        $rooms = $stmt->fetchAll();
        
        $title = "Ajukan Peminjaman Ruangan";
        $activeMenu = "booking";
        $baseUrl = '/webpinjamft/public';

        // FR-078: Retrieve suggested alternatives if any
        $alternatives = $_SESSION['booking_alternatives'] ?? null;
        unset($_SESSION['booking_alternatives']);
        
        ob_start();
        require __DIR__ . '/../Views/booking/create.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout/main.php';
    }

    public function store() {
        if (!Auth::check()) {
            header("Location: /webpinjamft/public/login");
            exit;
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/booking/create");
            exit;
        }
        
        $user = Auth::user();
        $roomId = (int)($_POST['room_id'] ?? 0);
        $date = trim($_POST['booking_date'] ?? '');
        $start = trim($_POST['start_time'] ?? '');
        $end = trim($_POST['end_time'] ?? '');
        $activityType = trim($_POST['activity_type'] ?? 'other');
        $activityName = trim($_POST['activity_name'] ?? '');
        $participants = (int)($_POST['participants_count'] ?? 0);
        $contact = trim($_POST['contact_person'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        // FR-079: Peminjaman berulang (weekly recurring pattern)
        $isRecurring = !empty($_POST['is_recurring']);
        $recurringWeeks = $isRecurring ? max(2, min(8, (int)($_POST['recurring_weeks'] ?? 2))) : 1;
        
        $db = Database::getInstance()->getConnection();

        // Fetch room
        $stmtRoom = $db->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmtRoom->execute([$roomId]);
        $room = $stmtRoom->fetch();

        if (!$room) {
            $_SESSION['flash_error'] = 'Ruangan tidak ditemukan.';
            header("Location: /webpinjamft/public/booking/create");
            exit;
        }

        // Generate target dates list
        $targetDates = [];
        for ($w = 0; $w < $recurringWeeks; $w++) {
            $targetDates[] = date('Y-m-d', strtotime("+{$w} weeks", strtotime($date)));
        }

        // 1 & 2. Validate eligibility and conflicts for EACH date independently (FR-071, FR-079)
        foreach ($targetDates as $wIdx => $targetDate) {
            $inputData = [
                'booking_date' => $targetDate,
                'start_time' => $start,
                'end_time' => $end,
                'participants_count' => $participants
            ];

            $eligibility = $this->conflictService->validateEligibility($inputData, $user, $room);
            if (!$eligibility['valid']) {
                $weekLabel = $isRecurring ? " (Pekan ke-" . ($wIdx + 1) . " / " . date('d-m-Y', strtotime($targetDate)) . ")" : "";
                $_SESSION['flash_error'] = $eligibility['message'] . $weekLabel;
                // FR-078: Check alternative rooms
                $_SESSION['booking_alternatives'] = $this->conflictService->getAlternativeRooms($roomId, $targetDate, $start, $end, $participants);
                header("Location: /webpinjamft/public/booking/create?room_id=" . $roomId);
                exit;
            }

            $conflict = $this->conflictService->checkConflicts($roomId, $targetDate, $start, $end);
            if ($conflict['has_conflict']) {
                $weekLabel = $isRecurring ? " pada pertemuan tanggal " . date('d/m/Y', strtotime($targetDate)) . " (Pekan ke-" . ($wIdx + 1) . ")" : "";
                $_SESSION['flash_error'] = "Bentrok{$weekLabel}: " . $conflict['message'];
                // FR-078: Suggest up to 5 alternative rooms
                $_SESSION['booking_alternatives'] = $this->conflictService->getAlternativeRooms($roomId, $targetDate, $start, $end, $participants);
                header("Location: /webpinjamft/public/booking/create?room_id=" . $roomId);
                exit;
            }
        }

        // 3. Handle PDF Attachment (BR-017, FR-070)
        $attachmentPath = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $maxMb = Settings::getInt('upload_max_mb_pdf', 2);
            if ($file['size'] > ($maxMb * 1024 * 1024)) {
                $_SESSION['flash_error'] = "Ukuran lampiran surat melebihi batas maksimal {$maxMb} MB.";
                header("Location: /webpinjamft/public/booking/create?room_id=" . $roomId);
                exit;
            }

            // Check mime
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if ($mime !== 'application/pdf') {
                $_SESSION['flash_error'] = 'Lampiran surat harus berformat PDF.';
                header("Location: /webpinjamft/public/booking/create?room_id=" . $roomId);
                exit;
            }

            $ext = 'pdf';
            $randomFilename = 'surat_' . bin2hex(random_bytes(16)) . '.' . $ext;
            $destination = __DIR__ . '/../../storage/uploads/' . $randomFilename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $attachmentPath = $randomFilename;
            }
        }

        // 4. Save booking (or linked recurring bookings FR-079)
        try {
            $recurringGroupId = $isRecurring ? ('REC-' . $user['id'] . '-' . time() . '-' . bin2hex(random_bytes(3))) : null;

            $stmtInsert = $db->prepare("
                INSERT INTO bookings (room_id, user_id, booking_date, start_time, end_time, activity_type, activity_name, participants_count, contact_person, notes, attachment_path, status, recurring_group_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', ?, NOW())
            ");

            foreach ($targetDates as $wIdx => $targetDate) {
                $sessionNotes = $notes;
                if ($isRecurring) {
                    $prefix = "[Peminjaman Berulang: Pekan " . ($wIdx + 1) . " dari {$recurringWeeks}]";
                    $sessionNotes = empty($sessionNotes) ? $prefix : ($prefix . " " . $sessionNotes);
                }

                $stmtInsert->execute([
                    $roomId,
                    $user['id'],
                    $targetDate,
                    $start,
                    $end,
                    $activityType,
                    $activityName,
                    $participants,
                    $contact,
                    $sessionNotes,
                    $attachmentPath,
                    $recurringGroupId
                ]);
                
                $bookingId = (int)$db->lastInsertId();
                AuditHelper::log('booking_submitted', 'bookings', (string)$bookingId);
            }

            // Queue notification to managers
            $recInfo = $isRecurring ? " (Berulang {$recurringWeeks} pekan)" : "";
            \App\Services\NotificationService::notifyManagers(
                'new_booking_submitted',
                "Permohonan Peminjaman Baru{$recInfo}: {$activityName}",
                "Pengguna {$user['name']} ({$user['role']}) mengajukan peminjaman ruang {$room['name']}{$recInfo} mulai tanggal {$date} ({$start}-{$end})."
            );
            
            if ($isRecurring) {
                $_SESSION['flash_message'] = "Pengajuan peminjaman berulang berhasil dibuat sebanyak {$recurringWeeks} pekan. Setiap tanggal divalidasi dan menunggu persetujuan pengelola.";
            } else {
                $_SESSION['flash_message'] = "Pengajuan peminjaman berhasil dikirim. Menunggu persetujuan pengelola.";
            }
            header("Location: /webpinjamft/public/me/bookings");
            exit;
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = "Gagal menyimpan pengajuan: " . $e->getMessage();
            header("Location: /webpinjamft/public/booking/create?room_id=" . $roomId);
            exit;
        }
    }

    /**
     * FR-078: Endpoint AJAX untuk cek saran ruangan alternatif
     */
    public function alternativesApi() {
        header('Content-Type: application/json; charset=utf-8');
        $roomId = (int)($_GET['room_id'] ?? 0);
        $date = trim($_GET['date'] ?? date('Y-m-d'));
        $start = trim($_GET['start_time'] ?? '08:00');
        $end = trim($_GET['end_time'] ?? '10:00');
        $capacity = (int)($_GET['capacity'] ?? 1);

        if (!$roomId) {
            echo json_encode(['success' => false, 'message' => 'Parameter room_id wajib diisi.']);
            exit;
        }

        $alternatives = $this->conflictService->getAlternativeRooms($roomId, $date, $start, $end, $capacity);
        echo json_encode(['success' => true, 'alternatives' => $alternatives]);
        exit;
    }

    /**
     * FR-077: Riwayat Peminjaman Saya
     */
    public function myBookings() {
        if (!Auth::check()) {
            header("Location: /webpinjamft/public/login");
            exit;
        }

        $title = "Riwayat Peminjaman Saya";
        $activeMenu = "my_bookings";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();
        $userId = Auth::id();

        $stmt = $db->prepare("
            SELECT b.*, r.name as room_name, r.code as room_code 
            FROM bookings b 
            JOIN rooms r ON b.room_id = r.id 
            WHERE b.user_id = ? 
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$userId]);
        $bookings = $stmt->fetchAll();

        ob_start();
        require __DIR__ . '/../Views/booking/my_bookings.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-074: Pembatalan oleh Pemohon
     */
    public function cancel() {
        if (!Auth::check()) {
            header("Location: /webpinjamft/public/login");
            exit;
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/me/bookings");
            exit;
        }

        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $result = $this->conflictService->cancelBooking($bookingId, Auth::id());

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header("Location: /webpinjamft/public/me/bookings");
        exit;
    }
}
