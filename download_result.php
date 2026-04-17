<?php
// ============================================================
//  download_result.php  — Generate & download individual result PDF
//  Uses FPDF (no composer needed — include fpdf.php directly)
// ============================================================
require_once 'config.php';
$student = requireLogin();

$sem = isset($_GET['sem']) ? (int)$_GET['sem'] : 1;

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM Results WHERE StudentID = ? AND Semester = ?");
$stmt->execute([$student['id'], $sem]);
$result = $stmt->fetch();

if (!$result) {
    die("No result found for Semester $sem.");
}

// ── Try FPDF; fall back to plain-HTML download ──────────────
$fpdfPath = __DIR__ . '/fpdf/fpdf.php';
if (file_exists($fpdfPath)) {
    require_once $fpdfPath;
    generatePDF($result, $student);
} else {
    generateHTMLDownload($result, $student);
}

// ── FPDF PDF ────────────────────────────────────────────────
function generatePDF(array $r, array $s): void {
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();

    // Header bar
    $pdf->SetFillColor(10, 22, 40);
    $pdf->Rect(0, 0, 210, 30, 'F');
    $pdf->SetTextColor(232, 160, 32);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetXY(0, 8);
    $pdf->Cell(210, 8, 'MAIT - Result Management System', 0, 1, 'C');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(210, 6, 'Department of Computer Science and Technology', 0, 1, 'C');

    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(8);

    // Student info
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Student Result — Semester ' . $r['Semester'], 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(50, 7, 'Student ID:');  $pdf->Cell(0, 7, $r['StudentID'], 0, 1);
    $pdf->Cell(50, 7, 'Name:');        $pdf->Cell(0, 7, $r['Name'], 0, 1);
    $pdf->Cell(50, 7, 'Class:');       $pdf->Cell(0, 7, $r['Class'], 0, 1);
    $pdf->Cell(50, 7, 'Semester:');    $pdf->Cell(0, 7, (string)$r['Semester'], 0, 1);
    $pdf->Ln(4);

    // Table header
    $pdf->SetFillColor(26, 75, 140);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(10, 8, '#',       1, 0, 'C', true);
    $pdf->Cell(90, 8, 'Subject', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Obtained',1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Max',     1, 0, 'C', true);
    $pdf->Cell(30, 8, '%',       1, 1, 'C', true);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $subjects   = SUBJECT_NAMES;
    $maxPerSub  = $r['MaxMarks'] / count($subjects);

    foreach ($subjects as $i => $name) {
        $marks  = (float)($r['Subject' . $i] ?? 0);
        $subPct = $maxPerSub > 0 ? round($marks / $maxPerSub * 100, 1) : 0;
        $fill   = ($i % 2 === 0);
        if ($fill) $pdf->SetFillColor(240, 245, 255);
        $pdf->Cell(10, 7, (string)$i,            1, 0, 'C', $fill);
        $pdf->Cell(90, 7, $name,                 1, 0, 'L', $fill);
        $pdf->Cell(30, 7, number_format($marks,1),1, 0, 'C', $fill);
        $pdf->Cell(30, 7, number_format($maxPerSub,0), 1, 0, 'C', $fill);
        $pdf->Cell(30, 7, $subPct . '%',         1, 1, 'C', $fill);
    }

    // Totals row
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(220, 230, 245);
    $pdf->Cell(10, 8, '',                         1, 0, 'C', true);
    $pdf->Cell(90, 8, 'TOTAL',                    1, 0, 'L', true);
    $pdf->Cell(30, 8, number_format($r['TotalMarks'],1), 1, 0, 'C', true);
    $pdf->Cell(30, 8, number_format($r['MaxMarks'],0),   1, 0, 'C', true);
    $pdf->Cell(30, 8, number_format($r['Percentage'],2).'%', 1, 1, 'C', true);

    $pdf->Ln(6);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Grade: ' . ($r['Grade'] ?? computeGrade((float)$r['Percentage'])), 0, 1);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(0, 6, 'Generated on ' . date('d M Y, H:i'), 0, 1);

    $filename = 'Result_' . $r['StudentID'] . '_Sem' . $r['Semester'] . '.pdf';
    $pdf->Output('D', $filename);
    exit;
}

// ── HTML fallback (if FPDF not installed) ───────────────────
function generateHTMLDownload(array $r, array $s): void {
    $subjects  = SUBJECT_NAMES;
    $maxPerSub = $r['MaxMarks'] / count($subjects);
    $grade     = $r['Grade'] ?? computeGrade((float)$r['Percentage']);
    $filename  = 'Result_' . $r['StudentID'] . '_Sem' . $r['Semester'] . '.html';

    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <title>Result</title>
    <style>
        body{font-family:Arial,sans-serif;padding:30px;color:#1e2a3a}
        h1{background:#0a1628;color:#e8a020;padding:12px 20px;margin:0}
        h2{color:#0a1628}
        table{border-collapse:collapse;width:100%;margin-top:16px}
        th{background:#1a4b8c;color:#fff;padding:8px 12px}
        td{padding:7px 12px;border-bottom:1px solid #d0dae8}
        .total{font-weight:bold;background:#eef3fb}
        .meta{margin:16px 0}
    </style></head><body>
    <h1>MAIT — Result Management System</h1>
    <div class="meta">
        <p><strong>Name:</strong> ' . htmlspecialchars($r['Name']) . '</p>
        <p><strong>Student ID:</strong> ' . htmlspecialchars($r['StudentID']) . '</p>
        <p><strong>Class:</strong> ' . htmlspecialchars($r['Class']) . '</p>
        <p><strong>Semester:</strong> ' . $r['Semester'] . '</p>
    </div>
    <table>
        <tr><th>#</th><th>Subject</th><th>Marks</th><th>Max</th><th>%</th></tr>';

    foreach ($subjects as $i => $name) {
        $marks  = (float)($r['Subject' . $i] ?? 0);
        $subPct = $maxPerSub > 0 ? round($marks / $maxPerSub * 100, 1) : 0;
        echo "<tr><td>$i</td><td>" . htmlspecialchars($name) . "</td>
              <td>{$marks}</td><td>" . number_format($maxPerSub, 0) . "</td>
              <td>{$subPct}%</td></tr>";
    }

    echo '<tr class="total">
        <td colspan="2">TOTAL</td>
        <td>' . number_format($r['TotalMarks'],1) . '</td>
        <td>' . number_format($r['MaxMarks'],0) . '</td>
        <td>' . number_format($r['Percentage'],2) . '%</td>
    </tr></table>
    <p style="margin-top:16px;font-size:1.1em"><strong>Grade: ' . htmlspecialchars($grade) . '</strong></p>
    <p style="color:#6b7c93;font-size:.85em">Generated: ' . date('d M Y, H:i') . '</p>
    </body></html>';
    exit;
}
