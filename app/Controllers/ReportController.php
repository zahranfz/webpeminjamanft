<?php
namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Security;
use App\Support\Settings;
use App\Services\FacilityReportService;

class ReportController {
    
    private FacilityReportService $reportService;

    public function __construct() {
        $this->reportService = new FacilityReportService();
    }

    public function create() {
        if (!Auth::check()) {
            $_SESSION['flash_error'] = 'Anda harus login untuk membuat laporan fasilitas.';
            header("Location: /webpinjamft/public/login");
            exit;
        }
        
        $selectedRoomId = (int)($_GET['room_id'] ?? 0);
        $db = Database::getInstance()->getConnection();
        
        // Rooms
        $stmt = $db->query("SELECT id, name, code FROM rooms WHERE is_active = 1 ORDER BY code ASC");
        $rooms = $stmt->fetchAll();

        // If room is selected, load room facilities and active reports (FR-081)
        $roomFacilities = [];
        $activeReports = [];
        if ($selectedRoomId) {
            $stmtFac = $db->prepare("SELECT type FROM room_facilities WHERE room_id = ? GROUP BY type");
            $stmtFac->execute([$selectedRoomId]);
            $roomFacilities = $stmtFac->fetchAll();

            $stmtActive = $db->prepare("
                SELECT fr.*, COUNT(s.user_id) as supports_count 
                FROM facility_reports fr 
                LEFT JOIN facility_report_supports s ON fr.id = s.report_id 
                WHERE fr.room_id = ? AND fr.status IN ('new', 'verified', 'in_progress')
                GROUP BY fr.id
                ORDER BY fr.created_at DESC
            ");
            $stmtActive->execute([$selectedRoomId]);
            $activeReports = $stmtActive->fetchAll();
        }
        
        $title = "Laporkan Kendala Fasilitas Ruangan";
        $activeMenu = "report";
        $baseUrl = '/webpinjamft/public';
        
        ob_start();
        require __DIR__ . '/../Views/report/create.php';
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
            header("Location: /webpinjamft/public/report/create");
            exit;
        }
        
        $data = [
            'room_id' => (int)($_POST['room_id'] ?? 0),
            'category' => trim($_POST['category'] ?? ''),
            'facility_name' => trim($_POST['facility_name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'urgency' => trim($_POST['urgency'] ?? 'normal')
        ];

        $photos = $_FILES['photos'] ?? [];

        $result = $this->reportService->createReport($data, Auth::user(), $photos);

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
            header("Location: /webpinjamft/public/me/reports");
            exit;
        } else {
            $_SESSION['flash_error'] = $result['message'];
            header("Location: /webpinjamft/public/report/create?room_id=" . $data['room_id']);
            exit;
        }
    }

    /**
     * FR-081: Tombol "Saya juga mengalami"
     */
    public function support() {
        if (!Auth::check()) {
            header("Location: /webpinjamft/public/login");
            exit;
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/");
            exit;
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        $result = $this->reportService->addSupport($reportId, Auth::id());

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        $redirect = $_SERVER['HTTP_REFERER'] ?? '/webpinjamft/public/me/reports';
        header("Location: " . $redirect);
        exit;
    }

    /**
     * FR-083: Riwayat Laporan Saya
     */
    public function myReports() {
        if (!Auth::check()) {
            header("Location: /webpinjamft/public/login");
            exit;
        }

        $title = "Riwayat Laporan Fasilitas Saya";
        $activeMenu = "my_reports";
        $baseUrl = '/webpinjamft/public';

        $db = Database::getInstance()->getConnection();
        $userId = Auth::id();

        $stmt = $db->prepare("
            SELECT fr.*, r.name as room_name, r.code as room_code,
                   (SELECT COUNT(*) FROM facility_report_supports WHERE report_id = fr.id) as supports_count
            FROM facility_reports fr 
            JOIN rooms r ON fr.room_id = r.id 
            WHERE fr.user_id = ? 
            ORDER BY fr.created_at DESC
        ");
        $stmt->execute([$userId]);
        $reports = $stmt->fetchAll();

        // Get photos for these reports
        $reportPhotos = [];
        if (!empty($reports)) {
            $ids = array_column($reports, 'id');
            $inClause = implode(',', array_fill(0, count($ids), '?'));
            $stmtPhotos = $db->prepare("SELECT report_id, path FROM facility_report_photos WHERE report_id IN ($inClause)");
            $stmtPhotos->execute($ids);
            while ($row = $stmtPhotos->fetch()) {
                $reportPhotos[$row['report_id']][] = $row['path'];
            }
        }

        ob_start();
        require __DIR__ . '/../Views/report/my_reports.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * FR-083: Buka kembali laporan selesai dalam 7 hari
     */
    public function reopen() {
        if (!Auth::check()) {
            header("Location: /webpinjamft/public/login");
            exit;
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/me/reports");
            exit;
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        $result = $this->reportService->reopenReport($reportId, Auth::id());

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header("Location: /webpinjamft/public/me/reports");
        exit;
    }
}
