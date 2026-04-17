<?php
// ============================================================
//  index.php  — Public landing page / entry point
//  Logged-in users get redirected straight to their dashboard
// ============================================================
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['student'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MAIT Result Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { margin: 0; overflow-x: hidden; }

        /* ── HERO ── */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #0a1628 0%, #1a3a70 60%, #0a1628 100%);
            display: flex; flex-direction: column;
        }

        /* ── TOP NAV ── */
        .top-nav {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.25rem 3rem;
        }
        .top-nav .logo {
            font-family: 'DM Serif Display', serif;
            font-size: 1.25rem; color: #fff;
        }
        .top-nav .logo span { color: var(--accent); }
        .top-nav .nav-links { display: flex; gap: .75rem; }

        /* ── HERO CONTENT ── */
        .hero-body {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center; padding: 2rem 1.5rem 4rem;
        }
        .hero-badge {
            display: inline-block;
            background: rgba(232,160,32,.15);
            border: 1px solid rgba(232,160,32,.4);
            color: var(--accent);
            font-size: .78rem; font-weight: 700;
            letter-spacing: .12em; text-transform: uppercase;
            padding: .35rem 1rem; border-radius: 20px;
            margin-bottom: 1.5rem;
        }
        .hero-title {
            font-family: 'DM Serif Display', serif;
            font-size: clamp(2rem, 5vw, 3.5rem);
            color: #fff; line-height: 1.15;
            margin-bottom: 1.25rem; max-width: 700px;
        }
        .hero-title span { color: var(--accent); }
        .hero-sub {
            color: rgba(255,255,255,.6);
            font-size: 1rem; max-width: 500px;
            line-height: 1.7; margin-bottom: 2.5rem;
        }
        .hero-actions { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; }

        /* ── FEATURES SECTION ── */
        .features {
            background: var(--light);
            padding: 4rem 1.5rem;
        }
        .features-inner { max-width: 960px; margin: 0 auto; }
        .section-label {
            text-align: center;
            font-size: .78rem; font-weight: 700;
            letter-spacing: .12em; text-transform: uppercase;
            color: var(--blue); margin-bottom: .75rem;
        }
        .section-title {
            font-family: 'DM Serif Display', serif;
            font-size: 1.9rem; color: var(--navy);
            text-align: center; margin-bottom: 2.5rem;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
        }
        .feat-card {
            background: var(--white);
            border-radius: 14px;
            padding: 1.75rem 1.5rem;
            box-shadow: 0 2px 16px rgba(10,22,40,.07);
            border-top: 3px solid transparent;
            transition: transform .2s, box-shadow .2s;
        }
        .feat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 28px rgba(10,22,40,.13); }
        .feat-card.c1 { border-top-color: var(--blue); }
        .feat-card.c2 { border-top-color: var(--accent); }
        .feat-card.c3 { border-top-color: var(--success); }
        .feat-card.c4 { border-top-color: #9b59b6; }
        .feat-icon { font-size: 2rem; margin-bottom: .85rem; }
        .feat-title { font-weight: 700; color: var(--navy); margin-bottom: .4rem; }
        .feat-desc { font-size: .88rem; color: var(--muted); line-height: 1.6; }

        /* ── FOOTER ── */
        footer {
            background: var(--navy); color: rgba(255,255,255,.45);
            text-align: center; padding: 1.25rem;
            font-size: .82rem;
        }
        footer a { color: var(--accent); text-decoration: none; }

        @media (max-width: 600px) {
            .top-nav { padding: 1rem 1.25rem; }
        }
    </style>
</head>
<body>

<!-- ── HERO ── -->
<section class="hero">
    <nav class="top-nav">
        <div class="logo">MAIT <span>Result Portal</span></div>
        <div class="nav-links">
            <a href="login.php"    class="btn btn-outline" style="color:#fff; border-color:rgba(255,255,255,.35); padding:.45rem 1.1rem; font-size:.88rem;">Login</a>
            <a href="register.php" class="btn btn-accent"  style="padding:.45rem 1.1rem; font-size:.88rem;">Register</a>
        </div>
    </nav>

    <div class="hero-body">
        <div class="hero-badge">Maharaja Agrasen Institute of Technology</div>
        <h1 class="hero-title">
            Your Results,<br><span>One Place.</span>
        </h1>
        <p class="hero-sub">
            View semester-wise results, track your performance, and download
            marksheets — all from one secure student portal.
        </p>
        <div class="hero-actions">
            <a href="register.php" class="btn btn-accent" style="font-size:1rem; padding:.75rem 2rem;">
                Get Started
            </a>
            <a href="login.php" class="btn btn-outline" style="font-size:1rem; padding:.75rem 2rem; color:#fff; border-color:rgba(255,255,255,.4);">
                Sign In
            </a>
        </div>
    </div>
</section>

<!-- ── FEATURES ── -->
<section class="features">
    <div class="features-inner">
        <p class="section-label">What you can do</p>
        <h2 class="section-title">Everything in one portal</h2>

        <div class="cards-grid">
            <div class="feat-card c1">
                <div class="feat-icon">📊</div>
                <div class="feat-title">View Your Results</div>
                <div class="feat-desc">Check your marks semester-by-semester with subject-wise breakdown and performance bars.</div>
            </div>
            <div class="feat-card c2">
                <div class="feat-icon">⬇️</div>
                <div class="feat-title">Download Marksheet</div>
                <div class="feat-desc">Download your individual result as a PDF instantly — ready to share or print.</div>
            </div>
            <div class="feat-card c3">
                <div class="feat-icon">👥</div>
                <div class="feat-title">Class Results</div>
                <div class="feat-desc">See how your entire class performed. Full rankings with your position highlighted.</div>
            </div>
            <div class="feat-card c4">
                <div class="feat-icon">📥</div>
                <div class="feat-title">Export to Excel</div>
                <div class="feat-desc">Download the full class result sheet as an Excel file for offline reference.</div>
            </div>
        </div>
    </div>
</section>

<!-- ── FOOTER ── -->
<footer>
    <p>Department of Computer Science &amp; Technology &nbsp;·&nbsp; MAIT Delhi &nbsp;·&nbsp;
       <a href="upload.php">Admin Panel</a>
    </p>
</footer>

</body>
</html>
