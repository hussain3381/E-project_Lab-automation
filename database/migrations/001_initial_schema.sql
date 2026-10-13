-- Initial relational schema for the Lab Automation System.
-- Shared deployments use the versioned migration runner; fresh local demos may import database/project_lab_db_import.sql.

CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(80) NOT NULL,
    email VARCHAR(190) NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'Tester',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id INT NOT NULL AUTO_INCREMENT,
    lab_name VARCHAR(150) NULL,
    department VARCHAR(150) NULL,
    admin_name VARCHAR(100) NULL,
    email VARCHAR(150) NULL,
    contact VARCHAR(50) NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS departments (
    id INT NOT NULL AUTO_INCREMENT,
    department_code VARCHAR(16) NOT NULL,
    department_name VARCHAR(150) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_departments_code (department_code),
    UNIQUE KEY uq_departments_name (department_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_types (
    id INT NOT NULL AUTO_INCREMENT,
    type_code VARCHAR(16) NOT NULL,
    numeric_code CHAR(2) NULL,
    type_name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_types_code (type_code),
    UNIQUE KEY uq_product_types_numeric_code (numeric_code),
    UNIQUE KEY uq_product_types_name (type_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS test_types (
    id INT NOT NULL AUTO_INCREMENT,
    test_code VARCHAR(16) NOT NULL,
    numeric_code CHAR(3) NULL,
    test_name VARCHAR(150) NOT NULL,
    department VARCHAR(150) NULL,
    department_id INT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_test_types_code (test_code),
    UNIQUE KEY uq_test_types_numeric_code (numeric_code),
    KEY ix_test_types_department_id (department_id),
    CONSTRAINT fk_test_types_department
        FOREIGN KEY (department_id) REFERENCES departments (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT NOT NULL AUTO_INCREMENT,
    product_id CHAR(10) NOT NULL,
    product_code VARCHAR(32) NOT NULL,
    product_name VARCHAR(180) NOT NULL,
    product_type VARCHAR(100) NOT NULL,
    product_type_id INT NULL,
    revision VARCHAR(16) NOT NULL,
    manufacturing_number VARCHAR(64) NOT NULL,
    manufacturing_date DATE NOT NULL,
    description TEXT NULL,
    status VARCHAR(64) NOT NULL DEFAULT 'Pending Testing',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_product_id (product_id),
    KEY ix_products_manufacturing_number (manufacturing_number),
    KEY ix_products_product_type_id (product_type_id),
    KEY ix_products_status (status),
    CONSTRAINT fk_products_product_type
        FOREIGN KEY (product_type_id) REFERENCES product_types (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_type_test_types (
    product_type_id INT NOT NULL,
    test_type_id INT NOT NULL,
    sequence_no SMALLINT NOT NULL DEFAULT 1,
    is_required TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (product_type_id, test_type_id),
    UNIQUE KEY uq_type_test_sequence (product_type_id, sequence_no),
    CONSTRAINT fk_product_type_tests_product_type
        FOREIGN KEY (product_type_id) REFERENCES product_types (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_product_type_tests_test_type
        FOREIGN KEY (test_type_id) REFERENCES test_types (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS testers (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NULL,
    name VARCHAR(120) NOT NULL,
    department VARCHAR(150) NULL,
    designation VARCHAR(100) NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(40) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_testers_user_id (user_id),
    KEY ix_testers_name (name),
    KEY ix_testers_department (department),
    CONSTRAINT fk_testers_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tests (
    id INT NOT NULL AUTO_INCREMENT,
    test_id CHAR(12) NOT NULL,
    product_id CHAR(10) NOT NULL,
    test_type_id INT NOT NULL,
    tester_id INT NULL,
    testing_date DATE NOT NULL,
    criteria TEXT NOT NULL,
    expected_output TEXT NOT NULL,
    actual_output TEXT NULL,
    result VARCHAR(16) NOT NULL DEFAULT 'PENDING',
    status VARCHAR(24) NOT NULL DEFAULT 'Pending',
    remarks TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tests_test_id (test_id),
    KEY ix_tests_product_id (product_id),
    KEY ix_tests_test_type_id (test_type_id),
    KEY ix_tests_tester_id (tester_id),
    KEY ix_tests_result (result),
    KEY ix_tests_status (status),
    KEY ix_tests_testing_date (testing_date),
    CONSTRAINT fk_tests_product
        FOREIGN KEY (product_id) REFERENCES products (product_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_tests_test_type
        FOREIGN KEY (test_type_id) REFERENCES test_types (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_tests_tester
        FOREIGN KEY (tester_id) REFERENCES testers (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS test_participants (
    test_record_id INT NOT NULL,
    tester_id INT NOT NULL,
    participant_role VARCHAR(40) NULL,
    PRIMARY KEY (test_record_id, tester_id),
    CONSTRAINT fk_test_participants_record
        FOREIGN KEY (test_record_id) REFERENCES tests (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_test_participants_tester
        FOREIGN KEY (tester_id) REFERENCES testers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS test_history (
    id INT NOT NULL AUTO_INCREMENT,
    test_id CHAR(12) NULL,
    product_id CHAR(10) NOT NULL,
    old_status VARCHAR(64) NULL,
    new_status VARCHAR(64) NOT NULL,
    remarks TEXT NULL,
    changed_by VARCHAR(150) NOT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_test_history_product (product_id),
    KEY ix_test_history_test (test_id),
    KEY ix_test_history_changed_at (changed_at),
    CONSTRAINT fk_test_history_product
        FOREIGN KEY (product_id) REFERENCES products (product_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
