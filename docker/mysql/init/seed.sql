-- seed.sql (complete, ready-to-run)
-- Uses fixed IDs so your demo data is stable every time.
-- Passwords are commented in plaintext for easy access.

USE garage_system;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Roles (fixed IDs)
-- ----------------------------
INSERT INTO roles (role_id, role_name) VALUES
(1, 'admin'),
(2, 'receptionist'),
(3, 'mechanic')
ON DUPLICATE KEY UPDATE role_name = VALUES(role_name);

-- ----------------------------
-- Staff accounts (fixed IDs)
-- ----------------------------
-- LOGIN CREDENTIALS:
-- Admin:        admin@screwdheela.local        Password: Admin@1234
-- Receptionist: receptionist@screwdheela.local Password: Recpt@1234
-- Mechanic 1:   moin@screwdheela.local         Password: MechMoin@1234
-- Mechanic 2:   nila@screwdheela.local         Password: MechNila@1234

INSERT INTO staff (staff_id, role_id, name, email, phone, password_hash, is_active) VALUES
(1000, 1, 'Admin User',        'admin@screwdheela.local',        '01700000001', '$2y$10$6LRCgb66oJN4jJxEUnLiwu/hBBYdfVfZi9aa9Gjzz0VUEK8XEL2IW', 1),
(1001, 2, 'Receptionist Riya', 'receptionist@screwdheela.local', '01700000002', '$2y$10$d6NoPRDnqDlOT6U9KbMS7u10UHRuPv8szVd9C0B.OBRCxvuPe0/wK', 1),
(1002, 3, 'Mechanic Moin',     'moin@screwdheela.local',         '01700000003', '$2y$10$8PvrON7m.JptMUP2fmdYeu6yThXz.68G3goH9GCHAHvNtZ0eNE4ra', 1),
(1003, 3, 'Mechanic Nila',     'nila@screwdheela.local',         '01700000004', '$2y$10$4UcKf30N4Gy1phZjWl8euOidQn/DOoWu/h4kzttor8LdkJ5fjPJzK', 1)
ON DUPLICATE KEY UPDATE
  role_id = VALUES(role_id),
  name = VALUES(name),
  phone = VALUES(phone),
  password_hash = VALUES(password_hash),
  is_active = VALUES(is_active);

-- ----------------------------
-- Customer accounts (fixed IDs)
-- ----------------------------
-- CUSTOMER LOGIN (if you use customer login):
-- rahim@gmail.com  Password: Cust@1234
-- sadia@gmail.com  Password: Cust@1234
-- tanvir@gmail.com Password: Cust@1234

INSERT INTO customers (customer_id, name, email, phone, password_hash) VALUES
(2000, 'Rahim Uddin',  'rahim@gmail.com',  '01810000001', '$2y$10$aPRucdNaefOut3OxCpaf6.rkjUAfvkL.4IkloLZvL//kDarFIfbxm'),
(2001, 'Sadia Islam',  'sadia@gmail.com',  '01810000002', '$2y$10$AwvgpSgaWB9tD7N2Kuocs.Vjo.93DlFoOYsmfzTWMLtJlXAD9lC16'),
(2002, 'Tanvir Ahmed', 'tanvir@gmail.com', '01810000003', '$2y$10$GRkoifr83C2oB.re3tEgbOB8ToR.3ovv5pqyUnICtH1PuHO4D.Ub6')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  phone = VALUES(phone),
  password_hash = VALUES(password_hash);

-- ----------------------------
-- Vehicles (fixed IDs)
-- ----------------------------
INSERT INTO vehicles (vehicle_id, customer_id, plate_no, make, model, year, color) VALUES
(3000, 2000, 'DHA-11-1234', 'Toyota', 'Corolla', 2016, 'White'),
(3001, 2000, 'DHA-12-9876', 'Honda',  'Civic',   2018, 'Black'),
(3002, 2001, 'DHA-15-2468', 'Suzuki', 'Swift',   2019, 'Red'),
(3003, 2002, 'DHA-10-1357', 'Nissan', 'X-Trail', 2017, 'Silver')
ON DUPLICATE KEY UPDATE
  customer_id = VALUES(customer_id),
  make = VALUES(make),
  model = VALUES(model),
  year = VALUES(year),
  color = VALUES(color);

-- ----------------------------
-- Services catalog (fixed IDs)
-- ----------------------------
INSERT INTO services (service_id, name, base_price, is_active) VALUES
(4000, 'Engine Oil Change',     1200.00, 1),
(4001, 'Brake Pad Replacement', 3500.00, 1),
(4002, 'Wheel Alignment',       1500.00, 1),
(4003, 'Battery Check',          500.00, 1),
(4004, 'AC Gas Refill',         2500.00, 1),
(4005, 'General Diagnostics',   1000.00, 1)
ON DUPLICATE KEY UPDATE
  base_price = VALUES(base_price),
  is_active = VALUES(is_active);

-- ----------------------------
-- Mechanic schedules (today + next 2 days)
-- Capacity is 4 as per your flow
-- ----------------------------
INSERT INTO mechanic_schedule (mechanic_id, work_date, capacity, reserved_count) VALUES
(1002, CURDATE(), 4, 0),
(1003, CURDATE(), 4, 0),
(1002, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 4, 0),
(1003, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 4, 0),
(1002, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 4, 0),
(1003, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 4, 0)
ON DUPLICATE KEY UPDATE
  capacity = VALUES(capacity);

-- ----------------------------
-- Appointments (fixed IDs)
-- Flow examples:
-- 5000: requested (no mechanic yet)
-- 5001: booked (assigned to mechanic)
-- 5002: completed with job + bill paid
-- ----------------------------

-- Appointment 5000: REQUESTED
INSERT INTO appointments (
  appointment_id, customer_id, vehicle_id,
  requested_date, requested_slot, problem_text,
  status, mechanic_id, assigned_by_staff_id, assigned_at, receptionist_note
) VALUES (
  5000, 2000, 3000,
  DATE_ADD(CURDATE(), INTERVAL 1 DAY), 2,
  'Car making unusual noise when braking.',
  'requested', NULL, NULL, NULL, NULL
)
ON DUPLICATE KEY UPDATE
  requested_date = VALUES(requested_date),
  requested_slot = VALUES(requested_slot),
  problem_text = VALUES(problem_text),
  status = VALUES(status);

-- Appointment 5001: BOOKED (assigned by receptionist)
INSERT INTO appointments (
  appointment_id, customer_id, vehicle_id,
  requested_date, requested_slot, problem_text,
  status, mechanic_id, assigned_by_staff_id, assigned_at, receptionist_note
) VALUES (
  5001, 2001, 3002,
  DATE_ADD(CURDATE(), INTERVAL 1 DAY), 1,
  'AC not cooling properly.',
  'booked', 1002, 1001, NOW(),
  'Assigned to Moin for AC inspection.'
)
ON DUPLICATE KEY UPDATE
  mechanic_id = VALUES(mechanic_id),
  assigned_by_staff_id = VALUES(assigned_by_staff_id),
  assigned_at = VALUES(assigned_at),
  status = VALUES(status),
  receptionist_note = VALUES(receptionist_note);

-- Reserve a slot for mechanic 1002 on that day (demo increment)
UPDATE mechanic_schedule
SET reserved_count = LEAST(4, reserved_count + 1)
WHERE mechanic_id = 1002
  AND work_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY);

-- Appointment 5002: IN_PROGRESS -> COMPLETED with Job + Bill
INSERT INTO appointments (
  appointment_id, customer_id, vehicle_id,
  requested_date, requested_slot, problem_text,
  status, mechanic_id, assigned_by_staff_id, assigned_at, receptionist_note
) VALUES (
  5002, 2002, 3003,
  CURDATE(), 3,
  'Engine light is on, needs diagnostics.',
  'in_progress', 1003, 1001, NOW(),
  'Customer waiting, please start diagnostics ASAP.'
)
ON DUPLICATE KEY UPDATE
  status = VALUES(status),
  mechanic_id = VALUES(mechanic_id);

UPDATE mechanic_schedule
SET reserved_count = LEAST(4, reserved_count + 1)
WHERE mechanic_id = 1003
  AND work_date = CURDATE();

-- ----------------------------
-- Job for appointment 5002 (fixed ID)
-- ----------------------------
INSERT INTO jobs (
  job_id, appointment_id, mechanic_id,
  status, started_at, notes
) VALUES (
  6000, 5002, 1003,
  'in_progress', NOW(),
  'Initial inspection started.'
)
ON DUPLICATE KEY UPDATE
  mechanic_id = VALUES(mechanic_id),
  status = VALUES(status);

-- Job services (mechanic adds performed services)
INSERT INTO job_services (job_id, service_id, qty, unit_price) VALUES
(6000, 4005, 1, 1000.00), -- General Diagnostics
(6000, 4003, 1,  500.00)  -- Battery Check
ON DUPLICATE KEY UPDATE
  qty = VALUES(qty),
  unit_price = VALUES(unit_price);

-- ----------------------------
-- Bill for job 6000 (fixed ID)
-- Mechanic generates bill -> unpaid
-- Receptionist marks paid
-- ----------------------------

-- Compute totals from job_services
SET @subtotal := (SELECT COALESCE(SUM(line_total), 0) FROM job_services WHERE job_id = 6000);
SET @discount := 0.00;
SET @total := @subtotal - @discount;

INSERT INTO bills (
  bill_id, job_id, bill_no,
  subtotal, discount, total,
  payment_status, created_by_staff_id,
  payment_method, paid_by_staff_id, paid_at
) VALUES (
  7000, 6000, CONCAT('BILL-', DATE_FORMAT(NOW(), '%Y%m%d'), '-6000'),
  @subtotal, @discount, @total,
  'unpaid', 1003,
  NULL, NULL, NULL
)
ON DUPLICATE KEY UPDATE
  subtotal = VALUES(subtotal),
  discount = VALUES(discount),
  total = VALUES(total),
  payment_status = VALUES(payment_status);

-- Copy bill items from job services for invoice display
INSERT INTO bill_items (bill_id, description, qty, unit_price) VALUES
(7000, 'General Diagnostics', 1, 1000.00),
(7000, 'Battery Check',       1,  500.00)
ON DUPLICATE KEY UPDATE
  qty = VALUES(qty),
  unit_price = VALUES(unit_price);

-- Receptionist marks payment (demo)
UPDATE bills
SET payment_status = 'paid',
    payment_method = 'bkash',
    paid_by_staff_id = 1001,
    paid_at = NOW()
WHERE bill_id = 7000;

-- Mark job + appointment completed (demo)
UPDATE jobs
SET status = 'completed',
    completed_at = NOW()
WHERE job_id = 6000;

UPDATE appointments
SET status = 'completed'
WHERE appointment_id = 5002;

SET FOREIGN_KEY_CHECKS = 1;
