# 🎓 University Result Management System
### MAIT Delhi — Web Technologies Assignment II (CIE-356T)

A web-based system for students to view and download their results, built with **PHP**, **MySQL**, and clean, responsive UI.

---

## 📁 Project Structure

```
result-management/
├── config.php              # DB config + helpers
├── login.php               # Student login
├── logout.php              # Session destroy
├── result.php              # Student result viewer (per semester)
├── download_result.php     # Download individual result as PDF
├── class_results.php       # View entire class results
├── download_class.php      # Download class results as Excel/CSV
├── upload.php              # Admin: upload & parse PDF/CSV results
├── schema.sql              # Database schema + sample data
├── composer.json           # PHP dependencies
├── css/
│   └── style.css           # Shared stylesheet
├── uploads/                # Uploaded PDF/CSV files (auto-created)
└── exports/                # (optional) cached exports
```

---

## ⚙️ Setup Instructions

### 1. Requirements
- PHP 8.1+
- MySQL 8.0+
- Composer (for PDF parsing + Excel export)
- Apache / Nginx with `mod_rewrite`

---

### 2. Database Setup

```sql
-- Run schema.sql in your MySQL client:
mysql -u root -p < schema.sql
```

Or paste the contents of `schema.sql` into **phpMyAdmin**.

---

### 3. Install PHP Dependencies

```bash
cd result-management/
composer install
```

This installs:
- `smalot/pdfparser` — PDF text extraction
- `phpoffice/phpspreadsheet` — Excel (.xlsx) generation

> ⚠️ If Composer is unavailable:
> - PDF uploads fall back to **CSV** parsing mode
> - Excel downloads fall back to **CSV** format

---

### 4. Configure Database

Open `config.php` and set your credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'ResultManagement');
```

---

### 5. Set Permissions

```bash
chmod 755 uploads/ exports/
```

---

### 6. Run the App

Place the project folder inside your web server's document root:

```
/var/www/html/result-management/    # Apache
/usr/share/nginx/html/result-management/   # Nginx
```

Or use PHP's built-in server for development:

```bash
cd result-management/
php -S localhost:8000
```

Open: [http://localhost:8000/login.php](http://localhost:8000/login.php)

---

## 🔐 Default Credentials

### Student Login
| Student ID | Password     | Name          |
|------------|-------------|---------------|
| 22001001   | password123 | Navya Panwar  |
| 22001002   | password123 | Aryan Gupta   |
| 22001003   | password123 | Priya Singh   |

> Change passwords using `password_hash('newpass', PASSWORD_BCRYPT)` and update in DB.

### Admin Upload Panel
- URL: `/upload.php`
- Password: `admin123` *(change in `upload.php` → `$adminPass`)*

---

## 📄 Features

| Feature | File | Description |
|---------|------|-------------|
| 🔒 Student Login | `login.php` | Secure session-based auth |
| 📊 View Result | `result.php` | Per-semester result with progress bars |
| ⬇ Download PDF | `download_result.php` | Individual result as PDF (FPDF) or HTML fallback |
| 👥 Class Results | `class_results.php` | Ranked class view, highlights own row |
| 📥 Download Excel | `download_class.php` | Class results as .xlsx (PhpSpreadsheet) or .csv fallback |
| 📤 Admin Upload | `upload.php` | Upload PDF/CSV result files, auto-parsed into DB |
| 📋 Upload Log | `upload.php` | Tracks every upload with record count |

---

## 📤 PDF Upload Format

The admin uploads a PDF where each student block looks like:

```
StudentID: 22001001
Name: Navya Panwar
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
...
```

Separate each student block with `---`.

---

## 🗂 CSV Upload Format

Alternatively, upload a `.csv` with these exact column headers:

```
StudentID,Name,Class,Semester,Subject1,Subject2,Subject3,Subject4,Subject5,Subject6,MaxMarks,Grade
22001001,Navya Panwar,6AIML-IV,5,88,92,79,85,91,76,600,A
```

---

## 🔒 Security Measures

- **Passwords** stored as bcrypt hashes (`password_hash` / `password_verify`)
- **PDO prepared statements** throughout — SQL injection protected
- **MIME type validation** on file uploads
- **File size limit** (10 MB) enforced server-side
- **Session-based auth** — students can only view their own class
- Admin panel protected by separate password + session

---

## 📦 Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `smalot/pdfparser` | ^2.0 | Extract text from uploaded PDFs |
| `phpoffice/phpspreadsheet` | ^2.0 | Generate .xlsx Excel files |

Both have graceful fallbacks if Composer is unavailable.

---

## 📧 Submission

Zip the entire folder and email to: **narinderkaur@mait.ac.in**

Include in the email:
- Name
- Enrollment Number
- Class

```bash
zip -r Assignment_WT_2_YourName.zip result-management/
```

---

*Built for Web Technologies (CIE-356T) | MAIT Delhi | Department of Computer Science and Technology*
