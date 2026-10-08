<?php
// Reusable statistic card; caller supplies trusted icon/tone tokens and a rendered value.
$statLabel = (string) ($statLabel ?? 'Metric');
$statValue = $statValue ?? 0;
$statNote = (string) ($statNote ?? 'Current records');
$statIcon = (string) ($statIcon ?? 'fa-chart-line');
$statTone = (string) ($statTone ?? 'var(--theme-accent)');
?>
<article class="stat-card" style="--stat-tint: <?php echo htmlspecialchars($statTone, ENT_QUOTES, 'UTF-8'); ?>" data-reveal>
    <div class="stat-card-top">
        <span class="stat-card-label"><?php echo htmlspecialchars($statLabel, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="stat-card-icon"><i class="fa-solid <?php echo htmlspecialchars($statIcon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i></span>
    </div>
    <div class="stat-card-value"><?php echo htmlspecialchars((string) $statValue, ENT_QUOTES, 'UTF-8'); ?></div>
    <p class="stat-card-note"><?php echo htmlspecialchars($statNote, ENT_QUOTES, 'UTF-8'); ?></p>
</article>
