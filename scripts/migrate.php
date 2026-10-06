<?php
// Apply versioned, idempotent schema changes from the command line only.

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This migration command must be run from the command line.\n");
}

require_once dirname(__DIR__) . '/models/Database.php';

/**
 * Check whether a column exists in the currently selected database.
 */
function migration_column_exists(mysqli $connection, string $table, string $column): bool
{
    $statement = $connection->prepare(
        'SELECT 1 FROM information_schema.COLUMNS ' .
        'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
    );
    $statement->bind_param('ss', $table, $column);
    $statement->execute();
    $exists = $statement->get_result()->num_rows > 0;
    $statement->close();

    return $exists;
}

/**
 * Add a missing, statically defined compatibility column for an older database.
 */
function migration_ensure_column(mysqli $connection, string $table, string $column, string $definition): void
{
    if (!preg_match('/^[a-z_]+$/', $table) || !preg_match('/^[a-z_]+$/', $column)) {
        throw new RuntimeException('Unsafe schema identifier in the migration map.');
    }

    if (!migration_column_exists($connection, $table, $column)) {
        $connection->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        echo "Added compatibility column {$table}.{$column}.\n";
    }
}

/**
 * Make older repository databases compatible without relying on vendor-specific ADD COLUMN syntax.
 */
function migration_ensure_compatibility_columns(mysqli $connection): void
{
    $columns = [
        'users' => [
            'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
        ],
        'testers' => [
            'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
        ],
        'products' => [
            'product_type_id' => 'INT NULL',
            'product_code_id' => 'INT NULL',
            'product_code_numeric' => 'CHAR(2) NULL',
            'rework_cycle' => 'INT NOT NULL DEFAULT 0',
            'cpri_status' => "VARCHAR(24) NOT NULL DEFAULT 'Not Ready'",
            'cpri_handoff_at' => 'DATETIME NULL',
            'cpri_reference' => 'VARCHAR(100) NULL',
            'cpri_handoff_by' => 'VARCHAR(120) NULL',
        ],
        'test_types' => [
            'numeric_code' => 'CHAR(3) NULL',
            'department_id' => 'INT NULL',
            'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
        ],
        'tests' => [
            'cycle_number' => 'INT NOT NULL DEFAULT 1',
            'department_id' => 'INT NULL',
        ],
    ];

    foreach ($columns as $table => $tableColumns) {
        foreach ($tableColumns as $column => $definition) {
            migration_ensure_column($connection, $table, $column, $definition);
        }
    }
}

/**
 * Check whether a named index exists before creating a safe lookup/uniqueness index.
 */
function migration_index_exists(mysqli $connection, string $table, string $indexName): bool
{
    $statement = $connection->prepare(
        'SELECT 1 FROM information_schema.STATISTICS ' .
        'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1'
    );
    $statement->bind_param('ss', $table, $indexName);
    $statement->execute();
    $exists = $statement->get_result()->num_rows > 0;
    $statement->close();

    return $exists;
}

/**
 * Create a named foreign key only when the current database does not already have it.
 */
function migration_ensure_foreign_key(mysqli $connection, string $constraintName, string $alterSql): void
{
    $statement = $connection->prepare(
        'SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS ' .
        'WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ? LIMIT 1'
    );
    $statement->bind_param('s', $constraintName);
    $statement->execute();
    $exists = $statement->get_result()->num_rows > 0;
    $statement->close();

    if (!$exists) {
        $connection->query($alterSql);
        echo "Added foreign key {$constraintName}.\n";
    }
}

/**
 * Add unique identity indexes only when legacy data contains no duplicates.
 */
function migration_ensure_unique_identity_indexes(mysqli $connection): void
{
    $identityColumns = [
        'products' => ['product_id' => 'uq_products_product_id'],
        'tests' => ['test_id' => 'uq_tests_test_id'],
        'test_types' => ['numeric_code' => 'uq_test_types_numeric_code'],
    ];

    foreach ($identityColumns as $table => $columns) {
        foreach ($columns as $column => $indexName) {
            if (migration_index_exists($connection, $table, $indexName)) {
                continue;
            }

            $duplicate = $connection->query(
                "SELECT `{$column}`, COUNT(*) AS total FROM `{$table}` " .
                "WHERE `{$column}` IS NOT NULL GROUP BY `{$column}` HAVING COUNT(*) > 1 LIMIT 1"
            )->fetch_assoc();

            if ($duplicate !== null) {
                throw new RuntimeException(
                    "Cannot add a unique {$table}.{$column} index; resolve duplicate value " .
                    (string) $duplicate[$column] . ' before continuing.'
                );
            }

            $connection->query(
                "ALTER TABLE `{$table}` ADD UNIQUE KEY `{$indexName}` (`{$column}`)"
            );
            echo "Added unique identity index {$indexName}.\n";
        }
    }
}

try {
    $connection = Database::connection();
    $connection->query(
        'CREATE TABLE IF NOT EXISTS schema_migrations (' .
        'migration VARCHAR(190) NOT NULL PRIMARY KEY, ' .
        'applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP' .
        ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $migrationDirectory = dirname(__DIR__) . '/database/migrations';
    $migrationFiles = glob($migrationDirectory . '/*.sql') ?: [];
    sort($migrationFiles, SORT_STRING);

    if ($migrationFiles === []) {
        throw new RuntimeException('No SQL migration files were found.');
    }

    $check = $connection->prepare('SELECT 1 FROM schema_migrations WHERE migration = ? LIMIT 1');
    $record = $connection->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
    $appliedCount = 0;

    foreach ($migrationFiles as $migrationFile) {
        $migrationName = basename($migrationFile);
        $check->bind_param('s', $migrationName);
        $check->execute();
        $alreadyApplied = $check->get_result()->num_rows > 0;

        if ($alreadyApplied) {
            echo "Skipped {$migrationName} (already applied).\n";
            continue;
        }

        $sql = file_get_contents($migrationFile);
        if ($sql === false) {
            throw new RuntimeException("Could not read migration {$migrationName}.");
        }

        if (!$connection->multi_query($sql)) {
            throw new RuntimeException($connection->error);
        }

        while (true) {
            if ($result = $connection->store_result()) {
                $result->free();
            }
            if (!$connection->more_results()) {
                break;
            }
            if (!$connection->next_result()) {
                throw new RuntimeException($connection->error);
            }
        }

        // Fill gaps left by the original flat-PHP schema using MySQL/MariaDB-neutral checks.
        if ($migrationName === '002_legacy_schema_compat.sql') {
            migration_ensure_compatibility_columns($connection);
        }

        if ($migrationName === '003_product_code_registry_and_test_rolls.sql') {
            migration_ensure_compatibility_columns($connection);
        }

        $record->bind_param('s', $migrationName);
        $record->execute();
        $appliedCount++;
        echo "Applied {$migrationName}.\n";
    }

    // These checks also repair partially upgraded databases without rerunning old SQL files.
    migration_ensure_compatibility_columns($connection);
    migration_ensure_foreign_key(
        $connection,
        'fk_products_product_code',
        'ALTER TABLE products ADD CONSTRAINT fk_products_product_code ' .
        'FOREIGN KEY (product_code_id) REFERENCES product_codes (id) ' .
        'ON UPDATE CASCADE ON DELETE SET NULL'
    );
    migration_ensure_foreign_key(
        $connection,
        'fk_products_product_type',
        'ALTER TABLE products ADD CONSTRAINT fk_products_product_type ' .
        'FOREIGN KEY (product_type_id) REFERENCES product_types (id) ' .
        'ON UPDATE CASCADE ON DELETE SET NULL'
    );
    migration_ensure_foreign_key(
        $connection,
        'fk_tests_department',
        'ALTER TABLE tests ADD CONSTRAINT fk_tests_department ' .
        'FOREIGN KEY (department_id) REFERENCES departments (id) ' .
        'ON UPDATE CASCADE ON DELETE SET NULL'
    );
    migration_ensure_foreign_key(
        $connection,
        'fk_test_types_department',
        'ALTER TABLE test_types ADD CONSTRAINT fk_test_types_department ' .
        'FOREIGN KEY (department_id) REFERENCES departments (id) ' .
        'ON UPDATE CASCADE ON DELETE SET NULL'
    );
    migration_ensure_unique_identity_indexes($connection);

    echo $appliedCount > 0 ? "Migrations complete.\n" : "Database schema is already current.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
