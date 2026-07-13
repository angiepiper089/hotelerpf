# Grand Horizon Hotel ERP

A prototype Hotel ERP system built for the **Business Process and ERP Systems — Group Assignment**.
It demonstrates how a hospitality business's core operations (Reservations, Rooms, Guests/CRM,
Billing/Finance, Inventory & Suppliers/SCM, HR, Security) can be integrated around one shared
database instead of siloed departmental spreadsheets — the central theme of *Enterprise Systems
for Management* (Motiwalla & Thompson).

**Stack:** PHP 8 (procedural, PDO) · HTML5 · CSS3 (custom + Bootstrap 5) · Azure SQL Database
(SQL Server), with a MySQL-compatible fallback for local development.

## Modules (≥ 4 required by the assignment — this build has 8)

| Module | Textbook concept it demonstrates |
|---|---|
| Reservations | Core business process; workflow automation |
| Room Management | Operations status tracking, housekeeping workflow |
| Guests | Customer Relationship Management (Ch. 12): unified profile, loyalty tier, lifetime value |
| Billing & Payments | Finance module; invoice generation triggered by the Reservations process |
| Inventory & Suppliers | Supply Chain Management (Ch. 11): procurement, purchase orders, restocking |
| Employees (HR) | People/organization module, linked to system accounts |
| User Accounts & Audit Log | Security/role-based access and accountability (Ch. 10) |
| Reports | Cross-module analytics (occupancy, revenue, CRM top-spenders, low stock) |

See [`docs/erp-module-design.md`](docs/erp-module-design.md) for the data model and
[`docs/business-process-analysis.md`](docs/business-process-analysis.md) for the process analysis
write-up (stakeholders, challenges, ERP benefits) that feeds directly into the assignment's 3-page
report.

## Project Structure

```
erp/
├── index.php, login.php, logout.php, dashboard.php   # Auth + landing
├── config/db.php            # PDO connection (Azure SQL, MySQL fallback)
├── includes/                # Shared header/sidebar/footer/auth/helpers
├── assets/css, assets/js    # Front-end styling and behaviour
├── modules/
│   ├── reservations/  rooms/  guests/  billing/
│   ├── inventory/  employees/  users/  reports/
├── database/
│   ├── schema.sql, seed.sql            # Azure SQL (T-SQL)
│   └── schema.mysql.sql, seed.mysql.sql # Local dev (MySQL/XAMPP)
├── docs/                     # Report-ready analysis & deployment guide
└── .github/workflows/        # CI/CD pipeline to Azure App Service
```

## Local Setup (XAMPP / MySQL — fastest way to develop as a team)

1. Copy `.env.example` to `.env` and set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` for your local
   MySQL instance (defaults match a stock XAMPP install).
2. Create the database and load sample data:
   ```
   mysql -u root -p < database/schema.mysql.sql
   mysql -u root -p < database/seed.mysql.sql
   ```
3. Copy the project into `htdocs/` (or run `php -S localhost:8000` from this folder) and open it
   in a browser.
4. Log in with any demo account — see below.

`config/db.php` automatically uses the MySQL fallback when the `pdo_sqlsrv` extension isn't
installed, so no code changes are needed between local dev and Azure.

## Azure Deployment (production target)

Full step-by-step instructions, including Azure SQL firewall setup and the GitHub Actions
pipeline, are in [`docs/deployment-guide.md`](docs/deployment-guide.md). Summary:

1. Create an **Azure SQL Database**, run `database/schema.sql` then `database/seed.sql` against it
   (e.g. via the Query Editor in the Azure Portal).
2. Create an **Azure App Service** (Linux, PHP 8.2 runtime).
3. Set Application Settings `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` to your Azure SQL
   server's values (see `.env.example`).
4. Deploy via GitHub Actions (`.github/workflows/azure-deploy.yml`) or `az webapp up`.

## Demo Accounts

All seeded accounts share the password **`Password123`**.

| Username | Role | Sees |
|---|---|---|
| `admin` | Admin | Everything, including User Accounts & Audit Log |
| `manager` | Manager | Everything except User Accounts |
| `frontdesk1` | FrontDesk | Reservations, Rooms, Guests, Billing |
| `housekeep1` | Housekeeping | Rooms (status updates only) |
| `finance1` | Finance | Billing, Inventory, Suppliers/POs, Reports |

## Security & Integration Notes

- Passwords are hashed with bcrypt (`password_hash`/`password_verify`); no plaintext passwords are
  stored anywhere, including in `seed.sql`.
- All SQL is executed through parameterized PDO statements to prevent SQL injection.
- Every module writes to a shared `AuditLog` table (`includes/auth.php::logAudit()`), viewable by
  Admins, which demonstrates the accountability/change-management practices from the ERP security
  chapter.
- Role-based navigation (`includes/sidebar.php`) mirrors how commercial ERP suites expose different
  modules to different job functions instead of giving every user access to everything.
- The Reservations check-out flow (`modules/reservations/checkout.php`) is the clearest example of
  **process integration**: one action updates Reservations, Rooms, Billing and Inventory in a
  single database transaction.