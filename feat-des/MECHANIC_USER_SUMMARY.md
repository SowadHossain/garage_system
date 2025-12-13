# Mechanic User - Brief Summary

Audience: staff users with role `mechanic` (login via `public/staff_login.php`).

## Main Page
- `public/mechanic_dashboard.php`
  - Guards: session check for `staff_role === 'mechanic'`, else redirect to staff login.
  - Stats: My Active Jobs, Completed Jobs, Total Open Jobs (all), Pending Appointments (booked/confirmed).
  - My Assigned Jobs: last 8 jobs assigned to this mechanic (joins appointments, customers, vehicles; shows status badge, vehicle, job date, problem notes).
  - Upcoming Appointments: next 6 booked/confirmed appointments with customer, phone, vehicle, date/time.
  - Quick links shown: View All Jobs, Add Services, View Appointments, Vehicle Info (see gaps below).

## Linked Pages (current state)
- `jobs/list.php` – file exists but empty; linked from dashboard.
- `jobs/add_services.php` – file exists but empty; linked from dashboard.
- `appointments/list.php` (wrapped by `public/appointments/list.php`) – target file is empty.
- `vehicles/list.php` – implemented but customer-only; mechanic users will be redirected to customer login.

## Data & Security
- Uses prepared statements for job counts and assigned-jobs queries.
- Joins: jobs → appointments → customers (+ vehicles) for assigned jobs.
- No direct write actions on dashboard; read-only overview.

## Gaps to be aware of
- Job list, add-services, and appointments list are placeholders (empty files); linked buttons currently lead to blank pages.
- Vehicle list link points to a customer-only page; mechanics cannot access without a customer session.

## Ready Actions (today)
- Mechanics can log in and view: personal stats, assigned jobs, and upcoming appointments from the dashboard.
