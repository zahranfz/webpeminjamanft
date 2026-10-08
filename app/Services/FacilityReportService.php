<?php
namespace App\Services;

use App\Support\Database;
use App\Support\Settings;
use App\Support\AuditHelper;
use PDO;

class FacilityReportService {

    /**
     * FR-080 & FR-081: Create facility report with photos and duplicate support
     */
    public function createReport(array $data, array $user, array $photos = []): array {
        $db = Database::getInstance()->getConnection();

        $roomId = (int)$data['room_id'];
        $category = $data['category']; // damage, issue, loss
        $facilityName = trim($data['facility_name']);
        $description = trim($data['description']);
        $urgency = $data['urgency'] ?? 'normal'; // normal, urgent

        // Validation
        if (strlen($description) < 10 || strlen($description) > 1000) {
            return ['success' => false, 'message' => 'Deskripsi laporan harus antara 10 hingga 1000 karakter (FR-080).'];
        }

        if (!in_array($category, ['damage', 'issue', 'loss'])) {
            return ['success' => false, 'message' => 'Kategori kendala tidak valid.'];
        }

        $db->beginTransaction();
        try {
            // 1. Insert report
            $stmtInsert = $db->prepare("
                INSERT INTO facility_reports (room_id, user_id, category, facility_name, description, urgency, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'new', NOW())
            ");
            $stmtInsert->execute([$roomId, $user['id'], $category, $facilityName, $description, $urgency]);
            $reportId = (int)$db->lastInsertId();

            // 2. Insert initial history (FR-082)
            $stmtHist = $db->prepare("
                INSERT INTO facility_report_history (report_id, from_status, to_status, actor_id, note, created_at)
                VALUES (?, NULL, 'new', ?, 'Laporan dibuat', NOW())
            ");
            $stmtHist->execute([$reportId, $user['id']]);

            // 3. Process photo uploads (Maks 3 berkas, JPG/PNG <= 2MB, BR-017)
            $maxMb = Settings::getInt('upload_max_mb_photo', 2);
            $photoCount = 0;

            if (!empty($photos['name']) && is_array($photos['name'])) {
                for ($i = 0; $i < count($photos['name']); $i++) {
                    if ($photoCount >= 3) break;
                    if ($photos['error'][$i] !== UPLOAD_ERR_OK) continue;

                    $tmpName = $photos['tmp_name'][$i];
                    $size = $photos['size'][$i];

                    if ($size > ($maxMb * 1024 * 1024)) {
                        continue; // skip files exceeding size
                    }

                    // Strict MIME verification via finfo (BR-017)
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmpName);
                    finfo_close($finfo);

                    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/jpg'])) {
                        continue; // skip non-jpg/png files
                    }

                    $ext = ($mime === 'image/png') ? 'png' : 'jpg';
                    $randomFilename = 'laporan_' . bin2hex(random_bytes(16)) . '.' . $ext;
                    $dest = __DIR__ . '/../../storage/uploads/' . $randomFilename;

                    if (move_uploaded_file($tmpName, $dest)) {
                        $stmtPhoto = $db->prepare("
                            INSERT INTO facility_report_photos (report_id, path, mime)
                            VALUES (?, ?, ?)
                        ");
                        $stmtPhoto->execute([$reportId, $randomFilename, $mime]);
                        $photoCount++;
                    }
                }
            }

            $db->commit();

            // Notify managers
            NotificationService::notifyManagers(
                'new_facility_report',
                "Laporan Fasilitas Baru: {$facilityName}",
                "Pengguna {$user['name']} melaporkan kendala pada fasilitas '{$facilityName}' di ruangan ID {$roomId}. Urgensi: {$urgency}."
            );

            AuditHelper::log('report_submitted', 'facility_reports', (string)$reportId);

            return ['success' => true, 'report_id' => $reportId, 'message' => 'Laporan fasilitas berhasil dikirim dan menunggu verifikasi pengelola.'];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Gagal menyimpan laporan: ' . $e->getMessage()];
        }
    }

    /**
     * FR-081: Add support ("Saya juga mengalami") to active report
     */
    public function addSupport(int $reportId, int $userId): array {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT id, status, facility_name FROM facility_reports WHERE id = ?");
        $stmt->execute([$reportId]);
        $rep = $stmt->fetch();

        if (!$rep || !in_array($rep['status'], ['new', 'verified', 'in_progress'])) {
            return ['success' => false, 'message' => 'Laporan tidak ditemukan atau sudah tidak aktif.'];
        }

        // Check if user already supported
        $stmtCheck = $db->prepare("SELECT report_id FROM facility_report_supports WHERE report_id = ? AND user_id = ?");
        $stmtCheck->execute([$reportId, $userId]);
        if ($stmtCheck->fetch()) {
            return ['success' => false, 'message' => 'Anda sudah memberikan dukungan pada laporan ini sebelumnya.'];
        }

        $stmtInsert = $db->prepare("INSERT INTO facility_report_supports (report_id, user_id) VALUES (?, ?)");
        $stmtInsert->execute([$reportId, $userId]);

        AuditHelper::log('report_support_added', 'facility_reports', (string)$reportId);

        return ['success' => true, 'message' => "Terima kasih, dukungan Anda pada laporan '{$rep['facility_name']}' telah dicatat."];
    }

    /**
     * FR-082: Transition report status with strict BR-015 validation
     */
    public function updateStatus(int $reportId, int $managerId, string $newStatus, ?string $note = null): array {
        $validTransitions = [
            'new' => ['verified', 'rejected'],
            'verified' => ['in_progress', 'rejected'],
            'in_progress' => ['resolved'],
            'resolved' => ['in_progress'] // Reopened by reporter or manager
        ];

        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM facility_reports WHERE id = ?");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch();

        if (!$report) {
            return ['success' => false, 'message' => 'Laporan tidak ditemukan.'];
        }

        $currentStatus = $report['status'];

        // Enforce BR-015
        if (!isset($validTransitions[$currentStatus]) || !in_array($newStatus, $validTransitions[$currentStatus])) {
            return ['success' => false, 'message' => "Transisi status dari '{$currentStatus}' ke '{$newStatus}' tidak diperbolehkan (BR-015)."];
        }

        if ($newStatus === 'rejected' && empty(trim($note ?? ''))) {
            return ['success' => false, 'message' => 'Alasan wajib disertakan saat menolak laporan (BR-015).'];
        }

        $db->beginTransaction();
        try {
            $stmtUpdate = $db->prepare("
                UPDATE facility_reports 
                SET status = ?, manager_id = ?, manager_notes = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$newStatus, $managerId, $note, $reportId]);

            // Insert into history
            $stmtHist = $db->prepare("
                INSERT INTO facility_report_history (report_id, from_status, to_status, actor_id, note, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmtHist->execute([$reportId, $currentStatus, $newStatus, $managerId, $note]);

            $db->commit();

            // Notify reporter (FR-15.1)
            NotificationService::queue(
                (int)$report['user_id'],
                'facility_report_status_changed',
                "Status Laporan Fasilitas Diperbarui: {$newStatus}",
                "Status laporan fasilitas '{$report['facility_name']}' telah diubah dari '{$currentStatus}' menjadi '{$newStatus}'. Catatan pengelola: " . ($note ?: '-')
            );

            AuditHelper::log('report_status_updated', 'facility_reports', (string)$reportId, null, [
                'from_status' => $currentStatus,
                'to_status' => $newStatus,
                'note' => $note
            ]);

            return ['success' => true, 'message' => "Status laporan berhasil diubah menjadi: {$newStatus}."];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Gagal memperbarui status: ' . $e->getMessage()];
        }
    }

    /**
     * FR-083: Reopen resolved report within 7 days
     */
    public function reopenReport(int $reportId, int $userId): array {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM facility_reports WHERE id = ? AND user_id = ?");
        $stmt->execute([$reportId, $userId]);
        $report = $stmt->fetch();

        if (!$report) {
            return ['success' => false, 'code' => 403, 'message' => 'Laporan tidak ditemukan atau bukan milik Anda.'];
        }

        if ($report['status'] !== 'resolved') {
            return ['success' => false, 'message' => 'Hanya laporan yang berstatus selesai (resolved) yang dapat dibuka kembali.'];
        }

        // Check 7 days limit (FR-083, report_reopen_days)
        $maxDays = Settings::getInt('report_reopen_days', 7);
        $resolvedTimestamp = strtotime($report['updated_at']);
        $limitTimestamp = strtotime("+{$maxDays} days", $resolvedTimestamp);

        if (time() > $limitTimestamp) {
            return ['success' => false, 'message' => "Batas waktu membuka kembali laporan ({$maxDays} hari) telah berakhir (FR-083)."];
        }

        $db->beginTransaction();
        try {
            $stmtUpdate = $db->prepare("
                UPDATE facility_reports 
                SET status = 'in_progress', updated_at = NOW() 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$reportId]);

            $stmtHist = $db->prepare("
                INSERT INTO facility_report_history (report_id, from_status, to_status, actor_id, note, created_at)
                VALUES (?, 'resolved', 'in_progress', ?, 'Dibuka kembali oleh pelapor (kendala belum tuntas)', NOW())
            ");
            $stmtHist->execute([$reportId, $userId]);

            $db->commit();

            NotificationService::notifyManagers(
                'facility_report_reopened',
                "Laporan Dibuka Kembali: {$report['facility_name']}",
                "Pelapor membuka kembali laporan fasilitas '{$report['facility_name']}' karena kendala dirasa belum tuntas."
            );

            AuditHelper::log('report_reopened_by_user', 'facility_reports', (string)$reportId);

            return ['success' => true, 'message' => 'Laporan berhasil dibuka kembali dan dikembalikan ke status Diproses (in_progress).'];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Gagal membuka kembali laporan: ' . $e->getMessage()];
        }
    }
}
