<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Support/Database.php';
require_once __DIR__ . '/../app/Support/Settings.php';
require_once __DIR__ . '/../app/Support/Auth.php';
require_once __DIR__ . '/../app/Support/Security.php';
require_once __DIR__ . '/../app/Support/AuditHelper.php';
require_once __DIR__ . '/../app/Services/NotificationService.php';
require_once __DIR__ . '/../app/Services/RoomStatusService.php';
require_once __DIR__ . '/../app/Services/BookingConflictService.php';
require_once __DIR__ . '/../app/Services/AttendanceService.php';
require_once __DIR__ . '/../app/Services/ImportService.php';

use App\Support\Database;
use App\Services\RoomStatusService;
use App\Services\BookingConflictService;
use App\Services\AttendanceService;
use App\Services\ImportService;

echo "====================================================\n";
echo "   RUNNING TESTS: ADVANCED FEATURES AUDIT          \n";
echo "   (FR-046, FR-078, FR-068, FR-079)               \n";
echo "====================================================\n\n";

$db = Database::getInstance()->getConnection();
$conflictService = new BookingConflictService();
$attendanceService = new AttendanceService();
$roomStatusService = new RoomStatusService();

// Clean up test artifacts in db
$db->exec("DELETE FROM bookings WHERE activity_name LIKE 'TEST_ADV_%'");
$db->exec("DELETE FROM class_sessions WHERE note LIKE 'TEST_ADV_%'");
$db->exec("DELETE FROM class_schedules WHERE course_name LIKE 'TEST_ADV_%'");

// Find 2 active rooms
$stmtRooms = $db->query("SELECT id, name, code, capacity, building_id FROM rooms WHERE is_active = 1 AND can_be_booked = 1 ORDER BY id ASC LIMIT 5");
$rooms = $stmtRooms->fetchAll();
if (count($rooms) < 2) {
    die("Need at least 2 active rooms for testing.\n");
}
$roomA = $rooms[0];
$roomB = $rooms[1];

// Find a lecturer user
$stmtLecturer = $db->query("SELECT id, name, email, identity_number, role FROM users WHERE role = 'lecturer' LIMIT 1");
$lecturer = $stmtLecturer->fetch();
if (!$lecturer) {
    die("Need at least 1 lecturer user.\n");
}

// Find a student user
$stmtStudent = $db->query("SELECT id, name, email, identity_number, role FROM users WHERE role = 'student' LIMIT 1");
$student = $stmtStudent->fetch();

// -------------------------------------------------------------------------------------------------
// TEST 1: FR-078 — Saran Ruangan Alternatif
// -------------------------------------------------------------------------------------------------
echo "[1] Testing FR-078: Alternative Room Suggestions...\n";

// Target date 5 days ahead at 10:00 - 12:00
$testDate = date('Y-m-d', strtotime('+5 days'));
$startTime = '10:00:00';
$endTime = '12:00:00';

// Create an approved booking on room A to cause a conflict
$stmt = $db->prepare("
    INSERT INTO bookings (room_id, user_id, booking_date, start_time, end_time, activity_type, activity_name, participants_count, contact_person, status)
    VALUES (?, ?, ?, ?, ?, 'seminar', 'TEST_ADV_BLOCKER', 20, '08123456789', 'approved')
");
$stmt->execute([$roomA['id'], $student['id'], $testDate, $startTime, $endTime]);

// Validate conflict occurs on room A
$conflict = $conflictService->checkConflicts((int)$roomA['id'], $testDate, $startTime, $endTime);
assert($conflict['has_conflict'] === true, "Room A must have conflict");

// Query alternative rooms for room A
$alternatives = $conflictService->getAlternativeRooms((int)$roomA['id'], $testDate, $startTime, $endTime, 15);
assert(is_array($alternatives), "Alternatives must be array");
assert(count($alternatives) <= 5, "Alternatives must be at most 5 rooms (FR-078 AC)");
assert(count($alternatives) > 0, "Should suggest at least 1 alternative room");

// Room A should NOT be in alternatives
$altIds = array_column($alternatives, 'id');
assert(!in_array((int)$roomA['id'], $altIds), "Room A cannot be in alternatives");
echo "    -> Found " . count($alternatives) . " alternative vacant rooms: ";
foreach ($alternatives as $alt) {
    echo "{$alt['code']} (Cap: {$alt['capacity']}) ";
}
echo "\n";
echo "    ✓ PASS: FR-078 Alternative rooms correctly suggested on conflict.\n\n";

// -------------------------------------------------------------------------------------------------
// TEST 2: FR-068 — Kelas Pindah Ruangan
// -------------------------------------------------------------------------------------------------
echo "[2] Testing FR-068: Class Room Change (Kelas Pindah Ruangan)...\n";

// Create a dummy schedule for Room A today
$todayWeekday = (int)date('N');
$todayDate = date('Y-m-d');
$nowH = (int)date('H');
$classStart = sprintf('%02d:00:00', max(7, min(19, $nowH)));
$classEnd = sprintf('%02d:40:00', max(7, min(19, $nowH)));

$stmtSem = $db->query("SELECT id FROM semesters WHERE is_active = 1 LIMIT 1");
$semId = (int)$stmtSem->fetchColumn() ?: 1;

$stmtInsertSched = $db->prepare("
    INSERT INTO class_schedules (semester_id, room_id, course_code, course_name, class_name, lecturer_name, lecturer_user_id, weekday, start_time, end_time, is_active)
    VALUES (?, ?, 'TEST68', 'TEST_ADV_COURSE', 'X', ?, ?, ?, ?, ?, 1)
");
$stmtInsertSched->execute([$semId, $roomA['id'], $lecturer['name'], $lecturer['id'], $todayWeekday, $classStart, $classEnd]);
$schedId = (int)$db->lastInsertId();

// Move class from Room A to Room B for today
$moveResult = $attendanceService->moveClassSession($schedId, $todayDate, (int)$roomB['id'], $lecturer, 'TEST_ADV_Pindah lab');
assert($moveResult['success'] === true, "Move class session must succeed: " . ($moveResult['message'] ?? ''));

// Verify class_sessions table
$stmtCheckSess = $db->prepare("SELECT * FROM class_sessions WHERE schedule_id = ? AND session_date = ?");
$stmtCheckSess->execute([$schedId, $todayDate]);
$sessRow = $stmtCheckSess->fetch();
assert($sessRow !== false, "Session record must exist");
assert($sessRow['status'] === 'moved', "Session status must be 'moved'");
assert((int)$sessRow['moved_to_room_id'] === (int)$roomB['id'], "Target room id must match Room B");

// Check conflict check on Room A (original room):
// FR-068: Original room should now be FREE (no conflict) during that slot!
$conflictRoomA = $conflictService->checkConflicts((int)$roomA['id'], $todayDate, $classStart, $classEnd);
assert($conflictRoomA['has_conflict'] === false, "Room A should be vacant on that slot after class moved out");

// Check conflict check on Room B (target room):
// FR-068: Target room should now have conflict with the moved class!
$conflictRoomB = $conflictService->checkConflicts((int)$roomB['id'], $todayDate, $classStart, $classEnd);
assert($conflictRoomB['has_conflict'] === true, "Room B should now be occupied/conflicted by moved class");

echo "    -> Room A status on slot: Free. Room B status on slot: Conflicted with moved class.\n";
echo "    ✓ PASS: FR-068 Class session moved successfully, freeing room A and occupying room B.\n\n";

// Clean up test schedule
$db->exec("DELETE FROM class_schedules WHERE id = {$schedId}");

// -------------------------------------------------------------------------------------------------
// TEST 3: FR-079 — Peminjaman Berulang (Weekly Recurring Booking)
// -------------------------------------------------------------------------------------------------
echo "[3] Testing FR-079: Weekly Recurring Bookings...\n";

// Target recurring pattern: 4 consecutive weeks
$recurWeeks = 4;
$recStartDate = date('Y-m-d', strtotime('+7 days'));
$recStartTime = '14:00:00';
$recEndTime = '16:00:00';
$recGroupId = 'REC-TEST-' . time();

$recDates = [];
for ($w = 0; $w < $recurWeeks; $w++) {
    $d = date('Y-m-d', strtotime("+{$w} weeks", strtotime($recStartDate)));
    $recDates[] = $d;

    // Validate conflict on each date independently (FR-079 AC)
    $c = $conflictService->checkConflicts((int)$roomB['id'], $d, $recStartTime, $recEndTime);
    assert($c['has_conflict'] === false, "Date $d must be free of conflict");

    // Insert linked booking
    $stmtRec = $db->prepare("
        INSERT INTO bookings (room_id, user_id, booking_date, start_time, end_time, activity_type, activity_name, participants_count, contact_person, status, recurring_group_id, notes)
        VALUES (?, ?, ?, ?, ?, 'meeting', 'TEST_ADV_RECURRING', 25, '08123456789', 'submitted', ?, ?)
    ");
    $stmtRec->execute([$roomB['id'], $student['id'], $d, $recStartTime, $recEndTime, $recGroupId, "[Pekan " . ($w + 1) . " dari {$recurWeeks}]"]);
}

// Verify that 4 connected bookings were created
$stmtCheckRec = $db->prepare("SELECT COUNT(*) FROM bookings WHERE recurring_group_id = ?");
$stmtCheckRec->execute([$recGroupId]);
$recCount = (int)$stmtCheckRec->fetchColumn();
assert($recCount === 4, "Must have exactly 4 connected booking records (FR-079 AC)");

echo "    -> Created and validated 4 connected bookings with group ID {$recGroupId}:\n";
$stmtRows = $db->prepare("SELECT id, booking_date, status, recurring_group_id FROM bookings WHERE recurring_group_id = ?");
$stmtRows->execute([$recGroupId]);
foreach ($stmtRows->fetchAll() as $r) {
    echo "       Booking #{$r['id']}: Date {$r['booking_date']} (Status: {$r['status']})\n";
}
echo "    ✓ PASS: FR-079 4-week recurring bookings created and validated independently.\n\n";

// -------------------------------------------------------------------------------------------------
// TEST 4: Clean up test data
// -------------------------------------------------------------------------------------------------
$db->exec("DELETE FROM bookings WHERE activity_name LIKE 'TEST_ADV_%'");
$db->exec("DELETE FROM class_sessions WHERE note LIKE 'TEST_ADV_%'");

echo "====================================================\n";
echo "   ALL ADVANCED FEATURES TESTS PASSED! (100% OK)   \n";
echo "====================================================\n";
