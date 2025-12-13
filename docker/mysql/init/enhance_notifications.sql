-- Enhance existing notifications system
-- Add more notification types and improve functionality

-- Add more notification types
INSERT IGNORE INTO notification_types (notification_type_id, code, description) VALUES
(10002, 'APPOINTMENT_CONFIRMED', 'Appointment has been confirmed'),
(10003, 'APPOINTMENT_CANCELLED', 'Appointment has been cancelled'),
(10004, 'JOB_ASSIGNED', 'New job has been assigned'),
(10005, 'JOB_STARTED', 'Job work has started'),
(10006, 'JOB_COMPLETED', 'Job has been completed'),
(10007, 'PAYMENT_RECEIVED', 'Payment has been received'),
(10008, 'PAYMENT_REMINDER', 'Payment reminder'),
(10009, 'MESSAGE_RECEIVED', 'New message received'),
(10010, 'REVIEW_SUBMITTED', 'New review submitted'),
(10011, 'SYSTEM_ALERT', 'System alert or announcement');

-- Add additional columns to notifications table for better tracking (ignore if already exists)
SET @stmt = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'priority') = 0,
    'ALTER TABLE notifications ADD COLUMN priority ENUM(''low'', ''normal'', ''high'', ''urgent'') DEFAULT ''normal'' AFTER is_read',
    'SELECT ''Column priority already exists'' AS msg'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'related_entity') = 0,
    'ALTER TABLE notifications ADD COLUMN related_entity VARCHAR(50) NULL AFTER is_read',
    'SELECT ''Column related_entity already exists'' AS msg'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'related_id') = 0,
    'ALTER TABLE notifications ADD COLUMN related_id INT NULL AFTER related_entity',
    'SELECT ''Column related_id already exists'' AS msg'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'read_at') = 0,
    'ALTER TABLE notifications ADD COLUMN read_at TIMESTAMP NULL AFTER is_read',
    'SELECT ''Column read_at already exists'' AS msg'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'sender_type') = 0,
    'ALTER TABLE notifications ADD COLUMN sender_type ENUM(''staff'', ''customer'', ''system'') DEFAULT ''system'' AFTER user_id',
    'SELECT ''Column sender_type already exists'' AS msg'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'notifications' AND COLUMN_NAME = 'sender_id') = 0,
    'ALTER TABLE notifications ADD COLUMN sender_id INT NULL AFTER sender_type',
    'SELECT ''Column sender_id already exists'' AS msg'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add indexes for better performance (ignore if already exist)
CREATE INDEX idx_notifications_priority ON notifications(priority);
CREATE INDEX idx_notifications_related ON notifications(related_entity, related_id);
CREATE INDEX idx_notifications_created ON notifications(created_at);
CREATE INDEX idx_notifications_user_read ON notifications(user_type, user_id, is_read);

-- Enhance notification_preferences with more options
SET @stmt = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'notification_preferences' AND COLUMN_NAME = 'sms_enabled') = 0,
    'ALTER TABLE notification_preferences ADD COLUMN sms_enabled TINYINT(1) DEFAULT 0 AFTER email_enabled',
    'SELECT ''Column sms_enabled already exists'' AS msg'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'notification_preferences' AND COLUMN_NAME = 'quiet_hours_start') = 0,
    'ALTER TABLE notification_preferences ADD COLUMN quiet_hours_start TIME NULL AFTER in_app_enabled',
    'SELECT ''Column quiet_hours_start already exists'' AS msg'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'notification_preferences' AND COLUMN_NAME = 'quiet_hours_end') = 0,
    'ALTER TABLE notification_preferences ADD COLUMN quiet_hours_end TIME NULL AFTER quiet_hours_start',
    'SELECT ''Column quiet_hours_end already exists'' AS msg'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @stmt = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'garage_db' AND TABLE_NAME = 'notification_preferences' AND COLUMN_NAME = 'email_digest') = 0,
    'ALTER TABLE notification_preferences ADD COLUMN email_digest ENUM(''none'', ''daily'', ''weekly'') DEFAULT ''none'' AFTER quiet_hours_end',
    'SELECT ''Column email_digest already exists'' AS msg'
);
PREPARE stmt FROM @stmt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Create default preferences for new notification types
INSERT IGNORE INTO notification_preferences (user_type, user_id, notification_type_id, email_enabled, in_app_enabled)
SELECT 'staff', staff_id, nt.notification_type_id, 1, 1
FROM staff
CROSS JOIN notification_types nt
WHERE NOT EXISTS (
    SELECT 1 FROM notification_preferences np
    WHERE np.user_type = 'staff'
    AND np.user_id = staff.staff_id
    AND np.notification_type_id = nt.notification_type_id
);

INSERT IGNORE INTO notification_preferences (user_type, user_id, notification_type_id, email_enabled, in_app_enabled)
SELECT 'customer', customer_id, nt.notification_type_id, 1, 1
FROM customers
CROSS JOIN notification_types nt
WHERE NOT EXISTS (
    SELECT 1 FROM notification_preferences np
    WHERE np.user_type = 'customer'
    AND np.user_id = customers.customer_id
    AND np.notification_type_id = nt.notification_type_id
);

-- Insert some sample notifications for testing
INSERT INTO notifications (
    user_type, user_id, notification_type_id,
    title, message, link_url, priority, related_entity, related_id
)
SELECT
    'customer',
    c.customer_id,
    10002,
    'Appointment Confirmed',
    CONCAT('Your appointment on ', DATE_FORMAT(a.appointment_datetime, '%M %d, %Y at %h:%i %p'), ' has been confirmed.'),
    CONCAT('/appointments/view_appointments.php?id=', a.appointment_id),
    'normal',
    'appointment',
    a.appointment_id
FROM customers c
INNER JOIN appointments a ON c.customer_id = a.customer_id
WHERE a.status = 'confirmed'
AND NOT EXISTS (
    SELECT 1 FROM notifications n
    WHERE n.user_type = 'customer'
    AND n.user_id = c.customer_id
    AND n.notification_type_id = 10002
    AND n.related_entity = 'appointment'
    AND n.related_id = a.appointment_id
)
LIMIT 3;

-- Sample job assignment notification
INSERT INTO notifications (
    user_type, user_id, notification_type_id,
    title, message, link_url, priority, related_entity, related_id, sender_type
)
SELECT
    'staff',
    j.assigned_mechanic_id,
    10004,
    'New Job Assigned',
    CONCAT('You have been assigned a new job for ', v.make, ' ', v.model, ' (', v.license_plate, ')'),
    CONCAT('/jobs/list.php?id=', j.job_id),
    'high',
    'job',
    j.job_id,
    'system'
FROM jobs j
INNER JOIN appointments a ON j.appointment_id = a.appointment_id
INNER JOIN vehicles v ON a.vehicle_id = v.vehicle_id
WHERE j.assigned_mechanic_id IS NOT NULL
AND j.status IN ('pending', 'in_progress')
AND NOT EXISTS (
    SELECT 1 FROM notifications n
    WHERE n.user_type = 'staff'
    AND n.user_id = j.assigned_mechanic_id
    AND n.notification_type_id = 10004
    AND n.related_entity = 'job'
    AND n.related_id = j.job_id
)
LIMIT 3;
