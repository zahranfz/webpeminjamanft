<?php
namespace App\Services;

use App\Support\Database;
use App\Support\AuditHelper;
use App\Support\Settings;
use PDO;

class ImportService {

    /**
     * FR-040: Download Template CSV
     */
    public function getCsvTemplate(): string {
        $output = "course_code,course_name,class_name,lecturer_name,lecturer_ref,weekday,start_time,end_time,room_code,attendees\n";
        $output .= "TI101,Algoritma dan Pemrograman,A,Dr. Ir. Budi Santoso,198001012005011001,Senin,08:00,09:40,A-101,40\n";
        $output .= "TI102,Struktur Data,B,Siti Rahayu M.Kom,198502022010122002,Selasa,10:00,11:40,B-201,35\n";
        return $output;
    }

    /**
     * FR-041 & FR-046: Parse and validate CSV or XLSX rows before applying
     */
    public function parseAndValidate(string $filePath, int $semesterId, string $originalFilename = ''): array {
        $db = Database::getInstance()->getConnection();

        // Load active rooms map
        $stmtRooms = $db->query("SELECT id, code, name FROM rooms WHERE is_active = 1");
        $rooms = $stmtRooms->fetchAll();
        $roomCodeMap = [];
        $roomNames = [];
        foreach ($rooms as $r) {
            $roomCodeMap[strtoupper(trim($r['code']))] = $r['id'];
            $roomNames[] = strtoupper(trim($r['code']));
        }

        // Determine if XLSX or CSV
        $ext = strtolower(pathinfo($originalFilename ?: $filePath, PATHINFO_EXTENSION));
        if ($ext === 'xlsx' || $this->isZipFile($filePath)) {
            try {
                $rawRows = $this->readXlsxRows($filePath);
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Gagal membaca berkas Excel (.xlsx): ' . $e->getMessage()];
            }
        } else {
            $rawRows = $this->readCsvRows($filePath);
            if ($rawRows === false) {
                return ['success' => false, 'message' => 'Gagal membaca berkas CSV atau berkas kosong.'];
            }
        }

        if (empty($rawRows)) {
            return ['success' => false, 'message' => 'Berkas tidak memiliki data baris atau kosong.'];
        }

        $header = array_shift($rawRows);
        if (!$header || empty(array_filter($header))) {
            return ['success' => false, 'message' => 'Header berkas kosong atau tidak ditemukan.'];
        }

        // Normalize header
        $header = array_map(function($h) {
            return strtolower(trim(str_replace(['"', "'", "\xEF\xBB\xBF"], '', (string)$h)));
        }, $header);

        $colIndices = [];
        $requiredCols = ['course_code', 'course_name', 'class_name', 'lecturer_name', 'weekday', 'start_time', 'end_time', 'room_code'];
        foreach ($requiredCols as $req) {
            $idx = array_search($req, $header);
            if ($idx === false) {
                // Try alternate naming
                if ($req === 'weekday') {
                    $idx = array_search('hari', $header);
                } elseif ($req === 'room_code') {
                    $idx = array_search('ruangan', $header);
                }
            }
            if ($idx === false) {
                fclose($handle);
                return ['success' => false, 'message' => "Kolom wajib '{$req}' tidak ditemukan dalam berkas CSV."];
            }
            $colIndices[$req] = $idx;
        }

        $colIndices['lecturer_ref'] = array_search('lecturer_ref', $header);
        $colIndices['attendees'] = array_search('attendees', $header);

        $hariMap = [
            'senin' => 1, 'selasa' => 2, 'rabu' => 3, 'kamis' => 4, 'jumat' => 5, 'sabtu' => 6, 'minggu' => 7,
            '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7
        ];

        $validRows = [];
        $invalidRows = [];
        $rowNumber = 1;
        $seenSlots = []; // To check collision within CSV (BR-004)

        foreach ($rawRows as $row) {
            $rowNumber++;
            if (empty(array_filter($row, function($v) { return $v !== '' && $v !== null; }))) continue; // skip blank line

            $courseCode = trim((string)($row[$colIndices['course_code']] ?? ''));
            $courseName = trim((string)($row[$colIndices['course_name']] ?? ''));
            $className = trim((string)($row[$colIndices['class_name']] ?? ''));
            $lecturerName = trim((string)($row[$colIndices['lecturer_name']] ?? ''));
            $rawWeekday = strtolower(trim((string)($row[$colIndices['weekday']] ?? '')));
            $startTime = $this->formatTimeCell($row[$colIndices['start_time']] ?? '');
            $endTime = $this->formatTimeCell($row[$colIndices['end_time']] ?? '');
            $roomCode = strtoupper(trim((string)($row[$colIndices['room_code']] ?? '')));
            $lecturerRef = ($colIndices['lecturer_ref'] !== false) ? trim((string)($row[$colIndices['lecturer_ref']] ?? '')) : '';
            $attendees = ($colIndices['attendees'] !== false && isset($row[$colIndices['attendees']]) && $row[$colIndices['attendees']] !== '') ? (int)$row[$colIndices['attendees']] : null;

            $errors = [];

            if (empty($courseCode) || empty($courseName) || empty($className) || empty($lecturerName)) {
                $errors[] = "Data mata kuliah / kelas / dosen tidak boleh kosong.";
            }

            $weekday = $hariMap[$rawWeekday] ?? null;
            if (!$weekday) {
                $errors[] = "Hari '{$rawWeekday}' tidak valid (Gunakan Senin-Minggu atau 1-7).";
            }

            if (!preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $startTime) || !preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $endTime)) {
                $errors[] = "Format jam harus HH:MM (contoh 08:00).";
            } else {
                $formattedStart = date('H:i:s', strtotime($startTime));
                $formattedEnd = date('H:i:s', strtotime($endTime));
                if ($formattedStart >= $formattedEnd) {
                    $errors[] = "Jam selesai ({$endTime}) harus setelah jam mulai ({$startTime}).";
                }
            }

            $roomId = $roomCodeMap[$roomCode] ?? null;
            if (!$roomId) {
                $errors[] = "Kode ruangan '{$roomCode}' tidak ditemukan di data master.";
            }

            // Check collision within the file (BR-004)
            if (empty($errors)) {
                $slotKey = "{$roomId}_{$weekday}";
                if (isset($seenSlots[$slotKey])) {
                    foreach ($seenSlots[$slotKey] as $prev) {
                        // Check overlap
                        if ($formattedStart < $prev['end'] && $formattedEnd > $prev['start']) {
                            // Check BR-004 parallel merged class exception
                            if ($courseCode === $prev['course_code'] && $lecturerRef === $prev['lecturer_ref']) {
                                // Allowed parallel class merge
                            } else {
                                $errors[] = "Bentrok dengan baris {$prev['row']} di ruangan {$roomCode} pada jam {$prev['start']}-{$prev['end']}.";
                            }
                        }
                    }
                }
            }

            if (empty($errors)) {
                $rowData = [
                    'row_number' => $rowNumber,
                    'semester_id' => $semesterId,
                    'room_id' => $roomId,
                    'room_code' => $roomCode,
                    'course_code' => $courseCode,
                    'course_name' => $courseName,
                    'class_name' => $className,
                    'lecturer_name' => $lecturerName,
                    'lecturer_ref' => $lecturerRef,
                    'weekday' => $weekday,
                    'start_time' => $formattedStart,
                    'end_time' => $formattedEnd,
                    'attendees' => $attendees
                ];
                $validRows[] = $rowData;
                $seenSlots["{$roomId}_{$weekday}"][] = [
                    'row' => $rowNumber,
                    'start' => $formattedStart,
                    'end' => $formattedEnd,
                    'course_code' => $courseCode,
                    'lecturer_ref' => $lecturerRef
                ];
            } else {
                $invalidRows[] = [
                    'row_number' => $rowNumber,
                    'course_code' => $courseCode,
                    'course_name' => $courseName,
                    'room_code' => $roomCode,
                    'errors' => $errors
                ];
            }
        }

        return [
            'success' => true,
            'total_rows' => count($validRows) + count($invalidRows),
            'valid_count' => count($validRows),
            'invalid_count' => count($invalidRows),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows
        ];
    }

    /**
     * Check if file starts with ZIP magic bytes PK\x03\x04
     */
    private function isZipFile(string $filePath): bool {
        $fh = @fopen($filePath, 'rb');
        if (!$fh) return false;
        $bytes = fread($fh, 4);
        fclose($fh);
        return $bytes === "PK\x03\x04";
    }

    /**
     * Read CSV rows into simple 2D array
     */
    private function readCsvRows(string $filePath) {
        $handle = @fopen($filePath, 'r');
        if (!$handle) return false;

        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $rows = [];
        while (($row = fgetcsv($handle, 2000, $delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    /**
     * FR-046: Read XLSX first sheet into simple 2D array
     */
    public function readXlsxRows(string $filePath): array {
        if (!class_exists('\ZipArchive')) {
            throw new \Exception('Ekstensi PHP ZipArchive tidak tersedia di server.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Gagal membuka berkas Excel (.xlsx).');
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $txt = '';
                        foreach ($si->r as $r) {
                            $txt .= (string)$r->t;
                        }
                        $sharedStrings[] = $txt;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Identify first worksheet XML
        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXmlContent === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/sheet[0-9]+\.xml$#i', $entryName)) {
                    $sheetXmlContent = $zip->getFromName($entryName);
                    break;
                }
            }
        }

        if ($sheetXmlContent === false) {
            $zip->close();
            throw new \Exception('Sheet pertama tidak ditemukan di dalam berkas .xlsx.');
        }

        $sheetXml = @simplexml_load_string($sheetXmlContent);
        $zip->close();

        if (!$sheetXml || !isset($sheetXml->sheetData)) {
            throw new \Exception('Data sheet Excel kosong atau tidak terbaca.');
        }

        $rows = [];
        foreach ($sheetXml->sheetData->row as $rowNode) {
            $rowCells = [];
            $maxColIdx = -1;

            foreach ($rowNode->c as $cell) {
                $coord = (string)$cell['r']; // e.g. "A1", "C2"
                $colLetters = preg_replace('/[0-9]/', '', $coord);
                $colIdx = 0;
                $len = strlen($colLetters);
                for ($k = 0; $k < $len; $k++) {
                    $colIdx = $colIdx * 26 + (ord(strtoupper($colLetters[$k])) - ord('A') + 1);
                }
                $colIdx -= 1; // 0-based

                $type = (string)$cell['t'];
                $val = '';
                if ($type === 's') {
                    $strIdx = (int)$cell->v;
                    $val = $sharedStrings[$strIdx] ?? '';
                } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                    $val = (string)$cell->is->t;
                } elseif (isset($cell->v)) {
                    $val = (string)$cell->v;
                }

                $rowCells[$colIdx] = trim($val);
                if ($colIdx > $maxColIdx) {
                    $maxColIdx = $colIdx;
                }
            }

            // Fill all columns up to maxColIdx
            $denseRow = [];
            for ($c = 0; $c <= $maxColIdx; $c++) {
                $denseRow[$c] = $rowCells[$c] ?? '';
            }
            if (!empty(array_filter($denseRow, function($v) { return $v !== '' && $v !== null; }))) {
                $rows[] = $denseRow;
            }
        }

        return $rows;
    }

    /**
     * Helper to format cell time string or numeric fraction
     */
    private function formatTimeCell($val): string {
        $val = trim((string)$val);
        if ($val === '') return '';

        // If decimal fraction from Excel (e.g. 0.333333 -> 08:00)
        if (is_numeric($val) && (float)$val > 0.0 && (float)$val < 1.0) {
            $totalSec = (int) round(((float)$val) * 86400);
            $h = floor($totalSec / 3600);
            $m = floor(($totalSec % 3600) / 60);
            return sprintf('%02d:%02d', $h, $m);
        }

        return $val;
    }

    /**
     * FR-042 & FR-045: Commit import in atomic transaction and check post-import conflicts
     */
    public function commitImport(int $semesterId, int $userId, string $filename, array $validRows, string $mode = 'replace'): array {
        $db = Database::getInstance()->getConnection();
        $batchId = 'BATCH-' . $semesterId . '-' . time();

        $db->beginTransaction();
        try {
            // 1. Insert into schedule_imports
            $stmtImport = $db->prepare("
                INSERT INTO schedule_imports (semester_id, filename, uploaded_by, total_rows, valid_rows, status, mode, committed_at)
                VALUES (?, ?, ?, ?, ?, 'committed', ?, NOW())
            ");
            $stmtImport->execute([$semesterId, $filename, $userId, count($validRows), count($validRows), $mode]);
            $importId = (int) $db->lastInsertId();

            // 2. If mode is 'replace', disable previous schedules in this semester
            if ($mode === 'replace') {
                $stmtDeactivate = $db->prepare("UPDATE class_schedules SET is_active = 0 WHERE semester_id = ?");
                $stmtDeactivate->execute([$semesterId]);
            }

            // 3. Insert valid schedules
            $stmtInsert = $db->prepare("
                INSERT INTO class_schedules (semester_id, room_id, course_code, course_name, class_name, lecturer_name, lecturer_ref, weekday, start_time, end_time, attendees, import_batch, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");

            foreach ($validRows as $r) {
                $stmtInsert->execute([
                    $semesterId,
                    $r['room_id'],
                    $r['course_code'],
                    $r['course_name'],
                    $r['class_name'],
                    $r['lecturer_name'],
                    $r['lecturer_ref'] ?: null,
                    $r['weekday'],
                    $r['start_time'],
                    $r['end_time'],
                    $r['attendees'],
                    $batchId
                ]);
            }

            // 4. FR-045: Post-import conflict detection with approved bookings
            // Check approved bookings that collide with new schedules
            $stmtTerm = $db->prepare("SELECT start_date, end_date FROM semesters WHERE id = ?");
            $stmtTerm->execute([$semesterId]);
            $term = $stmtTerm->fetch();

            $conflictedBookingIds = [];
            if ($term) {
                $stmtBookings = $db->prepare("
                    SELECT b.id, b.room_id, b.booking_date, b.start_time, b.end_time 
                    FROM bookings b 
                    WHERE b.status = 'approved' AND b.booking_date >= ? AND b.booking_date <= ?
                ");
                $stmtBookings->execute([date('Y-m-d'), $term['end_date']]);
                $approvedBookings = $stmtBookings->fetchAll();

                foreach ($approvedBookings as $bk) {
                    $weekday = (int) date('N', strtotime($bk['booking_date']));
                    // Check against newly imported schedules
                    foreach ($validRows as $sc) {
                        if ((int)$sc['room_id'] === (int)$bk['room_id'] && (int)$sc['weekday'] === $weekday) {
                            // Check overlap (BR-002)
                            if ($sc['start_time'] < $bk['end_time'] && $sc['end_time'] > $bk['start_time']) {
                                $conflictedBookingIds[] = $bk['id'];
                                break;
                            }
                        }
                    }
                }

                if (!empty($conflictedBookingIds)) {
                    $uniqueIds = array_unique($conflictedBookingIds);
                    $inPlaceholders = implode(',', array_fill(0, count($uniqueIds), '?'));
                    $stmtMark = $db->prepare("UPDATE bookings SET needs_review = 1 WHERE id IN ($inPlaceholders)");
                    $stmtMark->execute($uniqueIds);

                    NotificationService::notifyManagers(
                        'post_import_conflict',
                        "Konflik Pasca-Impor: " . count($uniqueIds) . " Peminjaman Perlu Ditinjau",
                        "Jadwal baru yang diimpor beririsan dengan " . count($uniqueIds) . " peminjaman yang telah disetujui sebelumnya. Peminjaman telah ditandai Perlu Ditinjau di dashboard pengelola."
                    );
                }
            }

            $db->commit();

            AuditHelper::log('import_schedule', 'schedule_imports', (string)$importId, null, [
                'batch_id' => $batchId,
                'total_rows' => count($validRows),
                'mode' => $mode,
                'conflicted_bookings_count' => count($conflictedBookingIds)
            ]);

            return [
                'success' => true,
                'import_id' => $importId,
                'batch_id' => $batchId,
                'rows_imported' => count($validRows),
                'conflicts_count' => count($conflictedBookingIds)
            ];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => "Gagal menerapkan impor jadwal: " . $e->getMessage()];
        }
    }

    /**
     * FR-043: Rollback import to previous batch
     */
    public function rollbackImport(int $importId, int $userId): array {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT * FROM schedule_imports WHERE id = ?");
        $stmt->execute([$importId]);
        $import = $stmt->fetch();

        if (!$import) {
            return ['success' => false, 'message' => 'Data riwayat impor tidak ditemukan.'];
        }

        if ($import['status'] === 'rolled_back') {
            return ['success' => false, 'message' => 'Batch impor ini sudah di-rollback sebelumnya.'];
        }

        $semesterId = $import['semester_id'];

        $db->beginTransaction();
        try {
            // 1. Mark this import as rolled_back
            $stmtUpdate = $db->prepare("UPDATE schedule_imports SET status = 'rolled_back' WHERE id = ?");
            $stmtUpdate->execute([$importId]);

            // 2. Deactivate schedules from this batch
            // The batch filename or created_at corresponds to import
            $stmtDeact = $db->prepare("UPDATE class_schedules SET is_active = 0 WHERE semester_id = ?");
            $stmtDeact->execute([$semesterId]);

            // 3. Find previous committed import for this semester
            $stmtPrev = $db->prepare("
                SELECT id FROM schedule_imports 
                WHERE semester_id = ? AND id < ? AND status = 'committed'
                ORDER BY id DESC LIMIT 1
            ");
            $stmtPrev->execute([$semesterId, $importId]);
            $prevImportId = $stmtPrev->fetchColumn();

            $restoredCount = 0;
            if ($prevImportId) {
                // We re-activate schedules of the previous batch if stored, or previous active batch
                // Let's activate schedules from previous batch
                $stmtPrevBatch = $db->prepare("SELECT import_batch FROM class_schedules WHERE semester_id = ? AND import_batch IS NOT NULL GROUP BY import_batch ORDER BY id DESC LIMIT 1 OFFSET 1");
                $stmtPrevBatch->execute([$semesterId]);
                $prevBatchName = $stmtPrevBatch->fetchColumn();

                if ($prevBatchName) {
                    $stmtReactivate = $db->prepare("UPDATE class_schedules SET is_active = 1 WHERE semester_id = ? AND import_batch = ?");
                    $stmtReactivate->execute([$semesterId, $prevBatchName]);
                    $restoredCount = $stmtReactivate->rowCount();
                }
            }

            $db->commit();

            AuditHelper::log('rollback_import', 'schedule_imports', (string)$importId, null, [
                'semester_id' => $semesterId,
                'restored_previous_id' => $prevImportId
            ]);

            return [
                'success' => true,
                'message' => "Batch impor berhasil di-rollback. " . ($restoredCount > 0 ? "Jadwal batch sebelumnya diaktifkan kembali ({$restoredCount} jadwal)." : "Seluruh jadwal batch ini telah dinonaktifkan.")
            ];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Gagal melakukan rollback: ' . $e->getMessage()];
        }
    }

    /**
     * FR-044: Get list of import history
     */
    public function getImportHistory(?int $semesterId = null): array {
        $db = Database::getInstance()->getConnection();
        $sql = "
            SELECT si.*, sem.name as semester_name, u.name as uploader_name 
            FROM schedule_imports si
            JOIN semesters sem ON si.semester_id = sem.id
            JOIN users u ON si.uploaded_by = u.id
        ";
        $params = [];
        if ($semesterId) {
            $sql .= " WHERE si.semester_id = ?";
            $params[] = $semesterId;
        }
        $sql .= " ORDER BY si.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
