<?php
$publicTitle = 'About the laboratory workspace';
$publicActivePage = 'about.php';
require __DIR__ . '/../layouts/public_start.php';
?>
<section class="public-section" data-reveal>
    <div class="public-section-heading">
        <p class="eyebrow">ABOUT THE SYSTEM</p>
        <h1 class="hero-title text-5xl sm:text-6xl">One record.<br><span>Every decision.</span></h1>
        <p>This plain-PHP laboratory application supports electrical product testing with a traceable workflow, searchable records and carefully scoped staff access.</p>
    </div>
    <div class="split-section">
        <article class="feature-card">
            <span class="feature-icon"><i class="fa-solid fa-diagram-project" aria-hidden="true"></i></span>
            <h2 class="mt-4 text-xl font-bold text-lab-text">Designed around the lab workflow</h2>
            <ul class="info-list mt-4">
                <li><i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span>Products map to a product family and exact model code.</span></li>
                <li><i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span>Required tests are listed against the product family.</span></li>
                <li><i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span>Failures route to re-manufacture; pass decisions stay in the test cycle.</span></li>
                <li><i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span>CPRI readiness is a manual workflow record; no external API transfer is implied.</span></li>
            </ul>
        </article>
        <article class="feature-card">
            <span class="feature-icon"><i class="fa-solid fa-user-lock" aria-hidden="true"></i></span>
            <h2 class="mt-4 text-xl font-bold text-lab-text">Access follows responsibility</h2>
            <ul class="info-list mt-4">
                <li><i class="fa-solid fa-user-shield" aria-hidden="true"></i><span>Administrator manages users, roles and configuration.</span></li>
                <li><i class="fa-solid fa-user-tie" aria-hidden="true"></i><span>Lab Manager coordinates products, staff and daily work.</span></li>
                <li><i class="fa-solid fa-flask" aria-hidden="true"></i><span>Tester records assigned results and sees their test history.</span></li>
                <li><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i><span>Quality Control reviews outcomes and workflow gates.</span></li>
            </ul>
        </article>
    </div>
    <aside class="feature-card mt-5"><p class="eyebrow">IMPORTANT</p><p class="m-0 text-sm leading-7 text-lab-muted">Demo measurements, accounts and CPRI references are synthetic training data. Replace them with approved procedures and real verified records before operational use.</p></aside>
</section>
<?php require __DIR__ . '/../layouts/public_end.php'; ?>
