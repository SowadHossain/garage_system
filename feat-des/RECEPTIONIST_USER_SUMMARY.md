# Receptionist User - Brief Summary

Audience: staff users with role `receptionist` (login via `public/staff_login.php`).

## Main Page
- `public/receptionist_dashboard.php`
  - Guards: session check for `staff_role === 'receptionist'`, else redirect to staff login.
  - Stats: Total Customers, Pending Appointments (booked/pending), Today’s Appointments, Unpaid Bills.
  - Recent Appointments: last 8 (joins customers/vehicles; shows status, vehicle, datetime, phone).
  - Recent Customers: last 6 customers with email/phone.
  - Quick links shown: Add Customer, View Customers, Book Appointment, Global Search.

## Core Workflows (implemented)
- Customers:
  - `customers/list.php` — search (LIKE on name/email/phone), table actions to edit customer, view vehicles, view appointments.
  - `customers/add.php` — create customer; validates required name/phone, unique phone/email, optional email, address length; logs activity.
  - `customers/edit.php` — edit/delete customer; validations + unique checks; blocks delete if active appointments; logs activity.
- Search:
  - `public/search.php` — global search (admin + receptionist) across customers, vehicles, appointments with LIKE; links back to edit customers.
- Advanced Filters:
  - `search/advanced_filters.php` — accessible to receptionist (also admin/mechanic); jobs/bills/appointments filtering + CSV export (via APIs `api/search_advanced.php`, `api/export_search.php`).

## Data & Security
- Authorization: `requireRole(['admin','receptionist'])` on customer and search pages; receptionist-only dashboard guard.
- Queries: prepared statements on search and CRUD; LIKE pattern matching for flexible lookups.
- Validation: server-side for required fields, lengths, unique phone/email; deletion safety (blocks if customer has active appointments).
- Activity logging: create/update/delete customer operations log to audit trail.

## Gaps / Notes
- Appointment booking/view pages not reviewed here; dashboard links to appointments but core receptionist CRUD verified mainly on customers + search.
- Vehicles list in customer actions points to `../vehicles/list.php?customer_id=...` (page is customer-facing; mechanics may need customer session; works for viewing customer vehicles when linked from customer context).

## Ready Actions (today)
- Receptionist can log in, view dashboard metrics, add/search/edit/delete customers, run global search, and use advanced filters/exports.
- Use customer list actions to jump to customer’s vehicles and appointments.
