-- =============================================================================
-- Aatman – database schema (MySQL 8.0.16+ / MariaDB 10.4+)
--
--   mysql -u root -p < database/create_database.sql   (database + app user)
--   mysql -u root -p aatman < database/schema.sql
--   mysql -u root -p aatman < database/seed.sql       (optional demo data)
--
-- WARNING: this file DROPS and recreates all tables. For an existing install
-- built from the old Aatman_final.sql dump, run migrations/ instead.
--
-- Design: one `users` table holds credentials + role (RBAC). Each role has a
-- 1:1 "details" table whose primary key is also a foreign key to users.id.
-- Deleting a user cascades to their details, requests and campaigns.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS login_attempts, requests, campaigns, volunteer_details,
                     people_in_need_details, ngo_details, donor_details, users;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- users: every account, whatever its role
-- -----------------------------------------------------------------------------
CREATE TABLE users (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(89)  NOT NULL,
  password    VARCHAR(255) NOT NULL COMMENT 'password_hash() output; 255 leaves room for future algorithms',
  role        ENUM('ngo','donor','need','volunteer','admin') NOT NULL,
  phone       VARCHAR(20)  NULL,
  status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Role-specific profile tables (1:1 with users)
-- -----------------------------------------------------------------------------
CREATE TABLE ngo_details (
  id                  INT UNSIGNED NOT NULL,
  organisation_name   VARCHAR(255) NOT NULL,
  registration_number VARCHAR(100) NOT NULL,
  address             VARCHAR(255) NULL,
  city                VARCHAR(100) NULL,
  state               VARCHAR(100) NULL,
  description         TEXT         NULL,
  category            ENUM('Education','Health','Environment','Women','Other') NOT NULL,
  status              ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ngo_registration (registration_number),
  CONSTRAINT fk_ngo_user FOREIGN KEY (id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE donor_details (
  id                 INT UNSIGNED NOT NULL,
  address            VARCHAR(255) NULL,
  city               VARCHAR(100) NULL,
  state              VARCHAR(100) NULL,
  donation_type      ENUM('Money','Goods','Services','Other') NOT NULL,
  preferred_category ENUM('Education','Health','Environment','Women','Other') NULL,
  status             ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_donor_user FOREIGN KEY (id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE volunteer_details (
  id                      INT UNSIGNED NOT NULL,
  alt_phone               VARCHAR(20)  NULL,
  emergency_contact_name  VARCHAR(255) NULL,
  emergency_contact_phone VARCHAR(20)  NULL,
  address                 VARCHAR(255) NULL,
  city                    VARCHAR(100) NULL,
  state                   VARCHAR(100) NULL,
  skills                  TEXT         NULL,
  availability            VARCHAR(255) NULL,
  preferred_category      VARCHAR(100) NULL COMMENT 'Any, one of the categories, or free text for Other',
  status                  ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_volunteer_user FOREIGN KEY (id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE people_in_need_details (
  id                   INT UNSIGNED NOT NULL,
  contact_person_name  VARCHAR(255) NOT NULL,
  contact_person_phone VARCHAR(20)  NOT NULL,
  alt_phone            VARCHAR(20)  NULL,
  address              VARCHAR(255) NULL,
  city                 VARCHAR(100) NULL,
  state                VARCHAR(100) NULL,
  status               ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_need_user FOREIGN KEY (id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- campaigns: drives organised by NGOs
-- -----------------------------------------------------------------------------
CREATE TABLE campaigns (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ngo_id               INT UNSIGNED NOT NULL,
  title                VARCHAR(255) NOT NULL,
  description          TEXT         NOT NULL,
  category             ENUM('Education','Health','Environment','Women','Other') NOT NULL,
  start_date           DATE         NULL,
  end_date             DATE         NULL,
  contact_person_name  VARCHAR(255) NULL,
  contact_person_phone VARCHAR(20)  NULL,
  status               ENUM('active','completed','disabled') NOT NULL DEFAULT 'active',
  created_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_campaigns_ngo (ngo_id),
  KEY idx_campaigns_status (status, created_at),
  CONSTRAINT fk_campaign_ngo FOREIGN KEY (ngo_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT chk_campaign_dates CHECK (end_date IS NULL OR start_date IS NULL OR end_date >= start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- requests: help requests posted by people in need (and NGOs asking donors)
-- user_name / user_role are a snapshot at posting time, for display/history.
-- -----------------------------------------------------------------------------
CREATE TABLE requests (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id              INT UNSIGNED NOT NULL,
  user_name            VARCHAR(100) NOT NULL,
  user_role            ENUM('ngo','need') NOT NULL,
  title                VARCHAR(255) NOT NULL,
  description          TEXT         NOT NULL,
  request_type         ENUM('Food','Shelter','Clothes','Medicine','Education','Other') NOT NULL,
  quantity             INT UNSIGNED NOT NULL DEFAULT 1,
  unit                 VARCHAR(50)  NOT NULL DEFAULT 'items',
  location             VARCHAR(255) NOT NULL,
  priority             ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  contact_person_name  VARCHAR(100) NOT NULL,
  contact_person_phone VARCHAR(20)  NOT NULL,
  status               ENUM('pending','completed') NOT NULL DEFAULT 'pending',
  created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_requests_user (user_id),
  KEY idx_requests_status (status, created_at),
  CONSTRAINT fk_request_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT chk_request_quantity CHECK (quantity >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- login_attempts: failed logins, used for brute-force throttling
-- -----------------------------------------------------------------------------
CREATE TABLE login_attempts (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email        VARCHAR(89)  NOT NULL,
  ip_address   VARCHAR(45)  NOT NULL COMMENT 'fits IPv6',
  attempted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attempts_email (email, attempted_at),
  KEY idx_attempts_ip (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
