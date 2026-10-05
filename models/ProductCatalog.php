<?php
// Keep product-family and exact model-code catalogue queries outside the page templates.

declare(strict_types=1);

final class ProductCatalog
{
    /**
     * List active product families for registration forms.
     */
    public static function activeTypes(mysqli $connection): array
    {
        $result = $connection->query(
            'SELECT id, type_code, type_name FROM product_types WHERE is_active = 1 ORDER BY type_name'
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Find an active product family by its internal database key.
     */
    public static function findActiveType(mysqli $connection, int $typeId): ?array
    {
        $statement = $connection->prepare(
            'SELECT id, type_code, type_name FROM product_types WHERE id = ? AND is_active = 1 LIMIT 1'
        );
        $statement->bind_param('i', $typeId);
        $statement->execute();
        $record = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $record;
    }

    /**
     * List product codes together with their family for the admin catalogue screen.
     */
    public static function allCodes(mysqli $connection): array
    {
        $result = $connection->query(
            'SELECT pc.id, pc.product_code, pc.numeric_code, pc.description, pc.is_active, ' .
            'pt.type_name FROM product_codes AS pc ' .
            'INNER JOIN product_types AS pt ON pt.id = pc.product_type_id ' .
            'ORDER BY pc.product_code'
        );

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Create one new, unique product family.
     */
    public static function createType(mysqli $connection, string $typeCode, string $typeName, string $description): void
    {
        $statement = $connection->prepare(
            'INSERT INTO product_types (type_code, type_name, description) VALUES (?, ?, ?)'
        );
        $statement->bind_param('sss', $typeCode, $typeName, $description);
        $statement->execute();
        $statement->close();
    }

    /**
     * Create a stable two-digit ID mapping for one exact product code/model.
     */
    public static function createCode(mysqli $connection, int $typeId, string $productCode, string $numericCode, string $description): int
    {
        $connection->begin_transaction();
        try {
            $statement = $connection->prepare(
                'INSERT INTO product_codes (product_type_id, product_code, numeric_code, description) VALUES (?, ?, ?, ?)'
            );
            $statement->bind_param('isss', $typeId, $productCode, $numericCode, $description);
            $statement->execute();
            $codeId = (int) $connection->insert_id;
            $statement->close();

            $family = self::findActiveType($connection, $typeId);
            if ($family === null) {
                throw new DomainException('The selected product family is no longer active.');
            }

            // Link legacy products with this exact code to its new immutable ID mapping.
            $backfill = $connection->prepare(
                'UPDATE products SET product_code_id = ?, product_code_numeric = ?, product_type_id = ?, product_type = ? ' .
                'WHERE product_code = ? AND product_code_id IS NULL'
            );
            $familyName = (string) $family['type_name'];
            $backfill->bind_param('isiss', $codeId, $numericCode, $typeId, $familyName, $productCode);
            $backfill->execute();
            $updatedProducts = $backfill->affected_rows;
            $backfill->close();

            $connection->commit();

            return max(0, $updatedProducts);
        } catch (Throwable $exception) {
            $connection->rollback();
            throw $exception;
        }
    }
}
