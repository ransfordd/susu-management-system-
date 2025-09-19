# Enhanced Susu System with Loan Management (PHP + MySQL)

## Requirements
- PHP 8.0+
- MySQL 8.0+
- Apache/Nginx with PHP

## Setup
1. Create database and tables:
   - Import `schema.sql` into MySQL (via PHPMyAdmin or CLI).
2. Web root:
   - Point your web server to this folder or copy this folder into your server root.
3. Configure DB connection (optional):
   - Environment variables: `DB_HOST`, `DB_NAME` (default `susu_system`), `DB_USER`, `DB_PASS`.
4. Seed an admin user:
   - Run `seed_admin.php` in browser or CLI to create an admin login.

## First Login
1. Go to `/index.php`.
2. Login with the admin credentials you seeded.

## Navigation
- Admin Dashboard: `/index.php` after login
- Loan Products: `/admin_products.php` → Create: `/admin_product_create.php`
- Loan Applications (Agent): `/agent_apps.php` → Create: `/agent_app_create.php`
- Integrated Collection (Agent): `/views/agent/collect.php`
- Notifications: `/notifications.php` (send: `/notifications_send.php`)
- Financial Report: `/admin_report_financial.php`
- Agent Commission Report: `/admin_agent_commission.php`
- Analytics: `/admin_analytics.php`
- Holidays: `/admin_holidays.php` (add: `/admin_holiday_create.php`, reschedule: `/admin_holiday_reschedule.php`)
- Client Portal Schedules: `/client_susu_schedule.php`, `/client_loan_schedule.php`
- CSV Export (agent commission): `/export_csv.php?type=agent_commission&start=YYYY-MM-DD&end=YYYY-MM-DD`

## Receipts
- Susu Receipt: `/receipt_susu.php?receipt=...`
- Loan Receipt: `/receipt_loan.php?receipt=...`

## Notes
- Security essentials implemented: prepared statements, CSRF token, role checks.
- Email/SMS stubs: `includes/mailer_stub.php`, `includes/sms_stub.php`.
- Mobile money flow is a placeholder (`/views/agent/mobile_money.php`).

## Development
- MVC-like structure: `controllers/`, `models/`, `views/`, `config/`, `includes/`.
- Business logic engines under `models/Engines/`.


