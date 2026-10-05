<?php
// Reserve a concurrency-safe five-digit roll and build a unique twelve-digit Test ID.

declare(strict_types=1);

final class TestIdGenerator
{
    /**
     * Reserve the next roll for one product/test type and return prefix(4)+test-code(3)+roll(5).
     *
     * Existing IDs in the approved layout are observed before allocation. Older IDs that
     * happen to collide are skipped safely while the sequence row remains locked.
     */
    public static function reserve(mysqli $connection, string $productId, int $testTypeId, string $numericTestCode): string
    {
        if (!preg_match('/^\d{10}$/', $productId)) {
            throw new InvalidArgumentException('Product ID must be exactly ten digits before a test can be assigned.');
        }

        if ($testTypeId < 1) {
            throw new InvalidArgumentException('Choose a valid test type.');
        }

        if (!preg_match('/^\d{3}$/', $numericTestCode)) {
            throw new InvalidArgumentException('The registered test-code number must be exactly three digits.');
        }

        $connection->begin_transaction();

        try {
            // Create the sequence row once, then lock it so simultaneous testers get different rolls.
            $insert = $connection->prepare(
                'INSERT IGNORE INTO test_roll_sequences (product_id, test_type_id, last_roll) VALUES (?, ?, 0)'
            );
            $insert->bind_param('si', $productId, $testTypeId);
            $insert->execute();
            $insert->close();

            $select = $connection->prepare(
                'SELECT last_roll FROM test_roll_sequences WHERE product_id = ? AND test_type_id = ? FOR UPDATE'
            );
            $select->bind_param('si', $productId, $testTypeId);
            $select->execute();
            $row = $select->get_result()->fetch_assoc();
            $select->close();

            if (!$row) {
                throw new RuntimeException('Could not reserve the next test roll number.');
            }

            // Backfill from any existing tests that already follow the selected ID layout.
            $productPrefix = substr($productId, 0, 4);
            $legacyMaximum = $connection->prepare(
                "SELECT MAX(CAST(SUBSTRING(test_id, 8, 5) AS UNSIGNED)) AS max_roll " .
                "FROM tests WHERE product_id = ? AND test_type_id = ? " .
                "AND test_id REGEXP '^[0-9]{12}$' " .
                "AND SUBSTRING(test_id, 1, 4) = ? AND SUBSTRING(test_id, 5, 3) = ?"
            );
            $legacyMaximum->bind_param('siss', $productId, $testTypeId, $productPrefix, $numericTestCode);
            $legacyMaximum->execute();
            $maximumRow = $legacyMaximum->get_result()->fetch_assoc();
            $legacyMaximum->close();

            $roll = max((int) $row['last_roll'], (int) ($maximumRow['max_roll'] ?? 0));
            $candidate = '';

            do {
                if ($roll >= 99999) {
                    throw new OverflowException('The five-digit test-roll sequence is full for this product and test type.');
                }

                $roll++;
                $candidate = $productPrefix . $numericTestCode . str_pad((string) $roll, 5, '0', STR_PAD_LEFT);

                // Also avoid collisions with legacy IDs created under the old concatenation rules.
                $collision = $connection->prepare('SELECT 1 FROM tests WHERE test_id = ? LIMIT 1');
                $collision->bind_param('s', $candidate);
                $collision->execute();
                $alreadyUsed = $collision->get_result()->num_rows > 0;
                $collision->close();
            } while ($alreadyUsed);

            $update = $connection->prepare(
                'UPDATE test_roll_sequences SET last_roll = ? WHERE product_id = ? AND test_type_id = ?'
            );
            $update->bind_param('isi', $roll, $productId, $testTypeId);
            $update->execute();
            $update->close();
            $connection->commit();

            if (!preg_match('/^\d{12}$/', $candidate)) {
                throw new LogicException('Generated Test ID did not meet the twelve-digit requirement.');
            }

            return $candidate;
        } catch (Throwable $exception) {
            // Rollback is harmless if the transaction already committed before a final assertion failed.
            $connection->rollback();
            throw $exception;
        }
    }
}
