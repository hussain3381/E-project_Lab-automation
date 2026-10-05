-- DEMO ONLY: create sample data; never run this against a production database.
-- The example ID split was selected for the project sprint but should still be checked against the supervisor's rules.

INSERT IGNORE INTO departments (id, department_code, department_name) VALUES
    (1, 'ELEC', 'Electrical Testing'),
    (2, 'MECH', 'Mechanical Testing');

INSERT IGNORE INTO product_types (id, type_code, numeric_code, type_name, description) VALUES
    (1, 'SWG', '01', 'Switch Gear', 'Switchgear panels and circuit-breaker assemblies'),
    (2, 'FUSE', '02', 'Fuse', 'Electrical fuses'),
    (3, 'CAP', '03', 'Capacitor', 'Power capacitors'),
    (4, 'RES', '04', 'Resistor', 'Electrical resistors');

-- This exact model-code mapping supplies the two-digit product-code segment in the demo ID.
INSERT IGNORE INTO product_codes (id, product_type_id, product_code, numeric_code, description) VALUES
    (1, 1, 'SWG01', '01', 'Demo model code for a 100A Switch Gear panel'),
    (2, 2, 'FUS01', '02', 'Demo model code for a fuse'),
    (3, 3, 'CAP01', '03', 'Demo model code for a capacitor'),
    (4, 4, 'RES01', '04', 'Demo model code for a resistor');

-- Link legacy product rows when their exact product code now has an approved mapping.
UPDATE products AS p
INNER JOIN product_codes AS pc ON pc.product_code = p.product_code
INNER JOIN product_types AS pt ON pt.id = pc.product_type_id
SET p.product_code_id = pc.id,
    p.product_code_numeric = pc.numeric_code,
    p.product_type_id = pc.product_type_id,
    p.product_type = pt.type_name
WHERE p.product_code_id IS NULL;

INSERT IGNORE INTO test_types (id, test_code, numeric_code, test_name, department, department_id, description) VALUES
    (1, 'TEMP', '001', 'Temperature Test', 'Electrical Testing', 1, 'Observe the product under the approved temperature criteria.'),
    (2, 'INS', '002', 'Insulation Resistance Test', 'Electrical Testing', 1, 'Measure insulation resistance against the approved specification.'),
    (3, 'HV', '003', 'High Voltage Test', 'Electrical Testing', 1, 'Record high-voltage test observations.'),
    (4, 'MECH', '004', 'Mechanical Operation Test', 'Mechanical Testing', 2, 'Record mechanical operation and endurance observations.');

-- Fill new routing/ID columns for legacy test types where the demo code is still unassigned.
UPDATE test_types AS tt
LEFT JOIN departments AS d ON d.department_name = tt.department
SET tt.numeric_code = COALESCE(tt.numeric_code, CASE tt.test_code
        WHEN 'TEMP' THEN '001' WHEN 'INS' THEN '002' WHEN 'HV' THEN '003' WHEN 'MECH' THEN '004' END),
    tt.department_id = COALESCE(tt.department_id, d.id),
    tt.is_active = 1
WHERE tt.test_code IN ('TEMP', 'INS', 'HV', 'MECH');

INSERT IGNORE INTO product_type_test_types (product_type_id, test_type_id, sequence_no, is_required) VALUES
    (1, 1, 1, 1),
    (1, 2, 2, 1),
    (1, 3, 3, 1),
    (1, 4, 4, 1),
    (2, 2, 1, 1),
    (2, 3, 2, 1),
    (3, 1, 1, 1),
    (3, 3, 2, 1),
    (4, 2, 1, 1);

INSERT IGNORE INTO testers (id, name, department, designation, is_active) VALUES
    (1, 'Adeel Khan', 'Electrical Testing', 'Test Engineer', 1),
    (2, 'Sana Ali', 'Electrical Testing', 'Quality Engineer', 1),
    (3, 'Bilal Ahmed', 'Mechanical Testing', 'Test Engineer', 1);

INSERT IGNORE INTO users (id, name, username, password, role, is_active) VALUES
    (1, 'Lab Administrator', 'admin', '$2y$12$V53AREmVvWnds7Bzx0fcaeFfxOQFD44MSLY.jYqDMXMXx09zMymwy', 'Administrator', 1);

INSERT IGNORE INTO products
    (id, product_id, product_code, product_code_id, product_code_numeric, product_name, product_type, product_type_id, revision, manufacturing_number, manufacturing_date, description, status)
VALUES
    (1, '0101004417', 'SWG01', 1, '01', '100A Switch Gear Panel', 'Switch Gear', 1, '01', '004417', '2026-10-04', 'Demo record uses the selected example split: product-code 01 + revision 01 + manufacturing 004417.', 'Pending Testing');

INSERT IGNORE INTO tests
    (id, test_id, product_id, test_type_id, tester_id, testing_date, criteria, expected_output, actual_output, result, status, remarks)
VALUES
    (1, '010100100001', '0101004417', 1, 1, '2026-10-04', 'Use the approved temperature test procedure.', 'Temperature remains within the approved specification.', '', 'PENDING', 'Pending', 'Demo record only; test ID uses the selected example split.');

INSERT IGNORE INTO test_roll_sequences (product_id, test_type_id, last_roll) VALUES
    ('0101004417', 1, 1);

-- Backfill matching existing Test IDs after legacy test-type numeric codes are assigned.
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

INSERT IGNORE INTO settings (id, lab_name, department, admin_name, email, contact) VALUES
    (1, 'Lab Automation System', 'Electrical Testing Laboratory', 'Lab Administrator', 'admin@example.invalid', '');
