# Admin User - Brief Summary

Audience: staff users with role `admin` (login via `public/staff_login.php`).

## Main Page
- `public/admin_dashboard.php`: guard for admin role; shows 6 stats (customers, staff, vehicles, revenue, pending appts, unpaid bills), recent appointments, top customers, customer feedback, quick actions to reports/search/staff.

## Core Capabilities
- Staff: `public/create_admin.php` (add staff: admin/receptionist/mechanic with validation + bcrypt), `public/admin/manage_staff.php` (list roles/status). Edit staff page not present.
- Activity Logs: `admin/activity_logs.php` (filters: user type/action/severity/status/entity/date/search, pagination), `admin/export_logs.php` (CSV export with filters).
- Analytics: `reports/analytics_dashboard.php` with 6 Chart.js charts and 8+ KPIs; powered by APIs in `api/analytics_*.php`.
- Search: `public/search.php` (global customers/vehicles/appointments), `search/advanced_filters.php` + `api/search_advanced.php` + `api/export_search.php` for filtered lists/CSV across appointments/bills/jobs.
- Customers: `customers/list.php`, `add.php`, `edit.php` (CRUD with validation, unique phone/email, delete guard on active appts), view vehicles/appointments per customer.
- Appointments/Jobs: `appointments/list.php` (shared), `jobs/list.php` (shared); admin can view all. (jobs list file currently empty placeholder—needs buildout if desired.)
- Reviews: `reviews/moderate.php` for approval/response.
- Vehicles: `public/vehicles/list.php` wrapper → `vehicles/list.php` (customer-focused view).

## Security & Data
- RBAC: `requireRole(['admin', ...])` on protected pages; dashboard hard-checks admin session.
- SQL safety: prepared statements throughout; outputs escaped with `htmlspecialchars`.
- Auth: staff login via `public/staff_login.php`; passwords hashed with bcrypt.
- Activity logging on CRUD and logins (via `includes/activity_logger.php`).

## Known Gaps
- Staff edit page not implemented (manage_staff is view-only).
- `jobs/list.php` placeholder empty; dashboard links there.

## Ready Actions
- Admin can log in, view full dashboard, add staff, audit logs/export, run analytics, search globally or with advanced filters, and manage customers/appointments/jobs/reviews. Add/edit staff and jobs list UIs are the main missing pieces if you want them built.
