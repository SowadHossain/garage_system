-- =========================================================
--  Screw Dheela Management System - Database Schema
-- =========================================================

CREATE DATABASE IF NOT EXISTS garage_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE garage_db;

-- =========================================================
--  Core user tables
-- =========================================================

CREATE TABLE IF NOT EXISTS customers (
    customer_id       INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(100) NOT NULL,
    phone             VARCHAR(20) UNIQUE,
    email             VARCHAR(100),
    address           VARCHAR(255),
    password_hash     VARCHAR(255),             -- optional: if you allow customer login
    is_email_verified TINYINT(1) DEFAULT 0,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS staff (
    staff_id          INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(100) NOT NULL,
    role              VARCHAR(20) NOT NULL,     -- admin / receptionist / mechanic
    username          VARCHAR(50) NOT NULL UNIQUE,
    email             VARCHAR(100),
    password_hash     VARCHAR(255) NOT NULL,
    is_email_verified TINYINT(1) DEFAULT 0,
    active            TINYINT(1) DEFAULT 1,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
--  Services offered by Screw Dheela
-- =========================================================

CREATE TABLE IF NOT EXISTS services (
    service_id   INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    description  VARCHAR(255),
    base_price   DECIMAL(10,2) NOT NULL DEFAULT 0,
    category     VARCHAR(50)          -- e.g. Engine, Wash, etc.
) ENGINE=InnoDB;

-- =========================================================
--  Vehicles and appointments
-- =========================================================

CREATE TABLE IF NOT EXISTS vehicles (
    vehicle_id      INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    registration_no VARCHAR(20) NOT NULL UNIQUE,
    brand           VARCHAR(50),
    model           VARCHAR(50),
    year            INT,
    vehicle_type    VARCHAR(30),  -- car, bike, etc.
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
    appointment_id       INT AUTO_INCREMENT PRIMARY KEY,
    customer_id          INT NOT NULL,
    vehicle_id           INT NOT NULL,
    appointment_datetime DATETIME NOT NULL,
    problem_description  TEXT,
    status               VARCHAR(20) DEFAULT 'booked',
    created_by_staff_id  INT,
    created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
        ON DELETE CASCADE,
    FOREIGN KEY (created_by_staff_id) REFERENCES staff(staff_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
--  Jobs (actual work) and job services
-- =========================================================

CREATE TABLE IF NOT EXISTS jobs (
    job_id         INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL UNIQUE,   -- 1 job per appointment
    job_date       DATE NOT NULL,
    status         VARCHAR(20) DEFAULT 'open',  -- open/completed/cancelled
    remarks        VARCHAR(255),
    mechanic_id    INT,
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
        ON DELETE CASCADE,
    FOREIGN KEY (mechanic_id) REFERENCES staff(staff_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS job_services (
    job_service_id INT AUTO_INCREMENT PRIMARY KEY,
    job_id         INT NOT NULL,
    service_id     INT NOT NULL,
    quantity       INT NOT NULL DEFAULT 1,
    unit_price     DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (job_id) REFERENCES jobs(job_id)
        ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================
--  Billing
-- =========================================================

CREATE TABLE IF NOT EXISTS bills (
    bill_id        INT AUTO_INCREMENT PRIMARY KEY,
    job_id         INT NOT NULL UNIQUE,   -- 1 bill per job
    bill_date      DATETIME DEFAULT CURRENT_TIMESTAMP,
    subtotal       DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount       DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount   DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(20),          -- cash/card/bkash etc.
    payment_status VARCHAR(20) DEFAULT 'unpaid', -- unpaid/paid
    FOREIGN KEY (job_id) REFERENCES jobs(job_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
--  Chat (conversations & messages)
-- =========================================================

CREATE TABLE IF NOT EXISTS conversations (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    staff_id        INT NOT NULL,
    status          VARCHAR(20) DEFAULT 'open',  -- open/closed
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS messages (
    message_id          INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id     INT NOT NULL,
    sender_type         ENUM('staff','customer') NOT NULL,
    sender_staff_id     INT NULL,
    sender_customer_id  INT NULL,
    content             TEXT NOT NULL,
    sent_at             DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read             TINYINT(1) DEFAULT 0,
    FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id)
        ON DELETE CASCADE,
    FOREIGN KEY (sender_staff_id) REFERENCES staff(staff_id)
        ON DELETE SET NULL,
    FOREIGN KEY (sender_customer_id) REFERENCES customers(customer_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
--  Notification types, preferences, in-app notifications
-- =========================================================

CREATE TABLE IF NOT EXISTS notification_types (
    notification_type_id INT AUTO_INCREMENT PRIMARY KEY,
    code                 VARCHAR(50) NOT NULL UNIQUE,   -- e.g. NEW_MESSAGE
    description          VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_preferences (
    pref_id              INT AUTO_INCREMENT PRIMARY KEY,
    user_type            ENUM('staff','customer') NOT NULL,
    user_id              INT NOT NULL,
    notification_type_id INT NOT NULL,
    email_enabled        TINYINT(1) DEFAULT 0,
    in_app_enabled       TINYINT(1) DEFAULT 1,
    frequency_minutes    INT NULL,                      -- for reminders
    updated_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_type_id) REFERENCES notification_types(notification_type_id)
        ON DELETE CASCADE
    -- user_id refers to either staff or customers depending on user_type (handled in app logic)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    notification_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_type            ENUM('staff','customer') NOT NULL,
    user_id              INT NOT NULL,
    notification_type_id INT NOT NULL,
    title                VARCHAR(100),
    message              VARCHAR(255),
    link_url             VARCHAR(255),
    is_read              TINYINT(1) DEFAULT 0,
    created_at           DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_type_id) REFERENCES notification_types(notification_type_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
--  OTP for signup/login
-- =========================================================

CREATE TABLE IF NOT EXISTS login_otps (
    otp_id           INT AUTO_INCREMENT PRIMARY KEY,
    user_type        ENUM('staff','customer') NOT NULL,
    user_id          INT NOT NULL,
    otp_code         VARCHAR(10) NOT NULL,
    purpose          VARCHAR(20) NOT NULL,   -- signup/login/reset_password
    delivery_channel ENUM('email','sms') DEFAULT 'email',
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at       DATETIME NOT NULL,
    used             TINYINT(1) DEFAULT 0
) ENGINE=InnoDB;

-- =========================================================
--  Broadcasts & email queue
-- =========================================================

CREATE TABLE IF NOT EXISTS broadcasts (
    broadcast_id        INT AUTO_INCREMENT PRIMARY KEY,
    title               VARCHAR(150) NOT NULL,
    body                TEXT NOT NULL,
    created_by_staff_id INT,
    target_user_type    ENUM('staff','customer','both') DEFAULT 'customer',
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    scheduled_at        DATETIME NULL,
    sent_at             DATETIME NULL,
    status              VARCHAR(20) DEFAULT 'draft', -- draft/scheduled/sent/cancelled
    FOREIGN KEY (created_by_staff_id) REFERENCES staff(staff_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS broadcast_recipients (
    broadcast_recipient_id INT AUTO_INCREMENT PRIMARY KEY,
    broadcast_id           INT NOT NULL,
    user_type              ENUM('staff','customer') NOT NULL,
    user_id                INT NOT NULL,
    channel                ENUM('email','in_app') NOT NULL,
    sent_at                DATETIME NULL,
    read_at                DATETIME NULL,
    status                 VARCHAR(20) DEFAULT 'queued', -- queued/sent/failed
    FOREIGN KEY (broadcast_id) REFERENCES broadcasts(broadcast_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS email_queue (
    email_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_type      ENUM('staff','customer') NOT NULL,
    user_id        INT NOT NULL,
    subject        VARCHAR(150) NOT NULL,
    body           TEXT NOT NULL,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    send_after     DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_at        DATETIME NULL,
    status         VARCHAR(20) DEFAULT 'queued' -- queued/sent/failed
) ENGINE=InnoDB;

-- =========================================================
--  VIEWS for simplified data retrieval
-- =========================================================

-- Customer summary with aggregates (demonstrates SUM, COUNT, GROUP BY)
CREATE OR REPLACE VIEW view_customer_summary AS
SELECT 
    c.customer_id,
    c.name,
    c.email,
    c.phone,
    c.address,
    COUNT(DISTINCT v.vehicle_id) as vehicle_count,
    COUNT(DISTINCT a.appointment_id) as appointment_count,
    COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.appointment_id END) as completed_appointments,
    COALESCE(SUM(b.total_amount), 0) as total_spent,
    COALESCE(AVG(b.total_amount), 0) as avg_bill_amount,
    MAX(a.appointment_datetime) as last_appointment_date
FROM customers c
LEFT JOIN vehicles v ON c.customer_id = v.customer_id
LEFT JOIN appointments a ON c.customer_id = a.customer_id
LEFT JOIN jobs j ON a.appointment_id = j.appointment_id
LEFT JOIN bills b ON j.job_id = b.job_id
GROUP BY c.customer_id, c.name, c.email, c.phone, c.address;

-- Pending work overview (demonstrates multi-table joins)
CREATE OR REPLACE VIEW view_pending_work AS
SELECT 
    a.appointment_id,
    a.appointment_datetime,
    a.problem_description,
    a.status,
    c.customer_id,
    c.name as customer_name,
    c.phone as customer_phone,
    c.email as customer_email,
    v.vehicle_id,
    v.registration_no,
    v.brand,
    v.model,
    v.year,
    s.staff_id as assigned_staff_id,
    s.name as assigned_staff_name
FROM appointments a
JOIN customers c ON a.customer_id = c.customer_id
LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
LEFT JOIN staff s ON a.created_by_staff_id = s.staff_id
WHERE a.status IN ('booked', 'confirmed', 'pending');

-- Revenue detail view (demonstrates complex joins)
CREATE OR REPLACE VIEW view_revenue_detail AS
SELECT 
    b.bill_id,
    b.bill_date,
    b.total_amount,
    b.subtotal,
    b.tax_amount,
    b.discount,
    b.payment_status,
    b.payment_method,
    j.job_id,
    j.job_date,
    j.status as job_status,
    a.appointment_id,
    a.appointment_datetime,
    c.customer_id,
    c.name as customer_name,
    c.email as customer_email,
    c.phone as customer_phone,
    v.vehicle_id,
    v.registration_no,
    v.brand,
    v.model,
    m.staff_id as mechanic_id,
    m.name as mechanic_name
FROM bills b
JOIN jobs j ON b.job_id = j.job_id
JOIN appointments a ON j.appointment_id = a.appointment_id
JOIN customers c ON a.customer_id = c.customer_id
LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
LEFT JOIN staff m ON j.mechanic_id = m.staff_id;
