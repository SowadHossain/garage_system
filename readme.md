# 🚗 Screw Dheela Management System

A comprehensive auto garage management system with **Super Admin Dashboard**, **Advanced Reports**, and **SQL Analytics**. Built with PHP, MySQL, Bootstrap, and Docker.

![Version](https://img.shields.io/badge/version-2.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.1-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker)

---

## ✨ NEW Features (v2.0)

### 🎯 Super Admin Dashboard
- Comprehensive system overview with real-time statistics
- Quick access to all reports and analytics
- Revenue metrics and performance indicators
- Direct links to customer/vehicle/appointment management

### 📊 Advanced Reports Module
1. **Revenue Reports** - SUM, AVG, MIN, MAX aggregates with GROUP BY/HAVING
2. **Service Performance** - Usage statistics, category analysis, unused services detection
3. **Customer Analytics** - SQL VIEWs, subqueries, IS NULL checks, DISTINCT queries

### 🔍 Global Search
- Search across customers, vehicles, and appointments simultaneously
- Uses SQL LIKE pattern matching
- Fast and intuitive interface

### � Database Access Control
- 4 database users with tiered permissions (GRANT statements)
- `reports_user` - Read-only access (SELECT)
- `operations_user` - Limited write access (SELECT/INSERT/UPDATE)
- `mechanic_user` - Job management access
- `admin_user` - Full privileges WITH GRANT OPTION

### 📈 SQL Features Implemented
- ✅ 3 SQL VIEWs for data aggregation
- ✅ Complex JOINs (INNER, LEFT, 3+ table joins)
- ✅ Aggregate functions (SUM, AVG, MIN, MAX, COUNT)
- ✅ GROUP BY and HAVING clauses
- ✅ Subqueries with IN/NOT IN
- ✅ LIKE pattern matching for search
- ✅ IS NULL checks and DISTINCT queries
- ✅ User Access Control with GRANT

---

## �🚀 Quick Start

### Prerequisites
- Docker Desktop
- 5 minutes of your time ⏱️

### Installation

1. **Navigate to the project:**
   ```powershell
   cd C:\xampp\htdocs\garage_system
   ```

2. **Start all services:**
   ```powershell
   docker compose up -d --build
   ```

3. **Wait 15 seconds, then initialize views and users:**
   ```powershell
   Get-Content docker\mysql\init\init.sql | docker compose exec -T db mysql -u root -proot_password_change_me garage_db
   Get-Content docker\mysql\init\grants.sql | docker compose exec -T db mysql -u root -proot_password_change_me
   ```

4. **Access the application:**
   - **Super Admin Dashboard:** http://localhost:8080/garage_system/public/admin_dashboard.php
   - **Landing Page:** http://localhost:8080/garage_system/public/welcome.php
   - **phpMyAdmin:** http://localhost:8081

---

## 🔑 Default Login Credentials

### Super Admin Account (NEW!)
- **Username:** `admin_user`
- **Password:** `staffpass`
- **Role:** Admin (full access)
- **Dashboard:** http://localhost:8080/garage_system/public/admin_dashboard.php
- **Features:**
  - Access to all reports and analytics
  - Revenue reports with aggregates
  - Service performance metrics
  - Customer analytics
  - Global search
  - Staff management

### Staff Account
- **Username:** `admin_user`
- **Password:** `staffpass`
- **URL:** http://localhost:8080/garage_system/public/staff_login.php

### Customer Accounts
- **Email:** `alice@example.com` or `bob@example.com`
- **Password:** `customer123`
- **URL:** http://localhost:8080/garage_system/public/customer_login.php

### Database Users (NEW!)
```sql
-- Read-only reports access
reports_user / reportspass

-- Operations access (SELECT/INSERT/UPDATE on specific tables)
operations_user / operationspass

-- Mechanic job management
mechanic_user / mechanicpass

-- Full admin privileges WITH GRANT OPTION
admin_user / adminpass
```

---

## 📊 Reports & Analytics URLs (NEW!)

### Super Admin Dashboard
- **Dashboard:** http://localhost:8080/garage_system/public/admin_dashboard.php
- System overview, quick stats, navigation hub

### Revenue Reports
- **URL:** http://localhost:8080/garage_system/public/reports/revenue.php
- **SQL Features:** SUM, AVG, MIN, MAX, GROUP BY, HAVING
- Monthly revenue breakdown, payment method analysis, top customers

### Service Performance
- **URL:** http://localhost:8080/garage_system/public/reports/services.php
- **SQL Features:** GROUP BY category, COUNT, IS NULL checks
- Popular services, category analysis, unused services detection

### Customer Analytics
- **URL:** http://localhost:8080/garage_system/public/reports/customers.php
- **SQL Features:** VIEWs, IN/NOT IN subqueries, DISTINCT, IS NULL
- Customer segmentation, vehicle analysis, loyalty metrics

### Global Search
- **URL:** http://localhost:8080/garage_system/public/search.php
- **SQL Features:** LIKE pattern matching across multiple tables
- Search customers, vehicles, appointments simultaneously

### Staff Management
- **URL:** http://localhost:8080/garage_system/public/admin/manage_staff.php
- View staff accounts and database user permissions

---

## 📚 Complete Documentation

- **[SETUP_GUIDE.md](SETUP_GUIDE.md)** - Detailed setup, troubleshooting, and development workflow
- **[PROJECT_COMPLETE.md](PROJECT_COMPLETE.md)** - Complete feature list and technical documentation
- **[IMPLEMENTATION_CHECKLIST.md](IMPLEMENTATION_CHECKLIST.md)** - SQL requirements mapping

---

## 🌟 Features

### 🎯 Admin Dashboard (NEW!)
- ✅ Comprehensive system statistics
- ✅ Quick access to all reports and analytics
- ✅ Recent appointments overview
- ✅ Top customers display
- ✅ SQL techniques summary card
- ✅ Direct links to all management modules

### � Reports Module (NEW!)
- ✅ Revenue analytics with aggregates (SUM, AVG, MIN, MAX)
- ✅ Service performance by category (GROUP BY)
- ✅ Customer insights from SQL VIEWs
- ✅ Advanced queries with subqueries and HAVING
- ✅ Unused services detection (IS NULL)
- ✅ Distinct vehicle brand analysis

### 🔍 Search & Discovery (NEW!)
- ✅ Global LIKE pattern search
- ✅ Customer search by name/email/phone
- ✅ Vehicle search by registration/brand/model
- ✅ Real-time results display

### �👥 Customer Portal (Green Theme)
- ✅ User registration and authentication
- ✅ Vehicle management (add, edit, delete)
- ✅ Appointment booking with calendar
- ✅ View service history
- ✅ Bill and invoice viewing with print
- ✅ Real-time chat with staff
- ✅ Responsive dashboard

### 👨‍💼 Staff Portal (Blue Theme)
- ✅ Secure staff authentication
- ✅ Customer management with search
- ✅ Appointment scheduling
- ✅ Job/service tracking
- ✅ Invoice generation
- ✅ Customer messaging system
- ✅ Analytics dashboard

---

## 🗄️ Database Architecture (NEW!)

### SQL VIEWs (3 Total)
```sql
-- Customer summary with aggregates
CREATE VIEW view_customer_summary AS
SELECT c.customer_id, c.name, c.email, c.phone,
    COUNT(DISTINCT v.vehicle_id) as vehicle_count,
    COUNT(DISTINCT a.appointment_id) as appointment_count,
    COALESCE(SUM(b.total_amount), 0) as total_spent,
    COALESCE(AVG(b.total_amount), 0) as avg_bill_amount,
    MAX(a.appointment_date) as last_appointment_date
FROM customers c
LEFT JOIN vehicles v ON c.customer_id = v.customer_id
LEFT JOIN appointments a ON v.vehicle_id = a.vehicle_id
LEFT JOIN jobs j ON a.appointment_id = j.appointment_id
LEFT JOIN bills b ON j.job_id = b.job_id
GROUP BY c.customer_id;

-- Pending work overview
CREATE VIEW view_pending_work AS ...

-- Revenue details
CREATE VIEW view_revenue_detail AS ...
```

### Database Users & Permissions
```sql
-- 1. Reports User (Read-only)
CREATE USER 'reports_user'@'%' IDENTIFIED BY 'reportspass';
GRANT SELECT ON garage_db.* TO 'reports_user'@'%';

-- 2. Operations User (Limited write)
CREATE USER 'operations_user'@'%' IDENTIFIED BY 'operationspass';
GRANT SELECT, INSERT, UPDATE ON garage_db.customers TO 'operations_user'@'%';
GRANT SELECT, INSERT, UPDATE ON garage_db.vehicles TO 'operations_user'@'%';
GRANT SELECT, INSERT, UPDATE ON garage_db.appointments TO 'operations_user'@'%';

-- 3. Mechanic User (Job management)
CREATE USER 'mechanic_user'@'%' IDENTIFIED BY 'mechanicpass';
GRANT SELECT, INSERT, UPDATE ON garage_db.jobs TO 'mechanic_user'@'%';
GRANT SELECT, INSERT, UPDATE ON garage_db.job_services TO 'mechanic_user'@'%';

-- 4. Admin User (Full privileges WITH GRANT OPTION)
CREATE USER 'admin_user'@'%' IDENTIFIED BY 'adminpass';
GRANT ALL PRIVILEGES ON garage_db.* TO 'admin_user'@'%' WITH GRANT OPTION;
```

### Advanced SQL Features Implemented
✅ **LIKE Pattern Matching** - `customers/list.php`, `vehicles/list.php`, `search.php`
✅ **IS NULL Checks** - Unused services, customers without vehicles
✅ **DISTINCT Queries** - Unique vehicle brands analysis
✅ **Aggregate Functions** - SUM, AVG, MIN, MAX, COUNT throughout reports
✅ **GROUP BY** - Monthly revenue, service categories, payment methods
✅ **HAVING Clauses** - Top customers filter, unpaid bills threshold
✅ **Subqueries with IN/NOT IN** - Customer segmentation queries
✅ **Multi-table JOINs** - 3+ table joins (INNER, LEFT)
✅ **SQL VIEWs** - 3 views for data aggregation
✅ **GRANT Statements** - 4 users with tiered access control

---

## Project Overview

The Screw Dheela Management System is a web-based application designed to digitalize and streamline the daily operations of a car or motorcycle service garage. Many garages currently rely on manual, paper-based methods to record appointments, track vehicle repairs, and generate bills. These approaches often result in inefficiency, data loss, and errors.

This system replaces traditional paper tracking with an organized digital platform developed using PHP, HTML, CSS, and MySQL within a XAMPP environment. It also satisfies the academic requirements for designing and implementing a multi-table relational database.

## Purpose of the Project

The main purpose of this project is to provide a central system that manages the entire workflow of Screw Dheela service garage. The system allows users to:

* Register and manage customer information
* Store details of multiple vehicles per customer
* Create and maintain appointment schedules
* Track service jobs performed on vehicles
* Add multiple services under each job
* Calculate and generate accurate bills
* Authenticate staff users securely
* Communicate through a built-in chat module
* Send notifications and reminders via the application and email

The system offers a unified and organized way to manage Screw Dheela operations, reducing manual errors, improving efficiency, and making data retrieval easier.

## Key Features

### 1. Customer Management

* Add, update, and view customer information
* Track each customer's service history
* Support multiple vehicles per customer

### 2. Vehicle Management

* Register vehicles associated with customers
* Maintain detailed vehicle profiles

### 3. Appointment Scheduling

* Create appointments with a selected customer and vehicle
* Record date, time, and service requirements
* Update appointment statuses: booked, in progress, completed, cancelled

### 4. Job Management

* Convert appointments into repair jobs
* Assign mechanics to each job
* Add multiple services using predefined service records

### 5. Service Catalog

* Maintain a list of services offered by Screw Dheela
* Set base prices for each service

### 6. Billing System

* Automatically calculate job totals including: subtotal, tax, discount, total
* Create and display printable bills
* Track payment status as paid or unpaid

### 7. Staff Authentication and Roles

* Secure login system for staff members
* Role-based access control: admin, mechanic, receptionist

### 8. OTP Login and Email Verification

* Send OTP codes via email for login or registration verification
* Mark email as verified after OTP confirmation

### 9. Chat Communication System

* Customer and staff communication through application messaging
* Messages include read/unread status and timestamps

### 10. Notification System

Includes both in-app notifications and email notifications. Supports:

* New message notifications
* Unread message reminders
* Billing notifications
* Broadcast messages
* System alerts

Users can manage notification preferences, enabling or disabling specific categories.

### 11. Broadcast Messaging (Admin Feature)

* Admin can create broadcast messages
* Send announcements to staff, customers, or both
* Broadcasts delivered via application notifications and/or email

## Database Design Overview

The system uses a relational MySQL database consisting of the following major tables:

* customers
* vehicles
* appointments
* jobs
* services
* job_services
* bills
* staff
* login_otps
* conversations
* messages
* notification_types
* notification_preferences
* notifications
* email_queue
* broadcasts
* broadcast_recipients

These tables maintain structured relationships supporting referential integrity, efficient querying, and real-time operations.

## System Workflow

1. A customer requests an appointment. The receptionist registers the customer and vehicle if needed.
2. The appointment is scheduled with date, time, and service details.
3. When the vehicle arrives, the appointment becomes a job.
4. A mechanic performs the job, adding relevant services.
5. The receptionist generates a bill, which is automatically calculated from the job details.
6. Customers and staff can communicate through the chat module.
7. Notifications appear in the application and are optionally sent via email.
8. The manager can create broadcast messages for announcements.

## Project Folder Structure

```
garage_system/
│
├── config/
│   └── db.php
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── auth_check.php
│
├── public/
│   ├── index.php
│   ├── login.php
│   └── logout.php
│
├── customers/
│   ├── add.php
│   ├── list.php
│   └── edit.php
│
├── vehicles/
│   ├── add.php
│   ├── list.php
│   └── edit.php
│
├── appointments/
│   ├── add.php
│   ├── list.php
│   └── update_status.php
│
├── jobs/
│   ├── create_from_appointment.php
│   ├── list.php
│   └── add_services.php
│
├── bills/
│   ├── generate.php
│   ├── view.php
│   └── list.php
│
├── chat/
│   ├── conversations.php
│   └── view.php
│
├── notifications/
│   ├── list.php
│   └── settings.php
│
├── broadcasts/
│   ├── create.php
│   └── list.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   └── img/
│
└── cron/
    ├── send_emails.php
    └── unread_reminder.php
```

## Technologies Used

* PHP (server-side scripting)
* MySQL (database management)
* HTML and CSS (frontend structure and design)
* XAMPP (local development environment)
* Git (version control)

## Conclusion

The Screw Dheela Management System demonstrates the application of database design, server-side programming, authentication, communication systems, and notification workflows within a well-structured multi-user environment. The system efficiently supports the daily operations of Screw Dheela automotive service garage and provides a strong foundation for real-world digital management solutions.
