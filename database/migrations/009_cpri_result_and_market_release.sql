-- Add manual CPRI outcome and final market-release traceability without replacing existing handoffs.
-- Legacy handoff rows remain in place and receive the PENDING default until staff records an outcome.
-- Create these workflow tables if a legacy database marked migration 004 without containing them.

CREATE TABLE IF NOT EXISTS `cpri_handoffs` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `product_record_id` INT NOT NULL,
    `product_id` CHAR(10) NOT NULL,
    `test_cycle` INT NOT NULL DEFAULT 1,
    `reference` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `handed_off_by` VARCHAR(120) NOT NULL,
    `handed_off_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
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

SET @workflow_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'cpri_result'),
    'SELECT 1',
    'ALTER TABLE `cpri_handoffs` ADD COLUMN `cpri_result` VARCHAR(16) NOT NULL DEFAULT ''PENDING'''
);
PREPARE workflow_stmt FROM @workflow_ddl; EXECUTE workflow_stmt; DEALLOCATE PREPARE workflow_stmt;

SET @workflow_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'cpri_result_notes'),
    'SELECT 1',
    'ALTER TABLE `cpri_handoffs` ADD COLUMN `cpri_result_notes` TEXT NULL'
);
PREPARE workflow_stmt FROM @workflow_ddl; EXECUTE workflow_stmt; DEALLOCATE PREPARE workflow_stmt;

SET @workflow_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'cpri_result_by'),
    'SELECT 1',
    'ALTER TABLE `cpri_handoffs` ADD COLUMN `cpri_result_by` VARCHAR(120) NULL'
);
PREPARE workflow_stmt FROM @workflow_ddl; EXECUTE workflow_stmt; DEALLOCATE PREPARE workflow_stmt;

SET @workflow_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'cpri_result_at'),
    'SELECT 1',
    'ALTER TABLE `cpri_handoffs` ADD COLUMN `cpri_result_at` DATETIME NULL'
);
PREPARE workflow_stmt FROM @workflow_ddl; EXECUTE workflow_stmt; DEALLOCATE PREPARE workflow_stmt;

SET @workflow_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'market_released_by'),
    'SELECT 1',
    'ALTER TABLE `cpri_handoffs` ADD COLUMN `market_released_by` VARCHAR(120) NULL'
);
PREPARE workflow_stmt FROM @workflow_ddl; EXECUTE workflow_stmt; DEALLOCATE PREPARE workflow_stmt;

SET @workflow_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'market_released_at'),
    'SELECT 1',
    'ALTER TABLE `cpri_handoffs` ADD COLUMN `market_released_at` DATETIME NULL'
);
PREPARE workflow_stmt FROM @workflow_ddl; EXECUTE workflow_stmt; DEALLOCATE PREPARE workflow_stmt;

SET @workflow_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cpri_handoffs' AND COLUMN_NAME = 'market_release_notes'),
    'SELECT 1',
    'ALTER TABLE `cpri_handoffs` ADD COLUMN `market_release_notes` TEXT NULL'
);
PREPARE workflow_stmt FROM @workflow_ddl; EXECUTE workflow_stmt; DEALLOCATE PREPARE workflow_stmt;
