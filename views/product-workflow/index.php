<?php
$pageTitle = 'Product workflow';
$pageEyebrow = (string) ($_SESSION['role'] ?? '') === 'Administrator' ? 'ADMIN / QUALITY CONTROL' : 'QUALITY CONTROL';
$pageDescription = 'Confirm completed rework, log each manual CPRI result, and record market release only after CPRI approval.';
$pageStylesheet = 'assets/css/pages/product-workflow.css';
$pageActionHtml = '<a class="button button-secondary" href="products.php"><i class="fa-solid fa-cubes-stacked" aria-hidden="true"></i> Products</a>';
require __DIR__ . '/../layouts/app_start.php';
?>
<div class="workflow-shell">
    <?php if ($workflowState['message'] !== ''): ?>
        <div class="workflow-alert workflow-alert--success" role="status"><?php echo htmlspecialchars($workflowState['message'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($workflowState['error'] !== ''): ?>
        <div class="workflow-alert workflow-alert--error" role="alert"><?php echo htmlspecialchars($workflowState['error'], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="workflow-guide panel" aria-label="Workflow steps" data-reveal>
        <article class="workflow-guide-step"><span>01</span><div><strong>Register and assign</strong><small>The Lab Manager registers the unit; an Administrator, Manager, or QC member assigns each eligible test to available Tester(s).</small></div></article>
        <article class="workflow-guide-step"><span>02</span><div><strong>Complete every required test</strong><small>Testers submit only their assigned pending work; all required tests must pass in this cycle.</small></div></article>
        <article class="workflow-guide-step"><span>03</span><div><strong>Rework and repeat the full plan</strong><small>QC confirms physical re-manufacture. An authorized assigner then schedules the next unused family-plan test in the new cycle.</small></div></article>
        <article class="workflow-guide-step"><span>04</span><div><strong>Record CPRI result, then release</strong><small>QC records the external PASS/FAIL manually. Market release is available only after PASS; no CPRI API is used.</small></div></article>
    </section>

    <section class="workflow-card panel" aria-label="Product workflow status" data-reveal>
        <div class="workflow-table-wrap">
            <table>
                <thead>
                    <tr><th>Product</th><th>Product stage</th><th>Test round</th><th>Required-test progress</th><th>CPRI / release history</th><th>Next action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($productRows as $product): ?>
                    <?php
                    $requiredTests = (int) $product['required_tests'];
                    $passedTests = (int) $product['passed_tests'];
                    $status = (string) $product['status'];
                    $cpriStatus = (string) $product['cpri_status'];
                    $statusClass = str_contains($status, 'Failed') ? 'is-danger'
                        : (in_array($status, ['CPRI Approved', 'Released to Market'], true) ? 'is-success'
                            : (in_array($status, ['CPRI Ready', 'Handed to CPRI', 'Testing In Progress', 'Ready for Retest'], true) ? 'is-info' : 'is-warning'));
                    $productKey = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $product['product_id']);
                    $cpriResult = strtoupper((string) ($product['cpri_result'] ?? 'PENDING'));
                    ?>
                    <tr>
                        <td>
                            <a class="workflow-product-link" href="product-details.php?id=<?php echo (int) $product['record_id']; ?>">
                                <?php echo htmlspecialchars((string) $product['product_id'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                            <small><?php echo htmlspecialchars((string) $product['product_name'] . ' · ' . (string) $product['product_type'], ENT_QUOTES, 'UTF-8'); ?></small>
                        </td>
                        <td><span class="status-pill <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><span class="workflow-cycle">Cycle <?php echo (int) $product['current_cycle']; ?></span></td>
                        <td class="workflow-progress-cell">
                            <?php if ($requiredTests > 0): ?>
                                <progress class="workflow-progress" value="<?php echo min($passedTests, $requiredTests); ?>" max="<?php echo $requiredTests; ?>" aria-label="<?php echo $passedTests; ?> of <?php echo $requiredTests; ?> required tests passed"></progress>
                                <small><?php echo $passedTests; ?> of <?php echo $requiredTests; ?> required tests passed</small>
                            <?php else: ?>
                                <span class="workflow-muted">No family test plan configured.</span>
                                <small>
                                    <?php if (app_can_access_route('product-test-plan.php')): ?>
                                        <a href="product-test-plan.php?product_type_id=<?php echo (int) $product['product_type_id']; ?>">Configure test plan</a>
                                    <?php else: ?>
                                        Ask an Administrator to configure the family test plan.
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="workflow-cpri-state">CPRI: <?php echo htmlspecialchars($cpriStatus, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if (!empty($product['last_handoff_at'])): ?>
                                <small>Cycle <?php echo (int) $product['last_handoff_cycle']; ?> handoff: <?php echo htmlspecialchars((string) $product['last_handoff_at'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string) ($product['last_handoff_by'] ?? ''), ENT_QUOTES, 'UTF-8'); ?><?php if ((string) ($product['last_handoff_reference'] ?? '') !== ''): ?> · Ref <?php echo htmlspecialchars((string) $product['last_handoff_reference'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></small>
                                <small>CPRI result: <?php echo htmlspecialchars($cpriResult === 'PENDING' ? 'Awaiting result' : $cpriResult, ENT_QUOTES, 'UTF-8'); ?><?php if (!empty($product['cpri_result_at'])): ?> · <?php echo htmlspecialchars((string) $product['cpri_result_at'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string) ($product['cpri_result_by'] ?? ''), ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></small>
                                <?php if (!empty($product['market_released_at'])): ?>
                                    <small>Market released: <?php echo htmlspecialchars((string) $product['market_released_at'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string) ($product['market_released_by'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <small>No CPRI handoff recorded for this product yet.</small>
                            <?php endif; ?>
                        </td>
                        <td class="workflow-action">
                            <?php if ($status === 'Failed - Re-manufacturing'): ?>
                                <form method="POST" action="product-workflow.php" onsubmit="return confirm('Confirm that physical re-manufacturing is complete and release the product for a full new test cycle?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="mark_remanufactured">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars((string) $product['product_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <small class="workflow-action-hint">Use only after physical re-manufacturing is complete.</small>
                                    <label class="sr-only" for="rework-note-<?php echo $productKey; ?>">Optional rework note</label>
                                    <textarea id="rework-note-<?php echo $productKey; ?>" name="notes" maxlength="5000" rows="2" placeholder="What was repaired? (optional)"></textarea>
                                    <button class="button button-primary" type="submit"><i class="fa-solid fa-arrow-rotate-right" aria-hidden="true"></i> Confirm rework and release retest</button>
                                </form>
                            <?php elseif ($status === 'CPRI Ready' && $cpriStatus === 'Ready'): ?>
                                <form method="POST" action="product-workflow.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="cpri_handoff">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars((string) $product['product_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <label class="sr-only" for="cpri-reference-<?php echo $productKey; ?>">Optional CPRI reference</label>
                                    <input id="cpri-reference-<?php echo $productKey; ?>" name="reference" maxlength="100" placeholder="Optional CPRI reference">
                                    <label class="sr-only" for="cpri-note-<?php echo $productKey; ?>">Optional handoff note</label>
                                    <textarea id="cpri-note-<?php echo $productKey; ?>" name="notes" maxlength="5000" rows="2" placeholder="Optional handoff note"></textarea>
                                    <button class="button button-primary" type="submit"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Record CPRI handoff</button>
                                </form>
                            <?php elseif ($status === 'Handed to CPRI' && $cpriStatus === 'Handed Off'): ?>
                                <form method="POST" action="product-workflow.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="cpri_result">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars((string) $product['product_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <small class="workflow-action-hint">Enter the result returned by CPRI. This is a manual record; nothing is sent externally.</small>
                                    <label class="sr-only" for="cpri-result-<?php echo $productKey; ?>">CPRI result</label>
                                    <select id="cpri-result-<?php echo $productKey; ?>" name="cpri_result" required>
                                        <option value="">Choose CPRI result</option>
                                        <option value="PASS">PASS — approved</option>
                                        <option value="FAIL">FAIL — rejected; route to re-manufacture</option>
                                    </select>
                                    <label class="sr-only" for="cpri-result-note-<?php echo $productKey; ?>">Optional CPRI result note</label>
                                    <textarea id="cpri-result-note-<?php echo $productKey; ?>" name="notes" maxlength="5000" rows="2" placeholder="Optional CPRI result note"></textarea>
                                    <button class="button button-primary" type="submit"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Record CPRI result</button>
                                </form>
                            <?php elseif ($status === 'CPRI Approved' && $cpriStatus === 'Approved'): ?>
                                <form method="POST" action="product-workflow.php" onsubmit="return confirm('Confirm this CPRI-approved product is authorized for release to market?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="market_release">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars((string) $product['product_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <small class="workflow-action-hint">Only record release after the required local approval is complete.</small>
                                    <label class="sr-only" for="release-note-<?php echo $productKey; ?>">Optional market release note</label>
                                    <textarea id="release-note-<?php echo $productKey; ?>" name="notes" maxlength="5000" rows="2" placeholder="Optional release note"></textarea>
                                    <button class="button button-primary" type="submit"><i class="fa-solid fa-box-open" aria-hidden="true"></i> Record market release</button>
                                </form>
                            <?php elseif ($status === 'Released to Market'): ?>
                                <span class="workflow-action-hint">Final release recorded. Test assignments are closed for this product cycle.</span>
                            <?php elseif ($status === 'Handed to CPRI'): ?>
                                <span class="workflow-muted">CPRI handoff logged for cycle <?php echo (int) ($product['last_handoff_cycle'] ?? $product['current_cycle']); ?>.</span>
                            <?php else: ?>
                                <a class="button button-secondary" href="testing.php"><i class="fa-solid fa-flask-vial" aria-hidden="true"></i> Open test records</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($productRows === []): ?>
                    <tr><td class="workflow-empty" colspan="6">No active products yet. Register a product, configure its family test plan, then assign the next required test.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside class="workflow-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span><strong>Manual boundary:</strong> The handoff, CPRI result, approval, and market release are stored locally with user/date/history. Nothing is sent to CPRI. A CPRI rejection routes the product to re-manufacture; after QC confirms rework, all required lab tests must pass again in a new cycle.</span></aside>
</div>
<?php require __DIR__ . '/../layouts/app_end.php'; ?>
