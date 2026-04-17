<?php
// ============================================================
//  login.php  — Student login
// ============================================================
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → go to dashboard
if (!empty($_SESSION['student'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['student_id'] ?? '');
    $password  = trim($_POST['password']   ?? '');

    if ($studentId === '' || $password === '') {
        $error = 'Please enter both Student ID and password.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM Students WHERE StudentID = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();

        if ($student && password_verify($password, $student['Password'])) {
            $_SESSION['student'] = [
                'id'    => $student['StudentID'],
                'name'  => $student['Name'],
                'class' => $student['Class'],
                'email' => $student['Email'],
            ];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid Student ID or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — Result Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-box">
        <div class="brand">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                <rect width="48" height="48" rx="12" fill="#0a1628"/>
                <path d="M10 34L24 14l14 20H10z" fill="#e8a020" opacity=".9"/>
                <path d="M17 34L24 22l7 12H17z" fill="#1a4b8c"/>
            </svg>
            <h1>MAIT Result Portal</h1>
            <p>Department of Computer Science & Technology</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="student_id">Student ID</label>
                <input type="text" id="student_id" name="student_id"
                       placeholder="e.g. 22001001"
                       value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>"
                       required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Enter your password"
                       required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:.5rem;">
                Sign In
            </button>
        </form>

        <p style="text-align:center; margin-top:1.2rem; font-size:.82rem; color:var(--muted);">
            New student? <a href="register.php" style="color:var(--blue);">Create an account →</a>
            &nbsp;·&nbsp; Admin? <a href="upload.php" style="color:var(--blue);">Upload Panel →</a>
        </p>
    </div>
</div>
</body>
</html>
