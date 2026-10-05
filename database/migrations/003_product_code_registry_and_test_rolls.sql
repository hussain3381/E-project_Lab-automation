-- Store the numeric ID mapping for exact product codes and reserve test rolls safely under concurrency.
-- Optional legacy columns are added by scripts/migrate.php after these tables are created.

CREATE TABLE IF NOT EXISTS product_codes (
    id INT NOT NULL AUTO_INCREMENT,
    product_type_id INT NULL,
    product_code VARCHAR(32) NOT NULL,
    numeric_code CHAR(2) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_product_codes_code (product_code),
    UNIQUE KEY uq_product_codes_numeric (numeric_code),
    KEY ix_product_codes_type (product_type_id),
    CONSTRAINT fk_product_codes_type
        FOREIGN KEY (product_type_id) REFERENCES product_types (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS test_roll_sequences (
    product_id CHAR(10) NOT NULL,
    test_type_id INT NOT NULL,
    last_roll INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id, test_type_id),
    KEY ix_test_roll_type (test_type_id),
    CONSTRAINT fk_test_roll_type
        FOREIGN KEY (test_type_id) REFERENCES test_types (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill only rows already matching the selected layout; the generator also checks legacy collisions at runtime.
INSERT IGNORE INTO test_roll_sequences (product_id, test_type_id, last_roll)
SELECT t.product_id, t.test_type_id, MAX(CAST(SUBSTRING(t.test_id, 8, 5) AS UNSIGNED))
FROM tests AS t
INNER JOIN test_types AS tt ON tt.id = t.test_type_id
WHERE t.test_id REGEXP '^[0-9]{12}$'
  AND tt.numeric_code REGEXP '^[0-9]{3}$'
  AND SUBSTRING(t.test_id, 1, 4) = SUBSTRING(t.product_id, 1, 4)
  AND SUBSTRING(t.test_id, 5, 3) = tt.numeric_code
GROUP BY t.product_id, t.test_type_id;

UPDATE test_roll_sequences AS sequence_row
INNER JOIN (
    SELECT t.product_id, t.test_type_id, MAX(CAST(SUBSTRING(t.test_id, 8, 5) AS UNSIGNED)) AS max_roll
    FROM tests AS t
    INNER JOIN test_types AS tt ON tt.id = t.test_type_id
    WHERE t.test_id REGEXP '^[0-9]{12}$'
      AND tt.numeric_code REGEXP '^[0-9]{3}$'
      AND SUBSTRING(t.test_id, 1, 4) = SUBSTRING(t.product_id, 1, 4)
      AND SUBSTRING(t.test_id, 5, 3) = tt.numeric_code
    GROUP BY t.product_id, t.test_type_id
) AS existing_tests
    ON existing_tests.product_id = sequence_row.product_id
   AND existing_tests.test_type_id = sequence_row.test_type_id
SET sequence_row.last_roll = GREATEST(sequence_row.last_roll, existing_tests.max_roll);
