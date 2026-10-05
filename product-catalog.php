<?php
// Keep catalogue management behind the login, role, and shared CSRF checks.

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_roles(['Administrator']);
require_once __DIR__ . '/controllers/ProductCatalogController.php';

$catalogState = product_catalog_handle_request($conn);
$productTypes = ProductCatalog::activeTypes($conn);
$productCodes = ProductCatalog::allCodes($conn);
$message = $catalogState['message'];
$error = $catalogState['error'];

require __DIR__ . '/views/product-catalog/index.php';
