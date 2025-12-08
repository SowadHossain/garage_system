# Garage Management System

A Web Application for Appointments, Jobs, Billing, Communication, and Notification Management

## Project Overview

The Garage Management System is a web-based application designed to digitalize and streamline the daily operations of a car or motorcycle service garage. Many garages currently rely on manual, paper-based methods to record appointments, track vehicle repairs, and generate bills. These approaches often result in inefficiency, data loss, and errors.

This system replaces traditional paper tracking with an organized digital platform developed using PHP, HTML, CSS, and MySQL within a XAMPP environment. It also satisfies the academic requirements for designing and implementing a multi-table relational database.

## Purpose of the Project

The main purpose of this project is to provide a central system that manages the entire workflow of a service garage. The system allows users to:

* Register and manage customer information
* Store details of multiple vehicles per customer
* Create and maintain appointment schedules
* Track service jobs performed on vehicles
* Add multiple services under each job
* Calculate and generate accurate bills
* Authenticate staff users securely
* Communicate through a built-in chat module
* Send notifications and reminders via the application and email

The system offers a unified and organized way to manage garage operations, reducing manual errors, improving efficiency, and making data retrieval easier.

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

* Maintain a list of services offered by the garage
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

The Garage Management System demonstrates the application of database design, server-side programming, authentication, communication systems, and notification workflows within a well-structured multi-user environment. The system efficiently supports the daily operations of an automotive service garage and provides a strong foundation for real-world digital management solutions.
