-- Initial schema for Screw Dheela Management System (minimal)
-- Creates a staff table used for login and admin creation.

CREATE TABLE IF NOT EXISTS `staff` (
  `staff_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'staff',
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(150) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `is_email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- You can extend this file with other tables (customers, vehicles, appointments, jobs, bills).
