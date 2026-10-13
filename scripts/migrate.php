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
            'email' => 'VARCHAR(190) NULL',
            'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'role_id' => 'SMALLINT UNSIGNED NULL',
            'google_sub' => 'VARCHAR(191) NULL',
        ],
        'testers' => [
            'user_id' => 'INT NULL',
            'email' => 'VARCHAR(190) NULL',
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
            'is_archived' => 'TINYINT(1) NOT NULL DEFAULT 0',
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

/** Widen the linked tester-profile email to match the account email limit without truncating legacy data. */
function migration_ensure_tester_email_capacity(mysqli $connection): void
{
    $result = $connection->query(
        "SELECT CHARACTER_MAXIMUM_LENGTH AS max_length FROM information_schema.COLUMNS " .
        "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testers' AND COLUMN_NAME = 'email' LIMIT 1"
    );
    $row = $result->fetch_assoc();
    $result->free();
    if ($row !== null && (int) $row['max_length'] < 190) {
        $connection->query('ALTER TABLE testers MODIFY COLUMN email VARCHAR(190) NULL');
        echo "Expanded testers.email to 190 characters for synchronized staff accounts.\n";
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
function migration_ensure_unique_user_email_index(mysqli $connection): void
{
    if (migration_index_exists($connection, 'users', 'uq_users_email')) {
        return;
    }

    // Empty legacy placeholders are treated as missing emails, not as duplicate addresses.
    $connection->query("UPDATE users SET email = NULL WHERE email = ''");
    $duplicate = $connection->query(
        'SELECT email, COUNT(*) AS total FROM users WHERE email IS NOT NULL ' .
        'GROUP BY email HAVING COUNT(*) > 1 LIMIT 1'
    )->fetch_assoc();
    if ($duplicate !== null) {
        throw new RuntimeException('Duplicate user email addresses must be corrected before adding unique user emails.');
    }

    $connection->query('ALTER TABLE users ADD UNIQUE KEY uq_users_email (email)');
    echo "Added unique account email index uq_users_email.\n";
}

function migration_ensure_unique_google_sub_index(mysqli $connection): void
{
    if (migration_index_exists($connection, 'users', 'uq_users_google_sub')) {
        return;
    }

    $duplicate = $connection->query(
        'SELECT google_sub, COUNT(*) AS total FROM users WHERE google_sub IS NOT NULL ' .
        'GROUP BY google_sub HAVING COUNT(*) > 1 LIMIT 1'
    )->fetch_assoc();
    if ($duplicate !== null) {
        throw new RuntimeException('Duplicate Google subject IDs must be reviewed before linking OAuth accounts.');
    }

    $connection->query('ALTER TABLE users ADD UNIQUE KEY uq_users_google_sub (google_sub)');
    echo "Added unique Google account link index uq_users_google_sub.\n";
}

function migration_ensure_unique_tester_user_index(mysqli $connection): void
{
    if (migration_index_exists($connection, 'testers', 'uq_testers_user_id')) {
        return;
    }

    $duplicate = $connection->query(
        'SELECT user_id, COUNT(*) AS total FROM testers WHERE user_id IS NOT NULL ' .
        'GROUP BY user_id HAVING COUNT(*) > 1 LIMIT 1'
    )->fetch_assoc();
    if ($duplicate !== null) {
        throw new RuntimeException('A user account is linked to more than one tester profile; fix duplicate testers.user_id values first.');
    }

    $connection->query('ALTER TABLE testers ADD UNIQUE KEY uq_testers_user_id (user_id)');
    echo "Added unique tester-account link index uq_testers_user_id.\n";
}

/** Create and safely backfill the normalized account-to-role relation. */
function migration_ensure_user_role_relation(mysqli $connection): void
{
    $connection->query(
        'CREATE TABLE IF NOT EXISTS roles (' .
        'id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT, ' .
        'role_name VARCHAR(50) NOT NULL, ' .
        'description VARCHAR(255) NOT NULL, ' .
        'is_system TINYINT(1) NOT NULL DEFAULT 1, ' .
        'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, ' .
        'PRIMARY KEY (id), UNIQUE KEY uq_roles_name (role_name)' .
        ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $builtInRoles = [
        ['Administrator', 'Full system access, including users, roles, catalogues, test plans, and workflow controls.'],
        ['Lab Manager', 'Register products, assign pending tests to testers, and monitor laboratory progress.'],
        ['Tester', 'Record and review assigned laboratory tests and product history.'],
        ['Quality Control', 'Review quality outcomes, assign pending tests, confirm rework, record CPRI results, and record market release after approval.'],
    ];
    $insertRole = $connection->prepare(
        'INSERT INTO roles (role_name, description, is_system) ' .
        'SELECT ?, ?, 1 WHERE NOT EXISTS (SELECT 1 FROM roles WHERE role_name = ?)'
    );
    foreach ($builtInRoles as [$roleName, $description]) {
        $insertRole->bind_param('sss', $roleName, $description, $roleName);
        $insertRole->execute();
    }
    $insertRole->close();

    $connection->query(
        'UPDATE users AS u ' .
        'INNER JOIN (SELECT role_name, MIN(id) AS role_id FROM roles GROUP BY role_name HAVING COUNT(*) = 1) AS r ' .
        'ON r.role_name = u.role ' .
        'SET u.role_id = r.role_id WHERE u.role_id IS NULL'
    );

    if (!migration_index_exists($connection, 'users', 'ix_users_role_id')) {
        $connection->query('ALTER TABLE users ADD KEY ix_users_role_id (role_id)');
        echo "Added user-role lookup index ix_users_role_id.\n";
    }

    $orphan = $connection->query(
        'SELECT COUNT(*) AS total FROM users AS u ' .
        'LEFT JOIN roles AS r ON r.id = u.role_id ' .
        'WHERE u.role_id IS NOT NULL AND r.id IS NULL'
    )->fetch_assoc();
    if ((int) ($orphan['total'] ?? 0) === 0) {
        migration_ensure_foreign_key(
            $connection,
            'fk_users_role',
            'ALTER TABLE users ADD CONSTRAINT fk_users_role ' .
            'FOREIGN KEY (role_id) REFERENCES roles (id) ' .
            'ON UPDATE CASCADE ON DELETE RESTRICT'
        );
    } else {
        echo "Skipped fk_users_role: resolve orphan users.role_id values first.\n";
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
    migration_ensure_tester_email_capacity($connection);
    migration_ensure_user_role_relation($connection);
    migration_ensure_unique_user_email_index($connection);
    migration_ensure_unique_google_sub_index($connection);
    migration_ensure_unique_tester_user_index($connection);
    migration_ensure_foreign_key(
        $connection,
        'fk_testers_user',
        'ALTER TABLE testers ADD CONSTRAINT fk_testers_user ' .
        'FOREIGN KEY (user_id) REFERENCES users (id) ' .
        'ON UPDATE CASCADE ON DELETE SET NULL'
    );
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
