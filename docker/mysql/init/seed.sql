USE garage_db;

-- =========================
-- Screw Dheela Management System
-- =========================

-- Add a staff account (id chosen to avoid colliding with create_admin.php)
-- Login credentials for staff: username = 'admin_user', password = 'staffpass'
INSERT INTO staff (staff_id, name, role, username, email, password_hash, is_email_verified, active, created_at)
VALUES (1000, 'Admin User', 'admin', 'admin_user', 'admin@example.com', '$2y$10$QY05j2FE31Am7yuPi0mIhOILHkCwfPeI6cM7tit8dWiqQcVk0gug6', 1, 1, NOW())
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    role = VALUES(role),
    username = VALUES(username),
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    is_email_verified = VALUES(is_email_verified),
    active = VALUES(active),
    created_at = VALUES(created_at);

-- Add a couple of customers
-- Login credentials for Alice: email = 'alice@example.com', password = 'customer123'
-- Login credentials for Bob: email = 'bob@example.com', password = 'customer123'
INSERT INTO customers (customer_id, name, phone, email, address, password_hash, is_email_verified, created_at)
VALUES
  (2000, 'Alice Johnson', '+15551230001', 'alice@example.com', '123 Main St', '$2y$10$QY05j2FE31Am7yuPi0mIhOILHkCwfPeI6cM7tit8dWiqQcVk0gug6', 1, NOW()),
  (2001, 'Bob Smith', '+15551230002', 'bob@example.com', '456 Oak Ave', '$2y$10$QY05j2FE31Am7yuPi0mIhOILHkCwfPeI6cM7tit8dWiqQcVk0gug6', 0, NOW())
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    phone = VALUES(phone),
    email = VALUES(email),
    address = VALUES(address),
    password_hash = VALUES(password_hash),
    is_email_verified = VALUES(is_email_verified);

-- Add services
INSERT INTO services (service_id, name, description, base_price, category)
VALUES
  (3000, 'Oil Change', 'Basic engine oil and filter change', 29.99, 'Maintenance'),
  (3001, 'Full Service', 'Comprehensive multi-point inspection and servicing', 199.99, 'Maintenance'),
  (3002, 'Wheel Alignment', '4-wheel alignment', 49.99, 'Repair')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Add a vehicle for Alice
INSERT INTO vehicles (vehicle_id, customer_id, registration_no, brand, model, year, vehicle_type)
VALUES (4000, 2000, 'ABC-1234', 'Toyota', 'Corolla', 2015, 'car')
ON DUPLICATE KEY UPDATE registration_no = VALUES(registration_no);

-- Create an appointment for Alice
INSERT INTO appointments (appointment_id, customer_id, vehicle_id, appointment_datetime, problem_description, status, created_by_staff_id, created_at)
VALUES (5000, 2000, 4000, DATE_ADD(NOW(), INTERVAL 2 DAY), 'Regular oil change', 'booked', 1000, NOW())
ON DUPLICATE KEY UPDATE appointment_datetime = VALUES(appointment_datetime);

-- Convert appointment to a job (1 job per appointment)
INSERT INTO jobs (job_id, appointment_id, job_date, status, remarks, mechanic_id)
VALUES (6000, 5000, CURDATE(), 'open', 'Started diagnostics', 1000)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Add job services
INSERT INTO job_services (job_service_id, job_id, service_id, quantity, unit_price)
VALUES
  (7000, 6000, 3000, 1, 29.99),
  (7001, 6000, 3002, 1, 49.99)
ON DUPLICATE KEY UPDATE unit_price = VALUES(unit_price);

-- Generate a bill for the job
INSERT INTO bills (bill_id, job_id, bill_date, subtotal, tax_amount, discount, total_amount, payment_method, payment_status)
VALUES (8000, 6000, NOW(), 79.98, 7.20, 0.00, 87.18, 'cash', 'unpaid')
ON DUPLICATE KEY UPDATE total_amount = VALUES(total_amount);

-- Conversations & messages between customer and staff
INSERT INTO conversations (conversation_id, customer_id, staff_id, status, created_at)
VALUES (9000, 2000, 1000, 'open', NOW())
ON DUPLICATE KEY UPDATE status = VALUES(status);

INSERT INTO messages (message_id, conversation_id, sender_type, sender_staff_id, sender_customer_id, content, sent_at, is_read)
VALUES
  (9100, 9000, 'customer', NULL, 2000, 'Hello, I would like to confirm my appointment.', NOW(), 0),
  (9101, 9000, 'staff', 1000, NULL, 'We have you scheduled for an oil change in 2 days.', NOW(), 0)
ON DUPLICATE KEY UPDATE sent_at = VALUES(sent_at);

-- Notification types
INSERT INTO notification_types (notification_type_id, code, description)
VALUES
  (10000, 'APPOINTMENT_REMINDER', 'Reminder for upcoming appointment'),
  (10001, 'BILL_GENERATED', 'Notify when a bill is generated')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Broadcast example
INSERT INTO broadcasts (broadcast_id, title, body, created_by_staff_id, target_user_type, created_at, scheduled_at, status)
VALUES (11000, 'Holiday Hours', 'We will be closed on public holidays.', 1000, 'both', NOW(), NULL, 'sent')
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- Email queue example (queued)
INSERT INTO email_queue (email_id, user_type, user_id, subject, body, created_at, send_after, status)
VALUES (12000, 'customer', 2000, 'Appointment Reminder', 'This is a reminder for your upcoming appointment.', NOW(), NOW(), 'queued')
ON DUPLICATE KEY UPDATE subject = VALUES(subject);

-- End of seed
