-- Upgrade legacy repository columns without deleting any existing records.
-- Conditional column additions are performed safely by scripts/migrate.php for MySQL/MariaDB compatibility.

ALTER TABLE products
    MODIFY COLUMN product_code VARCHAR(32) NOT NULL;
