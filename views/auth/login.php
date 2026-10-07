<?php
$publicTitle = 'Sign in';
$publicActivePage = 'login.php';
require __DIR__ . '/../layouts/public_start.php';
?>
<section class="auth-layout">
    <div class="form-card" data-reveal>
        <p class="eyebrow"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> SECURE LAB ACCESS</p>
        <h1>Welcome back.</h1>
        <p>Sign in to open the workspace for your assigned role.</p>
        <?php if (!empty($login_error)): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <form class="form-stack" action="login.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-field"><label for="username">Username</label><input type="text" id="username" name="username" autocomplete="username" required autofocus></div>
            <div class="form-field"><label for="password">Password</label><input type="password" id="password" name="password" autocomplete="current-password" required></div>
            <button type="submit" class="button button-primary w-full"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Sign in</button>
        </form>
        <p class="auth-footer-link">New to the lab workspace? <a href="register.php">Create a Tester account</a></p>
        <p class="form-hint mt-4">Public registration always creates the least-privileged Tester role. An Administrator manages any role changes.</p>
    </div>
</section>
<?php require __DIR__ . '/../layouts/public_end.php'; ?>
