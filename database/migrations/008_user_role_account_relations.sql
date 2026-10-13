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

-- Review these unambiguous email candidates; the account UI links a Tester profile transactionally.
SELECT 'Unambiguous email/profile link candidate' AS `check_name`,
       u.`id` AS `user_id`, u.`username`, u.`email`, t.`id` AS `tester_profile_id`
FROM `users` AS u
INNER JOIN `testers` AS t ON LOWER(TRIM(t.`email`)) = LOWER(TRIM(u.`email`))
WHERE u.`email` IS NOT NULL AND TRIM(u.`email`) <> '' AND t.`user_id` IS NULL
  AND (SELECT COUNT(*) FROM `users` AS u2 WHERE LOWER(TRIM(u2.`email`)) = LOWER(TRIM(u.`email`))) = 1
  AND (SELECT COUNT(*) FROM `testers` AS t2 WHERE LOWER(TRIM(t2.`email`)) = LOWER(TRIM(t.`email`))) = 1
  AND NOT EXISTS (SELECT 1 FROM `testers` AS linked WHERE linked.`user_id` = u.`id`);

SELECT 'Orphan tester account links' AS `check_name`, t.`user_id`, COUNT(*) AS `rows_found`
FROM `testers` AS t
LEFT JOIN `users` AS u ON u.`id` = t.`user_id`
WHERE t.`user_id` IS NOT NULL AND u.`id` IS NULL
GROUP BY t.`user_id`;
