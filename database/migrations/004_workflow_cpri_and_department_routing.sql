-- Record manual CPRI handoffs once every required laboratory test has passed.
-- No external CPRI API is called by this project.

CREATE TABLE IF NOT EXISTS cpri_handoffs (
    id INT NOT NULL AUTO_INCREMENT,
    product_record_id INT NOT NULL,
    product_id CHAR(10) NOT NULL,
    test_cycle INT NOT NULL DEFAULT 1,
    reference VARCHAR(100) NULL,
    notes TEXT NULL,
    handed_off_by VARCHAR(120) NOT NULL,
    handed_off_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cpri_handoffs_product_cycle (product_id, test_cycle),
    KEY ix_cpri_handoffs_date (handed_off_at),
    CONSTRAINT fk_cpri_handoffs_product_record
        FOREIGN KEY (product_record_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_workflow_events (
    id INT NOT NULL AUTO_INCREMENT,
    product_record_id INT NOT NULL,
    product_id CHAR(10) NOT NULL,
    event_type VARCHAR(40) NOT NULL,
    old_status VARCHAR(64) NULL,
    new_status VARCHAR(64) NOT NULL,
    notes TEXT NULL,
    changed_by VARCHAR(120) NOT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_product_workflow_events_product (product_id, changed_at),
    CONSTRAINT fk_product_workflow_events_record
        FOREIGN KEY (product_record_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
