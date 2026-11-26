-- =============================================================================
-- Creates the Aatman database and a least-privilege application user.
-- Run once as an administrative MySQL/MariaDB user:
--
--   mysql -u root -p < database/create_database.sql
--
-- Then put the same credentials in your .env file (DB_USER / DB_PASS).
-- CHANGE THE PASSWORD BELOW before running this on any shared machine.
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `aatman`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'aatman_app'@'localhost' IDENTIFIED BY 'change-me-to-a-strong-password';

-- The web app only needs to read and write rows – not create/drop tables or users.
GRANT SELECT, INSERT, UPDATE, DELETE ON `aatman`.* TO 'aatman_app'@'localhost';
FLUSH PRIVILEGES;
