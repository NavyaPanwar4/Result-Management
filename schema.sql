-- ============================================================
--  University Result Management System — Database Schema
--  Database: ResultManagement
-- ============================================================

CREATE DATABASE IF NOT EXISTS ResultManagement
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ResultManagement;

-- ── Students (for login / authentication) ──────────────────
CREATE TABLE IF NOT EXISTS Students (
    StudentID      VARCHAR(20)  NOT NULL PRIMARY KEY,
    Name           VARCHAR(100) NOT NULL,
    Email          VARCHAR(100) NOT NULL UNIQUE,
    Password       VARCHAR(255) NOT NULL,   -- bcrypt hash
    Class          VARCHAR(20)  NOT NULL,   -- e.g. 6AIML-IV
    CreatedAt      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Results ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Results (
    ResultID       INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    StudentID      VARCHAR(20)  NOT NULL,
    Name           VARCHAR(100) NOT NULL,
    Class          VARCHAR(20)  NOT NULL,
    Semester       TINYINT      NOT NULL,           -- 1–8
    Subject1       DECIMAL(5,2) DEFAULT 0,
    Subject2       DECIMAL(5,2) DEFAULT 0,
    Subject3       DECIMAL(5,2) DEFAULT 0,
    Subject4       DECIMAL(5,2) DEFAULT 0,
    Subject5       DECIMAL(5,2) DEFAULT 0,
    Subject6       DECIMAL(5,2) DEFAULT 0,
    TotalMarks     DECIMAL(7,2) GENERATED ALWAYS AS
                       (Subject1 + Subject2 + Subject3 +
                        Subject4 + Subject5 + Subject6) STORED,
    MaxMarks       DECIMAL(7,2) NOT NULL DEFAULT 600,
    Percentage     DECIMAL(5,2) GENERATED ALWAYS AS
                       ((Subject1 + Subject2 + Subject3 +
                         Subject4 + Subject5 + Subject6)
                        / MaxMarks * 100) STORED,
    Grade          VARCHAR(5)   DEFAULT NULL,       -- computed on insert
    UploadedAt     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (StudentID) REFERENCES Students(StudentID)
        ON DELETE CASCADE,
    UNIQUE KEY uniq_student_sem (StudentID, Semester)
) ENGINE=InnoDB;

-- ── Upload log ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS UploadLog (
    LogID          INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    FileName       VARCHAR(255) NOT NULL,
    UploadedBy     VARCHAR(100) NOT NULL DEFAULT 'admin',
    RecordsAdded   INT          NOT NULL DEFAULT 0,
    UploadedAt     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Sample data ────────────────────────────────────────────
-- Password for all sample students: "password123" (bcrypt)
-- Hash generated via: password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO Students (StudentID, Name, Email, Password, Class) VALUES
('22001001', 'Sahil Sharma',  'sahil@mait.ac.in',  '$2y$10$Rov9VBiMmAlzXqP7GdkpLuqHJNhSuWAFVeRRXHnVt0ByEFnXB3nGy', '6AIML-IV'),
('22001002', 'Aryan Gupta',   'aryan@mait.ac.in',  '$2y$10$Rov9VBiMmAlzXqP7GdkpLuqHJNhSuWAFVeRRXHnVt0ByEFnXB3nGy', '6AIML-IV'),
('22001003', 'Priya Singh',   'priya@mait.ac.in',  '$2y$10$Rov9VBiMmAlzXqP7GdkpLuqHJNhSuWAFVeRRXHnVt0ByEFnXB3nGy', '6AIML-IV'),
('22001004', 'Riya Mehta',    'riya@mait.ac.in',   '$2y$10$Rov9VBiMmAlzXqP7GdkpLuqHJNhSuWAFVeRRXHnVt0ByEFnXB3nGy', '6AIML-IV'),
('22001005', 'Karan Verma',   'karan@mait.ac.in',  '$2y$10$Rov9VBiMmAlzXqP7GdkpLuqHJNhSuWAFVeRRXHnVt0ByEFnXB3nGy', '6AIML-IV'),
('22001006', 'Sneha Joshi',   'sneha@mait.ac.in',  '$2y$10$Rov9VBiMmAlzXqP7GdkpLuqHJNhSuWAFVeRRXHnVt0ByEFnXB3nGy', '6AIML-IV'),
('22001007', 'Amit Sharma',   'amit@mait.ac.in',   '$2y$10$Rov9VBiMmAlzXqP7GdkpLuqHJNhSuWAFVeRRXHnVt0ByEFnXB3nGy', '6AIML-IV'),
('22001008', 'Pooja Yadav',   'pooja@mait.ac.in',  '$2y$10$Rov9VBiMmAlzXqP7GdkpLuqHJNhSuWAFVeRRXHnVt0ByEFnXB3nGy', '6AIML-IV'),
('22001009', 'Rahul Nair',    'rahul@mait.ac.in',  '$2y$10$Rov9VBiMmAlzXqP7GdkpLuqHJNhSuWAFVeRRXHnVt0ByEFnXB3nGy', '6AIML-IV'),
('22001010', 'Divya Kapoor',  'divya@mait.ac.in',  '$2y$10$Rov9VBiMmAlzXqP7GdkpLuqHJNhSuWAFVeRRXHnVt0ByEFnXB3nGy', '6AIML-IV');

INSERT INTO Results (StudentID, Name, Class, Semester, Subject1, Subject2, Subject3, Subject4, Subject5, Subject6, MaxMarks, Grade) VALUES
('22001001', 'Sahil Sharma', '6AIML-IV', 5, 88, 92, 79, 85, 91, 76, 600, 'A'),
('22001002', 'Aryan Gupta',  '6AIML-IV', 5, 72, 68, 75, 70, 65, 78, 600, 'B+'),
('22001003', 'Priya Singh',  '6AIML-IV', 5, 95, 93, 91, 97, 89, 92, 600, 'O'),
('220010011', 'Navya Panwar',  '6AIML-IV', 5, 85, 80, 78, 82, 88, 90, 600, 'A');
