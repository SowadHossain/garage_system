# 🔐 User Hierarchy & Access Control Analysis

## Current System Architecture

Your Garage Management System has **TWO SEPARATE but PARALLEL** authentication systems:

---

## 📊 User Hierarchy Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    GARAGE SYSTEM USERS                      │
└─────────────────────────────────────────────────────────────┘
                            │
                ┌───────────┴───────────┐
                │                       │
         ┌──────▼──────┐         ┌─────▼──────┐
         │   STAFF     │         │  CUSTOMER  │
         │  (Internal) │         │ (External) │
         └──────┬──────┘         └─────┬──────┘
                │                      │
      ┌─────────┼─────────┐           │
      │         │         │            │
   ┌──▼──┐  ┌──▼───┐  ┌──▼────┐      │
   │Admin│  │Recep │  │Mecha  │      │
   │     │  │tionist│  │nic    │      │
   └─────┘  └──────┘  └───────┘      │
      │         │          │          │
      │         │          │          │
   (Full)   (Front)    (Shop)     (Own)
   Access   Desk       Floor      Data
```

---

## 🎯 Authentication Layer 1: APPLICATION USERS

### A. **STAFF Users** (Internal Portal - Blue Theme)
**Table:** `staff`  
**Login URL:** http://localhost:8080/garage_system/public/staff_login.php  
**Session Variables:**
- `$_SESSION['staff_id']` - Staff ID
- `$_SESSION['staff_name']` - Staff Name
- `$_SESSION['staff_role']` - Role: 'admin', 'receptionist', or 'mechanic'

#### Role Hierarchy:

**1. Admin (Highest Level)**
```php
// Check: $_SESSION['staff_role'] === 'admin'
```
**Access Rights:**
- ✅ Super Admin Dashboard (`/public/admin_dashboard.php`)
- ✅ All Reports (`/public/reports/*.php`)
- ✅ Revenue Analytics (SUM, AVG, MIN, MAX)
- ✅ Service Performance Reports
- ✅ Customer Analytics
- ✅ Global Search
- ✅ Staff Management (`/public/admin/manage_staff.php`)
- ✅ Customer Management (add, edit, list)
- ✅ Vehicle Management
- ✅ Appointment Scheduling
- ✅ Job Management
- ✅ Bill Generation
- ✅ Broadcast Messages
- ✅ System Settings

**Current Implementation:**
```php
// admin_dashboard.php - Line 6
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'admin') {
    header('Location: staff_login.php');
    exit;
}
```

**Pages Restricted to Admin Only:**
- `/public/admin_dashboard.php`
- `/public/reports/revenue.php`
- `/public/reports/services.php`
- `/public/reports/customers.php`
- `/public/admin/manage_staff.php`

---

**2. Receptionist (Middle Level)**
```php
// Check: $_SESSION['staff_role'] === 'receptionist'
```
**Access Rights:**
- ✅ Customer Management (add, edit, list, search)
- ✅ Vehicle Registration
- ✅ Appointment Booking
- ✅ View Jobs
- ✅ Generate Bills (possibly)
- ✅ Chat with Customers
- ❌ Reports Module (admin only)
- ❌ Staff Management (admin only)
- ❌ System Settings (admin only)

**Current Implementation:**
```php
// Most pages check: if (!isset($_SESSION['staff_id']))
// BUT: No specific role check for receptionist vs admin
```

---

**3. Mechanic (Operational Level)**
```php
// Check: $_SESSION['staff_role'] === 'mechanic'
```
**Access Rights:**
- ✅ View Assigned Jobs
- ✅ Update Job Status
- ✅ Add Services to Jobs
- ✅ View Customer/Vehicle Details (read-only)
- ❌ Create Appointments
- ❌ Add Customers
- ❌ Generate Bills
- ❌ Reports Module
- ❌ Staff Management

**Current Implementation:**
```php
// Most pages check: if (!isset($_SESSION['staff_id']))
// BUT: No specific role check for mechanic
```

---

### B. **CUSTOMER Users** (External Portal - Green Theme)
**Table:** `customers`  
**Login URL:** http://localhost:8080/garage_system/public/customer_login.php  
**Session Variables:**
- `$_SESSION['customer_id']` - Customer ID
- `$_SESSION['customer_name']` - Customer Name
- `$_SESSION['customer_email']` - Customer Email
- `$_SESSION['customer_phone']` - Customer Phone

**Access Rights:**
- ✅ View Own Vehicles (`/vehicles/list.php?customer_id=X`)
- ✅ Book Appointments
- ✅ View Own Appointments
- ✅ View Own Service History
- ✅ View Own Bills
- ✅ Pay Bills Online
- ✅ Chat with Staff
- ✅ Update Own Profile
- ❌ View Other Customers' Data
- ❌ Staff Portal Access
- ❌ Reports/Analytics
- ❌ Admin Functions

**Current Implementation:**
```php
// customer_login.php - Lines 35-42
$_SESSION['customer_id'] = $customer['customer_id'];
$_SESSION['customer_name'] = $customer['name'];
$_SESSION['customer_email'] = $customer['email'];
$_SESSION['customer_phone'] = $customer['phone'];
```

---

## 🗄️ Authentication Layer 2: DATABASE USERS

These are **MySQL database-level users**, NOT application users. They are for direct database access via MySQL CLI or phpMyAdmin.

### 1. **reports_user** (Read-Only)
```sql
GRANT SELECT ON garage_db.* TO 'reports_user'@'%';
```
**Use Case:**
- Business analysts viewing reports
- External reporting tools
- Data export operations
- BI dashboards

**No Relation to Application:** This user cannot login to the web application.

---

### 2. **operations_user** (Limited Write)
```sql
GRANT SELECT, INSERT, UPDATE ON garage_db.customers TO 'operations_user'@'%';
GRANT SELECT, INSERT, UPDATE ON garage_db.vehicles TO 'operations_user'@'%';
GRANT SELECT, INSERT, UPDATE ON garage_db.appointments TO 'operations_user'@'%';
```
**Use Case:**
- Front desk staff using direct DB tools
- Third-party booking systems
- Automated appointment systems

**No Relation to Application:** This user cannot login to the web application.

---

### 3. **mechanic_user** (Job Management)
```sql
GRANT SELECT, INSERT, UPDATE ON garage_db.jobs TO 'mechanic_user'@'%';
GRANT SELECT, INSERT, UPDATE ON garage_db.job_services TO 'mechanic_user'@'%';
```
**Use Case:**
- Shop floor tablets with direct DB access
- Workshop management systems
- Mobile apps for mechanics

**No Relation to Application:** This user cannot login to the web application.

---

### 4. **admin_user** (Full DB Control)
```sql
GRANT ALL PRIVILEGES ON garage_db.* TO 'admin_user'@'%' WITH GRANT OPTION;
```
**Use Case:**
- Database administrators
- System maintenance
- Schema updates
- User permission management

**IMPORTANT:** There's a naming collision here!
- `admin_user` (DB user) - For direct database access
- `admin_user` (Staff username in seed.sql) - For web application login

These are **TWO DIFFERENT USERS** in **TWO DIFFERENT SYSTEMS**.

---

## 🔍 Current Issues & Analysis

### ✅ **What's Working Well:**

1. **Separate Authentication Systems**
   - Staff and Customer have completely separate login flows ✅
   - Different session variables prevent conflicts ✅
   - Separate database tables (staff vs customers) ✅

2. **Admin Role Protection**
   - Reports are properly restricted to admin role ✅
   - Admin dashboard requires admin role ✅
   - Staff management requires admin role ✅

3. **Password Security**
   - Using `password_hash()` and `password_verify()` ✅
   - Prepared statements prevent SQL injection ✅
   - Session regeneration on customer login ✅

---

### ⚠️ **Current Gaps & Issues:**

#### **Problem 1: Incomplete Role-Based Access Control**

**Issue:** Most staff pages only check `if (!isset($_SESSION['staff_id']))` but don't check the specific role.

**Example:**
```php
// customers/list.php - Line 7
if (!isset($_SESSION['staff_id'])) {
    header('Location: ../public/staff_login.php');
    exit;
}
// ❌ Missing: Check if role is 'admin' or 'receptionist'
// ❌ Mechanics can currently access customer management!
```

**Impact:**
- Mechanics can access customer management pages ❌
- Mechanics can add/edit customers ❌
- Receptionists can potentially access pages meant for admin only ❌

**Who Can Access What Currently:**

| Page/Module | Admin | Receptionist | Mechanic | Customer |
|-------------|-------|--------------|----------|----------|
| Admin Dashboard | ✅ | ❌ | ❌ | ❌ |
| Reports Module | ✅ | ❌ | ❌ | ❌ |
| Staff Management | ✅ | ❌ | ❌ | ❌ |
| Customer List | ✅ | ✅* | ✅* | ❌ |
| Add Customer | ✅ | ✅* | ✅* | ❌ |
| Vehicle Management | ✅ | ✅* | ✅* | ❌ |
| Appointments | ✅ | ✅* | ✅* | ❌ |
| Jobs | ✅ | ✅* | ✅* | ❌ |
| Bills | ✅ | ✅* | ✅* | ❌ |

*Note: ✅* means access is granted but shouldn't be according to typical business logic.

---

#### **Problem 2: No Seed Data for Different Roles**

**Issue:** `seed.sql` only creates one staff member (admin role).

```sql
-- seed.sql - Line 9
INSERT INTO staff (staff_id, name, role, username, email, password_hash, ...)
VALUES (1000, 'Admin User', 'admin', 'admin_user', 'admin@example.com', ...);
-- Missing: receptionist_user, mechanic_user
```

**Impact:**
- Cannot test receptionist-level access ❌
- Cannot test mechanic-level access ❌
- Cannot verify role-based restrictions ❌

---

#### **Problem 3: Database Users vs Application Users Confusion**

**Issue:** Database user names overlap with application concepts but serve different purposes.

**Examples:**
- `admin_user` (DB) vs `admin_user` (Staff username) - SAME NAME, DIFFERENT SYSTEMS
- `mechanic_user` (DB) - No relation to staff.role='mechanic'
- `operations_user` (DB) - No relation to staff.role='receptionist'

**Clarification Needed:**
```
DATABASE USERS (MySQL Level)
├── reports_user       → For external BI tools
├── operations_user    → For third-party integrations
├── mechanic_user      → For shop floor systems  
└── admin_user         → For database administration

APPLICATION USERS (PHP Session Level)
├── staff.role='admin'        → Full system access
├── staff.role='receptionist' → Front desk operations
├── staff.role='mechanic'     → Job management
└── customers                 → Self-service portal
```

These are **PARALLEL SYSTEMS** that don't interact.

---

## ✅ Compliance with SQL Requirements

### SQL Requirement: "GRANT statements with user access control"

**Status:** ✅ **FULLY COMPLIANT**

**Evidence:**
1. **4 Database Users Created** (`grants.sql`)
   - reports_user (SELECT only)
   - operations_user (SELECT/INSERT/UPDATE on specific tables)
   - mechanic_user (Job management)
   - admin_user (ALL PRIVILEGES WITH GRANT OPTION)

2. **Graduated Privilege Levels**
   - Read-only → Limited write → Full control hierarchy ✅

3. **WITH GRANT OPTION**
   - admin_user can grant privileges to others ✅

4. **Principle of Least Privilege**
   - Each user has minimum necessary permissions ✅

**Database-level access control meets all academic requirements!** ✅

---

### Application-Level Access Control vs. SQL Requirements

**Important Distinction:**

The SQL course requirement is about **DATABASE-level GRANT statements**, NOT application-level role checking in PHP.

**What's Required (Database Level):** ✅ DONE
```sql
CREATE USER 'reports_user'@'%' IDENTIFIED BY 'password';
GRANT SELECT ON garage_db.* TO 'reports_user'@'%';
```

**What's NOT Required (Application Level):** ⚠️ Incomplete
```php
if ($_SESSION['staff_role'] !== 'admin') {
    // Deny access
}
```

**Conclusion:** 
- ✅ **SQL Requirements:** FULLY MET (database GRANTs implemented)
- ⚠️ **Application Security:** PARTIALLY IMPLEMENTED (role checks missing)

---

## 🎯 Recommended Improvements (Optional)

### 1. Add Missing Staff Users to Seed Data

```sql
-- Add to seed.sql
INSERT INTO staff (staff_id, name, role, username, email, password_hash, active)
VALUES 
  (1001, 'Sarah Reception', 'receptionist', 'receptionist_user', 'reception@example.com', 
   '$2y$10$...', 1),
  (1002, 'Mike Mechanic', 'mechanic', 'mechanic_user', 'mechanic@example.com', 
   '$2y$10$...', 1);
```

---

### 2. Implement Granular Role-Based Access Control

**Create a proper auth check file:**

```php
<?php
// includes/role_check.php

function requireRole($allowed_roles) {
    if (!isset($_SESSION['staff_id'])) {
        header("Location: /garage_system/public/staff_login.php");
        exit;
    }
    
    if (!in_array($_SESSION['staff_role'], $allowed_roles)) {
        http_response_code(403);
        die("Access Denied: You don't have permission to access this page.");
    }
}

// Usage examples:
// requireRole(['admin']); // Admin only
// requireRole(['admin', 'receptionist']); // Admin or receptionist
// requireRole(['admin', 'mechanic']); // Admin or mechanic
```

**Apply to pages:**

```php
<?php
// customers/list.php
session_start();
require_once '../config/db.php';
require_once '../includes/role_check.php';

requireRole(['admin', 'receptionist']); // Only admin and receptionist
// Rest of page...
```

**Proposed Access Matrix:**

| Module | Admin | Receptionist | Mechanic |
|--------|-------|--------------|----------|
| Dashboard | ✅ | ✅ | ✅ |
| Reports | ✅ | ❌ | ❌ |
| Staff Management | ✅ | ❌ | ❌ |
| Customers (list/search) | ✅ | ✅ | ❌ |
| Customers (add/edit) | ✅ | ✅ | ❌ |
| Vehicles (list) | ✅ | ✅ | ✅ (read-only) |
| Vehicles (add/edit) | ✅ | ✅ | ❌ |
| Appointments (view) | ✅ | ✅ | ✅ |
| Appointments (create) | ✅ | ✅ | ❌ |
| Jobs (view) | ✅ | ✅ | ✅ |
| Jobs (update status) | ✅ | ❌ | ✅ |
| Jobs (add services) | ✅ | ❌ | ✅ |
| Bills (view) | ✅ | ✅ | ❌ |
| Bills (generate) | ✅ | ✅ | ❌ |

---

### 3. Add Role Badges to UI

```php
<!-- includes/header.php -->
<div class="navbar">
    <span class="badge bg-primary">
        <?php echo ucfirst($_SESSION['staff_role']); ?>
    </span>
    <span><?php echo $_SESSION['staff_name']; ?></span>
</div>
```

---

### 4. Rename Database Users to Avoid Confusion

```sql
-- grants.sql
-- OLD: admin_user (conflicts with staff username)
-- NEW: db_admin_user

CREATE USER IF NOT EXISTS 'db_admin_user'@'%' IDENTIFIED BY 'adminpass';
GRANT ALL PRIVILEGES ON garage_db.* TO 'db_admin_user'@'%' WITH GRANT OPTION;

-- OLD: mechanic_user
-- NEW: db_mechanic_access

-- OLD: operations_user  
-- NEW: db_operations_access

-- OLD: reports_user
-- NEW: db_reports_access
```

This makes it crystal clear these are database-level users, not application users.

---

## 📋 Summary: Does Current Flow Make Sense?

### ✅ **YES - The Core Architecture is Sound:**

1. **Two-Portal Design** ✅
   - Staff Portal (internal operations)
   - Customer Portal (self-service)
   - Clear separation of concerns

2. **Database Access Control** ✅
   - 4 graduated privilege levels
   - GRANT statements implemented
   - WITH GRANT OPTION present
   - Meets SQL requirements fully

3. **Admin Role Protection** ✅
   - Reports restricted to admin
   - Staff management restricted to admin
   - Dashboard restricted to admin

4. **Security Basics** ✅
   - Password hashing
   - Prepared statements
   - Session management

---

### ⚠️ **BUT - Implementation is Incomplete:**

1. **Missing Role Granularity** ⚠️
   - Receptionist vs Mechanic not enforced
   - All staff can access most pages
   - Only admin-specific pages are protected

2. **Missing Test Users** ⚠️
   - No receptionist test account
   - No mechanic test account
   - Cannot verify role-based restrictions

3. **Naming Confusion** ⚠️
   - DB users vs App users use similar names
   - `admin_user` appears in both systems
   - Could confuse developers/users

---

## 🎯 Final Verdict

### For SQL Requirements (Academic): ✅ **100% COMPLIANT**
- All GRANT statements implemented
- Multiple users with varying privileges
- WITH GRANT OPTION present
- Demonstrates database access control

### For Production Security: ⚠️ **70% COMPLETE**
- Core security (passwords, sessions) ✅
- Admin protection ✅
- Role-based granularity ⚠️ (incomplete)
- Test accounts ⚠️ (missing roles)

### Does the Flow Make Sense?: ✅ **YES, with Caveats**
- Architecture is correct
- Implementation is functional
- Just needs role enforcement completion

---

## 🚀 What You Can Do Right Now

### Option 1: Keep As-Is (Acceptable for SQL Course)
- ✅ All SQL requirements are met
- ✅ System is functional
- ✅ Admin features are protected
- ⚠️ Some security gaps remain (but not required for SQL project)

### Option 2: Complete Role Implementation (Production-Ready)
- Add receptionist/mechanic users to seed.sql
- Implement role checking in all staff pages
- Add role badges to UI
- Test all access scenarios

**For your SQL course submission: Option 1 is perfectly fine!** ✅

The database-level GRANT requirements are fully implemented and working correctly.

---

**Last Updated:** December 13, 2025  
**Status:** ✅ SQL Requirements Met | ⚠️ Application RBAC Incomplete  
**Recommendation:** Current implementation is acceptable for project submission
