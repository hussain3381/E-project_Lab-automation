<?php
$publicTitle = 'Create a Tester account';
$publicActivePage = 'register.php';
require __DIR__ . '/../layouts/public_start.php';
?>
<section class="auth-layout">
    <div class="form-card" data-reveal>
        <p class="eyebrow"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> NEW STAFF ACCOUNT</p>
        <h1>Join the workspace.</h1>
        <p>Create a Tester account. Elevated roles are assigned only by an Administrator.</p>
        <?php if (!empty($registrationError)): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($registrationError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <form class="form-stack" action="register.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="field-grid">
                <div class="form-field"><label for="name">Full name</label><input id="name" name="name" maxlength="120" autocomplete="name" required></div>
                <div class="form-field"><label for="username">Username</label><input id="username" name="username" minlength="3" maxlength="40" pattern="[A-Za-z0-9._-]+" autocomplete="username" required><span class="form-hint">3–40 letters, numbers, dots, underscores or hyphens.</span></div>
            </div>
            <div class="form-field"><label for="email">Work email</label><input id="email" name="email" type="email" maxlength="190" autocomplete="email" required></div>
            <div class="field-grid">
                <div class="form-field"><label for="password">Password</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required><span class="form-hint">At least 12 characters.</span></div>
                <div class="form-field"><label for="password-confirm">Confirm password</label><input id="password-confirm" name="password_confirm" type="password" minlength="12" autocomplete="new-password" required></div>
            </div>
            <div class="alert alert-success"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Registration creates an active Tester account and linked tester profile. No role selector is exposed.</div>
            <button type="submit" class="button button-primary w-full"><i class="fa-solid fa-user-check" aria-hidden="true"></i> Create Tester account</button>
        </form>
        <p class="auth-footer-link">Already registered? <a href="login.php">Sign in</a></p>
    </div>
</section>
<?php require __DIR__ . '/../layouts/public_end.php'; ?>
