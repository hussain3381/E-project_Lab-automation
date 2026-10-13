-- Safe, idempotent schema upgrade for an EXISTING project_lab_db.
-- It does NOT drop tables/columns, delete rows, or seed demo data.
-- Back up the database first, then run this once in phpMyAdmin > SQL (or Import).
-- Target: MySQL/MariaDB. If duplicate linked accounts exist, review the diagnostic SELECTs at the end.

CREATE DATABASE IF NOT EXISTS `project_lab_db`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `project_lab_db`;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `migration` VARCHAR(190) NOT NULL,
    `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Per-account workspace preferences are additive and do not change user roles or access.
CREATE TABLE IF NOT EXISTS `user_preferences` (
    `user_id` INT NOT NULL,
    `theme` VARCHAR(8) NOT NULL DEFAULT 'dark',
    `compact_dashboard` TINYINT(1) NOT NULL DEFAULT 0,
    `reduced_motion` TINYINT(1) NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_user_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compatibility columns used by current account, archive, department, and workflow code.
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'), 'SELECT 1', 'ALTER TABLE `users` ADD COLUMN `email` VARCHAR(190) NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'phone'), 'SELECT 1', 'ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(40) NULL AFTER `email`');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_active'), 'SELECT 1', 'ALTER TABLE `users` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'google_sub'), 'SELECT 1', 'ALTER TABLE `users` ADD COLUMN `google_sub` VARCHAR(191) NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;

SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testers' AND COLUMN_NAME = 'user_id'), 'SELECT 1', 'ALTER TABLE `testers` ADD COLUMN `user_id` INT NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testers' AND COLUMN_NAME = 'email'), 'SELECT 1', 'ALTER TABLE `testers` ADD COLUMN `email` VARCHAR(190) NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testers' AND COLUMN_NAME = 'phone'), 'SELECT 1', 'ALTER TABLE `testers` ADD COLUMN `phone` VARCHAR(40) NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
UPDATE `users` AS account INNER JOIN `testers` AS tester ON tester.user_id = account.id
SET account.phone = tester.phone
WHERE (account.phone IS NULL OR account.phone = '') AND tester.phone IS NOT NULL AND tester.phone <> '';
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testers' AND COLUMN_NAME = 'is_active'), 'SELECT 1', 'ALTER TABLE `testers` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;

SET @lab_email_length = (SELECT COALESCE(MAX(CHARACTER_MAXIMUM_LENGTH), 0) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testers' AND COLUMN_NAME = 'email');
SET @lab_ddl = IF(@lab_email_length > 0 AND @lab_email_length < 190, 'ALTER TABLE `testers` MODIFY COLUMN `email` VARCHAR(190) NULL', 'SELECT 1');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;

SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'product_type_id'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `product_type_id` INT NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'product_code_id'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `product_code_id` INT NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'product_code_numeric'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `product_code_numeric` CHAR(2) NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'rework_cycle'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `rework_cycle` INT NOT NULL DEFAULT 0');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'cpri_status'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `cpri_status` VARCHAR(24) NOT NULL DEFAULT ''Not Ready''');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'cpri_handoff_at'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `cpri_handoff_at` DATETIME NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'cpri_reference'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `cpri_reference` VARCHAR(100) NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'cpri_handoff_by'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `cpri_handoff_by` VARCHAR(120) NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'is_archived'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `is_archived` TINYINT(1) NOT NULL DEFAULT 0');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;

SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'test_types' AND COLUMN_NAME = 'numeric_code'), 'SELECT 1', 'ALTER TABLE `test_types` ADD COLUMN `numeric_code` CHAR(3) NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'test_types' AND COLUMN_NAME = 'department_id'), 'SELECT 1', 'ALTER TABLE `test_types` ADD COLUMN `department_id` INT NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'test_types' AND COLUMN_NAME = 'is_active'), 'SELECT 1', 'ALTER TABLE `test_types` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tests' AND COLUMN_NAME = 'cycle_number'), 'SELECT 1', 'ALTER TABLE `tests` ADD COLUMN `cycle_number` INT NOT NULL DEFAULT 1');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tests' AND COLUMN_NAME = 'department_id'), 'SELECT 1', 'ALTER TABLE `tests` ADD COLUMN `department_id` INT NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;

-- Workflow tables and additive CPRI result/release fields. Existing handoff rows and test history are preserved.
CREATE TABLE IF NOT EXISTS `cpri_handoffs` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `product_record_id` INT NOT NULL,
    `product_id` CHAR(10) NOT NULL,
    `test_cycle` INT NOT NULL DEFAULT 1,
    `reference` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `handed_off_by` VARCHAR(120) NOT NULL,
    `handed_off_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `cpri_result` VARCHAR(16) NOT NULL DEFAULT 'PENDING',
    `cpri_result_notes` TEXT NULL,
    `cpri_result_by` VARCHAR(120) NULL,
    `cpri_result_at` DATETIME NULL,
    `market_released_by` VARCHAR(120) NULL,
    `market_released_at` DATETIME NULL,
    `market_release_notes` TEXT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cpri_handoffs_product_cycle` (`product_id`, `test_cycle`),
    KEY `ix_cpri_handoffs_date` (`handed_off_at`),
    CONSTRAINT `fk_cpri_handoffs_product_record` FOREIGN KEY (`product_record_id`) REFERENCES `products` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_workflow_events` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `product_record_id` INT NOT NULL,
    `product_id` CHAR(10) NOT NULL,
    `event_type` VARCHAR(40) NOT NULL,
    `old_status` VARCHAR(64) NULL,
    `new_status` VARCHAR(64) NOT NULL,
    `notes` TEXT NULL,
    `changed_by` VARCHAR(120) NOT NULL,
    `changed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_product_workflow_events_product` (`product_id`, `changed_at`),
    CONSTRAINT `fk_product_workflow_events_record` FOREIGN KEY (`product_record_id`) REFERENCES `products` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'cpri_result'), 'SELECT 1', 'ALTER TABLE `cpri_handoffs` ADD COLUMN `cpri_result` VARCHAR(16) NOT NULL DEFAULT ''PENDING''');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'cpri_result_notes'), 'SELECT 1', 'ALTER TABLE `cpri_handoffs` ADD COLUMN `cpri_result_notes` TEXT NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'cpri_result_by'), 'SELECT 1', 'ALTER TABLE `cpri_handoffs` ADD COLUMN `cpri_result_by` VARCHAR(120) NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'cpri_result_at'), 'SELECT 1', 'ALTER TABLE `cpri_handoffs` ADD COLUMN `cpri_result_at` DATETIME NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'market_released_by'), 'SELECT 1', 'ALTER TABLE `cpri_handoffs` ADD COLUMN `market_released_by` VARCHAR(120) NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'market_released_at'), 'SELECT 1', 'ALTER TABLE `cpri_handoffs` ADD COLUMN `market_released_at` DATETIME NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;
SET @lab_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'market_release_notes'), 'SELECT 1', 'ALTER TABLE `cpri_handoffs` ADD COLUMN `market_release_notes` TEXT NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;

-- Mark migration 009 as present only after all of its additive CPRI fields have been ensured.
INSERT IGNORE INTO `schema_migrations` (`migration`) VALUES ('009_cpri_result_and_market_release.sql');
INSERT IGNORE INTO `schema_migrations` (`migration`) VALUES ('010_user_profiles_and_preferences.sql');

-- Audit table required by Administrator activity and record-correction screens.
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_user_id` INT NULL,
    `actor_name` VARCHAR(120) NOT NULL DEFAULT 'System',
    `actor_role` VARCHAR(50) NOT NULL DEFAULT 'System',
    `action` VARCHAR(80) NOT NULL,
    `entity` VARCHAR(80) NOT NULL,
    `entity_id` VARCHAR(100) NULL,
    `summary` VARCHAR(500) NOT NULL,
    `metadata` LONGTEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_audit_logs_created_at` (`created_at`),
    KEY `ix_audit_logs_actor` (`actor_user_id`, `created_at`),
    KEY `ix_audit_logs_entity` (`entity`, `entity_id`, `created_at`),
    CONSTRAINT `fk_audit_logs_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add the Google-subject unique index only when the existing values are unambiguous.
SET @lab_has_google_index = EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'uq_users_google_sub');
SET @lab_google_duplicates = (SELECT COUNT(*) FROM (SELECT `google_sub` FROM `users` WHERE `google_sub` IS NOT NULL GROUP BY `google_sub` HAVING COUNT(*) > 1) AS duplicate_google_subs);
SET @lab_ddl = IF(@lab_has_google_index OR @lab_google_duplicates > 0, 'SELECT 1', 'ALTER TABLE `users` ADD UNIQUE KEY `uq_users_google_sub` (`google_sub`)');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;

-- Link uniqueness and FK are added only when legacy rows are safe to constrain.
SET @lab_has_tester_index = EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testers' AND INDEX_NAME = 'uq_testers_user_id');
SET @lab_tester_duplicates = (SELECT COUNT(*) FROM (SELECT `user_id` FROM `testers` WHERE `user_id` IS NOT NULL GROUP BY `user_id` HAVING COUNT(*) > 1) AS duplicate_tester_links);
SET @lab_ddl = IF(@lab_has_tester_index OR @lab_tester_duplicates > 0, 'SELECT 1', 'ALTER TABLE `testers` ADD UNIQUE KEY `uq_testers_user_id` (`user_id`)');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;

SET @lab_has_tester_fk = EXISTS(SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'testers' AND COLUMN_NAME = 'user_id' AND REFERENCED_TABLE_NAME = 'users');
SET @lab_tester_orphans = (SELECT COUNT(*) FROM `testers` AS t LEFT JOIN `users` AS u ON u.`id` = t.`user_id` WHERE t.`user_id` IS NOT NULL AND u.`id` IS NULL);
SET @lab_ddl = IF(@lab_has_tester_fk OR @lab_tester_orphans > 0, 'SELECT 1', 'ALTER TABLE `testers` ADD CONSTRAINT `fk_testers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL');
PREPARE lab_stmt FROM @lab_ddl; EXECUTE lab_stmt; DEALLOCATE PREPARE lab_stmt;

-- Mark schema migration 007 as present because its audit table has been created above.
INSERT IGNORE INTO `schema_migrations` (`migration`) VALUES ('007_admin_audit_google_archive.sql');

-- Verify the columns used by the current product page and list any profile conflicts.
SELECT `TABLE_NAME`, `COLUMN_NAME`, `COLUMN_TYPE`
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND ((`TABLE_NAME` = 'products' AND `COLUMN_NAME` = 'is_archived')
    OR (`TABLE_NAME` = 'users' AND `COLUMN_NAME` IN ('google_sub', 'phone'))
    OR (`TABLE_NAME` = 'user_preferences' AND `COLUMN_NAME` IN ('theme', 'compact_dashboard', 'reduced_motion'))
    OR (`TABLE_NAME` = 'testers' AND `COLUMN_NAME` IN ('user_id', 'email', 'is_active'))
    OR (`TABLE_NAME` = 'cpri_handoffs' AND `COLUMN_NAME` IN ('cpri_result', 'cpri_result_notes', 'cpri_result_by', 'cpri_result_at', 'market_released_by', 'market_released_at', 'market_release_notes')))
ORDER BY `TABLE_NAME`, `COLUMN_NAME`;

SELECT 'Duplicate tester account links (must be empty before relying on unique link constraint)' AS `check_name`, `user_id`, COUNT(*) AS `rows_found`
FROM `testers` WHERE `user_id` IS NOT NULL GROUP BY `user_id` HAVING COUNT(*) > 1;

SELECT 'Orphan tester account links (must be empty before relying on FK)' AS `check_name`, t.`user_id`, COUNT(*) AS `rows_found`
FROM `testers` AS t LEFT JOIN `users` AS u ON u.`id` = t.`user_id`
WHERE t.`user_id` IS NOT NULL AND u.`id` IS NULL GROUP BY t.`user_id`;

SELECT 'Duplicate Google subject IDs (must be empty before Google linking)' AS `check_name`, `google_sub`, COUNT(*) AS `rows_found`
FROM `users` WHERE `google_sub` IS NOT NULL GROUP BY `google_sub` HAVING COUNT(*) > 1;


-- Add normalized user-role and account/profile relations. This block is duplicated from migration 008 for phpMyAdmin use.
-- Normalize role and account/profile links without deleting or replacing existing records.
-- Existing role strings remain for PHP/session compatibility; role_id is the enforced relation.

CREATE TABLE IF NOT EXISTS `roles` (
    `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_name` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add missing built-in roles only; preserve descriptions/customizations on existing rows.
INSERT INTO `roles` (`role_name`, `description`, `is_system`)
SELECT 'Administrator', 'Full system access, including users, roles, catalogues, test plans, and workflow controls.', 1
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'Administrator');
INSERT INTO `roles` (`role_name`, `description`, `is_system`)
SELECT 'Lab Manager', 'Register products, assign pending tests to testers, and monitor laboratory progress.', 1
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'Lab Manager');
INSERT INTO `roles` (`role_name`, `description`, `is_system`)
SELECT 'Tester', 'Record and review assigned laboratory tests and product history.', 1
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'Tester');
INSERT INTO `roles` (`role_name`, `description`, `is_system`)
SELECT 'Quality Control', 'Review quality outcomes, assign pending tests, confirm rework, record CPRI results, and record market release after approval.', 1
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'Quality Control');

-- Refresh only the unchanged built-in QC description; preserve any administrator customization.
UPDATE `roles`
SET `description` = 'Review quality outcomes, assign pending tests, confirm rework, record CPRI results, and record market release after approval.'
WHERE `role_name` = 'Quality Control'
  AND `description` = 'Review quality outcomes, confirm rework, record CPRI results, and record market release after approval.';

-- Compatibility columns for older project_lab_db installs.
SET @lab_rel_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'),
    'SELECT 1',
    'ALTER TABLE `users` ADD COLUMN `email` VARCHAR(190) NULL'
);
PREPARE lab_rel_stmt FROM @lab_rel_ddl; EXECUTE lab_rel_stmt; DEALLOCATE PREPARE lab_rel_stmt;
SET @lab_rel_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role_id'),
    'SELECT 1',
    'ALTER TABLE `users` ADD COLUMN `role_id` SMALLINT UNSIGNED NULL AFTER `role`'
);
PREPARE lab_rel_stmt FROM @lab_rel_ddl; EXECUTE lab_rel_stmt; DEALLOCATE PREPARE lab_rel_stmt;
SET @lab_rel_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testers' AND COLUMN_NAME = 'user_id'),
    'SELECT 1',
    'ALTER TABLE `testers` ADD COLUMN `user_id` INT NULL'
);
PREPARE lab_rel_stmt FROM @lab_rel_ddl; EXECUTE lab_rel_stmt; DEALLOCATE PREPARE lab_rel_stmt;
SET @lab_rel_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testers' AND COLUMN_NAME = 'email'),
    'SELECT 1',
    'ALTER TABLE `testers` ADD COLUMN `email` VARCHAR(190) NULL'
);
PREPARE lab_rel_stmt FROM @lab_rel_ddl; EXECUTE lab_rel_stmt; DEALLOCATE PREPARE lab_rel_stmt;

-- Expand existing email columns without truncating their current values.
SET @lab_rel_email_length = (
    SELECT COALESCE(MAX(`CHARACTER_MAXIMUM_LENGTH`), 0)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'
);
SET @lab_rel_ddl = IF(
    @lab_rel_email_length > 0 AND @lab_rel_email_length < 190,
    'ALTER TABLE `users` MODIFY COLUMN `email` VARCHAR(190) NULL',
    'SELECT 1'
);
PREPARE lab_rel_stmt FROM @lab_rel_ddl; EXECUTE lab_rel_stmt; DEALLOCATE PREPARE lab_rel_stmt;
SET @lab_rel_email_length = (
    SELECT COALESCE(MAX(`CHARACTER_MAXIMUM_LENGTH`), 0)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testers' AND COLUMN_NAME = 'email'
);
SET @lab_rel_ddl = IF(
    @lab_rel_email_length > 0 AND @lab_rel_email_length < 190,
    'ALTER TABLE `testers` MODIFY COLUMN `email` VARCHAR(190) NULL',
    'SELECT 1'
);
PREPARE lab_rel_stmt FROM @lab_rel_ddl; EXECUTE lab_rel_stmt; DEALLOCATE PREPARE lab_rel_stmt;

-- Backfill the normalized relation where the legacy role text has exactly one matching role.
UPDATE `users` AS u
INNER JOIN (
    SELECT `role_name`, MIN(`id`) AS `role_id`
    FROM `roles`
    GROUP BY `role_name`
    HAVING COUNT(*) = 1
) AS r ON r.`role_name` = u.`role`
SET u.`role_id` = r.`role_id`
WHERE u.`role_id` IS NULL;

-- Index role lookups and add the role FK only when existing references are valid.
SET @lab_rel_has_role_index = EXISTS(
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'role_id' AND SEQ_IN_INDEX = 1
);
SET @lab_rel_ddl = IF(
    @lab_rel_has_role_index,
    'SELECT 1',
    'ALTER TABLE `users` ADD KEY `ix_users_role_id` (`role_id`)'
);
PREPARE lab_rel_stmt FROM @lab_rel_ddl; EXECUTE lab_rel_stmt; DEALLOCATE PREPARE lab_rel_stmt;
SET @lab_rel_has_role_fk = EXISTS(
    SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'role_id' AND REFERENCED_TABLE_NAME = 'roles'
);
SET @lab_rel_role_orphans = (
    SELECT COUNT(*) FROM `users` AS u
    LEFT JOIN `roles` AS r ON r.`id` = u.`role_id`
    WHERE u.`role_id` IS NOT NULL AND r.`id` IS NULL
);
SET @lab_rel_ddl = IF(
    @lab_rel_has_role_fk OR @lab_rel_role_orphans > 0,
    'SELECT 1',
    'ALTER TABLE `users` ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT'
);
PREPARE lab_rel_stmt FROM @lab_rel_ddl; EXECUTE lab_rel_stmt; DEALLOCATE PREPARE lab_rel_stmt;

-- Enforce one non-empty login email per account only when legacy rows are unambiguous.
-- Blank values are left untouched; duplicate addresses are reported below instead of deleted/rewritten.
SET @lab_rel_email_duplicates = (
    SELECT COUNT(*) FROM (
        SELECT `email` FROM `users`
        WHERE `email` IS NOT NULL
        GROUP BY `email`
        HAVING COUNT(*) > 1
    ) AS duplicate_account_emails
);
SET @lab_rel_has_email_unique = EXISTS(
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'email' AND SEQ_IN_INDEX = 1 AND NON_UNIQUE = 0
);
SET @lab_rel_ddl = IF(
    @lab_rel_has_email_unique OR @lab_rel_email_duplicates > 0,
    'SELECT 1',
    'ALTER TABLE `users` ADD UNIQUE KEY `uq_users_email` (`email`)'
);
PREPARE lab_rel_stmt FROM @lab_rel_ddl; EXECUTE lab_rel_stmt; DEALLOCATE PREPARE lab_rel_stmt;

-- Keep the tester profile/account relationship one-to-one where current data permits it.
SET @lab_rel_tester_duplicates = (
    SELECT COUNT(*) FROM (
        SELECT `user_id` FROM `testers`
        WHERE `user_id` IS NOT NULL
        GROUP BY `user_id`
        HAVING COUNT(*) > 1
    ) AS duplicate_tester_links
);
SET @lab_rel_has_tester_unique = EXISTS(
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'testers'
      AND COLUMN_NAME = 'user_id' AND SEQ_IN_INDEX = 1 AND NON_UNIQUE = 0
);
SET @lab_rel_ddl = IF(
    @lab_rel_has_tester_unique OR @lab_rel_tester_duplicates > 0,
    'SELECT 1',
    'ALTER TABLE `testers` ADD UNIQUE KEY `uq_testers_user_id` (`user_id`)'
);
PREPARE lab_rel_stmt FROM @lab_rel_ddl; EXECUTE lab_rel_stmt; DEALLOCATE PREPARE lab_rel_stmt;
SET @lab_rel_has_tester_fk = EXISTS(
    SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
    WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'testers'
      AND COLUMN_NAME = 'user_id' AND REFERENCED_TABLE_NAME = 'users'
);
SET @lab_rel_tester_orphans = (
    SELECT COUNT(*) FROM `testers` AS t
    LEFT JOIN `users` AS u ON u.`id` = t.`user_id`
    WHERE t.`user_id` IS NOT NULL AND u.`id` IS NULL
);
SET @lab_rel_ddl = IF(
    @lab_rel_has_tester_fk OR @lab_rel_tester_orphans > 0,
    'SELECT 1',
    'ALTER TABLE `testers` ADD CONSTRAINT `fk_testers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL'
);
PREPARE lab_rel_stmt FROM @lab_rel_ddl; EXECUTE lab_rel_stmt; DEALLOCATE PREPARE lab_rel_stmt;

-- Record success for CLI migration tracking; constraints skipped for conflicts are retryable on the next run.
INSERT IGNORE INTO `schema_migrations` (`migration`) VALUES ('008_user_role_account_relations.sql');

-- Diagnostics: inspect these rows before resolving legacy role/email/profile conflicts.
SELECT 'Users without a matching role row' AS `check_name`, u.`id`, u.`username`, u.`role`, u.`role_id`
FROM `users` AS u
LEFT JOIN `roles` AS r ON r.`id` = u.`role_id`
WHERE u.`role_id` IS NULL OR r.`id` IS NULL;

SELECT 'Duplicate account email' AS `check_name`, `email`, COUNT(*) AS `rows_found`
FROM `users` WHERE `email` IS NOT NULL
GROUP BY `email` HAVING COUNT(*) > 1;

SELECT 'Duplicate tester account links' AS `check_name`, `user_id`, COUNT(*) AS `rows_found`
FROM `testers` WHERE `user_id` IS NOT NULL
GROUP BY `user_id` HAVING COUNT(*) > 1;

SELECT 'Orphan tester account links' AS `check_name`, t.`user_id`, COUNT(*) AS `rows_found`
FROM `testers` AS t
LEFT JOIN `users` AS u ON u.`id` = t.`user_id`
WHERE t.`user_id` IS NOT NULL AND u.`id` IS NULL
GROUP BY t.`user_id`;

-- Review these unambiguous email candidates; the account UI links a Tester profile transactionally.
SELECT 'Unambiguous email/profile link candidate' AS `check_name`,
       u.`id` AS `user_id`, u.`username`, u.`email`, t.`id` AS `tester_profile_id`
FROM `users` AS u
INNER JOIN `testers` AS t ON LOWER(TRIM(t.`email`)) = LOWER(TRIM(u.`email`))
WHERE u.`email` IS NOT NULL AND TRIM(u.`email`) <> '' AND t.`user_id` IS NULL
  AND (SELECT COUNT(*) FROM `users` AS u2 WHERE LOWER(TRIM(u2.`email`)) = LOWER(TRIM(u.`email`))) = 1
  AND (SELECT COUNT(*) FROM `testers` AS t2 WHERE LOWER(TRIM(t2.`email`)) = LOWER(TRIM(t.`email`))) = 1
  AND NOT EXISTS (SELECT 1 FROM `testers` AS linked WHERE linked.`user_id` = u.`id`);
