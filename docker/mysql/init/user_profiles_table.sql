-- User Profile Management Database Schema
-- Created: December 2024
-- Purpose: Profile photos, password history, 2FA, security questions

-- Add profile photo columns to existing tables (skip if exists)
SET @stmt = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'profile_photo') = 0,
    'ALTER TABLE staff ADD COLUMN profile_photo VARCHAR(255) NULL AFTER email',
    'SELECT 1'
));
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'bio') = 0,
    'ALTER TABLE staff ADD COLUMN bio TEXT NULL AFTER email',
    'SELECT 1'
));
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'last_password_change') = 0,
    'ALTER TABLE staff ADD COLUMN last_password_change TIMESTAMP NULL AFTER password_hash',
    'SELECT 1'
));
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'two_factor_enabled') = 0,
    'ALTER TABLE staff ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE AFTER last_password_change',
    'SELECT 1'
));
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'two_factor_secret') = 0,
    'ALTER TABLE staff ADD COLUMN two_factor_secret VARCHAR(100) NULL AFTER two_factor_enabled',
    'SELECT 1'
));
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'profile_completed') = 0,
    'ALTER TABLE staff ADD COLUMN profile_completed INT DEFAULT 0 AFTER two_factor_secret',
    'SELECT 1'
));
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Same for customers table
SET @stmt = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'profile_photo') = 0,
    'ALTER TABLE customers ADD COLUMN profile_photo VARCHAR(255) NULL AFTER email',
    'SELECT 1'
));
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'bio') = 0,
    'ALTER TABLE customers ADD COLUMN bio TEXT NULL AFTER email',
    'SELECT 1'
));
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'last_password_change') = 0,
    'ALTER TABLE customers ADD COLUMN last_password_change TIMESTAMP NULL AFTER password_hash',
    'SELECT 1'
));
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'two_factor_enabled') = 0,
    'ALTER TABLE customers ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE AFTER last_password_change',
    'SELECT 1'
));
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'two_factor_secret') = 0,
    'ALTER TABLE customers ADD COLUMN two_factor_secret VARCHAR(100) NULL AFTER two_factor_enabled',
    'SELECT 1'
));
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'profile_completed') = 0,
    'ALTER TABLE customers ADD COLUMN profile_completed INT DEFAULT 0 AFTER two_factor_secret',
    'SELECT 1'
));
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Password history table (prevent password reuse)
CREATE TABLE IF NOT EXISTS password_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('staff', 'customer') NOT NULL,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(100) NULL,
    change_reason VARCHAR(255) NULL,
    
    INDEX idx_user (user_type, user_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Security questions table
CREATE TABLE IF NOT EXISTS security_questions (
    question_id INT PRIMARY KEY AUTO_INCREMENT,
    question_text VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User security answers table
CREATE TABLE IF NOT EXISTS user_security_answers (
    answer_id INT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('staff', 'customer') NOT NULL,
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_question (user_type, user_id, question_id),
    FOREIGN KEY (question_id) REFERENCES security_questions(question_id) ON DELETE CASCADE,
    INDEX idx_user (user_type, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email verification tokens
CREATE TABLE IF NOT EXISTS email_verification_tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('staff', 'customer') NOT NULL,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_token (token),
    INDEX idx_user (user_type, user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('staff', 'customer') NOT NULL,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_token (token),
    INDEX idx_user (user_type, user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default security questions
INSERT INTO security_questions (question_text) VALUES
('What was the name of your first pet?'),
('What city were you born in?'),
('What is your mother''s maiden name?'),
('What was the make of your first car?'),
('What is your favorite book?'),
('What was the name of your elementary school?'),
('In what city did you meet your spouse/partner?'),
('What is your favorite movie?'),
('What is your favorite food?'),
('What was your childhood nickname?');

-- Create uploads directory structure (to be created on filesystem)
-- /garage_system/uploads/profiles/staff/
-- /garage_system/uploads/profiles/customers/

-- Sample data: Add profile photos to existing users (if files exist)
UPDATE staff SET profile_photo = 'default-avatar.png', profile_completed = 60 WHERE staff_id = 1;
UPDATE customers SET profile_photo = 'default-avatar.png', profile_completed = 50 WHERE customer_id = 1;
