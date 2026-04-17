<?php
// ============================================================
//  class_results.php  — View & download entire class results
// ============================================================
require_once 'config.php';
$student = requireLogin();

$db = getDB();

// Only show results for the logged-in student's class
$class = $student['class'];

// Available semesters for this class
$semStmt = $db->prepare("SELECT DISTINCT Semester FROM Results WHERE Class = ? ORDER BY Semester");
$semStmt->execute([$class]);
$semesters = $semStmt->fetchAll(PDO::FETCH_COLUMN);

$selectedSem = isset($_GET['sem']) ? (int)$_GET['sem'] : (end($semesters) ?: 1);

// Fetch all results for this class + semester
$resStmt = $db->prepare(
    "SELECT * FROM Results WHERE Class = ? AND Semester = ? ORDER BY Percentage DESC"
);
$resStmt->execute([$class, $selectedSem]);
$results = $resStmt->fetchAll();

$subjects = SUBJECT_NAMES;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Class Results — MAIT Result Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <span class="logo">MAIT <span>Result Portal</span></span>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="result.php">My Result</a>
        <a href="class_results.php" class="active">Class Results</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <div style="margin-bottom:1.5rem;">
        <h2 style="font-family:'DM Serif Display',serif; font-size:1.6rem; color:var(--navy);">
            Class Results — <?= htmlspecialchars($class) ?>
        </h2>
        <p style="color:var(--muted); font-size:.9rem;">
            Viewing results for your class only.
        </p>
    </div>

    <!-- Semester tabs -->
    <div class="sem-tabs">
        <?php foreach ($semesters as $sem): ?>
            <a href="?sem=<?= $sem ?>" class="sem-tab <?= $sem == $selectedSem ? 'active' : '' ?>">
                Semester <?= $sem ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!$results): ?>
        <div class="alert alert-info">No results found for Semester <?= $selectedSem ?>.</div>
    <?php else: ?>

    <!-- Summary stats -->
    <?php
        $percentages = array_column($results, 'Percentage');
        $avgPct = count($percentages) ? array_sum($percentages) / count($percentages) : 0;
        $topPct = max($percentages);
        $passCount = count(array_filter($percentages, fn($p) => $p >= 40));
    ?>
    <div class="stats">
        <div class="stat-box">
            <div class="val"><?= count($results) ?></div>
            <div class="lbl">Total Students</div>
        </div>
        <div class="stat-box accent">
            <div class="val"><?= number_format($avgPct, 1) ?>%</div>
            <div class="lbl">Class Average</div>
        </div>
        <div class="stat-box green">
            <div class="val"><?= $passCount ?></div>
            <div class="lbl">Passed</div>
        </div>
        <div class="stat-box">
            <div class="val"><?= number_format($topPct, 1) ?>%</div>
            <div class="lbl">Top Score</div>
        </div>
    </div>

    <!-- Download Excel -->
    <div style="margin-bottom:1rem;">
        <a href="download_class.php?sem=<?= $selectedSem ?>" class="btn btn-accent">
            ⬇ Download Class Results (Excel)
        </a>
    </div>

    <!-- Class table -->
    <div class="card">
        <div class="card-title">Semester <?= $selectedSem ?> — All Students</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <?php foreach ($subjects as $name): ?>
                            <th><?= htmlspecialchars($name) ?></th>
                        <?php endforeach; ?>
                        <th>Total</th>
                        <th>%</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $rank => $r):
                    $grade    = $r['Grade'] ?? computeGrade((float)$r['Percentage']);
                    $gradeCss = match($grade) {
                        'O'  => 'grade-O',
                        'A+' => 'grade-Ap',
                        'A'  => 'grade-A',
                        'B+' => 'grade-Bp',
                        'B'  => 'grade-B',
                        default => 'grade-F',
                    };
                    $isSelf = ($r['StudentID'] === $student['id']);
                ?>
                    <tr <?= $isSelf ? 'style="background:#fffbeb;"' : '' ?>>
                        <td><?= $rank + 1 ?></td>
                        <td><?= htmlspecialchars($r['StudentID']) ?></td>
                        <td>
                            <?= htmlspecialchars($r['Name']) ?>
                            <?php if ($isSelf): ?>
                                <span style="font-size:.75rem; color:var(--blue); font-weight:600;">(You)</span>
                            <?php endif; ?>
                        </td>
                        <?php foreach ($subjects as $i => $sname): ?>
                            <td><?= number_format((float)($r['Subject'.$i] ?? 0), 1) ?></td>
                        <?php endforeach; ?>
                        <td><strong><?= number_format($r['TotalMarks'], 1) ?></strong></td>
                        <td><?= number_format($r['Percentage'], 2) ?>%</td>
                        <td><span class="grade <?= $gradeCss ?>"><?= htmlspecialchars($grade) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>
</div>
</body>
</html>
