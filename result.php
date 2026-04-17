<?php
// ============================================================
//  result.php  — Student result viewer (per semester)
// ============================================================
require_once 'config.php';
$student = requireLogin();

$db = getDB();

// Available semesters for this student
$semStmt = $db->prepare("SELECT DISTINCT Semester FROM Results WHERE StudentID = ? ORDER BY Semester");
$semStmt->execute([$student['id']]);
$semesters = $semStmt->fetchAll(PDO::FETCH_COLUMN);

// Selected semester (default: latest)
$selectedSem = isset($_GET['sem']) ? (int)$_GET['sem'] : (end($semesters) ?: 1);

// Fetch result for selected semester
$resStmt = $db->prepare("SELECT * FROM Results WHERE StudentID = ? AND Semester = ?");
$resStmt->execute([$student['id'], $selectedSem]);
$result = $resStmt->fetch();

$subjects = SUBJECT_NAMES;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Result — MAIT Result Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <span class="logo">MAIT <span>Result Portal</span></span>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="result.php" class="active">My Result</a>
        <a href="class_results.php">Class Results</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <!-- Greeting -->
    <div style="margin-bottom:1.5rem;">
        <h2 style="font-family:'DM Serif Display',serif; font-size:1.6rem; color:var(--navy);">
            Welcome, <?= htmlspecialchars($student['name']) ?>
        </h2>
        <p style="color:var(--muted); font-size:.9rem;">
            <?= htmlspecialchars($student['class']) ?> &nbsp;·&nbsp; ID: <?= htmlspecialchars($student['id']) ?>
        </p>
    </div>

    <!-- Semester tabs -->
    <?php if ($semesters): ?>
    <div class="sem-tabs">
        <?php foreach ($semesters as $sem): ?>
            <a href="?sem=<?= $sem ?>"
               class="sem-tab <?= $sem == $selectedSem ? 'active' : '' ?>">
                Semester <?= $sem ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$result): ?>
        <div class="alert alert-info">No result found for Semester <?= $selectedSem ?>.</div>
    <?php else:
        $pct   = (float)$result['Percentage'];
        $grade = $result['Grade'] ?? computeGrade($pct);

        // Grade CSS class
        $gradeCss = match($grade) {
            'O'  => 'grade-O',
            'A+' => 'grade-Ap',
            'A'  => 'grade-A',
            'B+' => 'grade-Bp',
            'B'  => 'grade-B',
            default => 'grade-F',
        };
    ?>

    <!-- Summary stats -->
    <div class="stats">
        <div class="stat-box">
            <div class="val"><?= number_format($result['TotalMarks'], 1) ?></div>
            <div class="lbl">Total Marks</div>
        </div>
        <div class="stat-box accent">
            <div class="val"><?= number_format($pct, 2) ?>%</div>
            <div class="lbl">Percentage</div>
        </div>
        <div class="stat-box green">
            <div class="val"><?= htmlspecialchars($grade) ?></div>
            <div class="lbl">Grade</div>
        </div>
        <div class="stat-box">
            <div class="val"><?= $result['Semester'] ?></div>
            <div class="lbl">Semester</div>
        </div>
    </div>

    <!-- Subject-wise marks -->
    <div class="card">
        <div class="card-title">Subject-wise Marks — Semester <?= $result['Semester'] ?></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject</th>
                        <th>Marks Obtained</th>
                        <th>Max Marks</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $maxPerSubject = $result['MaxMarks'] / count($subjects);
                foreach ($subjects as $i => $name):
                    $col    = 'Subject' . $i;
                    $marks  = (float)($result[$col] ?? 0);
                    $subPct = $maxPerSubject > 0 ? ($marks / $maxPerSubject * 100) : 0;
                ?>
                    <tr>
                        <td><?= $i ?></td>
                        <td><?= htmlspecialchars($name) ?></td>
                        <td><strong><?= number_format($marks, 1) ?></strong></td>
                        <td><?= number_format($maxPerSubject, 0) ?></td>
                        <td style="min-width:140px;">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width:<?= min(100, $subPct) ?>%"></div>
                            </div>
                            <div style="font-size:.75rem; color:var(--muted); margin-top:.2rem;">
                                <?= number_format($subPct, 1) ?>%
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:var(--light);">
                        <td colspan="2"><strong>Total</strong></td>
                        <td><strong><?= number_format($result['TotalMarks'], 1) ?></strong></td>
                        <td><strong><?= number_format($result['MaxMarks'], 0) ?></strong></td>
                        <td>
                            <span class="grade <?= $gradeCss ?>">
                                <?= htmlspecialchars($grade) ?>
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Download button -->
    <div style="display:flex; gap:1rem; flex-wrap:wrap;">
        <a href="download_result.php?sem=<?= $selectedSem ?>" class="btn btn-primary">
            ⬇ Download Result (PDF)
        </a>
        <a href="class_results.php?sem=<?= $selectedSem ?>" class="btn btn-outline">
            📊 View Class Results
        </a>
    </div>

    <?php endif; ?>
</div>
</body>
</html>
