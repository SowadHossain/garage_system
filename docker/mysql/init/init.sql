-- init.sql for Screw Dheela Garage System (appointment flow focused)
-- No notifications. No activity logs.
-- MySQL/MariaDB compatible.

SET NAMES utf8mb4;
SET time_zone = "+00:00";
SET sql_mode = "STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION";

DROP DATABASE IF EXISTS garage_system;
CREATE DATABASE garage_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE garage_system;

CREATE DATABASE IF NOT EXISTS garage_system;
CREATE USER IF NOT EXISTS 'garage_user'@'%' IDENTIFIED BY 'GaragePass123!';
GRANT ALL PRIVILEGES ON garage_system.* TO 'garage_user'@'%';
FLUSH PRIVILEGES;

-- ----------------------------
-- Roles + Staff (Receptionist, Mechanic, Admin)
-- ----------------------------
CREATE TABLE roles (
  role_id INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO roles (role_name) VALUES
('admin'), ('receptionist'), ('mechanic');

CREATE TABLE staff (
  staff_id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_staff_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB;

-- Mechanic daily capacity: 1–4 jobs per day (as you described)
CREATE TABLE mechanic_schedule (
  schedule_id INT AUTO_INCREMENT PRIMARY KEY,
  mechanic_id INT NOT NULL,
  work_date DATE NOT NULL,
  capacity TINYINT NOT NULL DEFAULT 4,      -- max slots in a day
  reserved_count TINYINT NOT NULL DEFAULT 0, -- how many booked
  UNIQUE KEY uq_mech_day (mechanic_id, work_date),
  CONSTRAINT chk_capacity CHECK (capacity BETWEEN 1 AND 4),
  CONSTRAINT chk_reserved CHECK (reserved_count BETWEEN 0 AND 4),
  CONSTRAINT fk_sched_mech FOREIGN KEY (mechanic_id) REFERENCES staff(staff_id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------
-- Customers + Vehicles
-- ----------------------------
CREATE TABLE customers (
  customer_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NULL UNIQUE,
  phone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NULL, -- allow no-login customers
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE vehicles (
  vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  plate_no VARCHAR(30) NOT NULL,
  make VARCHAR(60) NOT NULL,
  model VARCHAR(60) NOT NULL,
  year SMALLINT NULL,
  color VARCHAR(40) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_plate (plate_no),
  KEY idx_vehicle_customer (customer_id),
  CONSTRAINT fk_vehicle_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------
-- Appointment Flow
-- requested -> booked -> in_progress -> completed/cancelled
-- ----------------------------
CREATE TABLE appointments (
  appointment_id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  vehicle_id INT NOT NULL,

  -- Customer request
  requested_date DATE NOT NULL,
  requested_slot TINYINT NOT NULL, -- 1..4
  problem_text TEXT NOT NULL,

  status ENUM('requested','booked','in_progress','completed','cancelled')
    NOT NULL DEFAULT 'requested',

  -- Receptionist assignment
  mechanic_id INT NULL,
  assigned_by_staff_id INT NULL,
  assigned_at DATETIME NULL,

  -- Optional notes
  receptionist_note TEXT NULL,

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  KEY idx_appt_day (requested_date),
  KEY idx_appt_status (status),
  KEY idx_appt_mech_day (mechanic_id, requested_date),

  CONSTRAINT chk_slot CHECK (requested_slot BETWEEN 1 AND 4),

  CONSTRAINT fk_appt_customer FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_appt_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_appt_mechanic FOREIGN KEY (mechanic_id) REFERENCES staff(staff_id)
    ON DELETE SET NULL,
  CONSTRAINT fk_appt_assigned_by FOREIGN KEY (assigned_by_staff_id) REFERENCES staff(staff_id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

-- ----------------------------
-- Services performed (catalog)
-- ----------------------------
CREATE TABLE services (
  service_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------
-- Jobs (mechanic work order tied to appointment)
-- ----------------------------
CREATE TABLE jobs (
  job_id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT NOT NULL UNIQUE, -- 1 job per appointment
  mechanic_id INT NULL,               -- copied from appointment at start
  status ENUM('open','in_progress','completed','cancelled')
    NOT NULL DEFAULT 'open',
  started_at DATETIME NULL,
  completed_at DATETIME NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  KEY idx_job_status (status),
  KEY idx_job_mech (mechanic_id),

  CONSTRAINT fk_job_appt FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_job_mech FOREIGN KEY (mechanic_id) REFERENCES staff(staff_id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE job_services (
  job_service_id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  service_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  line_total DECIMAL(10,2) AS (qty * unit_price) STORED,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  KEY idx_js_job (job_id),
  KEY idx_js_service (service_id),

  CONSTRAINT chk_qty CHECK (qty >= 1),
  CONSTRAINT fk_js_job FOREIGN KEY (job_id) REFERENCES jobs(job_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_js_service FOREIGN KEY (service_id) REFERENCES services(service_id)
) ENGINE=InnoDB;

-- ----------------------------
-- Bills + Payments (receptionist confirms payment)
-- ----------------------------
CREATE TABLE bills (
  bill_id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL UNIQUE,
  bill_no VARCHAR(40) NOT NULL UNIQUE,

  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(10,2) NOT NULL DEFAULT 0.00,

  payment_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',

  created_by_staff_id INT NOT NULL,  -- mechanic who generated bill
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  -- Receptionist payment confirmation fields
  payment_method ENUM('cash','bkash','nagad','card','bank','other') NULL,
  paid_by_staff_id INT NULL,         -- receptionist who confirmed
  paid_at DATETIME NULL,

  KEY idx_bill_status (payment_status),

  CONSTRAINT fk_bill_job FOREIGN KEY (job_id) REFERENCES jobs(job_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_bill_created_by FOREIGN KEY (created_by_staff_id) REFERENCES staff(staff_id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_bill_paid_by FOREIGN KEY (paid_by_staff_id) REFERENCES staff(staff_id)
    ON DELETE SET NULL
) ENGINE=InnoDB;

-- Optional: bill line items copied from job services at billing time
CREATE TABLE bill_items (
  bill_item_id INT AUTO_INCREMENT PRIMARY KEY,
  bill_id INT NOT NULL,
  description VARCHAR(255) NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  line_total DECIMAL(10,2) AS (qty * unit_price) STORED,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  KEY idx_bi_bill (bill_id),
  CONSTRAINT chk_bi_qty CHECK (qty >= 1),
  CONSTRAINT fk_bi_bill FOREIGN KEY (bill_id) REFERENCES bills(bill_id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- View: Customer Summary (for receptionist dashboard)
CREATE OR REPLACE VIEW view_customer_summary AS
SELECT
    c.customer_id,
    c.name,
    c.email,
    c.phone,

    /* Vehicles */
    COUNT(DISTINCT v.vehicle_id) AS vehicle_count,

    /* Appointments */
    COUNT(DISTINCT a.appointment_id) AS appointment_count,
    SUM(a.status = 'completed') AS completed_appointments,
    MAX(a.requested_date) AS last_appointment_date,

    /* Billing */
    COALESCE(
        SUM(
            CASE 
                WHEN b.payment_status = 'paid' 
                THEN b.total 
                ELSE 0 
            END
        ), 0
    ) AS total_spent,

    AVG(
        CASE 
            WHEN b.payment_status = 'paid' 
            THEN b.total 
            ELSE NULL 
        END
    ) AS avg_bill_amount

FROM customers c
LEFT JOIN vehicles v
    ON v.customer_id = c.customer_id
LEFT JOIN appointments a
    ON a.customer_id = c.customer_id
LEFT JOIN jobs j
    ON j.appointment_id = a.appointment_id
LEFT JOIN bills b
    ON b.job_id = j.job_id

GROUP BY
    c.customer_id,
    c.name,
    c.email,
    c.phone;
