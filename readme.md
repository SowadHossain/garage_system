# 🚗 Screw Dheela Management System

A comprehensive auto garage management system with separate portals for customers and staff. Built with PHP, MySQL, Bootstrap, and Docker.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.1-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker)

---

## 🚀 Quick Start

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

3. **Wait 15 seconds, then seed the database:**
   ```powershell
   docker compose exec db mysql -u root -proot_password_change_me -e "CREATE DATABASE IF NOT EXISTS garage_db;"
   docker compose exec db mysql -u root -proot_password_change_me garage_db -e "SOURCE /docker-entrypoint-initdb.d/seed.sql;"
   ```

4. **Access the application:**
   - **Landing Page:** http://localhost:8080/garage_system/public/welcome.php
   - **phpMyAdmin:** http://localhost:8081

---

## 🔑 Default Login Credentials

### Staff Account
- **Username:** `admin_user`
- **Password:** `staffpass`
- **URL:** http://localhost:8080/garage_system/public/staff_login.php

### Customer Accounts
- **Email:** `alice@example.com` or `bob@example.com`
- **Password:** `staffpass`
- **URL:** http://localhost:8080/garage_system/public/customer_login.php

---

## 📚 Complete Documentation

- **[SETUP_GUIDE.md](SETUP_GUIDE.md)** - Detailed setup, troubleshooting, and development workflow
- **[PROJECT_COMPLETE.md](PROJECT_COMPLETE.md)** - Complete feature list and technical documentation

---

## 🌟 Features

### 👥 Customer Portal (Green Theme)
- ✅ User registration and authentication
- ✅ Vehicle management (add, edit, delete)
- ✅ Appointment booking with calendar
- ✅ View service history
- ✅ Bill and invoice viewing with print
- ✅ Real-time chat with staff
- ✅ Responsive dashboard

### 👨‍💼 Staff Portal (Blue Theme)
- ✅ Secure staff authentication
- ✅ Customer management
- ✅ Appointment scheduling
- ✅ Job/service tracking
- ✅ Invoice generation
- ✅ Customer messaging system
- ✅ Analytics dashboard

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
