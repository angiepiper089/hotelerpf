-- =====================================================================
-- Grand Horizon Hotel ERP — MySQL variant of seed.sql
-- Run AFTER schema.mysql.sql. All demo users share the password:
-- Password123 (bcrypt hash below is valid for PHP password_verify()).
-- =====================================================================

INSERT INTO Roles (RoleName, Description) VALUES
('Admin',        'Full system access, user administration, audit log'),
('Manager',      'Cross-module oversight: reservations, rooms, guests, inventory, reports'),
('FrontDesk',    'Reservations, check-in/out, guest profiles, billing'),
('Housekeeping', 'Room status and cleaning workflow'),
('Finance',      'Billing, payments, inventory cost, purchase orders, reports');

INSERT INTO Users (FullName, Email, Username, PasswordHash, RoleID, IsActive) VALUES
('Aisha Perera',    'admin@grandhorizon.test',       'admin',       '$2b$10$yKvAWGaj4eMfPF48v89RmOgDqynHbS9xPDMckv9PxMukhmd6CfBA6', 1, 1),
('Kasun Fernando',  'manager@grandhorizon.test',     'manager',     '$2b$10$yKvAWGaj4eMfPF48v89RmOgDqynHbS9xPDMckv9PxMukhmd6CfBA6', 2, 1),
('Nadeesha Silva',  'frontdesk@grandhorizon.test',   'frontdesk1',  '$2b$10$yKvAWGaj4eMfPF48v89RmOgDqynHbS9xPDMckv9PxMukhmd6CfBA6', 3, 1),
('Ruwan Jayasuriya','housekeeping@grandhorizon.test','housekeep1',  '$2b$10$yKvAWGaj4eMfPF48v89RmOgDqynHbS9xPDMckv9PxMukhmd6CfBA6', 4, 1),
('Dilani Rathnayake','finance@grandhorizon.test',    'finance1',    '$2b$10$yKvAWGaj4eMfPF48v89RmOgDqynHbS9xPDMckv9PxMukhmd6CfBA6', 5, 1);

INSERT INTO RoomTypes (TypeName, BaseRate, MaxOccupancy, Description) VALUES
('Standard',  8500.00, 2, 'Standard double room with city view'),
('Deluxe',    12500.00, 2, 'Deluxe room with balcony and sea view'),
('Suite',     22000.00, 4, 'Two-room suite with lounge area'),
('Executive', 30000.00, 4, 'Top-floor executive suite with butler service');

INSERT INTO Rooms (RoomNumber, RoomTypeID, Floor, Status, Notes) VALUES
('101', 1, 1, 'Available', NULL),
('102', 1, 1, 'Cleaning', NULL),
('103', 1, 1, 'Available', NULL),
('201', 2, 2, 'Occupied', NULL),
('202', 2, 2, 'Available', NULL),
('203', 2, 2, 'Maintenance', 'AC unit being repaired'),
('301', 3, 3, 'Available', NULL),
('302', 3, 3, 'Occupied', NULL),
('401', 4, 4, 'Available', NULL),
('402', 4, 4, 'Available', NULL);

INSERT INTO Guests (FullName, Email, Phone, Address, IDNumber, LoyaltyTier) VALUES
('Ishan Wickramasinghe', 'ishan.w@example.com', '0771234567', '12 Galle Rd, Colombo', 'NIC990112345V', 'Gold'),
('Priya Kumaraswamy',    'priya.k@example.com', '0772345678', '45 Kandy Rd, Kandy',   'NIC950234567V', 'Silver'),
('John Anderson',        'john.a@example.com',  '0094771122334', '221B Baker Street, London', 'PASS12345', 'Platinum'),
('Chamari Dissanayake',  'chamari.d@example.com','0773456789', '9 Temple Rd, Galle',   'NIC880345678V', 'Standard'),
('Mark Thompson',        'mark.t@example.com',  '0094772233445', '5 Elm St, Sydney',     'PASS67890', 'Standard');

INSERT INTO Reservations (GuestID, RoomID, CheckInDate, CheckOutDate, ActualCheckIn, ActualCheckOut, NumGuests, Status, CreatedBy) VALUES
(1, 4, '2026-07-08', '2026-07-12', '2026-07-08 14:10:00', NULL, 2, 'CheckedIn', 3),
(2, 8, '2026-07-09', '2026-07-11', '2026-07-09 13:05:00', NULL, 1, 'CheckedIn', 3),
(3, 1, '2026-07-01', '2026-07-05', '2026-07-01 15:00:00', '2026-07-05 10:30:00', 2, 'CheckedOut', 3),
(4, 3, '2026-07-15', '2026-07-18', NULL, NULL, 1, 'Booked', 3),
(5, 7, '2026-07-20', '2026-07-22', NULL, NULL, 2, 'Booked', 3);

INSERT INTO Suppliers (SupplierName, ContactPerson, Phone, Email, Address) VALUES
('Ceylon Linen Supplies',   'S. Bandara', '0112233445', 'sales@ceylonlinen.test', 'Colombo 10'),
('Fresh Valley Foods',      'R. Gunasekara', '0112244556', 'orders@freshvalley.test', 'Nuwara Eliya'),
('Sparkle Housekeeping Co.','M. Perera', '0112255667', 'contact@sparklehk.test', 'Colombo 05');

INSERT INTO InventoryItems (ItemName, Category, UnitOfMeasure, QuantityOnHand, ReorderLevel, UnitCost, SupplierID) VALUES
('Bath Towel Set',      'Linen',      'set',   40, 20, 1500.00, 1),
('Bedsheet Set',        'Linen',      'set',   30, 15, 2200.00, 1),
('Mineral Water Bottle','Minibar',    'bottle',120, 50, 90.00, 2),
('Coffee Sachet Pack',  'Minibar',    'pack',  60, 25, 350.00, 2),
('All-Purpose Cleaner', 'Housekeeping','bottle',18, 20, 480.00, 3),
('Toilet Amenity Kit',  'Housekeeping','kit',   25, 20, 260.00, 3);

INSERT INTO PurchaseOrders (SupplierID, OrderDate, Status, TotalAmount, CreatedBy) VALUES
(3, '2026-07-05 09:00:00', 'Approved', 9600.00, 5),
(2, '2026-07-07 11:00:00', 'Pending',  15750.00, 5);

INSERT INTO PurchaseOrderItems (PurchaseOrderID, ItemID, Quantity, UnitCost) VALUES
(1, 5, 20, 480.00),
(2, 3, 100, 90.00),
(2, 4, 20, 350.00);

INSERT INTO Invoices (ReservationID, GuestID, IssueDate, RoomCharges, ServiceCharges, TaxAmount, TotalAmount, Status) VALUES
(3, 3, '2026-07-05 10:45:00', 34000.00, 720.00, 3472.00, 38192.00, 'Paid');

INSERT INTO InvoiceItems (InvoiceID, ItemID, Description, Quantity, Amount) VALUES
(1, NULL, 'Room charges (4 nights x Standard rate)', 4, 34000.00),
(1, 3, 'Mineral Water Bottle', 6, 540.00),
(1, 4, 'Coffee Sachet Pack', 2, 180.00);

INSERT INTO Payments (InvoiceID, PaymentDate, Amount, Method, ReceivedBy) VALUES
(1, '2026-07-05 10:50:00', 38192.00, 'Card', 3);

INSERT INTO Employees (UserID, FullName, Position, Department, HireDate, Phone, Email) VALUES
(2, 'Kasun Fernando',   'Operations Manager', 'Management',   '2022-03-01', '0771112222', 'manager@grandhorizon.test'),
(3, 'Nadeesha Silva',   'Front Desk Officer', 'Front Office', '2023-01-15', '0772223333', 'frontdesk@grandhorizon.test'),
(4, 'Ruwan Jayasuriya', 'Housekeeping Supervisor', 'Housekeeping', '2021-11-20', '0773334444', 'housekeeping@grandhorizon.test'),
(5, 'Dilani Rathnayake','Finance Officer', 'Finance', '2022-06-10', '0774445555', 'finance@grandhorizon.test');
