<!doctype html>
<html lang="en">
<head>
    <script>/* Apply the saved palette before the browser paints the page. */try{document.documentElement.dataset.theme=localStorage.getItem("lab-theme")||"dark";}catch(e){document.documentElement.dataset.theme="dark";}</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Catalog | Lab Automation</title>
    <link rel="stylesheet" href="assets/compiled/app.css">
    <script type="module" src="assets/compiled/app.js"></script>
    <link rel="stylesheet" href="assets/css/pages/product-catalog.css">
</head>
<body class="catalog-page">
    <main class="catalog-shell">
        <header class="catalog-header">
            <div>
                <p class="catalog-eyebrow">ADMIN / CONFIGURATION</p>
                <h1>Product Catalog</h1>
                <p class="catalog-lede">Set up product families and map each exact product code to its two-digit ID segment.</p>
            </div>
            <a class="catalog-link" href="settings.php">Back to settings</a>
        </header>

        <?php if ($message !== ''): ?>
            <div class="catalog-alert catalog-alert--success" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="catalog-alert catalog-alert--error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="catalog-grid" aria-label="Product catalog setup">
            <article class="catalog-card">
                <h2>Add product family</h2>
                <p class="catalog-muted">Examples from the SRS: Switch Gear, Fuse, Capacitor, Resistor.</p>
                <form method="POST" action="product-catalog.php">
                    <!-- The shared CSRF guard validates this session-bound token. -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="create_type">
                    <label for="type-code">Family code</label>
                    <input id="type-code" name="type_code" maxlength="16" placeholder="e.g. SWG" required>
                    <label for="type-name">Family name</label>
                    <input id="type-name" name="type_name" maxlength="120" placeholder="e.g. Switch Gear" required>
                    <label for="type-description">Description</label>
                    <textarea id="type-description" name="type_description" rows="3" placeholder="Optional family notes"></textarea>
                    <button type="submit">Add family</button>
                </form>
                <div class="catalog-table-wrap">
                    <table>
                        <thead><tr><th>Code</th><th>Product family</th></tr></thead>
                        <tbody>
                        <?php foreach ($productTypes as $type): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($type['type_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($type['type_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($productTypes === []): ?>
                            <tr><td colspan="2">No active product families yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="catalog-card">
                <h2>Map product code / model</h2>
                <p class="catalog-muted">Each exact product code gets one stable two-digit numeric segment used in Product ID.</p>
                <form method="POST" action="product-catalog.php">
                    <!-- The shared CSRF guard validates this session-bound token. -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="create_code">
                    <label for="product-type-id">Product family</label>
                    <select id="product-type-id" name="product_type_id" required>
                        <option value="">Select family</option>
                        <?php foreach ($productTypes as $type): ?>
                            <option value="<?php echo (int) $type['id']; ?>"><?php echo htmlspecialchars($type['type_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="product-code">Exact product code / model</label>
                    <input id="product-code" name="product_code" maxlength="32" placeholder="e.g. SWG01" required>
                    <label for="numeric-code">Two-digit ID code</label>
                    <input id="numeric-code" name="numeric_code" inputmode="numeric" pattern="[0-9]{2}" maxlength="2" placeholder="e.g. 01" required>
                    <label for="code-description">Description</label>
                    <textarea id="code-description" name="code_description" rows="3" placeholder="Optional model details"></textarea>
                    <button type="submit">Save product-code mapping</button>
                </form>
                <div class="catalog-table-wrap">
                    <table>
                        <thead><tr><th>Family</th><th>Product code</th><th>ID code</th><th>State</th></tr></thead>
                        <tbody>
                        <?php foreach ($productCodes as $code): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($code['type_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($code['product_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($code['numeric_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int) $code['is_active'] === 1 ? 'Active' : 'Inactive'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($productCodes === []): ?>
                            <tr><td colspan="4">No product-code mappings yet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <aside class="catalog-note">
            <strong>ID rule chosen for this sprint:</strong> Product ID = product-code numeric segment (2 digits) + revision (2 digits) + manufacturing sequence (6 digits). The numeric code maps to the exact product code/model, not just the family.
        </aside>
    </main>
</body>
</html>
