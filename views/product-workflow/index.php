<!doctype html>
<html lang="en">
<head>
    <script>/* Restore the saved palette before painting the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Workflow | Lab Automation</title>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
    <link rel="stylesheet" href="assets/css/pages/product-workflow.css">
</head>
<body class="workflow-page">
    <main class="workflow-shell">
        <header class="workflow-header">
            <div><p class="workflow-eyebrow">LAB OPERATIONS</p><h1>Product Workflow</h1><p>Review required-test progress, release re-manufactured products for retest, and log an external CPRI handoff only after the full required plan passes.</p></div>
            <a href="products.php">Back to Products</a>
        </header>
        <?php if ($workflowState['message'] !== ''): ?><div class="workflow-alert workflow-alert--success" role="status"><?php echo htmlspecialchars($workflowState['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($workflowState['error'] !== ''): ?><div class="workflow-alert workflow-alert--error" role="alert"><?php echo htmlspecialchars($workflowState['error'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <section class="workflow-card">
            <div class="workflow-table-wrap"><table>
                <thead><tr><th>Product</th><th>Status</th><th>Test cycle</th><th>Required tests</th><th>CPRI</th><th>Workflow action</th></tr></thead>
                <tbody>
                <?php foreach ($productRows as $product): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($product['product_id'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($product['product_name'] . ' · ' . $product['product_type'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                        <td><span class="workflow-status"><?php echo htmlspecialchars($product['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo (int) $product['current_cycle']; ?></td>
                        <td><?php echo (int) $product['passed_tests']; ?> / <?php echo (int) $product['required_tests']; ?> passed<?php if ((int) $product['required_tests'] === 0): ?><small><a href="product-test-plan.php?product_type_id=<?php echo (int) $product['product_type_id']; ?>">Configure test plan</a></small><?php endif; ?></td>
                        <td><?php echo htmlspecialchars($product['cpri_status'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($product['last_handoff_at']): ?><small><?php echo htmlspecialchars((string) $product['last_handoff_at'], ENT_QUOTES, 'UTF-8'); ?><?php if ((string) $product['last_handoff_reference'] !== ''): ?> · Ref <?php echo htmlspecialchars((string) $product['last_handoff_reference'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></small><?php endif; ?>
                        </td>
                        <td class="workflow-action">
                            <?php if ($product['status'] === 'Failed - Re-manufacturing'): ?>
                                <form method="POST" action="product-workflow.php" onsubmit="return confirm('Confirm that re-manufacturing is complete and release this product for retesting?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="mark_remanufactured">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input name="notes" maxlength="500" placeholder="Rework note (optional)">
                                    <button type="submit">Release for retest</button>
                                </form>
                            <?php elseif ($product['status'] === 'CPRI Ready' && $product['cpri_status'] === 'Ready'): ?>
                                <form method="POST" action="product-workflow.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="cpri_handoff">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input name="reference" maxlength="100" placeholder="CPRI reference (optional)">
                                    <input name="notes" maxlength="500" placeholder="Handoff note (optional)">
                                    <button type="submit">Record CPRI handoff</button>
                                </form>
                            <?php elseif ($product['status'] === 'Handed to CPRI'): ?>
                                <span class="workflow-muted">External handoff logged for cycle <?php echo (int) $product['last_handoff_cycle']; ?>.</span>
                            <?php else: ?>
                                <span class="workflow-muted">Complete assigned tests in Testing.</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($productRows === []): ?><tr><td colspan="6">No products have been registered.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </section>
        <aside class="workflow-note"><strong>Important:</strong> “Record CPRI handoff” saves the date, user, optional reference, and audit history locally. It does not send data to CPRI.</aside>
    </main>
</body>
</html>
