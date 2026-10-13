<?php
$publicTitle = 'Contact the laboratory team';
$publicActivePage = 'contact.php';
require __DIR__ . '/../layouts/public_start.php';
?>
<section class="public-section split-section" data-reveal>
    <div>
        <p class="eyebrow">CONTACT</p>
        <h1 class="hero-title text-5xl sm:text-6xl">Let’s talk<br><span>lab workflow.</span></h1>
        <p class="hero-lede">Send a question about setup, access or the testing workflow. This demo form records a message for an administrator; it does not send email.</p>
        <ul class="info-list">
            <li><i class="fa-solid fa-lock" aria-hidden="true"></i><span>Form submissions are protected with a session CSRF token.</span></li>
            <li><i class="fa-solid fa-database" aria-hidden="true"></i><span>Messages are stored in the local lab database.</span></li>
        </ul>
    </div>
    <section class="form-card">
        <h2>Send a message</h2>
        <p>Fields marked required are needed to save the request.</p>
        <?php if (!empty($contactSuccess)): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($contactSuccess, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if (!empty($contactError)): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($contactError, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <form class="form-stack" method="post" action="contact.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="field-grid">
                <div class="form-field"><label for="contact-name">Name</label><input id="contact-name" name="name" maxlength="120" autocomplete="name" required></div>
                <div class="form-field"><label for="contact-email">Email</label><input id="contact-email" name="email" type="email" maxlength="190" autocomplete="email" required></div>
            </div>
            <div class="form-field"><label for="contact-subject">Subject</label><input id="contact-subject" name="subject" maxlength="160" required></div>
            <div class="form-field"><label for="contact-message">Message</label><textarea id="contact-message" name="message" maxlength="5000" required></textarea></div>
            <button class="button button-primary w-full" type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Save message</button>
        </form>
    </section>
</section>
<?php require __DIR__ . '/../layouts/public_end.php'; ?>
