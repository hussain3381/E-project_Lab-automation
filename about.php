<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>About | LAB AUTOMATION</title>

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
            --border: #16373b;
            --accent: #42e6c4;
            --accent-hover: #6ff0d5;
            --text: #f2fbf9;
            --muted: #91aaa7;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Inter", sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: min(1180px, 92%);
            margin: auto;
        }

        /* NAVBAR */

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: rgba(7, 20, 23, 0.90);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border);
        }

        .nav-inner {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
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
            border: 1px solid var(--accent);
            color: var(--accent) !important;
            margin-left: 8px;
        }

        .nav-login:hover {
            background: var(--accent) !important;
            color: #061310 !important;
        }

        /* HERO */

        .about-hero {
            padding: 155px 0 90px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .about-hero::before {
            content: "";
            position: absolute;
            width: 450px;
            height: 450px;
            left: 50%;
            top: 70px;
            transform: translateX(-50%);
            background: rgba(66, 230, 196, 0.055);
            filter: blur(80px);
            border-radius: 50%;
        }

        .hero-label {
            position: relative;
            display: inline-block;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .about-hero h1 {
            position: relative;
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(42px, 6vw, 64px);
            line-height: 1.1;
            margin-bottom: 20px;
        }

        .about-hero h1 span {
            color: var(--accent);
        }

        .about-hero p {
            position: relative;
            max-width: 700px;
            margin: auto;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.8;
        }

        /* MAIN CONTENT */

        .section {
            padding: 90px 0;
        }

        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 65px;
            align-items: center;
        }

        .section-label {
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .section-title {
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(30px, 4vw, 44px);
            line-height: 1.15;
            margin-bottom: 20px;
        }

        .content-text {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.9;
            margin-bottom: 17px;
        }

        /* INFO CARD */

        .system-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 30px;
        }

        .system-card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 22px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 22px;
        }

        .system-icon {
            width: 48px;
            height: 48px;
            border: 1px solid var(--accent);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 21px;
        }

        .system-card-header h3 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 18px;
        }

        .system-card-header span {
            color: var(--muted);
            font-size: 11px;
        }

        .system-list {
            list-style: none;
        }

        .system-list li {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 12px 0;
            color: var(--muted);
            font-size: 13px;
            border-bottom: 1px solid rgba(22, 55, 59, 0.6);
        }

        .system-list li:last-child {
            border-bottom: 0;
        }

        .check {
            color: var(--accent);
            font-weight: 700;
        }

        /* PURPOSE CARDS */

        .purpose-section {
            background: #08181b;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .section-heading {
            max-width: 680px;
            margin-bottom: 42px;
        }

        .section-heading h2 {
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(30px, 4vw, 42px);
            margin-bottom: 14px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 14px;
        }

        .purpose-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .purpose-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 28px;
            transition: 0.25s ease;
        }

        .purpose-card:hover {
            transform: translateY(-5px);
            border-color: rgba(66, 230, 196, 0.45);
        }

        .purpose-number {
            color: var(--accent);
            font-family: "Space Grotesk", sans-serif;
            font-size: 12px;
            margin-bottom: 18px;
        }

        .purpose-card h3 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .purpose-card p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.75;
        }

        /* WORKFLOW */

        .workflow-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .workflow-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 11px;
            padding: 24px;
        }

        .workflow-number {
            color: var(--accent);
            font-family: "Space Grotesk", sans-serif;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .workflow-card h3 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .workflow-card p {
            color: var(--muted);
            font-size: 12px;
        }

        /* CTA */

        .cta {
            padding: 20px 0 100px;
        }

        .cta-box {
            padding: 50px;
            border: 1px solid var(--border);
            border-radius: 15px;
            background:
                linear-gradient(135deg, rgba(66, 230, 196, 0.08), transparent 55%),
                var(--card);
            text-align: center;
        }

        .cta-box h2 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .cta-box p {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 25px;
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
        }

        /* FOOTER */

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

        /* RESPONSIVE */

        @media (max-width: 900px) {
            .two-column {
                grid-template-columns: 1fr;
            }

            .purpose-grid {
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

            .purpose-grid,
            .workflow-grid,
            .footer-main {
                grid-template-columns: 1fr;
            }

            .cta-box {
                padding: 30px;
            }

            .footer-bottom {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->

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
            <a href="index.php">Home</a>
            <a href="about.php" class="active">About</a>
            <a href="contact.php">Contact</a>
            <a href="login.php" class="nav-login">Login</a>
        </nav>

    </div>
</header>


<!-- HERO -->

<section class="about-hero">

    <div class="container">

        <div class="hero-label">
            About The System
        </div>

        <h1>
            Built for <span>Smarter</span><br>
            Laboratory Operations
        </h1>

        <p>
            LAB AUTOMATION is designed to organize electrical product
            testing and laboratory activities through a centralized,
            structured and easy-to-use system.
        </p>

    </div>

</section>


<!-- ABOUT SYSTEM -->

<section class="section">

    <div class="container two-column">

        <div>

            <div class="section-label">
                Our System
            </div>

            <h2 class="section-title">
                Bringing laboratory testing into one organized platform.
            </h2>

            <p class="content-text">
                Electrical products such as switch gears, fuses,
                capacitors and resistors require proper laboratory
                testing before they can move forward in the production
                and approval process.
            </p>

            <p class="content-text">
                LAB AUTOMATION provides a centralized environment where
                product information, test types, testers, testing
                activities and results can be managed efficiently.
            </p>

            <p class="content-text">
                The system helps laboratory teams maintain organized
                records and provides a clearer view of the testing
                process.
            </p>

        </div>


        <div class="system-card">

            <div class="system-card-header">

                <div class="system-icon">⚡</div>

                <div>
                    <h3>LAB AUTOMATION</h3>
                    <span>Electrical Testing System</span>
                </div>

            </div>

            <ul class="system-list">

                <li>
                    <span class="check">✓</span>
                    Product information management
                </li>

                <li>
                    <span class="check">✓</span>
                    Electrical test management
                </li>

                <li>
                    <span class="check">✓</span>
                    Tester information management
                </li>

                <li>
                    <span class="check">✓</span>
                    Test type organization
                </li>

                <li>
                    <span class="check">✓</span>
                    Testing result records
                </li>

                <li>
                    <span class="check">✓</span>
                    Laboratory activity overview
                </li>

            </ul>

        </div>

    </div>

</section>


<!-- PURPOSE -->

<section class="section purpose-section">

    <div class="container">

        <div class="section-heading">

            <div class="section-label">
                Why LAB AUTOMATION
            </div>

            <h2>
                Designed around the laboratory workflow.
            </h2>

            <p>
                The system focuses on keeping important laboratory
                information organized and accessible throughout the
                testing process.
            </p>

        </div>


        <div class="purpose-grid">

            <div class="purpose-card">

                <div class="purpose-number">01 / ORGANIZE</div>

                <h3>Centralized Information</h3>

                <p>
                    Keep product, tester, test type and testing
                    information within one structured system.
                </p>

            </div>


            <div class="purpose-card">

                <div class="purpose-number">02 / TRACK</div>

                <h3>Track Testing Activities</h3>

                <p>
                    Record testing activities and results so laboratory
                    work can be followed more efficiently.
                </p>

            </div>


            <div class="purpose-card">

                <div class="purpose-number">03 / MANAGE</div>

                <h3>Better Laboratory Management</h3>

                <p>
                    Provide laboratory teams with a clearer overview
                    of products and their testing progress.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- WORKFLOW -->

<section class="section">

    <div class="container">

        <div class="section-heading">

            <div class="section-label">
                Laboratory Process
            </div>

            <h2>
                A structured approach to product testing.
            </h2>

            <p>
                LAB AUTOMATION supports the major stages involved in
                managing electrical product testing.
            </p>

        </div>


        <div class="workflow-grid">

            <div class="workflow-card">

                <div class="workflow-number">01 / PRODUCT</div>

                <h3>Product Entry</h3>

                <p>
                    Product details are recorded before laboratory
                    testing begins.
                </p>

            </div>


            <div class="workflow-card">

                <div class="workflow-number">02 / TESTING</div>

                <h3>Test Assignment</h3>

                <p>
                    Appropriate testing activities can be managed
                    according to product requirements.
                </p>

            </div>


            <div class="workflow-card">

                <div class="workflow-number">03 / RESULT</div>

                <h3>Result Recording</h3>

                <p>
                    Testing results are recorded and associated with
                    the relevant product.
                </p>

            </div>


            <div class="workflow-card">

                <div class="workflow-number">04 / FOLLOW-UP</div>

                <h3>Next Decision</h3>

                <p>
                    Testing information supports the next stage of the
                    product workflow.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- CTA -->

<section class="cta">

    <div class="container">

        <div class="cta-box">

            <h2>Ready to explore the system?</h2>

            <p>
                Access LAB AUTOMATION and manage laboratory testing
                activities from one platform.
            </p>

            <a href="login.php" class="btn btn-primary">
                Access System →
            </a>

        </div>

    </div>

</section>


<!-- FOOTER -->

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
            <a href="index.php#features">Features</a>
            <a href="login.php">Access System</a>

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