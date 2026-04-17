<?php
// ============================================================
//  dashboard.php  — Student home after login
// ============================================================
require_once 'config.php';
$student = requireLogin();

$db = getDB();

// All semester results for this student
$semStmt = $db->prepare(
    "SELECT Semester, Percentage, Grade, TotalMarks, MaxMarks
     FROM Results WHERE StudentID = ? ORDER BY Semester"
);
$semStmt->execute([$student['id']]);
$semResults = $semStmt->fetchAll();

$latest        = !empty($semResults) ? end($semResults)   : null;
$totalSemesters = count($semResults);
$rank          = null;
$totalStudents = 0;

if ($latest) {
    $rankStmt = $db->prepare("
        SELECT COUNT(*) + 1 FROM Results
        WHERE Class = ? AND Semester = ? AND Percentage > ?
    ");
    $rankStmt->execute([$student['class'], $latest['Semester'], $latest['Percentage']]);
    $rank = (int)$rankStmt->fetchColumn();

    $totStmt = $db->prepare("SELECT COUNT(*) FROM Results WHERE Class = ? AND Semester = ?");
    $totStmt->execute([$student['class'], $latest['Semester']]);
    $totalStudents = (int)$totStmt->fetchColumn();
}

// Best semester
$bestSem = null;
if ($semResults) {
    usort($semResults, fn($a,$b) => $b['Percentage'] <=> $a['Percentage']);
    $bestSem = $semResults[0];
    // restore order
    usort($semResults, fn($a,$b) => $a['Semester'] <=> $b['Semester']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — MAIT Result Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { display: flex; flex-direction: column; min-height: 100vh; }

        /* ── SIDEBAR LAYOUT ── */
        .layout { display: flex; flex: 1; }

        .sidebar {
            width: 230px; flex-shrink: 0;
            background: var(--navy);
            display: flex; flex-direction: column;
            padding-bottom: 1.5rem;
            position: sticky; top: 64px;
            height: calc(100vh - 64px);
            overflow-y: auto;
        }

        .sidebar .nav-section-label {
            font-size: .68rem; font-weight: 700;
            color: rgba(255,255,255,.3);
            text-transform: uppercase; letter-spacing: .12em;
            padding: 1.25rem 1.25rem .4rem;
        }

        .sidebar a {
            display: flex; align-items: center; gap: .7rem;
            padding: .65rem 1.25rem;
            color: rgba(255,255,255,.65);
            text-decoration: none; font-size: .9rem; font-weight: 500;
            border-left: 3px solid transparent;
            transition: all .18s;
        }
        .sidebar a:hover {
            color: #fff;
            background: rgba(255,255,255,.06);
            border-left-color: rgba(255,255,255,.3);
        }
        .sidebar a.active {
            color: var(--accent);
            background: rgba(232,160,32,.1);
            border-left-color: var(--accent);
        }
        .sidebar .nav-icon { font-size: 1.05rem; width: 20px; text-align: center; }

        .sidebar .user-chip {
            margin: 1.5rem 1rem .5rem;
            background: rgba(255,255,255,.06);
            border-radius: 10px; padding: .85rem 1rem;
        }
        .sidebar .user-chip .uname {
            font-weight: 600; color: #fff; font-size: .9rem;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar .user-chip .uid {
            font-size: .75rem; color: rgba(255,255,255,.4); margin-top: .15rem;
        }

        /* ── MAIN CONTENT ── */
        .main { flex: 1; padding: 2rem 2rem 3rem; overflow-y: auto; }

        .page-heading {
            font-family: 'DM Serif Display', serif;
            font-size: 1.65rem; color: var(--navy);
            margin-bottom: .3rem;
        }
        .page-sub { color: var(--muted); font-size: .9rem; margin-bottom: 2rem; }

        /* ── QUICK ACTION CARDS ── */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem; margin-bottom: 2rem;
        }
        .quick-card {
            background: var(--white);
            border-radius: 14px;
            box-shadow: 0 2px 14px rgba(10,22,40,.08);
            padding: 1.5rem;
            text-decoration: none; color: var(--text);
            display: flex; flex-direction: column; gap: .5rem;
            border-bottom: 3px solid transparent;
            transition: all .2s;
        }
        .quick-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(10,22,40,.13); }
        .quick-card.q1 { border-bottom-color: var(--blue); }
        .quick-card.q2 { border-bottom-color: var(--accent); }
        .quick-card.q3 { border-bottom-color: var(--success); }
        .quick-card.q4 { border-bottom-color: #9b59b6; }
        .quick-card .qicon { font-size: 1.75rem; }
        .quick-card .qtitle { font-weight: 700; font-size: .95rem; color: var(--navy); }
        .quick-card .qdesc { font-size: .8rem; color: var(--muted); }

        /* ── SEMESTER HISTORY TABLE ── */
        .no-results {
            background: var(--white); border-radius: 12px;
            padding: 3rem; text-align: center;
            box-shadow: 0 2px 14px rgba(10,22,40,.07);
        }
        .no-results .icon { font-size: 3rem; margin-bottom: 1rem; }
        .no-results p { color: var(--muted); }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { padding: 1rem; }
        }
    </style>
</head>
<body>

<!-- ── HEADER ── -->
<header>
    <span class="logo">MAIT <span>Result Portal</span></span>
    <nav>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="result.php">My Results</a>
        <a href="class_results.php">Class</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="layout">

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar">
        <div class="user-chip">
            <div class="uname"><?= htmlspecialchars($student['name']) ?></div>
            <div class="uid"><?= htmlspecialchars($student['id']) ?> · <?= htmlspecialchars($student['class']) ?></div>
        </div>

        <div class="nav-section-label">Main</div>
        <a href="dashboard.php" class="active">
            <span class="nav-icon">🏠</span> Dashboard
        </a>
        <a href="result.php">
            <span class="nav-icon">📊</span> My Results
        </a>
        <a href="class_results.php">
            <span class="nav-icon">👥</span> Class Results
        </a>

        <?php if ($latest): ?>
        <div class="nav-section-label">Download</div>
        <a href="download_result.php?sem=<?= $latest['Semester'] ?>">
            <span class="nav-icon">⬇️</span> My Marksheet (PDF)
        </a>
        <a href="download_class.php?sem=<?= $latest['Semester'] ?>">
            <span class="nav-icon">📥</span> Class Excel
        </a>
        <?php endif; ?>

        <div class="nav-section-label">Account</div>
        <a href="logout.php">
            <span class="nav-icon">🚪</span> Logout
        </a>
    </aside>

    <!-- ── MAIN ── -->
    <main class="main">

        <h1 class="page-heading">Welcome back, <?= htmlspecialchars(explode(' ', $student['name'])[0]) ?>!</h1>
        <p class="page-sub">Here's an overview of your academic performance.</p>

        <!-- Stats -->
        <?php if ($latest): ?>
        <div class="stats" style="margin-bottom:2rem;">
            <div class="stat-box">
                <div class="val"><?= $totalSemesters ?></div>
                <div class="lbl">Semesters</div>
            </div>
            <div class="stat-box accent">
                <div class="val"><?= number_format($latest['Percentage'], 1) ?>%</div>
                <div class="lbl">Latest %</div>
            </div>
            <div class="stat-box green">
                <div class="val"><?= htmlspecialchars($latest['Grade'] ?? computeGrade((float)$latest['Percentage'])) ?></div>
                <div class="lbl">Latest Grade</div>
            </div>
            <?php if ($rank && $totalStudents): ?>
            <div class="stat-box">
                <div class="val"><?= $rank ?>/<?= $totalStudents ?></div>
                <div class="lbl">Class Rank</div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <h2 style="font-family:'DM Serif Display',serif; font-size:1.15rem; color:var(--navy); margin-bottom:1rem;">Quick Actions</h2>
        <div class="quick-grid">
            <a href="result.php" class="quick-card q1">
                <div class="qicon">📊</div>
                <div class="qtitle">View My Results</div>
                <div class="qdesc">Check marks semester-by-semester</div>
            </a>
            <?php if ($latest): ?>
            <a href="download_result.php?sem=<?= $latest['Semester'] ?>" class="quick-card q2">
                <div class="qicon">⬇️</div>
                <div class="qtitle">Download Marksheet</div>
                <div class="qdesc">Latest semester result as PDF</div>
            </a>
            <?php endif; ?>
            <a href="class_results.php" class="quick-card q3">
                <div class="qicon">👥</div>
                <div class="qtitle">Class Results</div>
                <div class="qdesc">See full class rankings</div>
            </a>
            <?php if ($latest): ?>
            <a href="download_class.php?sem=<?= $latest['Semester'] ?>" class="quick-card q4">
                <div class="qicon">📥</div>
                <div class="qtitle">Export Class Excel</div>
                <div class="qdesc">Download class results spreadsheet</div>
            </a>
            <?php endif; ?>
        </div>

        <!-- Semester history -->
        <h2 style="font-family:'DM Serif Display',serif; font-size:1.15rem; color:var(--navy); margin-bottom:1rem; margin-top:2rem;">Semester History</h2>

        <?php if (!$semResults): ?>
            <div class="no-results">
                <div class="icon">📭</div>
                <p>No results found yet. Results will appear here once uploaded by the admin.</p>
            </div>
        <?php else: ?>
        <div class="card" style="padding:0; overflow:hidden;">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Semester</th>
                            <th>Total Marks</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th>Performance</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($semResults as $r):
                        $pct   = (float)$r['Percentage'];
                        $grade = $r['Grade'] ?? computeGrade($pct);
                        $gradeCss = match($grade) {
                            'O'  => 'grade-O',
                            'A+' => 'grade-Ap',
                            'A'  => 'grade-A',
                            'B+' => 'grade-Bp',
                            'B'  => 'grade-B',
                            default => 'grade-F',
                        };
                    ?>
                        <tr>
                            <td><strong>Semester <?= $r['Semester'] ?></strong></td>
                            <td><?= number_format($r['TotalMarks'], 1) ?> / <?= number_format($r['MaxMarks'], 0) ?></td>
                            <td><?= number_format($pct, 2) ?>%</td>
                            <td><span class="grade <?= $gradeCss ?>"><?= htmlspecialchars($grade) ?></span></td>
                            <td style="min-width:130px;">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width:<?= min(100,$pct) ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <a href="result.php?sem=<?= $r['Semester'] ?>" class="btn btn-outline btn-sm">View</a>
                                <a href="download_result.php?sem=<?= $r['Semester'] ?>" class="btn btn-primary btn-sm" style="margin-left:.3rem;">PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
