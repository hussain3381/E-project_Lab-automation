<?php
// Resolve the registered numeric identifier for an exact product code/model.

declare(strict_types=1);

final class ProductCode
{
    /**
     * Find an active product-code mapping and its product-family name.
     */
    public static function findActiveByCode(mysqli $connection, string $productCode): ?array
    {
        $statement = $connection->prepare(
            'SELECT pc.id, pc.product_type_id, pc.product_code, pc.numeric_code, pt.type_name ' .
            'FROM product_codes AS pc ' .
            'INNER JOIN product_types AS pt ON pt.id = pc.product_type_id ' .
            'WHERE pc.product_code = ? AND pc.is_active = 1 AND pt.is_active = 1 LIMIT 1'
        );
        $statement->bind_param('s', $productCode);
        $statement->execute();
        $record = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();

        return $record;
    }
}
