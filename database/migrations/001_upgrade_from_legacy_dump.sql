-- =============================================================================
-- Migration 001 – upgrade a database created from the old `Aatman_final.sql`
-- dump (database `aatman_final1`) to the current schema WITHOUT losing data.
--
--   mysqldump -u root -p aatman_final1 > backup_before_001.sql   (back up first!)
--   mysql -u root -p aatman_final1 < database/migrations/001_upgrade_from_legacy_dump.sql
--
-- Then either set DB_NAME=aatman_final1 in .env, or dump/restore into `aatman`.
-- =============================================================================

SET NAMES utf8mb4;

-- 1. Table name casing: the code used both `NGO_details` and `ngo_details`.
--    On Linux MySQL table names are case-sensitive, so one of them always failed.
--    Two-step rename works on both case-sensitive and case-insensitive servers.
RENAME TABLE `NGO_details` TO `ngo_details_tmp_001`;
RENAME TABLE `ngo_details_tmp_001` TO `ngo_details`;

-- 2. Room for modern password hashes (varchar(64) can truncate future algorithms).
ALTER TABLE users
  MODIFY password VARCHAR(255) NOT NULL,
  MODIFY status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ADD KEY idx_users_role_status (role, status);

-- 3. Volunteer category can hold a free-text "Other" value.
ALTER TABLE volunteer_details MODIFY preferred_category VARCHAR(100) NULL;

-- 4. Helpful indexes for the dashboard queries.
ALTER TABLE campaigns ADD KEY idx_campaigns_status (status, created_at);
ALTER TABLE requests  ADD KEY idx_requests_status (status, created_at);

-- 5. Brute-force protection for the login form.
CREATE TABLE IF NOT EXISTS login_attempts (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email        VARCHAR(89)  NOT NULL,
  ip_address   VARCHAR(45)  NOT NULL,
  attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attempts_email (email, attempted_at),
  KEY idx_attempts_ip (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Data clean-up: emails are compared lower-case by the app.
UPDATE users SET email = LOWER(TRIM(email));
