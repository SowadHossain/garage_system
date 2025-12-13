# Test Plan — Admin

Goal: Verify all admin capabilities and restrictions.

## Access & Auth
- Can: login via `public/staff_login.php` with admin role; redirected to `admin_dashboard.php`.
- Cannot: access admin pages when logged out (should redirect to `login.php` or `staff_login.php`).
- Cannot: access admin-only pages as receptionist/mechanic/customer (should see `access_denied.php` or redirect).

## Dashboard
- Loads 6 stats (customers, staff, vehicles, revenue, pending appts, unpaid bills) without SQL errors.
- Shows recent appointments, top customers, feedback section.
- Quick actions link to reports and management pages.

## Staff Management
- Create staff (`public/create_admin.php`):
  - Valid: name (<=150), username (unique), optional email (valid + unique), role in {admin,receptionist,mechanic}, password >=6.
  - Invalid: duplicate username/email; bad email; short password; invalid role.
- Manage staff list (`public/admin/manage_staff.php`) shows roles, statuses.
- Restriction: No edit staff page (verify link absence).

## Activity Logs
- View logs (`admin/activity_logs.php`): filters by user type/action/severity/status/entity/date/search.
- Pagination works; counts reflect filters.
- Export CSV (`admin/export_logs.php`): matches filter, opens download with BOM.

## Analytics
- Dashboard (`reports/analytics_dashboard.php`):
  - Filter date/mechanic/service; all 6 charts update; 8+ KPIs populate.
  - APIs return JSON: `api/analytics_*.php` respond 200 with expected keys.

## Search
- Global (`public/search.php`): LIKE search across customers/vehicles/appointments; min 2 chars enforced.
- Advanced (`search/advanced_filters.php`): entity select, filters apply; CSV export works.

## Customers
- List (`customers/list.php`): searches by name/email/phone.
- Add (`customers/add.php`): validates, prevents duplicate phone/email, logs activity.
- Edit (`customers/edit.php`): updates with uniqueness checks; delete blocked if active appointments; logs activity.

## Appointments & Jobs
- Appointments list accessible; filters show statuses.
- Jobs list page exists but currently empty — verify link behavior; no errors.

## Reviews
- Moderate (`reviews/moderate.php`): approve/reject/response flows work; status updates visible.

## Security
- Prepared statements in pages tested; outputs escaped; RBAC enforced; bcrypt passwords.

## Negative Paths
- Try SQL injection in search inputs (should be safe).
- XSS attempt in form inputs (should render escaped).
- Access admin pages as non-admin.

## Notes
- Document any missing pages or 500 errors with route and repro.
