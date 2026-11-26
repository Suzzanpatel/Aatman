-- =============================================================================
-- Aatman – DEMO data for local development only. Never load this in production.
--
--   mysql -u root -p aatman < database/seed.sql
--
-- Every demo account uses the password:  Aatman@123
-- All names, phone numbers and addresses below are fictitious.
-- For a real deployment create the admin with:  php scripts/create_admin.php
-- =============================================================================

SET NAMES utf8mb4;

INSERT INTO users (id, name, email, password, role, phone, status) VALUES
 (1, 'Aatman Admin',      'admin@aatman.test',     '$2y$10$FP1CGjgCeELI70aAwVVdRe4XKpyC10Mk.IX8BGng.W7T7kdH1FBk2', 'admin',     '9000000001', 'active'),
 (2, 'Asha Foundation',   'ngo@aatman.test',       '$2y$10$FP1CGjgCeELI70aAwVVdRe4XKpyC10Mk.IX8BGng.W7T7kdH1FBk2', 'ngo',       '9000000002', 'active'),
 (3, 'Ravi Mehta',        'donor@aatman.test',     '$2y$10$FP1CGjgCeELI70aAwVVdRe4XKpyC10Mk.IX8BGng.W7T7kdH1FBk2', 'donor',     '9000000003', 'active'),
 (4, 'Priya Shah',        'volunteer@aatman.test', '$2y$10$FP1CGjgCeELI70aAwVVdRe4XKpyC10Mk.IX8BGng.W7T7kdH1FBk2', 'volunteer', '9000000004', 'active'),
 (5, 'Kamla Devi',        'needy@aatman.test',     '$2y$10$FP1CGjgCeELI70aAwVVdRe4XKpyC10Mk.IX8BGng.W7T7kdH1FBk2', 'need',      '9000000005', 'active'),
 (6, 'Green Earth Trust', 'ngo2@aatman.test',      '$2y$10$FP1CGjgCeELI70aAwVVdRe4XKpyC10Mk.IX8BGng.W7T7kdH1FBk2', 'ngo',       '9000000006', 'active'),
 (7, 'Inactive Donor',    'inactive@aatman.test',  '$2y$10$FP1CGjgCeELI70aAwVVdRe4XKpyC10Mk.IX8BGng.W7T7kdH1FBk2', 'donor',     '9000000007', 'inactive');

INSERT INTO ngo_details (id, organisation_name, registration_number, address, city, state, description, category) VALUES
 (2, 'Asha Foundation',   'NGO-DEMO-0001', '12 Demo Road',  'Vadodara',  'Gujarat',     'Education support for children in urban slums.', 'Education'),
 (6, 'Green Earth Trust', 'NGO-DEMO-0002', '45 Sample Lane', 'Ahmedabad', 'Gujarat',    'Tree plantation and clean-up drives.',           'Environment');

INSERT INTO donor_details (id, address, city, state, donation_type, preferred_category) VALUES
 (3, '7 Example Street', 'Vadodara', 'Gujarat', 'Money', 'Health'),
 (7, '9 Example Street', 'Surat',    'Gujarat', 'Goods', 'Education');

INSERT INTO volunteer_details (id, alt_phone, emergency_contact_name, emergency_contact_phone, city, state, skills, availability, preferred_category) VALUES
 (4, '9000000014', 'Demo Contact', '9000000024', 'Vadodara', 'Gujarat', 'Teaching, first aid', 'Weekends', 'Education');

INSERT INTO people_in_need_details (id, contact_person_name, contact_person_phone, alt_phone, address, city, state) VALUES
 (5, 'Kamla Devi', '9000000005', NULL, 'Shelter Block B', 'Vadodara', 'Gujarat');

INSERT INTO campaigns (ngo_id, title, description, category, start_date, end_date, contact_person_name, contact_person_phone, status) VALUES
 (2, 'Back to School Kits',  'Collecting notebooks, bags and stationery for 200 children.', 'Education',   '2026-06-01', '2026-06-30', 'Asha Foundation',   '9000000002', 'completed'),
 (2, 'Winter Blanket Drive', 'Blankets for families living on the street this winter.',     'Health',      '2026-11-15', '2027-01-15', 'Asha Foundation',   '9000000002', 'active'),
 (6, 'Plant 1000 Trees',     'Monsoon plantation drive along the river front.',             'Environment', '2026-12-01', '2026-12-31', 'Green Earth Trust', '9000000006', 'active');

INSERT INTO requests (user_id, user_name, user_role, title, description, request_type, quantity, unit, location, priority,
                      contact_person_name, contact_person_phone, status) VALUES
 (5, 'Kamla Devi',      'need', 'Monthly rations',    'Rice, dal and oil for a family of five.',       'Food',      5,  'kg',      'Vadodara', 'High',   'Kamla Devi',      '9000000005', 'pending'),
 (5, 'Kamla Devi',      'need', 'School uniforms',    'Two uniforms for children in class 3 and 5.',   'Clothes',   2,  'sets',    'Vadodara', 'Medium', 'Kamla Devi',      '9000000005', 'completed'),
 (2, 'Asha Foundation', 'ngo',  'Notebooks for kids', 'Ruled notebooks for our evening classes.',      'Education', 300,'notebooks','Vadodara', 'Low',    'Asha Foundation', '9000000002', 'pending');
