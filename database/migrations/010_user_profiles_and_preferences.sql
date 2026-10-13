-- Add self-service contact details and account-synced workspace preferences without replacing users.
-- Existing tester phone numbers are copied only into empty account phone fields.

SET @profile_phone_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'phone'),
    'SELECT 1',
    'ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(40) NULL AFTER `email`'
);
PREPARE profile_phone_stmt FROM @profile_phone_ddl; EXECUTE profile_phone_stmt; DEALLOCATE PREPARE profile_phone_stmt;

UPDATE `users` AS account
INNER JOIN `testers` AS tester ON tester.user_id = account.id
SET account.phone = tester.phone
WHERE (account.phone IS NULL OR account.phone = '')
  AND tester.phone IS NOT NULL
  AND tester.phone <> '';

-- Refresh only the unchanged built-in QC description; preserve any administrator customization.
UPDATE `roles`
SET `description` = 'Review quality outcomes, assign pending tests, confirm rework, record CPRI results, and record market release after approval.'
WHERE `role_name` = 'Quality Control'
  AND `description` = 'Review quality outcomes, confirm rework, record CPRI results, and record market release after approval.';

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
