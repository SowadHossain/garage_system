# Final Test Plan — System Readiness

Goal: Run end-to-end checks across roles and core flows.

## Pre-checks
- Database seeded with sample customers, vehicles, appointments, jobs, bills.
- `.env`/config set for DB; web server running; sessions enabled.

## Auth
- Staff login: admin, receptionist, mechanic accounts can log in.
- Customer login/register/logout flows function and redirect correctly.

## RBAC
- Role guards enforce access: admin-only pages blocked for others; receptionist/mechanic pages blocked for others; customer-only pages redirect staff.

## Dashboards
- Admin/receptionist/mechanic/customer dashboards load with stats; no PHP/SQL errors; links navigate.

## CRUD & Logs
- Customer add/edit/delete with validations and activity logging.
- Activity logs view/export filters work; CSV downloads.

## Analytics
- Reports dashboard loads; all 6 charts render; 7 APIs return expected JSON; filters update data.

## Search
- Global search returns customers/vehicles/appointments; Advanced filters paginate and export CSV.

## Mechanic Flows
- Mechanic dashboard shows assigned jobs and upcoming appointments.
- Jobs and add-services pages currently placeholders (no crash).

## Customer Flows
- Vehicles list loads and search works; dashboard shows recent appointments.

## Security
- Prepared statements used; output escaped; session fixation prevented on logins; bcrypt hashing.

## Negative Tests
- Unauthorized access attempts; SQL injection strings in search/queries; XSS payloads in text inputs — system should stay safe.

## Deliverables
- Record pass/fail for each role’s test plan (see `TEST_PLAN_*` files).
- Log any defects with file path, repro steps, expected vs actual.
