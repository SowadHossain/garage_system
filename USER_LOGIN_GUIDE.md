# 🚪 Login Guide & Use Cases

## Complete User Access Reference

---

## 🎯 APPLICATION USERS (Web Portal Login)

### 1️⃣ Admin User

**🔐 Login Details:**
- **URL:** http://localhost:8080/garage_system/public/staff_login.php
- **Username:** `admin_user`
- **Password:** `staffpass`
- **Role Badge:** 🔴 Admin

**📍 Where They Land After Login:**
- **Dashboard:** http://localhost:8080/garage_system/public/staff_dashboard.php
- **Super Admin Hub:** http://localhost:8080/garage_system/public/admin_dashboard.php

**💼 Use Cases & Responsibilities:**

1. **System Administration**
   - Manage all staff accounts
   - View system-wide statistics
   - Configure system settings
   - Create broadcast messages

2. **Business Analytics**
   - View revenue reports (monthly, payment methods, top customers)
   - Analyze service performance (popular services, categories)
   - Track customer analytics (loyalty, spending patterns)
   - Export data for business decisions

3. **Complete Operations**
   - All customer management (add, edit, delete, search)
   - All vehicle registration
   - All appointment scheduling
   - All job management
   - All bill generation
   - Global search across system

**🎬 Typical Daily Workflow:**
```
1. Login → Admin Dashboard
2. Check system statistics (customers, revenue, pending work)
3. Review monthly revenue reports
4. Check top customers this month
5. Manage staff accounts if needed
6. Handle escalated customer issues
7. Generate business reports for management
```

**🔗 Quick Access Links:**
- Admin Dashboard: `/public/admin_dashboard.php`
- Revenue Reports: `/public/reports/revenue.php`
- Service Reports: `/public/reports/services.php`
- Customer Analytics: `/public/reports/customers.php`
- Staff Management: `/public/admin/manage_staff.php`
- Global Search: `/public/search.php`
- Customer Management: `/customers/list.php`

---

### 2️⃣ Receptionist User

**🔐 Login Details:**
- **URL:** http://localhost:8080/garage_system/public/staff_login.php
- **Username:** `receptionist_user`
- **Password:** `staffpass`
- **Role Badge:** 🔵 Receptionist

**📍 Where They Land After Login:**
- **Dashboard:** http://localhost:8080/garage_system/public/staff_dashboard.php

**💼 Use Cases & Responsibilities:**

1. **Customer Service (Front Desk)**
   - Register new customers (walk-ins, phone calls)
   - Update customer information (address, phone, email)
   - Search and retrieve customer records
   - Handle customer inquiries

2. **Vehicle Registration**
   - Register customer vehicles
   - Update vehicle details (registration, mileage)
   - Link vehicles to customers

3. **Appointment Management**
   - Book appointments for customers
   - Schedule service dates and times
   - Update appointment status
   - Handle appointment changes/cancellations

4. **Billing Operations**
   - Generate bills for completed jobs
   - Process payments
   - Track payment status
   - Print invoices for customers

5. **Customer Communication**
   - Chat with customers
   - Send notifications
   - Follow up on unpaid bills

**🎬 Typical Daily Workflow:**
```
1. Login → Staff Dashboard
2. Check today's appointments
3. Register walk-in customers
4. Book new appointments via phone/in-person
5. Update appointment statuses (customer arrived, in progress)
6. Generate bills for completed jobs
7. Process payments and print invoices
8. Search customer records as needed
9. Update customer contact information
```

**🔗 Quick Access Links:**
- Customer List: `/customers/list.php` ✅ Can Access
- Add Customer: `/customers/add.php` ✅ Can Access
- Global Search: `/public/search.php` ✅ Can Access
- Appointments: `/appointments/list.php` ✅ Can Access
- Bills: `/bills/list.php` ✅ Can Access

**❌ CANNOT Access:**
- Admin Dashboard (admin only)
- Revenue Reports (admin only)
- Service Reports (admin only)
- Staff Management (admin only)

---

### 3️⃣ Mechanic User

**🔐 Login Details:**
- **URL:** http://localhost:8080/garage_system/public/staff_login.php
- **Username:** `mechanic_user`
- **Password:** `staffpass`
- **Role Badge:** 🟢 Mechanic

**📍 Where They Land After Login:**
- **Dashboard:** http://localhost:8080/garage_system/public/staff_dashboard.php

**💼 Use Cases & Responsibilities:**

1. **Job Management (Workshop Floor)**
   - View assigned jobs/appointments
   - Update job status (in progress, completed)
   - Add services performed to jobs
   - Record parts used and labor hours
   - Mark jobs as complete

2. **Service Documentation**
   - Add service items to jobs
   - Specify quantities and prices
   - Document work performed
   - Note any additional issues found

3. **Read-Only Access**
   - View customer details (for context)
   - View vehicle information (make, model, history)
   - View appointment details
   - Check service catalog

**🎬 Typical Daily Workflow:**
```
1. Login → Staff Dashboard
2. Check assigned jobs for the day
3. View appointment details (customer, vehicle, problem)
4. Start work, update job status to "in progress"
5. Add services performed (oil change, repairs, etc.)
6. Document parts used and quantities
7. Mark job as completed
8. Move to next assignment
```

**🔗 Quick Access Links:**
- Jobs List: `/jobs/list.php` ✅ Can Access
- Add Services: `/jobs/add_services.php` ✅ Can Access
- View Appointments: `/appointments/list.php` ✅ Can Access (read-only)

**❌ CANNOT Access:**
- Customer Management (receptionist/admin only)
- Add/Edit Customers (receptionist/admin only)
- Vehicle Registration (receptionist/admin only)
- Appointment Booking (receptionist/admin only)
- Bill Generation (receptionist/admin only)
- Reports (admin only)
- Global Search (receptionist/admin only)

---

### 4️⃣ Customer Users

**🔐 Login Details:**
- **URL:** http://localhost:8080/garage_system/public/customer_login.php
- **Email:** `alice@example.com` OR `bob@example.com`
- **Password:** `customer123`
- **Portal Theme:** 🟢 Green (Customer Portal)

**📍 Where They Land After Login:**
- **Dashboard:** http://localhost:8080/garage_system/public/customer_dashboard.php

**💼 Use Cases & Responsibilities:**

1. **Self-Service Vehicle Management**
   - Register own vehicles
   - Update vehicle information
   - View vehicle service history
   - Track vehicle maintenance

2. **Appointment Booking**
   - Book service appointments online
   - Select preferred date/time
   - Describe vehicle problems
   - View upcoming appointments
   - View past appointments

3. **Billing & Payments**
   - View own bills/invoices
   - Check payment status
   - Download/print invoices
   - Track service expenses

4. **Communication**
   - Chat with garage staff
   - Receive notifications
   - Get service reminders

**🎬 Typical Customer Workflow:**
```
1. Login → Customer Dashboard
2. View my vehicles
3. Book appointment (if needed)
4. Check upcoming appointments
5. View service history
6. Check and pay bills
7. Chat with staff if questions
```

**🔗 Quick Access Links:**
- My Vehicles: `/vehicles/list.php` ✅ Own vehicles only
- Add Vehicle: `/vehicles/add.php` ✅ Can add
- My Appointments: `/appointments/list.php` ✅ Own appointments only
- My Bills: `/bills/customer_bills.php` ✅ Own bills only
- Chat: `/chat/customer_chat.php` ✅ Can message staff

**❌ CANNOT Access:**
- Staff portal
- Other customers' data
- Reports/analytics
- Admin functions
- System settings

---

## 🗄️ DATABASE USERS (Direct MySQL Access)

These users are for **database-level access** via MySQL CLI, phpMyAdmin, or database tools. They **CANNOT** login to the web application.

### 5️⃣ Reports User (DB)

**🔐 Login Details:**
- **phpMyAdmin:** http://localhost:8081
- **Username:** `reports_user`
- **Password:** `reportspass`
- **Port:** 3307 (for MySQL CLI)

**💼 Use Cases:**

1. **Business Intelligence**
   - Connect BI tools (Tableau, Power BI, Excel)
   - Pull data for external reporting
   - Export data for analysis
   - Create custom reports

2. **Data Analysis**
   - Run custom SQL queries
   - Generate ad-hoc reports
   - Analyze trends
   - Export CSV/Excel files

**🔒 Permissions:**
- ✅ SELECT only (read-only)
- ❌ No INSERT, UPDATE, DELETE
- ❌ Cannot modify data

**📊 Example Use Cases:**
```sql
-- Pull monthly revenue data
SELECT YEAR(bill_date), MONTH(bill_date), SUM(total_amount)
FROM bills
WHERE payment_status = 'paid'
GROUP BY YEAR(bill_date), MONTH(bill_date);

-- Export customer list
SELECT * FROM customers;

-- View service popularity
SELECT service_name, COUNT(*) as usage
FROM services s
JOIN job_services js ON s.service_id = js.service_id
GROUP BY s.service_id;
```

---

### 6️⃣ Operations User (DB)

**🔐 Login Details:**
- **phpMyAdmin:** http://localhost:8081
- **Username:** `operations_user`
- **Password:** `operationspass`
- **Port:** 3307

**💼 Use Cases:**

1. **Third-Party Integrations**
   - Online booking systems
   - CRM integrations
   - Appointment scheduling apps
   - Customer portals

2. **Automated Operations**
   - Bulk customer imports
   - Automated appointment creation
   - Vehicle registration scripts
   - Data synchronization

**🔒 Permissions:**
- ✅ SELECT, INSERT, UPDATE on: customers, vehicles, appointments
- ✅ SELECT on: services
- ❌ No DELETE
- ❌ No access to: bills, jobs, staff

**📊 Example Use Cases:**
```sql
-- Import customers from external system
INSERT INTO customers (name, email, phone, address)
VALUES ('New Customer', 'email@example.com', '123456', 'Address');

-- Update customer contact info
UPDATE customers SET phone = '999888777' WHERE customer_id = 123;

-- Book appointment from external system
INSERT INTO appointments (customer_id, vehicle_id, appointment_datetime, status)
VALUES (1, 5, '2025-12-20 10:00:00', 'booked');
```

---

### 7️⃣ Mechanic User (DB)

**🔐 Login Details:**
- **phpMyAdmin:** http://localhost:8081
- **Username:** `mechanic_user`
- **Password:** `mechanicpass`
- **Port:** 3307

**💼 Use Cases:**

1. **Shop Floor Systems**
   - Workshop tablets
   - Job tracking apps
   - Mobile mechanic apps
   - Barcode scanners

2. **Job Management Tools**
   - Update job progress
   - Add services/parts
   - Track time spent
   - Document work done

**🔒 Permissions:**
- ✅ SELECT, INSERT, UPDATE on: jobs, job_services
- ✅ SELECT on: services, appointments, vehicles, customers
- ❌ No DELETE
- ❌ No access to: bills, staff

**📊 Example Use Cases:**
```sql
-- Create job from tablet app
INSERT INTO jobs (appointment_id, mechanic_id, job_status)
VALUES (10, 1002, 'in_progress');

-- Add service to job
INSERT INTO job_services (job_id, service_id, quantity, price)
VALUES (5, 3000, 1, 29.99);

-- Update job status
UPDATE jobs SET job_status = 'completed', end_date = NOW()
WHERE job_id = 5;
```

---

### 8️⃣ Admin User (DB)

**🔐 Login Details:**
- **phpMyAdmin:** http://localhost:8081
- **Username:** `admin_user`
- **Password:** `adminpass`
- **Port:** 3307

**💼 Use Cases:**

1. **Database Administration**
   - Schema modifications
   - Index optimization
   - Performance tuning
   - Backup/restore operations

2. **User Management**
   - Create new database users
   - Grant/revoke permissions
   - Manage access control
   - Security audits

3. **Emergency Access**
   - Fix data issues
   - Resolve conflicts
   - Delete test data
   - Recovery operations

**🔒 Permissions:**
- ✅ ALL PRIVILEGES on garage_db.*
- ✅ WITH GRANT OPTION (can grant to others)
- ✅ Full control: SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER

**📊 Example Use Cases:**
```sql
-- Grant permissions to new user
GRANT SELECT ON garage_db.customers TO 'new_user'@'%';

-- Add new table
CREATE TABLE feedback (
    feedback_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    rating INT,
    comments TEXT
);

-- Emergency data fix
DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Optimize performance
CREATE INDEX idx_appointment_date ON appointments(appointment_date);
```

---

### 9️⃣ Root User (DB)

**🔐 Login Details:**
- **phpMyAdmin:** http://localhost:8081
- **Username:** `root`
- **Password:** `root_password_change_me`
- **Port:** 3307

**💼 Use Cases:**

1. **System-Level Administration**
   - Create/drop databases
   - Manage all database users
   - Server configuration
   - Global settings

2. **Emergency Only**
   - System recovery
   - Major upgrades
   - Security patches
   - Critical fixes

**🔒 Permissions:**
- ✅ ALL PRIVILEGES on *.*
- ✅ Full system access
- ⚠️ Use with extreme caution

---

## 📊 Quick Reference Table

| User | Login URL | Username | Use Case |
|------|-----------|----------|----------|
| 🔴 **Admin** | :8080/staff_login.php | admin_user | System management, reports, all operations |
| 🔵 **Receptionist** | :8080/staff_login.php | receptionist_user | Front desk, customers, appointments, billing |
| 🟢 **Mechanic** | :8080/staff_login.php | mechanic_user | Workshop jobs, service documentation |
| 👤 **Customer** | :8080/customer_login.php | alice@example.com | Self-service, book appointments, view bills |
| 📊 **Reports (DB)** | :8081 (phpMyAdmin) | reports_user | BI tools, data export, read-only |
| 🔌 **Operations (DB)** | :8081 (phpMyAdmin) | operations_user | Integrations, bulk imports |
| 🔧 **Mechanic (DB)** | :8081 (phpMyAdmin) | mechanic_user | Shop floor systems, job apps |
| 🛠️ **Admin (DB)** | :8081 (phpMyAdmin) | admin_user | DB administration |
| ⚙️ **Root (DB)** | :8081 (phpMyAdmin) | root | System-level admin |

---

## 🎯 Common Scenarios

### Scenario 1: Customer Calls to Book Appointment
**Who handles:** 🔵 Receptionist
```
1. Receptionist logs in (receptionist_user)
2. Searches for customer (or adds new one)
3. Selects customer's vehicle (or adds new one)
4. Books appointment with date/time
5. Customer receives confirmation
```

### Scenario 2: Vehicle Arrives for Service
**Who handles:** 🔵 Receptionist → 🟢 Mechanic
```
1. Receptionist updates appointment status to "in progress"
2. Creates job from appointment
3. Assigns mechanic
4. Mechanic logs in (mechanic_user)
5. Mechanic views assigned jobs
6. Performs work, adds services
7. Marks job complete
```

### Scenario 3: Customer Wants to Pay Bill
**Who handles:** 🔵 Receptionist
```
1. Receptionist generates bill from completed job
2. Shows bill to customer
3. Processes payment
4. Updates payment status to "paid"
5. Prints invoice for customer
```

### Scenario 4: Management Needs Monthly Report
**Who handles:** 🔴 Admin
```
1. Admin logs in (admin_user)
2. Goes to Reports → Revenue
3. Views monthly breakdown
4. Exports data or takes screenshots
5. Presents to management
```

### Scenario 5: External BI Tool Needs Data
**Who handles:** 📊 Reports User (DB)
```
1. BI tool connects to MySQL (port 3307)
2. Uses reports_user credentials
3. Runs SELECT queries
4. Pulls data for dashboards
5. Read-only, no modifications
```

---

## 🔐 Security Best Practices

### For Web Application Users:
- ✅ Use strong passwords in production
- ✅ Log out after each shift
- ✅ Don't share credentials
- ✅ Report suspicious activity

### For Database Users:
- ✅ Use from trusted IPs only
- ✅ Enable SSL/TLS in production
- ✅ Limit connection times
- ✅ Monitor access logs
- ✅ Never use root for applications

---

**Last Updated:** December 13, 2025  
**System Status:** ✅ All users active and tested  
**Access Control:** ✅ Role-based restrictions enforced
