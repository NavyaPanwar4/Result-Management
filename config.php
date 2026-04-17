<?php
// ============================================================
//  config.php  — Database connection & global constants
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');          
define('DB_PASS', '');             
define('DB_NAME', 'ResultManagement');

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('EXPORT_DIR', __DIR__ . '/exports/');

// Subject names for Semester (adjust per curriculum)
define('SUBJECT_NAMES', [
    1 => 'Web Technologies',
    2 => 'Machine Learning',
    3 => 'Database Systems',
    4 => 'Operating Systems',
    5 => 'Computer Networks',
    6 => 'Software Engineering',
]);

// Grade thresholds (based on percentage)
function computeGrade(float $pct): string {
    return match(true) {
        $pct >= 90 => 'O',
        $pct >= 80 => 'A+',
        $pct >= 70 => 'A',
        $pct >= 60 => 'B+',
        $pct >= 50 => 'B',
        $pct >= 40 => 'C',
        default    => 'F',
    };
}

// ── PDO connection (singleton) ──────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

// ── Session helper ──────────────────────────────────────────
function requireLogin(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['student'])) {
        header('Location: login.php');
        exit;
    }
    return $_SESSION['student'];
}
