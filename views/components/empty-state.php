<?php
// Standard no-results state with optional safe icon and short instruction.
$emptyTitle = (string) ($emptyTitle ?? 'Nothing to show yet');
$emptyText = (string) ($emptyText ?? 'Records will appear here when available.');
$emptyIcon = (string) ($emptyIcon ?? 'fa-inbox');
?>
<div class="empty-state">
    <i class="fa-solid <?php echo htmlspecialchars($emptyIcon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
    <strong><?php echo htmlspecialchars($emptyTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
    <p><?php echo htmlspecialchars($emptyText, ENT_QUOTES, 'UTF-8'); ?></p>
</div>
