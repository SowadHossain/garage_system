-- =========================================================
--  Screw Dheela Management System - User Access Control
--  Demonstrates: CREATE USER, GRANT, WITH GRANT OPTION
-- =========================================================

USE garage_db;

-- =========================================================
--  Read-Only User (Reports)
--  Purpose: For reporting tools and read-only dashboards
-- =========================================================

CREATE USER IF NOT EXISTS 'reports_user'@'%' IDENTIFIED BY 'ReportsPass123!';

-- Grant SELECT privilege on all tables
GRANT SELECT ON garage_db.* TO 'reports_user'@'%';

-- Allow reports user to see views
GRANT SELECT ON garage_db.view_customer_summary TO 'reports_user'@'%';
GRANT SELECT ON garage_db.view_pending_work TO 'reports_user'@'%';
GRANT SELECT ON garage_db.view_revenue_detail TO 'reports_user'@'%';

-- =========================================================
--  Operations User (Limited Write Access)
--  Purpose: For front-desk staff, receptionists
-- =========================================================

CREATE USER IF NOT EXISTS 'operations_user'@'%' IDENTIFIED BY 'OpsPass123!';

-- Grant SELECT, INSERT, UPDATE on customer-facing tables
GRANT SELECT, INSERT, UPDATE ON garage_db.customers TO 'operations_user'@'%';
GRANT SELECT, INSERT, UPDATE ON garage_db.vehicles TO 'operations_user'@'%';
GRANT SELECT, INSERT, UPDATE ON garage_db.appointments TO 'operations_user'@'%';
GRANT SELECT, INSERT, UPDATE ON garage_db.messages TO 'operations_user'@'%';
GRANT SELECT, INSERT, UPDATE ON garage_db.conversations TO 'operations_user'@'%';

-- Read-only access to other tables
GRANT SELECT ON garage_db.services TO 'operations_user'@'%';
GRANT SELECT ON garage_db.jobs TO 'operations_user'@'%';
GRANT SELECT ON garage_db.bills TO 'operations_user'@'%';
GRANT SELECT ON garage_db.staff TO 'operations_user'@'%';

-- Allow operations user to use views
GRANT SELECT ON garage_db.view_customer_summary TO 'operations_user'@'%';
GRANT SELECT ON garage_db.view_pending_work TO 'operations_user'@'%';

-- =========================================================
--  Admin User (Full Privileges with GRANT OPTION)
--  Purpose: For system administrators and super users
-- =========================================================

CREATE USER IF NOT EXISTS 'admin_user'@'%' IDENTIFIED BY 'AdminPass123!';

-- Grant ALL privileges on the entire database
GRANT ALL PRIVILEGES ON garage_db.* TO 'admin_user'@'%' WITH GRANT OPTION;

-- This allows admin_user to:
-- 1. Perform all operations (SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, etc.)
-- 2. Grant privileges to other users (WITH GRANT OPTION)
-- 3. Manage database structure and users

-- =========================================================
--  Mechanic User (Job and Service Management)
--  Purpose: For mechanics to update job status and services
-- =========================================================

CREATE USER IF NOT EXISTS 'mechanic_user'@'%' IDENTIFIED BY 'MechanicPass123!';

-- Grant access to job-related tables
GRANT SELECT, INSERT, UPDATE ON garage_db.jobs TO 'mechanic_user'@'%';
GRANT SELECT, INSERT, UPDATE ON garage_db.job_services TO 'mechanic_user'@'%';

-- Read-only access to reference data
GRANT SELECT ON garage_db.services TO 'mechanic_user'@'%';
GRANT SELECT ON garage_db.appointments TO 'mechanic_user'@'%';
GRANT SELECT ON garage_db.vehicles TO 'mechanic_user'@'%';
GRANT SELECT ON garage_db.customers TO 'mechanic_user'@'%';

-- Allow view access
GRANT SELECT ON garage_db.view_pending_work TO 'mechanic_user'@'%';

-- =========================================================
--  Apply all privilege changes
-- =========================================================

FLUSH PRIVILEGES;

-- =========================================================
--  Verification queries (for documentation)
-- =========================================================

-- To see all users:
-- SELECT User, Host FROM mysql.user WHERE User LIKE '%user';

-- To see grants for a specific user:
-- SHOW GRANTS FOR 'reports_user'@'%';
-- SHOW GRANTS FOR 'operations_user'@'%';
-- SHOW GRANTS FOR 'admin_user'@'%';
-- SHOW GRANTS FOR 'mechanic_user'@'%';
