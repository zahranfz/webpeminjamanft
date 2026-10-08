<?php
namespace App\Controllers;

use App\Support\Auth;
use App\Support\Database;
use App\Support\Security;
use App\Services\ImportService;

class ImportController {
    
    private ImportService $importService;

    public function __construct() {
        $this->importService = new ImportService();
    }

    private function checkAdmin() {
        if (!Auth::check() || !Auth::hasRole(['manager', 'superadmin'])) {
            $_SESSION['flash_error'] = 'Akses ditolak. Khusus pengelola.';
            header("Location: /webpinjamft/public/");
            exit;
        }
    }

    /**
     * Halaman Utama Impor Jadwal & Riwayat (FR-040, FR-044)
     */
    public function index() {
        $this->checkAdmin();
        $title = "Impor Jadwal Kuliah (Bapendik)";
        $activeMenu = "admin_import";
        $baseUrl = '/webpinjamft/public';
        
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM semesters ORDER BY is_active DESC, start_date DESC");
        $semesters = $stmt->fetchAll();

        // Riwayat Impor (FR-044)
        $history = $this->importService->getImportHistory();
        
        // Cek preview session data jika ada
        $previewData = $_SESSION['import_preview'] ?? null;
        
        ob_start();
        require __DIR__ . '/../Views/admin/import.php';
        $content = ob_get_clean();
        
        require __DIR__ . '/../Views/layout/main.php';
    }

    /**
     * Unduh Template CSV (FR-040)
     */
    public function downloadTemplate() {
        $this->checkAdmin();
        $content = $this->importService->getCsvTemplate();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=template_jadwal_kuliah.csv');
        echo $content;
        exit;
    }

    /**
     * Upload & Pratinjau Validasi CSV (FR-041)
     */
    public function preview() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/import");
            exit;
        }
        
        $semesterId = (int)($_POST['semester_id'] ?? 0);
        if (!$semesterId) {
            $_SESSION['flash_error'] = 'Silakan pilih semester terlebih dahulu.';
            header("Location: /webpinjamft/public/admin/import");
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Gagal mengunggah file. Pastikan file berformat CSV atau Excel (.xlsx) (maksimal 5 MB).';
            header("Location: /webpinjamft/public/admin/import");
            exit;
        }

        $fileSize = $_FILES['csv_file']['size'];
        if ($fileSize > 5 * 1024 * 1024) {
            $_SESSION['flash_error'] = 'Ukuran berkas melebihi batas maksimal 5 MB (FR-040).';
            header("Location: /webpinjamft/public/admin/import");
            exit;
        }
        
        $tmpPath = $_FILES['csv_file']['tmp_name'];
        $originalName = $_FILES['csv_file']['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'xlsx'])) {
            $_SESSION['flash_error'] = 'Format berkas tidak didukung. Harap unggah berkas .csv atau .xlsx (FR-040, FR-046).';
            header("Location: /webpinjamft/public/admin/import");
            exit;
        }

        $result = $this->importService->parseAndValidate($tmpPath, $semesterId, $originalName);

        if (!$result['success']) {
            $_SESSION['flash_error'] = $result['message'];
            header("Location: /webpinjamft/public/admin/import");
            exit;
        }

        // Store preview in session
        $_SESSION['import_preview'] = [
            'semester_id' => $semesterId,
            'filename' => $originalName,
            'total_rows' => $result['total_rows'],
            'valid_count' => $result['valid_count'],
            'invalid_count' => $result['invalid_count'],
            'valid_rows' => $result['valid_rows'],
            'invalid_rows' => $result['invalid_rows']
        ];

        $_SESSION['flash_message'] = "Validasi selesai: {$result['valid_count']} baris valid, {$result['invalid_count']} baris bermasalah.";
        header("Location: /webpinjamft/public/admin/import");
        exit;
    }

    /**
     * Terapkan Hasil Impor (FR-042, FR-045)
     */
    public function commit() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/import");
            exit;
        }

        $preview = $_SESSION['import_preview'] ?? null;
        if (!$preview || empty($preview['valid_rows'])) {
            $_SESSION['flash_error'] = 'Tidak ada data pratinjau impor yang siap diterapkan.';
            header("Location: /webpinjamft/public/admin/import");
            exit;
        }

        $mode = $_POST['mode'] ?? 'replace'; // 'replace' or 'append'
        $allowValidOnly = isset($_POST['allow_valid_only']);

        if ($preview['invalid_count'] > 0 && !$allowValidOnly) {
            $_SESSION['flash_error'] = 'Masih terdapat baris bermasalah. Centang opsi "Terapkan baris valid saja" jika ingin melanjutkan.';
            header("Location: /webpinjamft/public/admin/import");
            exit;
        }

        $result = $this->importService->commitImport(
            $preview['semester_id'],
            Auth::id(),
            $preview['filename'],
            $preview['valid_rows'],
            $mode
        );

        unset($_SESSION['import_preview']);

        if ($result['success']) {
            $msg = "Berhasil menerapkan impor jadwal! Sebanyak {$result['rows_imported']} jadwal aktif.";
            if ($result['conflicts_count'] > 0) {
                $msg .= " Perhatian: Ditemukan {$result['conflicts_count']} peminjaman yang bentrok dan ditandai Perlu Ditinjau (FR-045).";
            }
            $_SESSION['flash_message'] = $msg;
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header("Location: /webpinjamft/public/admin/import");
        exit;
    }

    /**
     * Batal Pratinjau
     */
    public function cancelPreview() {
        $this->checkAdmin();
        unset($_SESSION['import_preview']);
        $_SESSION['flash_message'] = 'Pratinjau impor dibatalkan.';
        header("Location: /webpinjamft/public/admin/import");
        exit;
    }

    /**
     * Rollback Impor (FR-043)
     */
    public function rollback() {
        $this->checkAdmin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
            header("Location: /webpinjamft/public/admin/import");
            exit;
        }

        $importId = (int)($_POST['import_id'] ?? 0);
        $result = $this->importService->rollbackImport($importId, Auth::id());

        if ($result['success']) {
            $_SESSION['flash_message'] = $result['message'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }

        header("Location: /webpinjamft/public/admin/import");
        exit;
    }
}
