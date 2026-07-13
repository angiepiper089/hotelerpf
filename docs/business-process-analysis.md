# Business Process Analysis — Hotel Industry

*Prepared for: Business Process and ERP Systems — Group Assignment*
*Industry domain: Hospitality (Hotel operations)*

> This document is the raw material for Deliverable 2 (3-page report). Read it, discuss it as a
> group, and rewrite it in your own words — the assignment's Academic Integrity section requires
> you to be able to explain everything you submit.

## 1. Main Organizational Processes

A mid-sized independent hotel ("Grand Horizon Hotel") runs on five interlocking processes:

1. **Reservation-to-Stay** — a guest books a room (walk-in, phone, or OTA), the room is held for
   the date range, the guest checks in, occupies the room, and checks out.
2. **Room & Housekeeping Operations** — rooms cycle through Available → Occupied → Cleaning →
   Available; housekeeping and maintenance must be coordinated with the front desk so a room is
   never sold twice or sold while dirty/under repair.
3. **Guest Relationship Management** — guest profiles, contact details, stay history and loyalty
   status are tracked so repeat guests are recognised and can be upsold or retained.
4. **Procure-to-Pay (Supply Chain)** — consumable stock (linen, toiletries, minibar items,
   cleaning supplies) is monitored, reordered from suppliers via purchase orders, received, and
   paid for.
5. **Order-to-Cash (Billing/Finance)** — charges incurred during a stay (room rate, minibar,
   services) are consolidated into an invoice at check-out and payment is collected and reconciled.

## 2. Stakeholders Involved

| Stakeholder | Interest / Role |
|---|---|
| Front Desk staff | Create/modify reservations, check guests in/out, answer billing queries |
| Housekeeping staff | Update room cleanliness status, flag maintenance issues |
| Finance / Accounts staff | Issue invoices, record payments, manage supplier purchase orders |
| Operations Manager | Oversees occupancy, revenue, staffing and supplier relationships |
| Hotel Guests | Book rooms, expect accurate billing and personalised service |
| Suppliers | Provide linen, minibar stock, cleaning supplies; expect timely, accurate POs |
| IT/System Administrator | Manages user accounts, access control, system uptime |

## 3. Existing Challenges / Inefficiencies (pre-ERP, siloed operation)

- **Information silos** — reservations recorded in one book/spreadsheet, housekeeping status
  tracked verbally or on a whiteboard, and billing calculated manually at checkout; the three
  rarely agree, leading to double-bookings or guests billed for a room that wasn't ready.
- **No single guest history** — a returning guest's previous stays, preferences, and loyalty tier
  are not visible to whoever is on shift, so service personalisation is inconsistent.
- **Manual stock tracking** — minibar and housekeeping supplies are counted physically; stock-outs
  are discovered only when someone goes looking for an item, not proactively.
- **Delayed/error-prone billing** — charges from different sources (room, minibar, services) must
  be manually reconciled at checkout, slowing the process and risking billing errors.
- **No audit trail** — it is difficult to answer "who changed this booking / approved this
  purchase order / issued this refund," which is a problem for accountability and dispute
  resolution.
- **Role confusion** — without system-level access control, any staff member can (in principle)
  see or edit any data, increasing the risk of accidental or malicious data changes.

## 4. Opportunities for Process Improvement

- Centralise reservations, rooms, guests, billing and inventory in **one integrated database** so
  a single action (e.g. check-out) automatically keeps every downstream record consistent.
- Automate the **room status lifecycle** so front desk and housekeeping always see the same,
  current status, preventing double-booking or selling an unclean room.
- Give every staff member **role-based access** to only the modules relevant to their job,
  improving both usability and data security.
- Trigger **low-stock alerts and purchase orders automatically** from actual consumption recorded
  at checkout, rather than relying on manual stock counts.
- Generate **invoices automatically** from the reservation and any recorded extra charges,
  reducing manual calculation and billing errors.
- Maintain a system-wide **audit log** for accountability and easier issue investigation.

## 5. How an ERP System Improves Operational Efficiency

The prototype in this repository demonstrates the improvement directly:

- **Reservations → Rooms integration**: checking a guest in/out automatically updates the room's
  status (`Occupied` → `Cleaning` → `Available`), eliminating the manual whiteboard step and the
  double-booking risk that comes with it (see `modules/reservations/checkin.php` and
  `checkout.php`).
- **Reservations → Billing integration**: check-out automatically calculates room charges from the
  length of stay and room rate, and lets staff add consumed inventory items as service charges in
  the same transaction — producing one accurate invoice instead of a manual reconciliation
  (`modules/reservations/checkout.php`).
- **Billing → Inventory integration**: any consumable item billed to a guest is deducted from
  stock in the same database transaction, so stock levels are always accurate without a separate
  manual count (also in `checkout.php`).
- **Inventory → Supply Chain integration**: items below their reorder level are flagged on the
  Reports and Inventory pages, and a Purchase Order can be raised directly against a supplier;
  receiving the PO restocks Inventory automatically (`modules/inventory/po-action.php`).
- **Guests (CRM)**: every reservation and invoice rolls up into a guest profile showing stay
  history and lifetime spend, enabling loyalty recognition and better service
  (`modules/guests/profile.php`).
- **Security & accountability**: role-based navigation restricts each account to relevant modules,
  and every meaningful action is written to a shared audit log
  (`includes/auth.php::logAudit()`, `modules/users/audit-log.php`).

In short, the ERP prototype replaces five disconnected manual processes with **one shared data
model and set of automated workflows**, which is exactly the "systems integration" argument made
throughout *Enterprise Systems for Management* (see Chapter 2, "Systems Integration," and
Chapter 9, "Business Process Reengineering").