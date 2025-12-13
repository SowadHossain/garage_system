# Test Plan — Receptionist

Goal: Verify receptionist capabilities for customer management and search, and restrictions.

## Access & Auth
- Can: login via `public/staff_login.php` → `receptionist_dashboard.php`.
- Cannot: access admin-only pages; should redirect/deny.

## Dashboard
- Stats: total customers, pending appointments, today’s appointments, unpaid bills load.
- Recent lists: appointments (8), customers (6) show correct joins.
- Quick links navigate without errors.

## Customers
- List (`customers/list.php`): LIKE search name/email/phone; table actions to edit, view vehicles, view appointments.
- Add (`customers/add.php`): validations (required name/phone, unique phone/email, address length), activity logging.
- Edit/Delete (`customers/edit.php`): updates with uniqueness checks; delete blocks on active appointments; logs activity.

## Search
- Global search (`public/search.php`): min 2-char input; customers/vehicles/appointments results.
- Advanced filters (`search/advanced_filters.php`): can access; run filters on appointments/bills/jobs; CSV export.

## Appointments
- Can open `public/appointments/list.php` wrapper; underlying `appointments/list.php` is currently empty — verify no crash.
- Update status (`appointments/update_status.php`) presence check; ensure RBAC if receptionist uses it.

## Restrictions
- Cannot: access admin activity logs, analytics management; ensure role guard.
- Cannot: view customer-only pages requiring `customer_id` session unless viewed via context redirects.

## Security
- Prepared statements; outputs escaped; RBAC via `requireRole(['admin','receptionist'])`.

## Negative Paths
- Try duplicate customer email/phone; verify errors.
- Try deleting customer with active appointments; verify block.
- Attempt accessing admin pages.
