<!DOCTYPE html>
<html lang="en">
<head>
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lab Automation | Login</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/pages/login.css">
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

            <form action="login.php" method="POST">

                <!-- Protect the sign-in POST request against cross-site request forgery. -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <?php if (!empty($login_error)): ?>
                    <div class="login-alert" role="alert"><?php echo htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

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

                    <span class="forgot">Forgot password? Contact your administrator.</span>

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