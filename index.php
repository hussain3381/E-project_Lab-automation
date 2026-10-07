<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LAB AUTOMATION | Electrical Testing System</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg: #071417;
            --bg-secondary: #091b1f;
            --card: #0b1c20;
            --card-light: #102326;
            --border: #16373b;
            --accent: #42e6c4;
            --accent-hover: #6ff0d5;
            --text: #f2fbf9;
            --muted: #91aaa7;
            --danger: #ff6b6b;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Inter", sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: min(1180px, 92%);
            margin: auto;
        }

        /* ================= NAVBAR ================= */

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: rgba(7, 20, 23, 0.88);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(22, 55, 59, 0.8);
        }

        .nav-inner {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border: 1px solid var(--accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 20px;
            box-shadow: 0 0 20px rgba(66, 230, 196, 0.1);
        }

        .brand-text h2 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 17px;
            letter-spacing: 1.2px;
        }

        .brand-text span {
            display: block;
            color: var(--muted);
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a {
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
            padding: 9px 14px;
            border-radius: 8px;
            transition: 0.25s ease;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--accent);
            background: #102f32;
        }

        .nav-login {
            border: 1px solid var(--accent) !important;
            color: var(--accent) !important;
            margin-left: 8px;
        }

        .nav-login:hover {
            background: var(--accent) !important;
            color: #061310 !important;
        }

        /* ================= HERO ================= */

        .hero {
            min-height: 100vh;
            padding: 150px 0 80px;
            position: relative;
            display: flex;
            align-items: center;
        }

        .hero::before {
            content: "";
            position: absolute;
            width: 500px;
            height: 500px;
            right: -180px;
            top: 100px;
            background: rgba(66, 230, 196, 0.06);
            filter: blur(80px);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 70px;
            align-items: center;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 7px 12px;
            border: 1px solid var(--border);
            background: rgba(11, 28, 32, 0.8);
            border-radius: 30px;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 22px;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            background: var(--accent);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--accent);
        }

        .hero h1 {
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(42px, 5vw, 70px);
            line-height: 1.04;
            letter-spacing: -2px;
            margin-bottom: 24px;
        }

        .hero h1 span {
            color: var(--accent);
        }

        .hero-description {
            color: var(--muted);
            max-width: 620px;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 32px;
        }

        .hero-buttons {
            display: flex;
            gap: 13px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 21px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.25s ease;
        }

        .btn-primary {
            background: var(--accent);
            color: #061310;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(66, 230, 196, 0.15);
        }

        .btn-outline {
            border: 1px solid var(--border);
            color: var(--text);
            background: var(--card);
        }

        .btn-outline:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-2px);
        }

        /* ================= DASHBOARD PREVIEW ================= */

        .preview {
            position: relative;
        }

        .preview-card {
            background: rgba(11, 28, 32, 0.94);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
        }

        .preview-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 18px;
        }

        .preview-title {
            font-family: "Space Grotesk", sans-serif;
            font-weight: 600;
        }

        .preview-live {
            color: var(--accent);
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .mini-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .mini-card {
            padding: 17px;
            background: #08181b;
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .mini-card p {
            color: var(--muted);
            font-size: 11px;
            margin-bottom: 5px;
        }

        .mini-card strong {
            font-family: "Space Grotesk", sans-serif;
            font-size: 27px;
        }

        .mini-card strong.green {
            color: var(--accent);
        }

        .test-list {
            margin-top: 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        .test-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 13px 15px;
            border-bottom: 1px solid var(--border);
            font-size: 12px;
        }

        .test-row:last-child {
            border-bottom: 0;
        }

        .test-name {
            color: var(--text);
        }

        .test-status {
            color: var(--accent);
            font-size: 11px;
        }

        .test-status.pending {
            color: #f0c96a;
        }

        /* ================= SECTION ================= */

        .section {
            padding: 100px 0;
        }

        .section-heading {
            max-width: 650px;
            margin-bottom: 45px;
        }

        .section-label {
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .section-heading h2 {
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(30px, 4vw, 44px);
            line-height: 1.15;
            margin-bottom: 15px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        /* ================= FEATURES ================= */

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .feature-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 27px;
            transition: 0.25s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: rgba(66, 230, 196, 0.45);
        }

        .feature-icon {
            width: 45px;
            height: 45px;
            border: 1px solid var(--border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 19px;
            margin-bottom: 20px;
            background: #08181b;
        }

        .feature-card h3 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 18px;
            margin-bottom: 9px;
        }

        .feature-card p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        /* ================= WORKFLOW ================= */

        .workflow {
            background: #08181b;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .workflow-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .workflow-step {
            position: relative;
            padding: 25px 20px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 11px;
        }

        .step-number {
            color: var(--accent);
            font-family: "Space Grotesk", sans-serif;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .workflow-step h3 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .workflow-step p {
            color: var(--muted);
            font-size: 12px;
        }

        /* ================= STATS ================= */

        .stats {
            padding: 65px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .stat {
            padding: 30px;
            background: var(--card);
            border-right: 1px solid var(--border);
        }

        .stat:last-child {
            border-right: 0;
        }

        .stat strong {
            display: block;
            color: var(--accent);
            font-family: "Space Grotesk", sans-serif;
            font-size: 30px;
            margin-bottom: 3px;
        }

        .stat span {
            color: var(--muted);
            font-size: 12px;
        }

        /* ================= CTA ================= */

        .cta {
            padding: 35px 0 100px;
        }

        .cta-box {
            padding: 55px;
            border: 1px solid var(--border);
            border-radius: 15px;
            background:
                linear-gradient(135deg, rgba(66, 230, 196, 0.08), transparent 55%),
                var(--card);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .cta-box h2 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 32px;
            margin-bottom: 8px;
        }

        .cta-box p {
            color: var(--muted);
            font-size: 13px;
        }

        /* ================= FOOTER ================= */

        footer {
            border-top: 1px solid var(--border);
            background: #061114;
        }

        .footer-main {
            padding: 50px 0;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 60px;
        }

        .footer-about p {
            color: var(--muted);
            font-size: 13px;
            max-width: 360px;
            margin-top: 15px;
        }

        .footer-column h4 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .footer-column a {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 9px;
            transition: 0.2s;
        }

        .footer-column a:hover {
            color: var(--accent);
        }

        .footer-bottom {
            border-top: 1px solid var(--border);
            padding: 18px 0;
            color: #607875;
            font-size: 11px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        /* ================= RESPONSIVE ================= */

        @media (max-width: 900px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 45px;
            }

            .features-grid {
                grid-template-columns: 1fr 1fr;
            }

            .workflow-grid {
                grid-template-columns: 1fr 1fr;
            }

            .footer-main {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 650px) {
            .nav-links a:not(.nav-login) {
                display: none;
            }

            .hero {
                padding-top: 125px;
            }

            .hero h1 {
                letter-spacing: -1px;
            }

            .features-grid,
            .workflow-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat {
                border-right: 0;
                border-bottom: 1px solid var(--border);
            }

            .stat:last-child {
                border-bottom: 0;
            }

            .cta-box {
                padding: 30px;
                flex-direction: column;
                align-items: flex-start;
            }

            .footer-main {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .footer-bottom {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<!-- ================= NAVBAR ================= -->

<header class="navbar">
    <div class="container nav-inner">

        <a href="index.php" class="brand">
            <div class="brand-icon">⚡</div>

            <div class="brand-text">
                <h2>LAB AUTOMATION</h2>
                <span>Electrical Testing System</span>
            </div>
        </a>

        <nav class="nav-links">
            <a href="index.php" class="active">Home</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <a href="login.php" class="nav-login">Login</a>
        </nav>

    </div>
</header>


<!-- ================= HERO ================= -->

<main>

<section class="hero">
    <div class="container hero-grid">

        <div class="hero-content">

            <div class="status">
                <span class="status-dot"></span>
                Electrical Laboratory Management
            </div>

            <h1>
                Smarter Testing.<br>
                <span>Better Results.</span>
            </h1>

            <p class="hero-description">
                A modern laboratory automation system designed to manage
                electrical product testing, test records, products and
                laboratory operations in one organized platform.
            </p>

            <div class="hero-buttons">
                <a href="login.php" class="btn btn-primary">
                    Access System →
                </a>

                <a href="#features" class="btn btn-outline">
                    Explore Features
                </a>
            </div>

        </div>


        <!-- Dashboard Preview -->

        <div class="preview">

            <div class="preview-card">

                <div class="preview-top">
                    <div class="preview-title">
                        Laboratory Overview
                    </div>

                    <div class="preview-live">
                        <span class="status-dot"></span>
                        SYSTEM ACTIVE
                    </div>
                </div>

                <div class="mini-grid">

                    <div class="mini-card">
                        <p>Total Products</p>
                        <strong>24</strong>
                    </div>

                    <div class="mini-card">
                        <p>Total Tests</p>
                        <strong>48</strong>
                    </div>

                    <div class="mini-card">
                        <p>Passed Tests</p>
                        <strong class="green">44</strong>
                    </div>

                    <div class="mini-card">
                        <p>Pass Rate</p>
                        <strong class="green">92%</strong>
                    </div>

                </div>

                <div class="test-list">

                    <div class="test-row">
                        <span class="test-name">Voltage Test</span>
                        <span class="test-status">PASSED</span>
                    </div>

                    <div class="test-row">
                        <span class="test-name">Current Test</span>
                        <span class="test-status">PASSED</span>
                    </div>

                    <div class="test-row">
                        <span class="test-name">Insulation Test</span>
                        <span class="test-status pending">PENDING</span>
                    </div>

                </div>

            </div>

        </div>

    </div>
</section>


<!-- ================= FEATURES ================= -->

<section class="section" id="features">

    <div class="container">

        <div class="section-heading">
            <div class="section-label">Core Features</div>

            <h2>
                Everything needed for organized laboratory testing.
            </h2>

            <p>
                Manage products, testing activities and laboratory records
                through a centralized and easy-to-use system.
            </p>
        </div>


        <div class="features-grid">

            <div class="feature-card">
                <div class="feature-icon">▣</div>

                <h3>Product Management</h3>

                <p>
                    Maintain organized records of electrical products
                    entering the laboratory for testing.
                </p>
            </div>


            <div class="feature-card">
                <div class="feature-icon">✓</div>

                <h3>Testing Management</h3>

                <p>
                    Create and manage product tests with clear testing
                    status and results.
                </p>
            </div>


            <div class="feature-card">
                <div class="feature-icon">⌁</div>

                <h3>Test Types</h3>

                <p>
                    Manage different electrical test types according to
                    product testing requirements.
                </p>
            </div>


            <div class="feature-card">
                <div class="feature-icon">◉</div>

                <h3>Tester Management</h3>

                <p>
                    Keep laboratory tester information organized and
                    accessible when needed.
                </p>
            </div>


            <div class="feature-card">
                <div class="feature-icon">▤</div>

                <h3>Testing Records</h3>

                <p>
                    Keep testing information structured so previous
                    activities can be tracked easily.
                </p>
            </div>


            <div class="feature-card">
                <div class="feature-icon">↗</div>

                <h3>Reports & Insights</h3>

                <p>
                    Get a clearer overview of laboratory activities and
                    testing performance.
                </p>
            </div>

        </div>

    </div>

</section>


<!-- ================= WORKFLOW ================= -->

<section class="section workflow">

    <div class="container">

        <div class="section-heading">
            <div class="section-label">Testing Workflow</div>

            <h2>
                From product entry to testing result.
            </h2>

            <p>
                A structured workflow helps laboratory teams keep the
                testing process organized.
            </p>
        </div>


        <div class="workflow-grid">

            <div class="workflow-step">
                <div class="step-number">01 / PRODUCT</div>

                <h3>Product Registered</h3>

                <p>
                    Electrical product information is added to the
                    laboratory system.
                </p>
            </div>


            <div class="workflow-step">
                <div class="step-number">02 / TEST</div>

                <h3>Testing Started</h3>

                <p>
                    Required testing type is selected and testing
                    activity is recorded.
                </p>
            </div>


            <div class="workflow-step">
                <div class="step-number">03 / RESULT</div>

                <h3>Result Recorded</h3>

                <p>
                    Test results are recorded for the tested product.
                </p>
            </div>


            <div class="workflow-step">
                <div class="step-number">04 / DECISION</div>

                <h3>Next Action</h3>

                <p>
                    Products can proceed according to their laboratory
                    testing result.
                </p>
            </div>

        </div>

    </div>

</section>


<!-- ================= STATS ================= -->

<section class="stats">

    <div class="container">

        <div class="stats-grid">

            <div class="stat">
                <strong>24+</strong>
                <span>Products Managed</span>
            </div>

            <div class="stat">
                <strong>48+</strong>
                <span>Tests Recorded</span>
            </div>

            <div class="stat">
                <strong>92%</strong>
                <span>Testing Pass Rate</span>
            </div>

            <div class="stat">
                <strong>24/7</strong>
                <span>System Accessibility</span>
            </div>

        </div>

    </div>

</section>


<!-- ================= CTA ================= -->

<section class="cta">

    <div class="container">

        <div class="cta-box">

            <div>
                <h2>Ready to manage your laboratory?</h2>

                <p>
                    Access the laboratory automation system and manage
                    your testing operations efficiently.
                </p>
            </div>

            <a href="login.php" class="btn btn-primary">
                Login to System →
            </a>

        </div>

    </div>

</section>

</main>


<!-- ================= FOOTER ================= -->

<footer>

    <div class="container footer-main">

        <div class="footer-about">

            <div class="brand">

                <div class="brand-icon">⚡</div>

                <div class="brand-text">
                    <h2>LAB AUTOMATION</h2>
                    <span>Electrical Testing System</span>
                </div>

            </div>

            <p>
                A centralized platform for managing electrical product
                testing and laboratory operations.
            </p>

        </div>


        <div class="footer-column">

            <h4>Navigation</h4>

            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <a href="login.php">Login</a>

        </div>


        <div class="footer-column">

            <h4>System</h4>

            <a href="dashboard.php">Dashboard</a>
            <a href="#features">Features</a>
            <a href="#features">Testing</a>

        </div>

    </div>


    <div class="container footer-bottom">

        <span>
            © 2026 LAB AUTOMATION SYSTEM. All rights reserved.
        </span>

        <span>
            Electrical Testing & Laboratory Management
        </span>

    </div>

</footer>

</body>
</html>
