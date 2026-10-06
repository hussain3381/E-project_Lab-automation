-- Full, importable MySQL/MariaDB database for Lab Automation.
-- Import into a NEW/EMPTY project_lab_db in phpMyAdmin (SQL tab or Import tab).
-- Demo accounts are created with password LabDemo@123; change them before real use.
-- Existing data is not dropped by this file. For an existing legacy database, take a backup
-- and run `php scripts/migrate.php` followed by `php scripts/seed_demo.php` instead.

CREATE DATABASE IF NOT EXISTS `project_lab_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE `project_lab_db`;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `migration` VARCHAR(190) NOT NULL,
    `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `username` VARCHAR(80) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'Tester',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
    `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_name` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `lab_name` VARCHAR(150) NULL,
    `department` VARCHAR(150) NULL,
    `admin_name` VARCHAR(100) NULL,
    `email` VARCHAR(150) NULL,
    `contact` VARCHAR(50) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `department_code` VARCHAR(16) NOT NULL,
    `department_name` VARCHAR(150) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_departments_code` (`department_code`),
    UNIQUE KEY `uq_departments_name` (`department_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_types` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `type_code` VARCHAR(16) NOT NULL,
    `numeric_code` CHAR(2) NULL,
    `type_name` VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_product_types_code` (`type_code`),
    UNIQUE KEY `uq_product_types_numeric_code` (`numeric_code`),
    UNIQUE KEY `uq_product_types_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_codes` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `product_type_id` INT NULL,
    `product_code` VARCHAR(32) NOT NULL,
    `numeric_code` CHAR(2) NOT NULL,
    `description` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_product_codes_code` (`product_code`),
    UNIQUE KEY `uq_product_codes_numeric` (`numeric_code`),
    KEY `ix_product_codes_type` (`product_type_id`),
    CONSTRAINT `fk_product_codes_type` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `test_types` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `test_code` VARCHAR(16) NOT NULL,
    `numeric_code` CHAR(3) NULL,
    `test_name` VARCHAR(150) NOT NULL,
    `department` VARCHAR(150) NULL,
    `department_id` INT NULL,
    `description` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_test_types_code` (`test_code`),
    UNIQUE KEY `uq_test_types_numeric_code` (`numeric_code`),
    KEY `ix_test_types_department_id` (`department_id`),
    CONSTRAINT `fk_test_types_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `product_id` CHAR(10) NOT NULL,
    `product_code` VARCHAR(32) NOT NULL,
    `product_code_id` INT NULL,
    `product_code_numeric` CHAR(2) NULL,
    `product_name` VARCHAR(180) NOT NULL,
    `product_type` VARCHAR(100) NOT NULL,
    `product_type_id` INT NULL,
    `revision` VARCHAR(16) NOT NULL,
    `manufacturing_number` VARCHAR(64) NOT NULL,
    `manufacturing_date` DATE NOT NULL,
    `description` TEXT NULL,
    `status` VARCHAR(64) NOT NULL DEFAULT 'Pending Testing',
    `rework_cycle` INT NOT NULL DEFAULT 0,
    `cpri_status` VARCHAR(24) NOT NULL DEFAULT 'Not Ready',
    `cpri_handoff_at` DATETIME NULL,
    `cpri_reference` VARCHAR(100) NULL,
    `cpri_handoff_by` VARCHAR(120) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_products_product_id` (`product_id`),
    KEY `ix_products_manufacturing_number` (`manufacturing_number`),
    KEY `ix_products_product_type_id` (`product_type_id`),
    KEY `ix_products_product_code_id` (`product_code_id`),
    KEY `ix_products_status` (`status`),
    CONSTRAINT `fk_products_product_code` FOREIGN KEY (`product_code_id`) REFERENCES `product_codes` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_products_product_type` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_type_test_types` (
    `product_type_id` INT NOT NULL,
    `test_type_id` INT NOT NULL,
    `sequence_no` SMALLINT NOT NULL DEFAULT 1,
    `is_required` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`product_type_id`, `test_type_id`),
    UNIQUE KEY `uq_type_test_sequence` (`product_type_id`, `sequence_no`),
    CONSTRAINT `fk_product_type_tests_product_type` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_product_type_tests_test_type` FOREIGN KEY (`test_type_id`) REFERENCES `test_types` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `testers` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `department` VARCHAR(150) NULL,
    `designation` VARCHAR(100) NULL,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(40) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_testers_name` (`name`),
    KEY `ix_testers_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tests` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `test_id` CHAR(12) NOT NULL,
    `product_id` CHAR(10) NOT NULL,
    `test_type_id` INT NOT NULL,
    `tester_id` INT NULL,
    `testing_date` DATE NOT NULL,
    `criteria` TEXT NOT NULL,
    `expected_output` TEXT NOT NULL,
    `actual_output` TEXT NULL,
    `result` VARCHAR(16) NOT NULL DEFAULT 'PENDING',
    `status` VARCHAR(24) NOT NULL DEFAULT 'Pending',
    `remarks` TEXT NULL,
    `cycle_number` INT NOT NULL DEFAULT 1,
    `department_id` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tests_test_id` (`test_id`),
    KEY `ix_tests_product_id` (`product_id`),
    KEY `ix_tests_test_type_id` (`test_type_id`),
    KEY `ix_tests_tester_id` (`tester_id`),
    KEY `ix_tests_result` (`result`),
    KEY `ix_tests_status` (`status`),
    KEY `ix_tests_testing_date` (`testing_date`),
    KEY `ix_tests_department` (`department_id`),
    CONSTRAINT `fk_tests_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_tests_test_type` FOREIGN KEY (`test_type_id`) REFERENCES `test_types` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_tests_tester` FOREIGN KEY (`tester_id`) REFERENCES `testers` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_tests_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `test_participants` (
    `test_record_id` INT NOT NULL,
    `tester_id` INT NOT NULL,
    `participant_role` VARCHAR(40) NULL,
    PRIMARY KEY (`test_record_id`, `tester_id`),
    CONSTRAINT `fk_test_participants_record` FOREIGN KEY (`test_record_id`) REFERENCES `tests` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_test_participants_tester` FOREIGN KEY (`tester_id`) REFERENCES `testers` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `test_history` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `test_id` CHAR(12) NULL,
    `product_id` CHAR(10) NOT NULL,
    `old_status` VARCHAR(64) NULL,
    `new_status` VARCHAR(64) NOT NULL,
    `remarks` TEXT NULL,
    `changed_by` VARCHAR(150) NOT NULL,
    `changed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ix_test_history_product` (`product_id`),
    KEY `ix_test_history_test` (`test_id`),
    KEY `ix_test_history_changed_at` (`changed_at`),
    CONSTRAINT `fk_test_history_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `test_roll_sequences` (
    `product_id` CHAR(10) NOT NULL,
    `test_type_id` INT NOT NULL,
    `last_roll` INT NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`product_id`, `test_type_id`),
    KEY `ix_test_roll_type` (`test_type_id`),
    CONSTRAINT `fk_test_roll_type` FOREIGN KEY (`test_type_id`) REFERENCES `test_types` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

INSERT IGNORE INTO `schema_migrations` (`migration`) VALUES
    ('001_initial_schema.sql'),
    ('002_legacy_schema_compat.sql'),
    ('003_product_code_registry_and_test_rolls.sql'),
    ('004_workflow_cpri_and_department_routing.sql'),
    ('005_roles_registry.sql');

INSERT IGNORE INTO `departments` (`id`, `department_code`, `department_name`) VALUES
    (1, 'ELEC', 'Electrical Testing'),
    (2, 'MECH', 'Mechanical Testing');

INSERT IGNORE INTO `roles` (`id`, `role_name`, `description`, `is_system`) VALUES
    (1, 'Administrator', 'Full system access, including users, roles, catalogues, test plans, and workflow controls.', 1),
    (2, 'Lab Manager', 'Manage laboratory operations, products, departments, and workflow.', 1),
    (3, 'Tester', 'Record and review assigned laboratory test results.', 1),
    (4, 'Quality Control', 'Review quality outcomes and support controlled workflow actions.', 1);

INSERT IGNORE INTO `product_types` (`id`, `type_code`, `numeric_code`, `type_name`, `description`) VALUES
    (1, 'SWG', '01', 'Switch Gear', 'Switchgear panels and circuit-breaker assemblies'),
    (2, 'FUSE', '02', 'Fuse', 'Electrical fuses'),
    (3, 'CAP', '03', 'Capacitor', 'Power capacitors'),
    (4, 'RES', '04', 'Resistor', 'Electrical resistors');

INSERT IGNORE INTO `product_codes` (`id`, `product_type_id`, `product_code`, `numeric_code`, `description`) VALUES
    (1, 1, 'SWG01', '01', 'Demo model code for a 100A Switch Gear panel'),
    (2, 2, 'FUS01', '02', 'Demo model code for a fuse'),
    (3, 3, 'CAP01', '03', 'Demo model code for a capacitor'),
    (4, 4, 'RES01', '04', 'Demo model code for a resistor');

INSERT IGNORE INTO `test_types` (`id`, `test_code`, `numeric_code`, `test_name`, `department`, `department_id`, `description`) VALUES
    (1, 'TEMP', '001', 'Temperature Test', 'Electrical Testing', 1, 'Observe the product under the approved temperature criteria.'),
    (2, 'INS', '002', 'Insulation Resistance Test', 'Electrical Testing', 1, 'Measure insulation resistance against the approved specification.'),
    (3, 'HV', '003', 'High Voltage Test', 'Electrical Testing', 1, 'Record high-voltage test observations.'),
    (4, 'MECH', '004', 'Mechanical Operation Test', 'Mechanical Testing', 2, 'Record mechanical operation and endurance observations.');

INSERT IGNORE INTO `product_type_test_types` (`product_type_id`, `test_type_id`, `sequence_no`, `is_required`) VALUES
    (1, 1, 1, 1), (1, 2, 2, 1), (1, 3, 3, 1), (1, 4, 4, 1),
    (2, 2, 1, 1), (2, 3, 2, 1),
    (3, 1, 1, 1), (3, 3, 2, 1),
    (4, 2, 1, 1);

INSERT IGNORE INTO `testers` (`id`, `name`, `department`, `designation`, `is_active`) VALUES
    (1, 'Adeel Khan', 'Electrical Testing', 'Test Engineer', 1),
    (2, 'Sana Ali', 'Electrical Testing', 'Quality Engineer', 1),
    (3, 'Bilal Ahmed', 'Mechanical Testing', 'Test Engineer', 1);

-- These shared demo credentials are intended for coursework only: password is LabDemo@123.
INSERT IGNORE INTO `users` (`name`, `username`, `password`, `role`, `is_active`) VALUES
    ('Lab Administrator', 'admin', '$2y$12$V53AREmVvWnds7Bzx0fcaeFfxOQFD44MSLY.jYqDMXMXx09zMymwy', 'Administrator', 1),
    ('Lab Manager', 'manager', '$2y$12$V53AREmVvWnds7Bzx0fcaeFfxOQFD44MSLY.jYqDMXMXx09zMymwy', 'Lab Manager', 1),
    ('Test Engineer', 'tester', '$2y$12$V53AREmVvWnds7Bzx0fcaeFfxOQFD44MSLY.jYqDMXMXx09zMymwy', 'Tester', 1),
    ('Quality Control', 'quality', '$2y$12$V53AREmVvWnds7Bzx0fcaeFfxOQFD44MSLY.jYqDMXMXx09zMymwy', 'Quality Control', 1);

INSERT IGNORE INTO `products`
    (`id`, `product_id`, `product_code`, `product_code_id`, `product_code_numeric`, `product_name`, `product_type`, `product_type_id`, `revision`, `manufacturing_number`, `manufacturing_date`, `description`, `status`)
VALUES
    (1, '0101004417', 'SWG01', 1, '01', '100A Switch Gear Panel', 'Switch Gear', 1, '01', '004417', '2026-10-04', 'Demo record; replace with your approved model mapping and manufacturing data.', 'Pending Testing');

INSERT IGNORE INTO `tests`
    (`id`, `test_id`, `product_id`, `test_type_id`, `tester_id`, `testing_date`, `criteria`, `expected_output`, `actual_output`, `result`, `status`, `remarks`, `cycle_number`, `department_id`)
VALUES
    (1, '010100100001', '0101004417', 1, 1, '2026-10-04', 'Use the approved temperature test procedure.', 'Temperature remains within the approved specification.', '', 'PENDING', 'Pending', 'Demo record only; Test ID uses the selected example split.', 1, 1);

INSERT IGNORE INTO `test_roll_sequences` (`product_id`, `test_type_id`, `last_roll`) VALUES
    ('0101004417', 1, 1);

INSERT IGNORE INTO `settings` (`id`, `lab_name`, `department`, `admin_name`, `email`, `contact`) VALUES
    (1, 'Lab Automation System', 'Electrical Testing Laboratory', 'Lab Administrator', 'admin@example.invalid', '');
