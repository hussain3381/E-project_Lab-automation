<?php
// Shared status pill for tables and details pages.
$badgeLabel = (string) ($badgeLabel ?? 'Unknown');
$badgeTone = strtolower((string) ($badgeTone ?? $badgeLabel));
$badgeClass = match (true) {
    in_array($badgeTone, ['pass', 'passed', 'completed', 'ready', 'active'], true) => 'is-success',
    in_array($badgeTone, ['pending', 'in progress', 'testing in progress', 'ready for retest'], true) => 'is-warning',
    in_array($badgeTone, ['fail', 'failed', 'failed - re-manufacturing', 'inactive'], true) => 'is-danger',
    in_array($badgeTone, ['cpri ready', 'handed to cpri', 'review'], true) => 'is-info',
    default => '',
};
?>
<span class="status-pill <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
