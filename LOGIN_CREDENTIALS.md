# 
    🔑 Login Credentials Reference

## Complete Access Guide for All User Types

---

## 🎯 Staff/Admin Accounts (Application Login)

### 1. Super Admin Account

**Username:** `admin_user`
**Password:** `staffpass`
**Login URL:** http://localhost:8080/garage_system/public/staff_login.php
**Dashboard:** http://localhost:8080/garage_system/public/admin_dashboard.php

**Access Level:**

- ✅ Full system access
- ✅ All reports and analytics
- ✅ Customer management
- ✅ Vehicle management
- ✅ Appointment scheduling
- ✅ Job management
- ✅ Bill generation
- ✅ Staff management
- ✅ Broadcast messages
- ✅ System settings

**Direct Links:**

- Revenue Reports: http://localhost:8080/garage_system/public/reports/revenue.php
- Service Reports: http://localhost:8080/garage_system/public/reports/services.php
- Customer Analytics: http://localhost:8080/garage_system/public/reports/customers.php
- Global Search: http://localhost:8080/garage_system/public/search.php
- Staff Management: http://localhost:8080/garage_system/public/admin/manage_staff.php

---

### 2. Receptionist Account (NEW!)

**Username:** `receptionist_user`
**Password:** `staffpass`
**Login URL:** http://localhost:8080/garage_system/public/staff_login.php
**Dashboard:** http://localhost:8080/garage_system/public/staff_dashboard.php

**Access Level:**

- ✅ Customer management (add, edit, list, search)
- ✅ Vehicle registration
- ✅ Appointment booking
- ✅ Bill generation
- ✅ Global search
- ❌ Reports module (admin only)
- ❌ Staff management (admin only)

---

### 3. Mechanic Account (NEW!)

**Username:** `mechanic_user`
**Password:** `staffpass`
**Login URL:** http://localhost:8080/garage_system/public/staff_login.php
**Dashboard:** http://localhost:8080/garage_system/public/staff_dashboard.php

**Access Level:**

- ✅ View assigned jobs
- ✅ Update job status
- ✅ Add services to jobs
- ✅ View appointments (read-only)
- ✅ View vehicle/customer details (read-only)
- ❌ Customer management
- ❌ Appointment creation
- ❌ Bill generation
- ❌ Reports module

---

### 4. Legacy Staff Account (Deprecated - Use specific role accounts above)

**Username:** `admin_user`
**Password:** `staffpass`
**Login URL:** http://localhost:8080/garage_system/public/staff_login.php

**Access Level:**

- Full operational access (same as admin in current setup)

---

## 👥 Customer Accounts (Application Login)

### Customer 1 - Alice

**Email:** `alice@example.com`
**Password:** `customer123`
**Login URL:** http://localhost:8080/garage_system/public/customer_login.php
**Dashboard:** http://localhost:8080/garage_system/public/customer_dashboard.php

**Access Level:**

- ✅ View own vehicles
- ✅ Book appointments
- ✅ View service history
- ✅ View and pay bills
- ✅ Chat with staff
- ✅ Manage profile

---

### Customer 2 - Bob

**Email:** `bob@example.com`
**Password:** `customer123`
**Login URL:** http://localhost:8080/garage_system/public/customer_login.php
**Dashboard:** http://localhost:8080/garage_system/public/customer_dashboard.php

**Access Level:**

- Same as Customer 1 (Alice)

---

## 🗄️ Database Users (Direct MySQL Access)

### 1. Reports User (Read-Only Analytics)

**Username:** `reports_user`
**Password:** `reportspass`
**Host:** `localhost` or `127.0.0.1`
**Port:** `3307`
**Database:** `garage_db`

**Connection String:**

```bash
mysql -h 127.0.0.1 -P 3307 -u reports_user -preportspass garage_db
```

**phpMyAdmin Login:**

- URL: http://localhost:8081
- Username: `reports_user`
- Password: `reportspass`

**Privileges:**

- ✅ SELECT on all tables
- ❌ No INSERT, UPDATE, DELETE
- **Use Case:** Business analysts, report generation, data export

**Example Queries:**

```sql
-- View customer summary
SELECT * FROM view_customer_summary;

-- Monthly revenue report
SELECT YEAR(bill_date) as year, MONTH(bill_date) as month, 
       SUM(total_amount) as revenue
FROM bills 
WHERE payment_status = 'paid'
GROUP BY YEAR(bill_date), MONTH(bill_date);
```

---

### 2. Operations User (Limited Write Access)

**Username:** `operations_user`
**Password:** `operationspass`
**Host:** `localhost` or `127.0.0.1`
**Port:** `3307`
**Database:** `garage_db`

**Connection String:**

```bash
mysql -h 127.0.0.1 -P 3307 -u operations_user -poperationspass garage_db
```

**phpMyAdmin Login:**

- URL: http://localhost:8081
- Username: `operations_user`
- Password: `operationspass`

**Privileges:**

- ✅ SELECT, INSERT, UPDATE on: `customers`, `vehicles`, `appointments`
- ✅ SELECT on: `services`
- ❌ No DELETE privileges
- ❌ No access to: `bills`, `jobs`, `staff`
- **Use Case:** Front desk staff, receptionists, customer service

**Example Operations:**

```sql
-- Add new customer
INSERT INTO customers (name, email, phone, address) 
VALUES ('John Doe', 'john@example.com', '1234567890', '123 Main St');

-- Update customer info
UPDATE customers SET phone = '0987654321' WHERE customer_id = 1;

-- Book appointment
INSERT INTO appointments (vehicle_id, appointment_date, status) 
VALUES (1, '2025-12-15 10:00:00', 'booked');
```

---

### 3. Mechanic User (Job Management)

**Username:** `mechanic_user`
**Password:** `mechanicpass`
**Host:** `localhost` or `127.0.0.1`
**Port:** `3307`
**Database:** `garage_db`

**Connection String:**

```bash
mysql -h 127.0.0.1 -P 3307 -u mechanic_user -pmechanicpass garage_db
```

**phpMyAdmin Login:**

- URL: http://localhost:8081
- Username: `mechanic_user`
- Password: `mechanicpass`

**Privileges:**

- ✅ SELECT, INSERT, UPDATE on: `jobs`, `job_services`
- ✅ SELECT on: `services`, `appointments`, `vehicles`, `customers`
- ❌ No DELETE privileges
- ❌ No access to: `bills`, `staff`
- **Use Case:** Mechanics, technicians, workshop staff

**Example Operations:**

```sql
-- Create job from appointment
INSERT INTO jobs (appointment_id, mechanic_id, job_status) 
VALUES (1, 1, 'in_progress');

-- Add service to job
INSERT INTO job_services (job_id, service_id, quantity, price) 
VALUES (1, 1, 1, 50.00);

-- Update job status
UPDATE jobs SET job_status = 'completed', end_date = NOW() 
WHERE job_id = 1;
```

---

### 4. Admin User (Full Database Control)

**Username:** `admin_user`
**Password:** `adminpass`
**Host:** `localhost` or `127.0.0.1`
**Port:** `3307`
**Database:** `garage_db`

**Connection String:**

```bash
mysql -h 127.0.0.1 -P 3307 -u admin_user -padminpass garage_db
```

**phpMyAdmin Login:**

- URL: http://localhost:8081
- Username: `admin_user`
- Password: `adminpass`

**Privileges:**

- ✅ ALL PRIVILEGES on `garage_db.*`
- ✅ WITH GRANT OPTION (can grant privileges to other users)
- ✅ Full control: SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX, etc.
- **Use Case:** Database administrators, system administrators, developers

**Example Operations:**

```sql
-- Grant permissions to another user
GRANT SELECT ON garage_db.customers TO 'new_user'@'%';

-- Create new table
CREATE TABLE new_table (id INT PRIMARY KEY);

-- Modify table structure
ALTER TABLE customers ADD COLUMN loyalty_points INT DEFAULT 0;

-- Delete records
DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

### 5. Root User (MySQL System Administrator)

**Username:** `root`
**Password:** `root_password_change_me`
**Host:** `localhost` or `127.0.0.1`
**Port:** `3307`
**Database:** Any (system-wide access)

**Connection String:**

```bash
mysql -h 127.0.0.1 -P 3307 -u root -proot_password_change_me
```

**Docker Exec (Recommended):**

```powershell
docker compose exec db mysql -u root -proot_password_change_me
```

**phpMyAdmin Login:**

- URL: http://localhost:8081
- Username: `root`
- Password: `root_password_change_me`

**Privileges:**

- ✅ FULL SYSTEM ACCESS to all databases
- ✅ Create/drop databases
- ✅ Manage all users
- ✅ System configuration
- **Use Case:** System maintenance, backups, emergency access only

---

## 🌐 Web Application URLs

### Public Pages

- **Landing Page:** http://localhost:8080/garage_system/public/welcome.php
- **Staff Login:** http://localhost:8080/garage_system/public/staff_login.php
- **Customer Login:** http://localhost:8080/garage_system/public/customer_login.php

### Admin Pages (Requires staff login)

- **Admin Dashboard:** http://localhost:8080/garage_system/public/admin_dashboard.php
- **Revenue Reports:** http://localhost:8080/garage_system/public/reports/revenue.php
- **Service Performance:** http://localhost:8080/garage_system/public/reports/services.php
- **Customer Analytics:** http://localhost:8080/garage_system/public/reports/customers.php
- **Global Search:** http://localhost:8080/garage_system/public/search.php
- **Staff Management:** http://localhost:8080/garage_system/public/admin/manage_staff.php

### Customer Management

- **Customer List:** http://localhost:8080/garage_system/customers/list.php
- **Add Customer:** http://localhost:8080/garage_system/customers/add.php

### Vehicle Management

- **Vehicle List:** http://localhost:8080/garage_system/vehicles/list.php
- **Add Vehicle:** http://localhost:8080/garage_system/vehicles/add.php

### Appointments

- **Appointment List:** http://localhost:8080/garage_system/appointments/list.php
- **Add Appointment:** http://localhost:8080/garage_system/appointments/add.php

### Jobs

- **Job List:** http://localhost:8080/garage_system/jobs/list.php
- **Add Services to Job:** http://localhost:8080/garage_system/jobs/add_services.php

### Bills

- **Bill List:** http://localhost:8080/garage_system/bills/list.php
- **Generate Bill:** http://localhost:8080/garage_system/bills/generate.php

---

## 🛠️ Database Management Tools

### phpMyAdmin

**URL:** http://localhost:8081**Supported Logins:**

- Root: `root` / `root_password_change_me`
- Admin: `admin_user` / `adminpass`
- Reports: `reports_user` / `reportspass`
- Operations: `operations_user` / `operationspass`
- Mechanic: `mechanic_user` / `mechanicpass`

---

## 📋 Quick Reference Table

| User Type                        | Username          | Password                | Access Level    | URL/Port                 |
| -------------------------------- | ----------------- | ----------------------- | --------------- | ------------------------ |
| **Web App - Super Admin**  | admin_user        | staffpass               | Full system     | :8080/staff_login.php    |
| **Web App - Receptionist** | receptionist_user | staffpass               | Front desk ops  | :8080/staff_login.php    |
| **Web App - Mechanic**     | mechanic_user     | staffpass               | Job management  | :8080/staff_login.php    |
| **Web App - Customer 1**   | alice@example.com | customer123             | Customer portal | :8080/customer_login.php |
| **Web App - Customer 2**   | bob@example.com   | customer123             | Customer portal | :8080/customer_login.php |
| **DB - Reports User**      | reports_user      | reportspass             | SELECT only     | :3307 or :8081           |
| **DB - Operations**        | operations_user   | operationspass          | Limited write   | :3307 or :8081           |
| **DB - Mechanic**          | mechanic_user     | mechanicpass            | Job management  | :3307 or :8081           |
| **DB - Admin**             | admin_user        | adminpass               | Full DB control | :3307 or :8081           |
| **DB - Root**              | root              | root_password_change_me | System admin    | :3307 or :8081           |

---

## 🔒 Security Notes

### Production Deployment Checklist

- [ ] Change ALL default passwords
- [ ] Remove or restrict root remote access
- [ ] Use strong passwords (min 16 characters, mixed case, numbers, symbols)
- [ ] Enable SSL/TLS for database connections
- [ ] Restrict database user hosts to specific IPs
- [ ] Implement password rotation policy
- [ ] Enable audit logging for admin users
- [ ] Review and minimize user privileges
- [ ] Change phpMyAdmin default URL
- [ ] Implement 2FA for admin accounts

### Recommended Password Changes

```sql
-- Change database user passwords
ALTER USER 'reports_user'@'%' IDENTIFIED BY 'NewSecurePassword123!';
ALTER USER 'operations_user'@'%' IDENTIFIED BY 'NewSecurePassword456!';
ALTER USER 'mechanic_user'@'%' IDENTIFIED BY 'NewSecurePassword789!';
ALTER USER 'admin_user'@'%' IDENTIFIED BY 'NewSecurePassword000!';
ALTER USER 'root'@'%' IDENTIFIED BY 'NewRootPassword999!';
FLUSH PRIVILEGES;
```

---

## 📞 Support

For issues or questions:

1. Check `README.md` for setup instructions
2. Check `IMPLEMENTATION_CHECKLIST.md` for SQL feature documentation
3. Check `SQL_FEATURES_SUMMARY.md` for quick reference

---

**Last Updated:** December 13, 2025
**System Status:** ✅ All accounts active and verified
**Environment:** Development (Docker)
