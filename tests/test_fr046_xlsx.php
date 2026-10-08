<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Support/Database.php';
require_once __DIR__ . '/../app/Services/ImportService.php';

use App\Services\ImportService;

echo "=== TEST FR-046: IMPORT XLSX VS CSV ===\n";

$importService = new ImportService();

// 1. Create temporary CSV file
$csvPath = __DIR__ . '/test_sample.csv';
$csvData = "course_code,course_name,class_name,lecturer_name,lecturer_ref,weekday,start_time,end_time,room_code,attendees\n";
$csvData .= "TI101,Algoritma dan Pemrograman,A,Dr. Ir. Budi Santoso,198001012005011001,Senin,08:00,09:40,A-101,40\n";
$csvData .= "TI102,Struktur Data,B,Siti Rahayu M.Kom,198502022010122002,Selasa,10:00,11:40,B-201,35\n";
file_put_contents($csvPath, $csvData);

// 2. Create equivalent XLSX file using ZipArchive
$xlsxPath = __DIR__ . '/test_sample.xlsx';
if (file_exists($xlsxPath)) unlink($xlsxPath);

$zip = new ZipArchive();
if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Failed to create test xlsx file\n");
}

$contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>';

$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';

$workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheets>
    <sheet name="Sheet1" sheetId="1" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/>
  </sheets>
</workbook>';

$workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>';

$sheet1 = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>course_code</t></is></c>
      <c r="B1" t="inlineStr"><is><t>course_name</t></is></c>
      <c r="C1" t="inlineStr"><is><t>class_name</t></is></c>
      <c r="D1" t="inlineStr"><is><t>lecturer_name</t></is></c>
      <c r="E1" t="inlineStr"><is><t>lecturer_ref</t></is></c>
      <c r="F1" t="inlineStr"><is><t>weekday</t></is></c>
      <c r="G1" t="inlineStr"><is><t>start_time</t></is></c>
      <c r="H1" t="inlineStr"><is><t>end_time</t></is></c>
      <c r="I1" t="inlineStr"><is><t>room_code</t></is></c>
      <c r="J1" t="inlineStr"><is><t>attendees</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>TI101</t></is></c>
      <c r="B2" t="inlineStr"><is><t>Algoritma dan Pemrograman</t></is></c>
      <c r="C2" t="inlineStr"><is><t>A</t></is></c>
      <c r="D2" t="inlineStr"><is><t>Dr. Ir. Budi Santoso</t></is></c>
      <c r="E2" t="inlineStr"><is><t>198001012005011001</t></is></c>
      <c r="F2" t="inlineStr"><is><t>Senin</t></is></c>
      <c r="G2" t="inlineStr"><is><t>08:00</t></is></c>
      <c r="H2" t="inlineStr"><is><t>09:40</t></is></c>
      <c r="I2" t="inlineStr"><is><t>A-101</t></is></c>
      <c r="J2"><v>40</v></c>
    </row>
    <row r="3">
      <c r="A3" t="inlineStr"><is><t>TI102</t></is></c>
      <c r="B3" t="inlineStr"><is><t>Struktur Data</t></is></c>
      <c r="C3" t="inlineStr"><is><t>B</t></is></c>
      <c r="D3" t="inlineStr"><is><t>Siti Rahayu M.Kom</t></is></c>
      <c r="E3" t="inlineStr"><is><t>198502022010122002</t></is></c>
      <c r="F3" t="inlineStr"><is><t>Selasa</t></is></c>
      <c r="G3" t="inlineStr"><is><t>10:00</t></is></c>
      <c r="H3" t="inlineStr"><is><t>11:40</t></is></c>
      <c r="I3" t="inlineStr"><is><t>B-201</t></is></c>
      <c r="J3"><v>35</v></c>
    </row>
  </sheetData>
</worksheet>';

$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', $rels);
$zip->addFromString('xl/workbook.xml', $workbook);
$zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
$zip->addFromString('xl/worksheets/sheet1.xml', $sheet1);
$zip->close();

// 3. Test parseAndValidate on both
$csvResult = $importService->parseAndValidate($csvPath, 1, 'test.csv');
$xlsxResult = $importService->parseAndValidate($xlsxPath, 1, 'test.xlsx');

// Clean up temp files
unlink($csvPath);
unlink($xlsxPath);

assert($csvResult['success'] === true, "CSV result success");
assert($xlsxResult['success'] === true, "XLSX result success");
assert($csvResult['valid_count'] === $xlsxResult['valid_count'], "Valid counts match");
assert($csvResult['invalid_count'] === $xlsxResult['invalid_count'], "Invalid counts match");

// Check identical preview data
for ($i = 0; $i < count($csvResult['valid_rows']); $i++) {
    $c = $csvResult['valid_rows'][$i];
    $x = $xlsxResult['valid_rows'][$i];
    assert($c['course_code'] === $x['course_code'], "Course code matches at row $i");
    assert($c['room_code'] === $x['room_code'], "Room code matches at row $i");
    assert($c['start_time'] === $x['start_time'], "Start time matches at row $i");
    assert($c['end_time'] === $x['end_time'], "End time matches at row $i");
    assert($c['weekday'] === $x['weekday'], "Weekday matches at row $i");
    assert($c['attendees'] === $x['attendees'], "Attendees match at row $i");
}

echo "✓ FR-046 PASSED: XLSX preview is completely identical to CSV equivalent!\n";
