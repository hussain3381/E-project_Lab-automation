<?php
$publicTitle = 'Laboratory workflow, in one clear view';
$publicActivePage = 'index.php';
require __DIR__ . '/../layouts/public_start.php';
?>
<section class="hero-section">
    <div class="hero-copy" data-reveal>
        <p class="eyebrow"><i class="fa-solid fa-wave-square" aria-hidden="true"></i> ELECTRICAL TESTING · QUALITY · TRACEABILITY</p>
        <h1 class="hero-title">Test smarter.<br><span>Track better.</span></h1>
        <p class="hero-lede">A focused laboratory workspace to register products, run assigned tests, capture results and follow every quality decision—from intake to a manual CPRI handoff.</p>
        <div class="action-row">
            <a class="button button-primary" href="login.php"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Sign in to workspace</a>
            <a class="button button-secondary" href="about.php">Explore the workflow <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <ul class="info-list mt-6 grid grid-cols-1 gap-2 sm:grid-cols-2">
            <li><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span>Role-based access to lab operations</span></li>
            <li><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>Traceable test history and workflow</span></li>
        </ul>
    </div>
    <div class="hero-visual" aria-label="Illustration of a laboratory dashboard">
        <span class="hero-orb hero-orb-one"></span><span class="hero-orb hero-orb-two"></span>
        <div class="lab-visual-card" data-reveal>
            <div class="visual-card-head"><div><strong>Lab overview</strong><span class="table-secondary">Illustrative interface</span></div><span class="status-pill is-success"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Live</span></div>
            <div class="visual-chart" aria-hidden="true"><i style="height:28%"></i><i></i><i></i><i></i><i></i><i></i></div>
            <div class="visual-card-row"><span><i class="fa-solid fa-flask-vial" aria-hidden="true"></i> Test records</span><strong>TRACEABLE</strong></div>
            <div class="visual-card-row"><span><i class="fa-solid fa-diagram-project" aria-hidden="true"></i> Quality workflow</span><strong>CONTROLLED</strong></div>
            <div class="visual-card-row"><span><i class="fa-solid fa-user-shield" aria-hidden="true"></i> Role access</span><strong>SCOPED</strong></div>
        </div>
    </div>
</section>

<div class="marquee" aria-label="Product capabilities">
    <div class="marquee-track">
        <?php for ($copy = 0; $copy < 2; $copy++): ?>
            <div class="marquee-group" aria-hidden="<?php echo $copy === 1 ? 'true' : 'false'; ?>">
                <span><i class="fa-solid fa-cubes-stacked" aria-hidden="true"></i> Product records</span>
                <span><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Test results</span>
                <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Controlled access</span>
                <span><i class="fa-solid fa-arrows-spin" aria-hidden="true"></i> Re-manufacture workflow</span>
                <span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Manual CPRI handoff</span>
            </div>
        <?php endfor; ?>
    </div>
</div>

<section class="public-section" data-reveal>
    <div class="public-section-heading">
        <p class="eyebrow">BUILT FOR THE LAB FLOOR</p>
        <h2>Less chasing records.<br>More confidence in every result.</h2>
        <p>Keep product identity, test criteria, expected and actual output, outcomes, remarks, date and tester information together in one searchable history.</p>
    </div>
    <div class="feature-grid">
        <article class="feature-card"><span class="feature-icon"><i class="fa-solid fa-barcode" aria-hidden="true"></i></span><h3>Reliable product IDs</h3><p>Exact model-code mapping and validated ID segments help prevent ambiguous or truncated product identities.</p></article>
        <article class="feature-card"><span class="feature-icon"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span><h3>Structured testing</h3><p>Capture criteria, expected output, actual output, result and detailed remarks for each test cycle.</p></article>
        <article class="feature-card"><span class="feature-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span><h3>Role-aware workspace</h3><p>Administrators, lab managers, testers and quality control see the tools relevant to their work.</p></article>
    </div>
</section>

<section class="public-section split-section" data-reveal>
    <article class="feature-card">
        <p class="eyebrow">A CONTROLLED PATH</p>
        <h2 class="text-2xl font-bold tracking-tight text-lab-text">From intake to handoff</h2>
        <ul class="info-list mt-5">
            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>Register the product and map it to its exact model code.</span></li>
            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>Run required family tests and retain tester assignments.</span></li>
            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>Route failures to re-manufacture; retest in a new cycle.</span></li>
            <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>Only mark CPRI readiness after all required current-cycle tests pass.</span></li>
        </ul>
    </article>
    <article class="feature-card flex flex-col justify-between">
        <div><p class="eyebrow">GET STARTED</p><h2 class="text-2xl font-bold tracking-tight text-lab-text">A clearer picture of your lab.</h2><p class="mt-3 text-sm leading-7 text-lab-muted">Sign in to open your role-based dashboard, or contact the team to learn how the workflow is configured.</p></div>
        <div class="action-row mt-6"><a class="button button-primary" href="login.php">Open workspace</a><a class="button button-quiet" href="contact.php">Talk to the team</a></div>
    </article>
</section>
<?php require __DIR__ . '/../layouts/public_end.php'; ?>
