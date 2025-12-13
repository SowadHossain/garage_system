-- Activity Logs & Audit Trail Database Schema
-- Created: December 2024
-- Purpose: Comprehensive tracking of all user actions and system events

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- User information
    user_type ENUM('staff', 'customer', 'system') NOT NULL,
    user_id INT NULL,
    username VARCHAR(100) NULL,
    
    -- Action details
    action VARCHAR(100) NOT NULL,
    action_type ENUM(
        'create', 'read', 'update', 'delete',
        'login', 'logout', 'login_failed',
        'status_change', 'permission_change',
        'export', 'import', 'other'
    ) NOT NULL,
    
    -- Entity details
    entity_type VARCHAR(50) NULL,  -- e.g., 'customer', 'appointment', 'job', 'bill'
    entity_id INT NULL,
    
    -- Change tracking
    old_values TEXT NULL,  -- JSON format for before state
    new_values TEXT NULL,  -- JSON format for after state
    
    -- Request details
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    request_method VARCHAR(10) NULL,
    request_url VARCHAR(500) NULL,
    
    -- Status
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    status ENUM('success', 'failed', 'pending') DEFAULT 'success',
    
    -- Metadata
    additional_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes for fast searching
    INDEX idx_user (user_type, user_id),
    INDEX idx_action (action),
    INDEX idx_action_type (action_type),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at),
    INDEX idx_severity (severity),
    INDEX idx_ip_address (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create a table for login attempts tracking (security)
CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    user_type ENUM('staff', 'customer') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    status ENUM('success', 'failed') NOT NULL,
    failure_reason VARCHAR(255) NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample activity logs for testing
INSERT INTO activity_logs (
    user_type, user_id, username, action, action_type, 
    entity_type, entity_id, description, 
    ip_address, severity, status
) VALUES
(
    'staff', 1, 'admin_user',
    'Login Successful',
    'login',
    NULL, NULL,
    'User logged in successfully',
    '192.168.1.100',
    'info',
    'success'
),
(
    'staff', 1, 'admin_user',
    'Created Customer',
    'create',
    'customer', 1,
    'Created new customer record for John Doe',
    '192.168.1.100',
    'info',
    'success'
),
(
    'staff', 2, 'receptionist_user',
    'Updated Appointment Status',
    'status_change',
    'appointment', 1,
    'Changed appointment status from "booked" to "confirmed"',
    '192.168.1.101',
    'info',
    'success'
),
(
    'customer', 1, 'john.doe@example.com',
    'Login Failed',
    'login_failed',
    NULL, NULL,
    'Invalid password attempt',
    '192.168.1.105',
    'warning',
    'failed'
),
(
    'staff', 3, 'mechanic_user',
    'Completed Job',
    'status_change',
    'job', 1,
    'Marked job #1 as completed',
    '192.168.1.102',
    'info',
    'success'
),
(
    'staff', 1, 'admin_user',
    'Deleted Vehicle',
    'delete',
    'vehicle', 999,
    'Deleted vehicle record (License: OLD-CAR)',
    '192.168.1.100',
    'warning',
    'success'
);

-- Insert sample login attempts
INSERT INTO login_attempts (
    username, user_type, ip_address, status, failure_reason
) VALUES
('admin_user', 'staff', '192.168.1.100', 'success', NULL),
('john.doe@example.com', 'customer', '192.168.1.105', 'failed', 'Invalid password'),
('john.doe@example.com', 'customer', '192.168.1.105', 'failed', 'Invalid password'),
('john.doe@example.com', 'customer', '192.168.1.105', 'success', NULL),
('receptionist_user', 'staff', '192.168.1.101', 'success', NULL);

-- Create a view for recent activity summary
CREATE OR REPLACE VIEW recent_activity_summary AS
SELECT 
    DATE(created_at) as activity_date,
    action_type,
    COUNT(*) as action_count,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count
FROM activity_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at), action_type
ORDER BY activity_date DESC, action_count DESC;

-- Create a view for user activity summary
CREATE OR REPLACE VIEW user_activity_summary AS
SELECT 
    user_type,
    user_id,
    username,
    COUNT(*) as total_actions,
    COUNT(CASE WHEN action_type = 'login' THEN 1 END) as login_count,
    COUNT(CASE WHEN action_type = 'create' THEN 1 END) as create_count,
    COUNT(CASE WHEN action_type = 'update' THEN 1 END) as update_count,
    COUNT(CASE WHEN action_type = 'delete' THEN 1 END) as delete_count,
    MAX(created_at) as last_activity
FROM activity_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY user_type, user_id, username
ORDER BY total_actions DESC;
