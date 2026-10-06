<?php
// Validate catalogue form requests and coordinate their model calls.

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/ProductCatalog.php';

/**
 * Handle one product-family or exact product-code catalogue form submission.
 */
function product_catalog_handle_request(mysqli $connection): array
{
    $state = ['message' => '', 'error' => ''];
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return $state;
    }

    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'create_type') {
            $typeCode = strtoupper(trim((string) ($_POST['type_code'] ?? '')));
            $typeName = trim((string) ($_POST['type_name'] ?? ''));
            $description = trim((string) ($_POST['type_description'] ?? ''));

            if (!preg_match('/^[A-Z0-9_-]{2,16}$/', $typeCode) || $typeName === '' || strlen($typeName) > 120) {
                throw new InvalidArgumentException('Enter a type code (2–16 letters/numbers) and a type name.');
            }

            ProductCatalog::createType($connection, $typeCode, $typeName, $description);
            $state['message'] = 'Product family added.';
        } elseif ($action === 'create_code') {
            $typeId = (int) ($_POST['product_type_id'] ?? 0);
            $productCode = strtoupper(trim((string) ($_POST['product_code'] ?? '')));
            $numericCode = trim((string) ($_POST['numeric_code'] ?? ''));
            $description = trim((string) ($_POST['code_description'] ?? ''));

            if ($typeId < 1 || $productCode === '' || strlen($productCode) > 32 || !preg_match('/^\d{2}$/', $numericCode)) {
                throw new InvalidArgumentException('Choose a product family, enter the exact product code, and assign a unique two-digit ID code.');
            }

            if (ProductCatalog::findActiveType($connection, $typeId) === null) {
                throw new InvalidArgumentException('The selected product family is not active.');
            }

            $updatedProducts = ProductCatalog::createCode($connection, $typeId, $productCode, $numericCode, $description);
            $state['message'] = 'Product code/model mapping added. Existing products linked: ' . $updatedProducts . '.';
        } else {
            throw new InvalidArgumentException('Choose a valid catalogue action.');
        }
    } catch (InvalidArgumentException $exception) {
        $state['error'] = $exception->getMessage();
    } catch (mysqli_sql_exception $exception) {
        error_log('Lab Automation product-catalog write failed: ' . $exception->getMessage());
        $state['error'] = 'That type code, product code, or numeric ID code is already in use.';
    }

    return $state;
}
