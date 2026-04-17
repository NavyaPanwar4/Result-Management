<?php
// ============================================================
//  register.php  — Student self-registration
// ============================================================
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in → go to dashboard
if (!empty($_SESSION['student'])) {
    header('Location: dashboard.php');
    exit;
}

$errors  = [];
$success = false;
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'student_id' => trim($_POST['student_id'] ?? ''),
        'name'       => trim($_POST['name']       ?? ''),
        'email'      => trim($_POST['email']      ?? ''),
        'class'      => trim($_POST['class']      ?? ''),
    ];
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    // ── Validate ──────────────────────────────────────────
    if ($old['student_id'] === '') {
        $errors['student_id'] = 'Student ID is required.';
    } elseif (!preg_match('/^\d{6,12}$/', $old['student_id'])) {
        $errors['student_id'] = 'Student ID must be 6–12 digits.';
    }

    if ($old['name'] === '') {
        $errors['name'] = 'Full name is required.';
    } elseif (strlen($old['name']) < 3) {
        $errors['name'] = 'Name must be at least 3 characters.';
    }

    if ($old['email'] === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if ($old['class'] === '') {
        $errors['class'] = 'Class is required.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters.';
    }

    if ($password2 === '') {
        $errors['password2'] = 'Please confirm your password.';
    } elseif ($password !== $password2) {
        $errors['password2'] = 'Passwords do not match.';
    }

    // ── Check duplicates ───────────────────────────────────
    if (empty($errors)) {
        $db = getDB();

        $chkId = $db->prepare("SELECT StudentID FROM Students WHERE StudentID = ?");
        $chkId->execute([$old['student_id']]);
        if ($chkId->fetch()) {
            $errors['student_id'] = 'This Student ID is already registered.';
        }

        $chkEmail = $db->prepare("SELECT StudentID FROM Students WHERE Email = ?");
        $chkEmail->execute([$old['email']]);
        if ($chkEmail->fetch()) {
            $errors['email'] = 'This email is already registered.';
        }
    }

    // ── Insert ─────────────────────────────────────────────
    if (empty($errors)) {
        $db   = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare(
            "INSERT INTO Students (StudentID, Name, Email, Password, Class)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $old['student_id'],
            $old['name'],
            $old['email'],
            $hash,
            $old['class'],
        ]);

        $success = true;
        $old     = []; // clear form
    }
}

$classes = ['6AIML-I','6AIML-II','6AIML-III','6AIML-IV','6CSE-I','6CSE-II','6IT-I','6IT-II'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register — MAIT Result Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .field-error {
            color: var(--danger);
            font-size: .78rem;
            margin-top: .25rem;
            display: block;
        }
        input.error, select.error {
            border-color: var(--danger);
        }
        .password-wrap { position: relative; }
        .toggle-pw {
            position: absolute; right: .75rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; color: var(--muted);
            font-size: .85rem; padding: 0;
        }
        .toggle-pw:hover { color: var(--blue); }
    </style>
</head>
<body>
<div class="login-wrap" style="padding: 2rem 1rem;">
    <div class="login-box" style="max-width:480px;">

        <div class="brand">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                <rect width="48" height="48" rx="12" fill="#0a1628"/>
                <path d="M10 34L24 14l14 20H10z" fill="#e8a020" opacity=".9"/>
                <path d="M17 34L24 22l7 12H17z" fill="#1a4b8c"/>
            </svg>
            <h1>Create Account</h1>
            <p>MAIT Result Portal — Student Registration</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✔ Registration successful! You can now <a href="login.php" style="color:var(--success); font-weight:600;">log in</a>.
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="" novalidate>

            <!-- Student ID -->
            <div class="form-group">
                <label for="student_id">Student ID</label>
                <input type="text" id="student_id" name="student_id"
                       placeholder="e.g. 22001004"
                       value="<?= htmlspecialchars($old['student_id'] ?? '') ?>"
                       class="<?= isset($errors['student_id']) ? 'error' : '' ?>"
                       maxlength="12">
                <?php if (isset($errors['student_id'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['student_id']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Full Name -->
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name"
                       placeholder="e.g. Riya Mehta"
                       value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                       class="<?= isset($errors['name']) ? 'error' : '' ?>">
                <?php if (isset($errors['name'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['name']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       placeholder="e.g. riya@mait.ac.in"
                       value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                       class="<?= isset($errors['email']) ? 'error' : '' ?>">
                <?php if (isset($errors['email'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Class -->
            <div class="form-group">
                <label for="class">Class</label>
                <select id="class" name="class"
                        class="<?= isset($errors['class']) ? 'error' : '' ?>">
                    <option value="">— Select your class —</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c ?>"
                            <?= ($old['class'] ?? '') === $c ? 'selected' : '' ?>>
                            <?= $c ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['class'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['class']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password"
                           placeholder="Minimum 6 characters"
                           class="<?= isset($errors['password']) ? 'error' : '' ?>">
                    <button type="button" class="toggle-pw" onclick="togglePw('password', this)">Show</button>
                </div>
                <?php if (isset($errors['password'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['password']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="password2">Confirm Password</label>
                <div class="password-wrap">
                    <input type="password" id="password2" name="password2"
                           placeholder="Re-enter your password"
                           class="<?= isset($errors['password2']) ? 'error' : '' ?>">
                    <button type="button" class="toggle-pw" onclick="togglePw('password2', this)">Show</button>
                </div>
                <?php if (isset($errors['password2'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['password2']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary"
                    style="width:100%; justify-content:center; margin-top:.5rem;">
                Register
            </button>
        </form>
        <?php endif; ?>

        <p style="text-align:center; margin-top:1.2rem; font-size:.82rem; color:var(--muted);">
            Already have an account?
            <a href="login.php" style="color:var(--blue);">Sign in →</a>
        </p>

    </div>
</div>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = 'Hide';
    } else {
        input.type = 'password';
        btn.textContent = 'Show';
    }
}
</script>
</body>
</html>
