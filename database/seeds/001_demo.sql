-- DEMO ONLY: create sample data; never run this against a production database.
-- The example ID split was selected for the project sprint but should still be checked against the supervisor's rules.

INSERT IGNORE INTO departments (id, department_code, department_name) VALUES
    (1, 'ELEC', 'Electrical Testing'),
    (2, 'MECH', 'Mechanical Testing');

INSERT IGNORE INTO roles (role_name, description, is_system) VALUES
    ('Administrator', 'Full system access, including users, roles, catalogues, test plans, and workflow controls.', 1),
    ('Lab Manager', 'Manage laboratory operations, products, departments, and workflow.', 1),
    ('Tester', 'Record and review assigned laboratory test results.', 1),
    ('Quality Control', 'Review quality outcomes and support controlled workflow actions.', 1);

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

-- The same hash is used by all four disposable demo logins; each password is LabDemo@123.
-- Replace these passwords before using the app with non-demo data.
INSERT IGNORE INTO users (name, username, password, role, is_active) VALUES
    ('Lab Administrator', 'admin', '$2y$12$V53AREmVvWnds7Bzx0fcaeFfxOQFD44MSLY.jYqDMXMXx09zMymwy', 'Administrator', 1),
    ('Lab Manager', 'manager', '$2y$12$V53AREmVvWnds7Bzx0fcaeFfxOQFD44MSLY.jYqDMXMXx09zMymwy', 'Lab Manager', 1),
    ('Test Engineer', 'tester', '$2y$12$V53AREmVvWnds7Bzx0fcaeFfxOQFD44MSLY.jYqDMXMXx09zMymwy', 'Tester', 1),
    ('Quality Control', 'quality', '$2y$12$V53AREmVvWnds7Bzx0fcaeFfxOQFD44MSLY.jYqDMXMXx09zMymwy', 'Quality Control', 1);

-- Explicit parentheses around the column list and VALUES block keep this phpMyAdmin-friendly.
INSERT IGNORE INTO `products` (
    `id`, `product_id`, `product_code`, `product_code_id`, `product_code_numeric`,
    `product_name`, `product_type`, `product_type_id`, `revision`,
    `manufacturing_number`, `manufacturing_date`, `description`, `status`,
    `rework_cycle`, `cpri_status`, `cpri_handoff_at`, `cpri_reference`, `cpri_handoff_by`
) VALUES
    (1, '0101004417', 'SWG01', 1, '01', '100A Switch Gear Panel', 'Switch Gear', 1, '01', '004417', '2026-10-04', 'Synthetic demo record; replace with real lab data.', 'Pending Testing', 0, 'Not Ready', NULL, NULL, NULL);

-- Additional synthetic rows exercise multiple product families and workflow states.
INSERT IGNORE INTO `products` (
    `id`, `product_id`, `product_code`, `product_code_id`, `product_code_numeric`,
    `product_name`, `product_type`, `product_type_id`, `revision`,
    `manufacturing_number`, `manufacturing_date`, `description`, `status`,
    `rework_cycle`, `cpri_status`, `cpri_handoff_at`, `cpri_reference`, `cpri_handoff_by`
) VALUES
    (2, '0102004418', 'SWG01', 1, '01', '125A Switch Gear Panel', 'Switch Gear', 1, '02', '004418', '2026-09-18', 'Synthetic demo sample; second revision in testing.', 'Testing In Progress', 0, 'Not Ready', NULL, NULL, NULL),
    (3, '0201002017', 'FUS01', 2, '02', 'Industrial Fuse Assembly', 'Fuse', 2, '01', '002017', '2026-09-21', 'Synthetic demo sample; required tests passed and awaiting manual handoff.', 'CPRI Ready', 0, 'Ready', NULL, NULL, NULL),
    (4, '0302000089', 'CAP01', 3, '03', 'Power Capacitor Module', 'Capacitor', 3, '02', '000089', '2026-09-25', 'Synthetic demo sample; failure routed to re-manufacture.', 'Failed - Re-manufacturing', 1, 'Not Ready', NULL, NULL, NULL),
    (5, '0401000017', 'RES01', 4, '04', 'Precision Resistor Unit', 'Resistor', 4, '01', '000017', '2026-09-28', 'Synthetic demo sample; shown after a manual CPRI handoff.', 'Handed to CPRI', 0, 'Handed Off', '2026-10-06 09:30:00', 'DEMO-NOT-A-REAL-HANDOFF', 'Lab Administrator');

-- All sample Test IDs follow product-prefix + 3-digit test code + 5-digit roll.
INSERT IGNORE INTO `tests` (
    `id`, `test_id`, `product_id`, `test_type_id`, `tester_id`, `testing_date`,
    `criteria`, `expected_output`, `actual_output`, `result`, `status`, `remarks`,
    `cycle_number`, `department_id`
) VALUES
    (1, '010100100001', '0101004417', 1, 1, '2026-10-04', 'Use the approved temperature test procedure.', 'Temperature remains within the approved specification.', '', 'PENDING', 'Pending', 'Synthetic demo only; enter actual lab observations for real products.', 1, 1),
    (2, '010200100001', '0102004418', 1, 1, '2026-10-06', 'Run the approved temperature check at the specified load.', 'No abnormal temperature rise under approved conditions.', 'No abnormal rise observed in this synthetic example.', 'PASS', 'Completed', 'Synthetic training sample; not a certified measurement.', 1, 1),
    (3, '010200200001', '0102004418', 2, 2, '2026-10-06', 'Measure insulation resistance with the approved test setup.', 'Reading meets the lab-approved acceptance criteria.', 'Demo reading: 1.8 GOhm; within the example acceptance range.', 'PASS', 'Completed', 'Synthetic training sample; replace with actual instrument output.', 1, 1),
    (4, '020100200001', '0201002017', 2, 2, '2026-10-06', 'Measure insulation resistance with the approved test setup.', 'Reading meets the lab-approved acceptance criteria.', 'Demo reading: 2.1 GOhm; within the example acceptance range.', 'PASS', 'Completed', 'Synthetic training sample; replace with actual instrument output.', 1, 1),
    (5, '020100300001', '0201002017', 3, 1, '2026-10-06', 'Perform the approved high-voltage withstand procedure.', 'No unacceptable breakdown under approved criteria.', 'No breakdown observed in this synthetic example.', 'PASS', 'Completed', 'Synthetic training sample; not a certification record.', 1, 1),
    (6, '030200100001', '0302000089', 1, 1, '2026-10-06', 'Run the approved temperature check at the specified load.', 'Temperature remains within the lab-approved acceptance criteria.', 'Abnormal temperature rise observed in this synthetic example.', 'FAIL', 'Completed', 'Synthetic failure sample; product routed to re-manufacture.', 1, 1),
    (7, '040100200001', '0401000017', 2, 2, '2026-10-06', 'Measure insulation resistance with the approved test setup.', 'Reading meets the lab-approved acceptance criteria.', 'Demo reading: 2.4 GOhm; within the example acceptance range.', 'PASS', 'Completed', 'Synthetic training sample; a manual demo handoff is recorded separately.', 1, 1);

INSERT IGNORE INTO `test_participants` (`test_record_id`, `tester_id`, `participant_role`) VALUES
    (2, 1, 'Lead tester'), (2, 2, 'Witness'),
    (3, 2, 'Lead tester'),
    (4, 2, 'Lead tester'),
    (5, 1, 'Lead tester'),
    (6, 1, 'Lead tester'),
    (7, 2, 'Lead tester');

INSERT IGNORE INTO `test_history` (`id`, `test_id`, `product_id`, `old_status`, `new_status`, `remarks`, `changed_by`, `changed_at`) VALUES
    (1, '010200100001', '0102004418', 'Pending Testing', 'Testing In Progress', 'Synthetic demo PASS result recorded.', 'Demo Seed', '2026-10-06 09:00:00'),
    (2, '010200200001', '0102004418', 'Testing In Progress', 'Testing In Progress', 'Synthetic demo PASS result recorded; other required family tests remain.', 'Demo Seed', '2026-10-06 09:10:00'),
    (3, '020100200001', '0201002017', 'Pending Testing', 'Testing In Progress', 'Synthetic demo PASS result recorded; high-voltage test remained.', 'Demo Seed', '2026-10-06 09:15:00'),
    (4, '020100300001', '0201002017', 'Testing In Progress', 'CPRI Ready', 'All required synthetic demo tests passed.', 'Demo Seed', '2026-10-06 09:20:00'),
    (5, '030200100001', '0302000089', 'Pending Testing', 'Failed - Re-manufacturing', 'Synthetic failure sample routed to re-manufacture.', 'Demo Seed', '2026-10-06 09:25:00'),
    (6, '040100200001', '0401000017', 'Pending Testing', 'CPRI Ready', 'All required synthetic demo tests passed before handoff.', 'Demo Seed', '2026-10-06 09:28:00');

INSERT IGNORE INTO `cpri_handoffs` (`id`, `product_record_id`, `product_id`, `test_cycle`, `reference`, `notes`, `handed_off_by`, `handed_off_at`) VALUES
    (1, 5, '0401000017', 1, 'DEMO-NOT-A-REAL-HANDOFF', 'Synthetic demo row only; this is not an actual CPRI transfer.', 'Lab Administrator', '2026-10-06 09:30:00');

INSERT IGNORE INTO `product_workflow_events` (`id`, `product_record_id`, `product_id`, `event_type`, `old_status`, `new_status`, `notes`, `changed_by`, `changed_at`) VALUES
    (1, 5, '0401000017', 'CPRI_HANDOFF', 'CPRI Ready', 'Handed to CPRI', 'Synthetic demo row only; no API call was made.', 'Lab Administrator', '2026-10-06 09:30:00');

INSERT IGNORE INTO `test_roll_sequences` (`product_id`, `test_type_id`, `last_roll`) VALUES
    ('0101004417', 1, 1),
    ('0102004418', 1, 1), ('0102004418', 2, 1),
    ('0201002017', 2, 1), ('0201002017', 3, 1),
    ('0302000089', 1, 1),
    ('0401000017', 2, 1);

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
