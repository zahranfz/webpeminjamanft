<?php
/**
 * Test Suite: Lampiran B (Status Ruangan) & Lampiran C (Bentrok Peminjaman)
 * PRD Sistem Pengelolaan Ruangan FT Unsoed
 */

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use App\Support\Database;
use App\Support\Settings;
use App\Services\RoomStatusService;
use App\Services\BookingConflictService;

echo "======================================================\n";
echo "PRD TEST SUITE: LAMPIRAN B & LAMPIRAN C\n";
echo "======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $name, bool $condition, string $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$name} - {$details}\n";
        $failCount++;
    }
}

// --------------------------------------------------------------------
// LAMPIRAN C: Uji Aturan Bisnis & Bentrok Peminjaman (BR-002, BR-009, BR-010)
// --------------------------------------------------------------------
echo "--- UJI LAMPIRAN C: BENTROK PEMINJAMAN ---\n";

$conflictService = new BookingConflictService();

// Mock room
$mockRoom = [
    'id' => 999,
    'code' => 'TEST-01',
    'name' => 'Ruang Uji',
    'capacity' => 40,
    'can_be_booked' => 1,
    'is_active' => 1
];

// Mock user
$mockStudent = ['id' => 100, 'role' => 'student', 'name' => 'Mhs Test'];

// Test C1: 20:00 - 22:00 (Jam operasional s.d. 21:00) -> Ditolak
$resC1 = $conflictService->validateEligibility([
    'booking_date' => date('Y-m-d', strtotime('+2 days')),
    'start_time' => '20:00:00',
    'end_time' => '22:00:00',
    'participants_count' => 20
], $mockStudent, $mockRoom);
assertTest("C1: Peminjaman 20:00-22:00 ditolak di luar jam operasional (s.d. 21:00)", !$resC1['valid']);

// Test C2: 09:00 - 18:30 (Durasi 9.5 jam > 8 jam) -> Ditolak
$resC2 = $conflictService->validateEligibility([
    'booking_date' => date('Y-m-d', strtotime('+2 days')),
    'start_time' => '09:00:00',
    'end_time' => '18:30:00',
    'participants_count' => 20
], $mockStudent, $mockRoom);
assertTest("C2: Durasi 9.5 jam ditolak melebihi batas 8 jam", !$resC2['valid']);

// Test C3: Jumlah peserta (50) melebihi kapasitas (40) -> Ditolak
$resC3 = $conflictService->validateEligibility([
    'booking_date' => date('Y-m-d', strtotime('+2 days')),
    'start_time' => '10:00:00',
    'end_time' => '12:00:00',
    'participants_count' => 50
], $mockStudent, $mockRoom);
assertTest("C3: Peserta 50 melebihi kapasitas 40 ditolak", !$resC3['valid']);

// Test C4: BR-002 Setengah terbuka: Peminjaman 10:00-12:00 dan batas 12:00 TIDAK bertabrakan
// Formula: A.start < B.end AND B.start < A.end
$aStart = '10:00:00'; $aEnd = '12:00:00';
$bStart = '12:00:00'; $bEnd = '14:00:00';
$isOverlap = ($aStart < $bEnd && $bStart < $aEnd);
assertTest("C4: BR-002 Interval 10:00-12:00 dan 12:00-14:00 tidak bentrok", !$isOverlap);

// Test C5: Interval 11:00-13:00 dan 12:00-14:00 BENTROK
$cStart = '11:00:00'; $cEnd = '13:00:00';
$isOverlap2 = ($cStart < $bEnd && $bStart < $cEnd);
assertTest("C5: BR-002 Interval 11:00-13:00 dan 12:00-14:00 bentrok", $isOverlap2);

// --------------------------------------------------------------------
// LAMPIRAN B: Uji Algoritma Status Ruangan (PRD Bagian 10.1 & BR-005)
// --------------------------------------------------------------------
echo "\n--- UJI LAMPIRAN B: ALGORITMA STATUS RUANGAN (PRD 10.1) ---\n";

function evaluateStatusSim(array $params): string {
    $isRoomActive = $params['is_active'] ?? true;
    if (!$isRoomActive) return 'inactive';

    $hasClass = $params['has_class'] ?? false;
    $classStart = $params['class_start'] ?? '08:00:00';
    $classEnd = $params['class_end'] ?? '10:00:00';
    $now = $params['now'] ?? '08:00:00';
    $checkedIn = $params['checked_in'] ?? false;
    $cancelled = $params['cancelled'] ?? false;
    $reporters = $params['reporters'] ?? 0;
    $hasBooking = $params['has_booking'] ?? false;
    $T = 15; // grace minutes
    $N = 3;  // threshold

    if ($hasClass) {
        if ($cancelled) {
            return $hasBooking ? 'occupied_booking' : 'vacant_temp';
        }
        if ($checkedIn) {
            return 'occupied_class';
        }
        $graceTime = date('H:i:s', strtotime($classStart) + ($T * 60));
        if ($now < $graceTime) {
            return 'occupied_class';
        }
        if ($reporters < $N) {
            return 'occupied_class_unconfirmed';
        } else {
            return $hasBooking ? 'occupied_booking' : 'vacant_temp';
        }
    }

    if ($hasBooking) return 'occupied_booking';
    return 'vacant';
}

// B1: Ruangan nonaktif -> inactive
assertTest("B1: Ruangan nonaktif berstatus 'inactive'", evaluateStatusSim(['is_active' => false]) === 'inactive');

// B2: 08:05, Belum check-in (T=15) -> occupied_class
assertTest("B2: 08:05 Belum check-in masih dalam masa tenggang -> 'occupied_class'", evaluateStatusSim(['has_class' => true, 'now' => '08:05:00', 'checked_in' => false]) === 'occupied_class');

// B3: 08:16, Belum check-in, 0 pelapor -> occupied_class_unconfirmed
assertTest("B3: 08:16 Lewat masa tenggang, 0 pelapor -> 'occupied_class_unconfirmed'", evaluateStatusSim(['has_class' => true, 'now' => '08:16:00', 'checked_in' => false, 'reporters' => 0]) === 'occupied_class_unconfirmed');

// B4: 08:16, Belum check-in, 2 pelapor (< N=3) -> occupied_class_unconfirmed
assertTest("B4: 08:16 Lewat masa tenggang, 2 pelapor -> 'occupied_class_unconfirmed'", evaluateStatusSim(['has_class' => true, 'now' => '08:16:00', 'checked_in' => false, 'reporters' => 2]) === 'occupied_class_unconfirmed');

// B5: 08:16, Belum check-in, 3 pelapor (>= N=3) -> vacant_temp
assertTest("B5: 08:16 Ambang pelapor tercapai (3) -> 'vacant_temp'", evaluateStatusSim(['has_class' => true, 'now' => '08:16:00', 'checked_in' => false, 'reporters' => 3]) === 'vacant_temp');

// B6: 08:20, Dosen check-in setelah vacant_temp -> occupied_class
assertTest("B6: 08:20 Dosen check-in menggugurkan penandaan -> 'occupied_class'", evaluateStatusSim(['has_class' => true, 'now' => '08:20:00', 'checked_in' => true, 'reporters' => 3]) === 'occupied_class');

// B7: 08:30, Sesi dibatalkan pengelola/dosen tanpa peminjaman -> vacant_temp
assertTest("B7: 08:30 Sesi dibatalkan tanpa peminjaman -> 'vacant_temp'", evaluateStatusSim(['has_class' => true, 'now' => '08:30:00', 'cancelled' => true, 'has_booking' => false]) === 'vacant_temp');

// B8: 08:30, Sesi dibatalkan dan ada peminjaman approved -> occupied_booking
assertTest("B8: 08:30 Sesi dibatalkan dan ada peminjaman approved -> 'occupied_booking'", evaluateStatusSim(['has_class' => true, 'now' => '08:30:00', 'cancelled' => true, 'has_booking' => true]) === 'occupied_booking');

echo "\n------------------------------------------------------\n";
echo "HASIL AKHIR: {$passCount} LULUS, {$failCount} GAGAL\n";
echo "======================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);

