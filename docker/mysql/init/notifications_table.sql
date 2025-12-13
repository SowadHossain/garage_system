-- Enhanced Notifications System Database Schema
-- Created: December 2024
-- Purpose: Real-time notification system with preferences and history

-- Main notifications table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_type ENUM('staff', 'customer') NOT NULL,
    recipient_id INT NOT NULL,
    sender_type ENUM('staff', 'customer', 'system') DEFAULT 'system',
    sender_id INT NULL,
    
    -- Notification content
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM(
        'appointment_booked',
        'appointment_confirmed',
        'appointment_cancelled',
        'job_assigned',
        'job_started',
        'job_completed',
        'bill_generated',
        'payment_received',
        'payment_reminder',
        'message_received',
        'review_submitted',
        'system_alert'
    ) NOT NULL,
    
    -- Related entities
    related_entity VARCHAR(50) NULL,  -- e.g., 'appointment', 'job', 'bill'
    related_id INT NULL,              -- e.g., appointment_id, job_id, bill_id
    
    -- Status
    is_read BOOLEAN DEFAULT FALSE,
    is_sent BOOLEAN DEFAULT FALSE,
    is_emailed BOOLEAN DEFAULT FALSE,
    
    -- Priority
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    
    -- Indexes for performance
    INDEX idx_recipient (recipient_type, recipient_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created (created_at),
    INDEX idx_type (notification_type),
    INDEX idx_priority (priority),
    INDEX idx_related (related_entity, related_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    user_type ENUM('staff', 'customer') NOT NULL,
    user_id INT NOT NULL,
    
    -- Channel preferences
    enable_browser_notifications BOOLEAN DEFAULT TRUE,
    enable_email_notifications BOOLEAN DEFAULT TRUE,
    enable_sms_notifications BOOLEAN DEFAULT FALSE,
    
    -- Notification type preferences
    notify_appointments BOOLEAN DEFAULT TRUE,
    notify_jobs BOOLEAN DEFAULT TRUE,
    notify_bills BOOLEAN DEFAULT TRUE,
    notify_messages BOOLEAN DEFAULT TRUE,
    notify_reviews BOOLEAN DEFAULT TRUE,
    notify_system BOOLEAN DEFAULT TRUE,
    
    -- Timing preferences
    quiet_hours_start TIME NULL,
    quiet_hours_end TIME NULL,
    
    -- Email preferences
    email_digest ENUM('none', 'daily', 'weekly') DEFAULT 'none',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user (user_type, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create default preferences for existing users
INSERT INTO notification_preferences (user_type, user_id)
SELECT 'staff', staff_id FROM staff
WHERE NOT EXISTS (
    SELECT 1 FROM notification_preferences 
    WHERE user_type = 'staff' AND user_id = staff.staff_id
);

INSERT INTO notification_preferences (user_type, user_id)
SELECT 'customer', customer_id FROM customers
WHERE NOT EXISTS (
    SELECT 1 FROM notification_preferences 
    WHERE user_type = 'customer' AND user_id = customers.customer_id
);

-- Sample notifications for testing
INSERT INTO notifications (
    recipient_type, recipient_id, 
    title, message, notification_type, 
    related_entity, related_id, priority
) VALUES
(
    'customer', 
    1,
    'Appointment Confirmed',
    'Your appointment for Oil Change on 2024-01-15 has been confirmed.',
    'appointment_confirmed',
    'appointment',
    1,
    'normal'
),
(
    'staff',
    2,
    'New Job Assigned',
    'A new job (#123) has been assigned to you: Brake Repair for John Doe.',
    'job_assigned',
    'job',
    1,
    'high'
),
(
    'customer',
    1,
    'Job Completed',
    'Your vehicle service has been completed. Please review your invoice.',
    'job_completed',
    'job',
    1,
    'high'
),
(
    'customer',
    2,
    'Payment Reminder',
    'You have an outstanding invoice of $250.00. Please make payment at your earliest convenience.',
    'payment_reminder',
    'bill',
    1,
    'urgent'
);
