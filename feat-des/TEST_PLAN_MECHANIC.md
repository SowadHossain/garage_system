# Test Plan — Mechanic

Goal: Verify mechanic dashboard and accessible views, and restrictions.

## Access & Auth
- Can: login via `public/staff_login.php` → `mechanic_dashboard.php`.
- Cannot: access admin-only or receptionist-only pages.

## Dashboard
- Stats: My Active Jobs, Completed Jobs, Total Open Jobs, Pending Appointments calculate correctly.
- My Assigned Jobs: shows last 8 jobs for this mechanic (joins with appointments/customers/vehicles).
- Upcoming Appointments: shows next 6 booked/confirmed.
- Quick links: View All Jobs, Add Services, View Appointments, Vehicle Info navigate.

## Linked Pages
- `jobs/list.php`: currently empty; verify navigation doesn’t error; note as gap.
- `jobs/add_services.php`: currently empty; verify no crash; note as gap.
- `public/appointments/list.php` wrapper: underlying file currently empty; verify no crash.
- `public/vehicles/list.php` → `vehicles/list.php`: customer-only; accessing without customer session should redirect to customer login (expected restriction).

## Restrictions
- Cannot: use admin analytics or logs.
- Cannot: access customer management pages.

## Security
- Dashboard queries use prepared statements; RBAC hard-checks mechanic role.

## Negative Paths
- Access mechanic dashboard without session → redirect to staff login.
- Attempt to open vehicle list directly → redirected (customer-only).
