<?php
// ============================================================
//  upload.php  — Admin: Upload PDF result file & parse into DB
//
//  PDF parsing uses Smalot\PdfParser (via composer).
//  If not installed, falls back to a CSV upload mode.
//
//  Expected PDF text format per student (one block per student):
//  StudentID: 22001001
//  Name: Sahil Sharma
//  Class: 6AIML-IV
//  Semester: 5
//  Subject1: 88
//  Subject2: 92
//  ... (up to Subject6)
// ============================================================
require_once 'config.php';

// Simple admin auth (no session for admin in this demo — add as needed)
// For production: implement a proper admin login
$adminPass = 'admin123'; // CHANGE THIS
session_start();
if (!isset($_SESSION['admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['admin_pass'] ?? '') === $adminPass) {
        $_SESSION['admin'] = true;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_pass'])) {
        $loginError = 'Wrong admin password.';
    }
    if (empty($_SESSION['admin'])) {
        showAdminLogin($loginError ?? '');
        exit;
    }
}

$message = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['result_pdf'])) {
    $file = $_FILES['result_pdf'];

    // Validate
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Upload error: ' . uploadErrorMsg($file['error']);
        $msgType = 'danger';
    } elseif ($file['size'] > 10 * 1024 * 1024) {
        $message = 'File too large (max 10 MB).';
        $msgType = 'danger';
    } elseif (!in_array(mime_content_type($file['tmp_name']), ['application/pdf', 'text/plain', 'text/csv'])) {
        $message = 'Only PDF or CSV files are accepted.';
        $msgType = 'danger';
    } else {
        // Move to uploads dir
        $destName = date('Ymd_His_') . preg_replace('/[^a-z0-9._-]/i', '_', basename($file['name']));
        $destPath = UPLOAD_DIR . $destName;

        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
        move_uploaded_file($file['tmp_name'], $destPath);

        // Parse
        $ext = strtolower(pathinfo($destName, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            [$count, $msg] = parsePDF($destPath);
        } else {
            [$count, $msg] = parseCSV($destPath);
        }

        if ($count >= 0) {
            // Log
            $db = getDB();
            $db->prepare("INSERT INTO UploadLog (FileName, RecordsAdded) VALUES (?,?)")
               ->execute([$destName, $count]);
            $message = "✔ Upload successful. $count record(s) inserted/updated. $msg";
            $msgType = 'success';
        } else {
            $message = "Parse error: $msg";
            $msgType = 'danger';
        }
    }
}

// Recent uploads
$db     = getDB();
$recent = $db->query("SELECT * FROM UploadLog ORDER BY UploadedAt DESC LIMIT 10")->fetchAll();

// ── HTML ────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Upload — MAIT Result Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <span class="logo">MAIT <span>Admin Panel</span></span>
    <nav>
        <a href="upload.php" class="active">Upload Results</a>
        <a href="login.php">Student Login</a>
    </nav>
</header>

<div class="container">
    <div style="margin-bottom:1.5rem;">
        <h2 style="font-family:'DM Serif Display',serif; font-size:1.6rem; color:var(--navy);">
            Upload Result File
        </h2>
        <p style="color:var(--muted); font-size:.9rem;">
            Upload a PDF or CSV file containing student result data.
        </p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-title">Upload PDF / CSV</div>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label>Result File (PDF or CSV)</label>
                <input type="file" name="result_pdf" accept=".pdf,.csv,.txt" required>
                <p style="font-size:.8rem; color:var(--muted); margin-top:.4rem;">
                    Max 10 MB. PDF should follow the MAIT result format.<br>
                    CSV columns: StudentID, Name, Class, Semester, Subject1…Subject6, MaxMarks, Grade
                </p>
            </div>
            <button type="submit" class="btn btn-primary">Upload & Parse</button>
        </form>
    </div>

    <!-- PDF format guide -->
    <div class="card">
        <div class="card-title">Expected PDF Text Format</div>
        <pre style="background:var(--light); padding:1rem; border-radius:8px; font-size:.82rem; overflow-x:auto;">StudentID: 22001001
Name: Sahil Sharma
Class: 6AIML-IV
Semester: 5
Subject1: 88
Subject2: 92
Subject3: 79
Subject4: 85
Subject5: 91
Subject6: 76
Grade: A
---
StudentID: 22001002
...</pre>
        <p style="font-size:.8rem; color:var(--muted); margin-top:.75rem;">
            Separate each student block with <code>---</code>. Only fields present will be updated.
        </p>
    </div>

    <!-- CSV format guide -->
    <div class="card">
        <div class="card-title">Expected CSV Format</div>
        <pre style="background:var(--light); padding:1rem; border-radius:8px; font-size:.82rem; overflow-x:auto;">StudentID,Name,Class,Semester,Subject1,Subject2,Subject3,Subject4,Subject5,Subject6,MaxMarks,Grade
22001001,Sahil Sharma,6AIML-IV,5,88,92,79,85,91,76,600,A
22001002,Aryan Gupta,6AIML-IV,5,72,68,75,70,65,78,600,B+</pre>
    </div>

    <!-- Upload log -->
    <?php if ($recent): ?>
    <div class="card">
        <div class="card-title">Recent Uploads</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>File</th><th>Records Added</th><th>Uploaded At</th></tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['FileName']) ?></td>
                        <td><?= $log['RecordsAdded'] ?></td>
                        <td><?= $log['UploadedAt'] ?></td>
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

<?php
// ── Admin login screen ──────────────────────────────────────
function showAdminLogin(string $err): void { ?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login</title>
<link rel="stylesheet" href="css/style.css">
</head><body>
<div class="login-wrap">
<div class="login-box">
    <div class="brand">
        <svg width="48" height="48" viewBox="0 0 48 48"><rect width="48" height="48" rx="12" fill="#0a1628"/>
        <path d="M10 34L24 14l14 20H10z" fill="#e8a020" opacity=".9"/><path d="M17 34L24 22l7 12H17z" fill="#1a4b8c"/></svg>
        <h1>Admin Panel</h1><p>Enter admin password to continue</p>
    </div>
    <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Admin Password</label>
            <input type="password" name="admin_pass" placeholder="••••••••" required>
        </div>
        <button class="btn btn-primary" style="width:100%;justify-content:center;">Login</button>
    </form>
</div></div></body></html>
<?php }

// ── Parse PDF using Smalot\PdfParser ───────────────────────
function parsePDF(string $path): array {
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        return [-1, 'Composer autoload not found. Install with: composer require smalot/pdfparser'];
    }
    require_once $autoload;

    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($path);
        $text   = $pdf->getText();
        return parseStudentBlocks($text);
    } catch (\Exception $e) {
        return [-1, $e->getMessage()];
    }
}

// ── Parse CSV ───────────────────────────────────────────────
function parseCSV(string $path): array {
    $handle = fopen($path, 'r');
    if (!$handle) return [-1, 'Could not open file.'];

    $headers = array_map('trim', fgetcsv($handle));
    $db      = getDB();
    $count   = 0;

    while (($row = fgetcsv($handle)) !== false) {
        $data = array_combine($headers, array_map('trim', $row));
        if (empty($data['StudentID'])) continue;

        upsertResult($db, [
            'StudentID' => $data['StudentID'],
            'Name'      => $data['Name']      ?? '',
            'Class'     => $data['Class']      ?? '',
            'Semester'  => (int)($data['Semester'] ?? 1),
            'Subject1'  => (float)($data['Subject1'] ?? 0),
            'Subject2'  => (float)($data['Subject2'] ?? 0),
            'Subject3'  => (float)($data['Subject3'] ?? 0),
            'Subject4'  => (float)($data['Subject4'] ?? 0),
            'Subject5'  => (float)($data['Subject5'] ?? 0),
            'Subject6'  => (float)($data['Subject6'] ?? 0),
            'MaxMarks'  => (float)($data['MaxMarks']  ?? 600),
            'Grade'     => $data['Grade'] ?? null,
        ]);
        $count++;
    }
    fclose($handle);
    return [$count, ''];
}

// ── Parse PDF text blocks ───────────────────────────────────
function parseStudentBlocks(string $text): array {
    $blocks = preg_split('/---+/', $text);
    $db     = getDB();
    $count  = 0;

    foreach ($blocks as $block) {
        $block = trim($block);
        if (!$block) continue;

        $data = [];
        if (preg_match('/StudentID\s*:\s*(\S+)/i',  $block, $m)) $data['StudentID'] = $m[1];
        if (preg_match('/Name\s*:\s*(.+)/i',         $block, $m)) $data['Name']      = trim($m[1]);
        if (preg_match('/Class\s*:\s*(\S+)/i',       $block, $m)) $data['Class']     = trim($m[1]);
        if (preg_match('/Semester\s*:\s*(\d+)/i',    $block, $m)) $data['Semester']  = (int)$m[1];
        if (preg_match('/Grade\s*:\s*(\S+)/i',       $block, $m)) $data['Grade']     = trim($m[1]);

        for ($i = 1; $i <= 6; $i++) {
            if (preg_match("/Subject{$i}\\s*:\\s*([\\d.]+)/i", $block, $m)) {
                $data["Subject$i"] = (float)$m[1];
            }
        }

        if (empty($data['StudentID'])) continue;

        $data += ['Name'=>'', 'Class'=>'', 'Semester'=>1,
                  'Subject1'=>0,'Subject2'=>0,'Subject3'=>0,
                  'Subject4'=>0,'Subject5'=>0,'Subject6'=>0,
                  'MaxMarks'=>600, 'Grade'=>null];

        upsertResult($db, $data);
        $count++;
    }

    return [$count, ''];
}

// ── Upsert into Results ─────────────────────────────────────
function upsertResult(PDO $db, array $d): void {
    $grade = $d['Grade'] ?? computeGrade(
        ($d['Subject1']+$d['Subject2']+$d['Subject3']+
         $d['Subject4']+$d['Subject5']+$d['Subject6']) / $d['MaxMarks'] * 100
    );

    $sql = "INSERT INTO Results
                (StudentID, Name, Class, Semester, Subject1, Subject2, Subject3,
                 Subject4, Subject5, Subject6, MaxMarks, Grade)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                Name=VALUES(Name), Class=VALUES(Class),
                Subject1=VALUES(Subject1), Subject2=VALUES(Subject2),
                Subject3=VALUES(Subject3), Subject4=VALUES(Subject4),
                Subject5=VALUES(Subject5), Subject6=VALUES(Subject6),
                MaxMarks=VALUES(MaxMarks), Grade=VALUES(Grade)";

    // Add UNIQUE constraint if not present: ALTER TABLE Results ADD UNIQUE KEY uniq_student_sem (StudentID, Semester);
    $db->prepare($sql)->execute([
        $d['StudentID'], $d['Name'], $d['Class'], $d['Semester'],
        $d['Subject1'],  $d['Subject2'], $d['Subject3'],
        $d['Subject4'],  $d['Subject5'], $d['Subject6'],
        $d['MaxMarks'],  $grade,
    ]);
}

function uploadErrorMsg(int $code): string {
    return match($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds size limit.',
        UPLOAD_ERR_PARTIAL   => 'File only partially uploaded.',
        UPLOAD_ERR_NO_FILE   => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR=> 'Missing temp folder.',
        UPLOAD_ERR_CANT_WRITE=> 'Failed to write to disk.',
        UPLOAD_ERR_EXTENSION => 'Upload blocked by extension.',
        default              => "Unknown error ($code).",
    };
}
