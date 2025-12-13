# Customer User - Brief Summary

Audience: end-users (customers) using self-service portal.

## Auth & Access
- Login: `public/customer_login.php` (email + password, bcrypt verify, logs attempts). Redirects to dashboard on success; blocks empty inputs; invalid credentials show friendly error.
- Register: `public/customer_register.php` (name/email/phone/address/password+confirm; validates email, password length, match; checks duplicate email; hashes password; auto-login on success).
- Logout: `public/customer_logout.php` destroys session and redirects to login.

## Main Page
- `public/customer_dashboard.php`
  - Guard: requires `$_SESSION['customer_id']`, else redirect to login.
  - Stats: total vehicles, total appointments, total bills (via join jobs→bills→appointments per customer).
  - Vehicles: lists all customer vehicles (card grid; brand/model/year/registration/type).
  - Recent Appointments: last 5 with vehicle info and status.
  - Quick actions: link to manage vehicles and appointments (uses downstream pages below).

## Core Workflows
- Vehicles: `vehicles/list.php`
  - Guard: customer session; optional search (LIKE on registration/brand/model/type).
  - Shows vehicle cards with brand/model/year/registration/type and actions (edit/delete) where implemented.
- Appointments: customer-facing pages exist under `appointments/` and are linked from dashboard; primary dashboard shows recent appointments (read-only); booking flow located in `appointments/book.php` (not reviewed here in detail this pass).

## Data & Security
- Prepared statements for login, registration checks, vehicle and appointment queries.
- Password hashing: bcrypt on registration; password_verify on login.
- Session-based auth for all customer pages; redirects to login if missing.
- Validation: server-side for registration (required fields, email format, password length, match, unique email) and for vehicle search inputs (sanitized); vehicle CRUD includes length/uniqueness checks where implemented.

## Gaps / Notes
- Appointment booking/view pages were not deeply reviewed in this short pass; dashboard already pulls recent appointments. If needed, I can document `appointments/book.php` and related flows next.
- Bills/Invoices pages are not customer-facing; totals are shown on dashboard but bill detail pages are staff-side.

## Ready Actions (today)
- Customers can register, log in/out, view dashboard stats, see their vehicles, and view recent appointments.
- Vehicle search and listing are functional; appointments list/booking expected under `appointments/` (not summarized yet).
