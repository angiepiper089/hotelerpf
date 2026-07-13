# ERP Module Design & Architecture

## 1. Mapping to Classic ERP Module Categories

*Enterprise Systems for Management* (Ch. 3, "Enterprise Systems Architecture") describes ERP
systems as being built from a common set of module categories: Production, Purchasing, Inventory
Management, Sales & Marketing, Finance, and Human Resources. This hotel prototype maps its
industry-specific modules onto those categories as follows:

| Textbook ERP category | Hotel ERP module(s) in this prototype |
|---|---|
| Sales & Marketing | Reservations, Guests (CRM) |
| Production / Operations | Room Management (housekeeping/maintenance workflow) |
| Purchasing | Suppliers & Purchase Orders (inside the Inventory module) |
| Inventory Management | Inventory Items |
| Finance | Billing & Payments |
| Human Resources | Employees |
| (cross-cutting) Security | User Accounts & Audit Log |
| (cross-cutting) Business Intelligence | Reports |

## 2. Layered Architecture

Following the layered architecture pattern in Ch. 3 ("Layered Architecture Example"), the
prototype separates concerns into three layers, all deployed as a single PHP application for
simplicity:

```
┌───────────────────────────────────────────────┐
│ Presentation Layer                             │
│ HTML/CSS + Bootstrap 5, includes/header.php,   │
│ sidebar.php, footer.php — role-aware navigation│
└───────────────────────────────────────────────┘
                     │
┌───────────────────────────────────────────────┐
│ Business Logic Layer                           │
│ modules/*/*.php — validation, workflow rules   │
│ (e.g. no double-booking, stock checks,         │
│ invoice/tax calculation, PO approval flow)      │
│ includes/auth.php — role-based access control  │
└───────────────────────────────────────────────┘
                     │
┌───────────────────────────────────────────────┐
│ Data Layer                                     │
│ config/db.php (PDO) → Azure SQL Database        │
│ database/schema.sql — one shared schema         │
└───────────────────────────────────────────────┘
```

This mirrors the book's point that ERP systems replace siloed, per-department data stores with one
shared data layer accessed by multiple functional modules.

## 3. Entity-Relationship Overview

```
Roles ──< Users ──< AuditLog
                 │
                 ├──< Reservations >── Guests
                 │         │
                 │         └── Rooms >── RoomTypes
                 │
                 ├──< PurchaseOrders >── Suppliers
                 │         └──< PurchaseOrderItems >── InventoryItems
                 │
                 └──< Employees

Reservations ──< Invoices >── Guests
Invoices ──< InvoiceItems >── InventoryItems (nullable — room charges have no item)
Invoices ──< Payments
```

Key integration points (the "process integration" the assignment specifically asks for):

- `Reservations.RoomID` → `Rooms` — booking a room is what changes its status.
- `Invoices.ReservationID` → `Reservations` — every invoice traces back to a specific stay.
- `InvoiceItems.ItemID` → `InventoryItems` — billed consumables are the same rows whose stock is
  decremented, so Billing and Inventory can never disagree about what was consumed.
- `PurchaseOrderItems.ItemID` → `InventoryItems` — receiving a PO increments the same stock level
  that Billing decrements, closing the supply-chain loop.
- `Users.RoleID` → `Roles` drives `includes/sidebar.php` — the presentation layer itself changes
  per role, not just what a role is *allowed* to submit.

## 4. Module Responsibility Summary

| Module | Key files | Responsibilities |
|---|---|---|
| Auth/Security | `index.php`, `login.php`, `logout.php`, `includes/auth.php` | Login, session, role-based access, audit logging |
| Dashboard | `dashboard.php` | Cross-module KPIs and workflow visualisation |
| Reservations | `modules/reservations/*.php` | Booking CRUD, double-booking prevention, check-in/out workflow |
| Rooms | `modules/rooms/*.php` | Room & room-type CRUD, housekeeping status updates |
| Guests (CRM) | `modules/guests/*.php` | Guest CRUD, profile with stay/billing history, loyalty tier |
| Billing | `modules/billing/*.php` | Invoice viewing/printing, payment recording |
| Inventory/SCM | `modules/inventory/*.php` | Stock CRUD, supplier CRUD, purchase order lifecycle |
| Employees (HR) | `modules/employees/*.php` | Staff records, optional link to a system account |
| Users/Audit | `modules/users/*.php` | User account management, audit log viewer (Admin only) |
| Reports | `modules/reports/index.php` | Revenue by room type, room status breakdown, low stock, top guests |

## 5. Presentation Slide Mapping

The assignment requires 10 slides covering specific topics — here's where to find each in the
running app for screenshots/demo:

| Slide topic | Where to demo it |
|---|---|
| User Login | `index.php` |
| ERP Dashboard | `dashboard.php` |
| Business Process Workflow | `dashboard.php` workflow diagram, or walk through Reservation → Check-in → Check-out live |
| Interaction Between ERP Modules | `modules/reservations/checkout.php` (updates Rooms, Billing, Inventory in one transaction) |
| Database Functionality | `database/schema.sql` ERD + a live CRUD action (e.g. add a guest, raise a PO) |
| Azure Deployment | The deployed Azure App Service URL + Azure Portal resource view |
| GitHub Repository | Repo structure, commit history, `README.md` |