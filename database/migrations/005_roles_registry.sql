-- Register the built-in application roles used by AuthMiddleware and the Users screen.
-- User.role remains a string for compatibility with the existing PHP routes.

CREATE TABLE IF NOT EXISTS roles (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (role_name, description, is_system) VALUES
    ('Administrator', 'Full system access, including user accounts, role registry, catalogues, test plans, and workflow controls.', 1),
    ('Lab Manager', 'Manage laboratory products, departments, test types, testers, settings, and workflow operations.', 1),
    ('Tester', 'Record and review assigned laboratory tests and product history.', 1),
    ('Quality Control', 'Review quality results, support re-manufacturing decisions, and record CPRI handoffs.', 1)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    is_system = VALUES(is_system);
