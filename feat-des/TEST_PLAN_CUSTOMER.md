# Test Plan — Customer

Goal: Verify customer self-service features and restrictions.

## Auth & Access
- Register (`public/customer_register.php`):
  - Valid: name/email/phone/address/password+confirm; email format; password >=6; passwords match; unique email → auto-login.
  - Invalid: duplicate email; bad email; short password; mismatch; missing required.
- Login (`public/customer_login.php`): correct credentials → dashboard; wrong credentials → error; logs attempts; session regeneration on success.
- Logout (`public/customer_logout.php`): ends session; redirects to login.

## Dashboard (`public/customer_dashboard.php`)
- Guard: requires customer session.
- Stats: total vehicles, total appointments, total bills (via joins) populate.
- Vehicles list: shows customer vehicles.
- Recent appointments: last 5 with vehicle info.

## Vehicles (`vehicles/list.php`)
- Guard: customer session; redirects to login if missing.
- Search: LIKE on registration/brand/model/type.
- Cards: show brand/model/year/registration/type.

## Appointments
- Booking/view: verify presence of `appointments/book.php` and `appointments/view_appointments.php` flows (basic navigation and guards).
- Recent appointments visibility on dashboard.

## Restrictions
- Cannot: access staff dashboards or admin-only pages.
- Cannot: see other customers’ vehicles/appointments.

## Security
- Prepared statements; bcrypt hashing; output escaping.

## Negative Paths
- Attempt accessing vehicles list without session → redirect to customer login.
- Try SQL injection via search; should be safe.
