-- Add durable admin activity history, Google account linking, and reversible product archiving.
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_user_id INT NULL,
    actor_name VARCHAR(120) NOT NULL DEFAULT 'System',
    actor_role VARCHAR(50) NOT NULL DEFAULT 'System',
    action VARCHAR(80) NOT NULL,
    entity VARCHAR(80) NOT NULL,
    entity_id VARCHAR(100) NULL,
    summary VARCHAR(500) NOT NULL,
    metadata LONGTEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_audit_logs_created_at (created_at),
    KEY ix_audit_logs_actor (actor_user_id, created_at),
    KEY ix_audit_logs_entity (entity, entity_id, created_at),
    CONSTRAINT fk_audit_logs_actor FOREIGN KEY (actor_user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
