<?php
// Login page
// Backend authentication baad mein connect karenge.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lab Automation | Login</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>

        /* ================================
           RESET
        ================================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: "Inter", sans-serif;
            background: #071014;
            color: #ffffff;
            overflow: hidden;
        }


        /* ================================
           BACKGROUND
        ================================= */

        .page {
            min-height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background:
                radial-gradient(circle at 15% 20%, rgba(0, 220, 190, 0.10), transparent 28%),
                radial-gradient(circle at 85% 80%, rgba(0, 150, 255, 0.08), transparent 30%),
                #071014;
        }


        /* Grid */

        .grid {
            position: absolute;
            inset: 0;
            opacity: 0.20;
            background-image:
                linear-gradient(rgba(105, 170, 170, 0.10) 1px, transparent 1px),
                linear-gradient(90deg, rgba(105, 170, 170, 0.10) 1px, transparent 1px);
            background-size: 55px 55px;
            mask-image: linear-gradient(
                to bottom,
                transparent,
                black 20%,
                black 80%,
                transparent
            );
        }


        /* Glow circles */

        .glow {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }

        .glow-one {
            width: 300px;
            height: 300px;
            background: rgba(0, 220, 190, 0.08);
            top: -100px;
            left: -80px;
        }

        .glow-two {
            width: 350px;
            height: 350px;
            background: rgba(0, 130, 255, 0.07);
            right: -120px;
            bottom: -120px;
        }


        /* ================================
           CIRCUIT DECORATION
        ================================= */

        .circuit {
            position: absolute;
            opacity: 0.30;
        }

        .circuit-left {
            left: 7%;
            top: 25%;
        }

        .circuit-right {
            right: 7%;
            bottom: 22%;
            transform: rotate(180deg);
        }

        .line {
            height: 1px;
            background: #2aa89b;
            position: absolute;
        }

        .line.one {
            width: 130px;
            top: 0;
            left: 0;
        }

        .line.two {
            width: 1px;
            height: 70px;
            top: 0;
            left: 130px;
        }

        .line.three {
            width: 90px;
            top: 70px;
            left: 130px;
        }

        .node {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #48d7c4;
            box-shadow: 0 0 14px rgba(72, 215, 196, 0.8);
            position: absolute;
        }

        .node.one {
            left: -3px;
            top: -3px;
        }

        .node.two {
            left: 127px;
            top: -3px;
        }

        .node.three {
            left: 127px;
            top: 67px;
        }

        .node.four {
            left: 217px;
            top: 67px;
        }


        /* ================================
           MAIN CONTAINER
        ================================= */

        .login-wrapper {
            width: min(1050px, 92%);
            min-height: 610px;
            position: relative;
            z-index: 5;

            display: grid;
            grid-template-columns: 1.05fr 0.95fr;

            background: rgba(12, 25, 29, 0.72);
            border: 1px solid rgba(112, 190, 180, 0.16);
            border-radius: 28px;

            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);

            box-shadow:
                0 30px 80px rgba(0, 0, 0, 0.45),
                inset 0 1px 0 rgba(255, 255, 255, 0.04);

            overflow: hidden;
        }


        /* ================================
           LEFT PANEL
        ================================= */

        .intro {
            padding: 65px;
            display: flex;
            flex-direction: column;
            justify-content: center;

            border-right: 1px solid rgba(255, 255, 255, 0.06);

            background:
                linear-gradient(
                    145deg,
                    rgba(0, 209, 181, 0.07),
                    transparent 50%
                );
        }


        /* Brand */

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 55px;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            border: 1px solid rgba(72, 215, 196, 0.45);
            border-radius: 14px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: rgba(72, 215, 196, 0.07);

            font-size: 22px;

            box-shadow:
                0 0 25px rgba(72, 215, 196, 0.08);
        }

        .brand-text h2 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 18px;
            letter-spacing: 0.5px;
        }

        .brand-text p {
            color: #7e9899;
            font-size: 11px;
            margin-top: 3px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }


        /* Heading */

        .intro h1 {
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(38px, 4vw, 58px);
            line-height: 1.05;
            letter-spacing: -2px;
            max-width: 500px;
            margin-bottom: 22px;
        }

        .intro h1 span {
            color: #48d7c4;
        }

        .intro-description {
            max-width: 470px;
            color: #91a6a8;
            font-size: 14px;
            line-height: 1.8;
        }


        /* Feature boxes */

        .features {
            display: flex;
            gap: 12px;
            margin-top: 45px;
            flex-wrap: wrap;
        }

        .feature {
            padding: 12px 15px;
            border-radius: 10px;

            background: rgba(255, 255, 255, 0.025);
            border: 1px solid rgba(255, 255, 255, 0.06);

            color: #a7b9ba;
            font-size: 11px;
            letter-spacing: 0.3px;
        }

        .feature span {
            color: #48d7c4;
            margin-right: 6px;
        }


        /* ================================
           RIGHT LOGIN PANEL
        ================================= */

        .login-panel {
            padding: 65px 58px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            margin-bottom: 36px;
        }

        .login-header .small-title {
            color: #48d7c4;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .login-header h2 {
            font-family: "Space Grotesk", sans-serif;
            font-size: 32px;
            letter-spacing: -1px;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #819698;
            font-size: 13px;
        }


        /* ================================
           FORM
        ================================= */

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            color: #b7c6c7;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 9px;
        }


        .input-box {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 17px;
            top: 50%;
            transform: translateY(-50%);

            color: #688083;
            font-size: 16px;

            pointer-events: none;
        }

        .input-box input {
            width: 100%;
            height: 52px;

            border: 1px solid rgba(126, 170, 170, 0.15);
            border-radius: 12px;

            background: rgba(255, 255, 255, 0.035);
            color: #ffffff;

            padding: 0 45px;
            outline: none;

            font-family: "Inter", sans-serif;
            font-size: 13px;

            transition: 0.25s ease;
        }

        .input-box input::placeholder {
            color: #526769;
        }

        .input-box input:focus {
            border-color: rgba(72, 215, 196, 0.6);
            background: rgba(72, 215, 196, 0.035);

            box-shadow:
                0 0 0 4px rgba(72, 215, 196, 0.06);
        }


        /* Show password */

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);

            border: none;
            background: transparent;
            color: #688083;
            cursor: pointer;

            font-size: 12px;
        }

        .password-toggle:hover {
            color: #48d7c4;
        }


        /* ================================
           OPTIONS
        ================================= */

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin: 5px 0 28px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;

            color: #819698;
            font-size: 11px;
            cursor: pointer;
        }

        .remember input {
            width: 14px;
            height: 14px;
            accent-color: #48d7c4;
            cursor: pointer;
        }

        .forgot {
            color: #48d7c4;
            text-decoration: none;
            font-size: 11px;
        }

        .forgot:hover {
            text-decoration: underline;
        }


        /* ================================
           LOGIN BUTTON
        ================================= */

        .login-btn {
            width: 100%;
            height: 54px;

            border: none;
            border-radius: 12px;

            background: #48d7c4;
            color: #061110;

            font-family: "Space Grotesk", sans-serif;
            font-size: 14px;
            font-weight: 700;

            cursor: pointer;

            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease,
                background 0.25s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);

            background: #5ee5d2;

            box-shadow:
                0 12px 30px rgba(72, 215, 196, 0.18);
        }

        .login-btn:active {
            transform: translateY(0);
        }


        /* ================================
           SECURITY NOTE
        ================================= */

        .security-note {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;

            margin-top: 24px;

            color: #506567;
            font-size: 10px;
        }

        .security-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #48d7c4;
            box-shadow: 0 0 8px rgba(72, 215, 196, 0.8);
        }


        /* ================================
           FOOTER
        ================================= */

        .footer {
            position: absolute;
            bottom: 18px;
            left: 0;
            width: 100%;

            text-align: center;

            color: #3f5658;
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
            z-index: 3;
        }


        /* ================================
           RESPONSIVE
        ================================= */

        @media (max-width: 850px) {

            body {
                overflow-y: auto;
            }

            .page {
                padding: 30px 0;
            }

            .login-wrapper {
                grid-template-columns: 1fr;
                width: 92%;
                min-height: auto;
            }

            .intro {
                padding: 45px 35px;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            }

            .intro h1 {
                font-size: 42px;
            }

            .brand {
                margin-bottom: 35px;
            }

            .login-panel {
                padding: 45px 35px;
            }

            .circuit {
                display: none;
            }

            .footer {
                position: relative;
                margin-top: 20px;
            }
        }


        @media (max-width: 480px) {

            .intro {
                padding: 35px 25px;
            }

            .login-panel {
                padding: 35px 25px;
            }

            .intro h1 {
                font-size: 35px;
            }

            .features {
                margin-top: 30px;
            }

            .login-header h2 {
                font-size: 28px;
            }
        }

    </style>
</head>

<body>

<div class="page">

    <!-- Background -->
    <div class="grid"></div>

    <div class="glow glow-one"></div>
    <div class="glow glow-two"></div>


    <!-- Decorative Circuit Left -->
    <div class="circuit circuit-left">
        <div class="line one"></div>
        <div class="line two"></div>
        <div class="line three"></div>

        <div class="node one"></div>
        <div class="node two"></div>
        <div class="node three"></div>
        <div class="node four"></div>
    </div>


    <!-- Decorative Circuit Right -->
    <div class="circuit circuit-right">
        <div class="line one"></div>
        <div class="line two"></div>
        <div class="line three"></div>

        <div class="node one"></div>
        <div class="node two"></div>
        <div class="node three"></div>
        <div class="node four"></div>
    </div>


    <!-- Main Login Card -->
    <div class="login-wrapper">


        <!-- LEFT -->
        <section class="intro">

            <div class="brand">

                <div class="brand-icon">
                    ⚡
                </div>

                <div class="brand-text">
                    <h2>LAB AUTOMATION</h2>
                    <p>Electrical Testing System</p>
                </div>

            </div>


            <h1>
                Test smarter.<br>
                <span>Track better.</span>
            </h1>

            <p class="intro-description">
                A centralized laboratory testing system designed to
                manage products, testing procedures, results and
                complete testing history in one secure workspace.
            </p>


            <div class="features">

                <div class="feature">
                    <span>●</span> Test Tracking
                </div>

                <div class="feature">
                    <span>●</span> Result Management
                </div>

                <div class="feature">
                    <span>●</span> Product Records
                </div>

            </div>

        </section>



        <!-- RIGHT -->
        <section class="login-panel">

            <div class="login-header">

                <div class="small-title">
                    Secure Access
                </div>

                <h2>Welcome back</h2>

                <p>
                    Sign in to access the laboratory workspace.
                </p>

            </div>


            <!-- Login Form -->

            <form action="dashboard.php" method="POST">

                <!-- Username -->

                <div class="form-group">

                    <label for="username">
                        Username
                    </label>

                    <div class="input-box">

                        <span class="input-icon">
                            ◉
                        </span>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username"
                            required
                        >

                    </div>

                </div>


                <!-- Password -->

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-box">

                        <span class="input-icon">
                            ◆
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                        >
                            SHOW
                        </button>

                    </div>

                </div>


                <!-- Options -->

                <div class="form-options">

                    <label class="remember">

                        <input
                            type="checkbox"
                            name="remember"
                        >

                        Remember me

                    </label>

                    <a href="#" class="forgot">
                        Forgot password?
                    </a>

                </div>


                <!-- Login Button -->

                <button
                    type="submit"
                    class="login-btn"
                >
                    SIGN IN TO LAB
                </button>

            </form>


            <!-- Security -->

            <div class="security-note">

                <span class="security-dot"></span>

                Secure laboratory access

            </div>

        </section>

    </div>


    <div class="footer">
        LAB AUTOMATION SYSTEM • ELECTRICAL TESTING &amp; QUALITY CONTROL
    </div>

</div>


<script>

    function togglePassword() {

        const password =
            document.getElementById("password");

        const button =
            document.querySelector(".password-toggle");

        if (password.type === "password") {

            password.type = "text";
            button.textContent = "HIDE";

        } else {

            password.type = "password";
            button.textContent = "SHOW";

        }

    }

</script>

</body>
</html>