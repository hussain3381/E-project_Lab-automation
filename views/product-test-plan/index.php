<?php
$pageTitle = 'Family test plans';
$pageEyebrow = 'ADMIN / WORKFLOW SETUP';
$pageDescription = 'Choose required tests for each product family and control the CPRI readiness gate.';
$pageStylesheet = 'assets/css/pages/product-test-plan.css';
$pageActionHtml = '<a class="button button-quiet" href="test-types.php">Back to test types</a>' ;
require __DIR__ . '/../layouts/app_start.php';
?>
<div class="test-plan-shell">
        <?php if ($planState['message'] !== ''): ?><div class="test-plan-alert test-plan-alert--success" role="status"><?php echo htmlspecialchars($planState['message'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($planState['error'] !== ''): ?><div class="test-plan-alert test-plan-alert--error" role="alert"><?php echo htmlspecialchars($planState['error'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <section class="test-plan-card">
            <form method="GET" action="product-test-plan.php" class="test-plan-family-form">
                <label for="product-type-id">Product family</label>
                <select id="product-type-id" name="product_type_id" required onchange="this.form.submit()">
                    <?php foreach ($productTypes as $type): ?>
                        <option value="<?php echo (int) $type['id']; ?>" <?php echo (int) $type['id'] === $selectedTypeId ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['type_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php if ($productTypes === []): ?>
                <p>No active product families. Add one in <a href="product-catalog.php">Product Catalog</a>.</p>
            <?php else: ?>
                <form method="POST" action="product-test-plan.php">
                    <!-- Shared CSRF protection applies to this plan update. -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="product_type_id" value="<?php echo $selectedTypeId; ?>">
                    <div class="test-plan-list">
                        <?php foreach ($testTypes as $testType): ?>
                            <?php $testTypeId = (int) $testType['id']; ?>
                            <article class="test-plan-row">
                                <label class="test-plan-test">
                                    <input type="checkbox" name="test_type_ids[]" value="<?php echo $testTypeId; ?>" <?php echo array_key_exists($testTypeId, $existingMap) ? 'checked' : ''; ?>>
                                    <span><strong><?php echo htmlspecialchars($testType['test_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($testType['test_code'] . ' / ID ' . ($testType['numeric_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($testType['department'] ?? 'Unassigned department', ENT_QUOTES, 'UTF-8'); ?></small></span>
                                </label>
                                <label class="test-plan-required"><input type="checkbox" name="required_test_type_ids[]" value="<?php echo $testTypeId; ?>" <?php echo (int) ($existingMap[$testTypeId] ?? 0) === 1 ? 'checked' : ''; ?>> Required for CPRI</label>
                            </article>
                        <?php endforeach; ?>
                        <?php if ($testTypes === []): ?><p>No active test types. Add them in <a href="test-types.php">Test Types</a> first.</p><?php endif; ?>
                    </div>
                    <p class="test-plan-help">Selected tests are saved in the displayed order. Clear all checks only if this family should have no active test plan.</p>
                    <button type="submit">Save test plan</button>
                </form>
            <?php endif; ?>
        </section>
        <aside class="test-plan-note"><strong>Workflow:</strong> a FAIL sends the product to re-manufacture and starts a new test cycle after release. CPRI handoff is manual; this app does not call an external API.</aside>
    </div>
<?php require __DIR__ . '/../layouts/app_end.php'; ?>
