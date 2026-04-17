<?php
// ============================================================
//  download_class.php — Export class results to Excel (.xlsx)
//  Uses PhpSpreadsheet (install via composer: phpoffice/phpspreadsheet)
// ============================================================
require_once 'config.php';
$student = requireLogin();

$sem   = isset($_GET['sem']) ? (int)$_GET['sem'] : 1;
$class = $student['class'];

$db      = getDB();
$stmt    = $db->prepare(
    "SELECT * FROM Results WHERE Class = ? AND Semester = ? ORDER BY Percentage DESC"
);
$stmt->execute([$class, $sem]);
$results = $stmt->fetchAll();

if (!$results) {
    die("No results found for Class $class, Semester $sem.");
}

$subjects = SUBJECT_NAMES;

// ── Try PhpSpreadsheet ──────────────────────────────────────
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
    generateExcel($results, $subjects, $class, $sem);
} else {
    generateCSVFallback($results, $subjects, $class, $sem);
}

// ── PhpSpreadsheet XLSX ─────────────────────────────────────
function generateExcel(array $results, array $subjects, string $class, int $sem): void {
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\{Fill, Alignment, Font, Border};

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Sem $sem Results");

    $navy   = '0A1628';
    $accent = 'E8A020';
    $blue   = '1A4B8C';
    $light  = 'EEF3FB';

    // ── Title row ──
    $sheet->mergeCells('A1:' . columnLetter(count($subjects) + 5) . '1');
    $sheet->setCellValue('A1', "MAIT - Result Management System | Class: $class | Semester: $sem");
    $sheet->getStyle('A1')->applyFromArray([
        'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $navy]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(28);

    // ── Header row ──
    $headers = ['Rank', 'Student ID', 'Name'];
    foreach ($subjects as $name) $headers[] = $name;
    $headers[] = 'Total';
    $headers[] = '%';
    $headers[] = 'Grade';

    $col = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($col . '2', $h);
        $col++;
    }

    $lastCol = chr(ord('A') + count($headers) - 1);
    $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $blue]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getRowDimension(2)->setRowHeight(20);

    // ── Data rows ──
    foreach ($results as $rank => $r) {
        $row = $rank + 3;
        $grade = $r['Grade'] ?? computeGrade((float)$r['Percentage']);
        $data  = [$rank + 1, $r['StudentID'], $r['Name']];
        foreach ($subjects as $i => $n) $data[] = round((float)($r['Subject'.$i] ?? 0), 1);
        $data[] = round($r['TotalMarks'],  1);
        $data[] = round($r['Percentage'],  2);
        $data[] = $grade;

        $col = 'A';
        foreach ($data as $val) {
            $sheet->setCellValue($col . $row, $val);
            $col++;
        }

        if ($row % 2 === 0) {
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")
                  ->getFill()->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB($light);
        }
    }

    // Auto-width
    foreach (range('A', $lastCol) as $c) {
        $sheet->getColumnDimension($c)->setAutoSize(true);
    }

    $filename = "ClassResults_{$class}_Sem{$sem}.xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename=\"$filename\"");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function columnLetter(int $n): string {
    $letter = '';
    while ($n > 0) {
        $n--;
        $letter = chr(65 + ($n % 26)) . $letter;
        $n = (int)($n / 26);
    }
    return $letter;
}

// ── CSV fallback (no PhpSpreadsheet) ───────────────────────
function generateCSVFallback(array $results, array $subjects, string $class, int $sem): void {
    $filename = "ClassResults_{$class}_Sem{$sem}.csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

    // Headers
    $headers = ['Rank', 'Student ID', 'Name'];
    foreach ($subjects as $name) $headers[] = $name;
    $headers[] = 'Total';
    $headers[] = '%';
    $headers[] = 'Grade';
    fputcsv($out, $headers);

    foreach ($results as $rank => $r) {
        $grade = $r['Grade'] ?? computeGrade((float)$r['Percentage']);
        $row   = [$rank + 1, $r['StudentID'], $r['Name']];
        foreach ($subjects as $i => $n) $row[] = round((float)($r['Subject'.$i] ?? 0), 1);
        $row[] = round($r['TotalMarks'],  1);
        $row[] = round($r['Percentage'],  2);
        $row[] = $grade;
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}
