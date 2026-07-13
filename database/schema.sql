/* =====================================================================
   Grand Horizon Hotel ERP — Database Schema
   Target: Azure SQL Database (T-SQL)

   Design notes (mapping to Enterprise Systems for Management concepts):
   - One shared schema replaces the "information silos" the book describes
     in Ch.2 (Systems Integration): Reservations, Rooms, Guests, Billing,
     Inventory/Suppliers and HR all read/write the same integrated tables
     instead of separate departmental spreadsheets.
   - Roles/Users implement role-based access described in the Security
     chapter (Ch.10).
   - Suppliers/InventoryItems/PurchaseOrders model the Supply Chain
     Management processes in Ch.11 (procurement / e-procurement flow).
   - Guests/Reservations model the Customer Relationship Management
     lifecycle in Ch.12 (acquisition -> stay -> billing -> retention).
   - AuditLog supports the change-management / accountability theme
     that runs through the Implementation and Security chapters.
   ===================================================================== */

IF DB_ID('hotel_erp') IS NULL
BEGIN
    PRINT 'Run this script while connected to the target database (hotel_erp). Create the database first if needed.';
END
GO

/* ---------------------------------------------------------------------
   1. Security / Access module
   --------------------------------------------------------------------- */
CREATE TABLE Roles (
    RoleID       INT IDENTITY(1,1) PRIMARY KEY,
    RoleName     VARCHAR(30) NOT NULL UNIQUE,
    Description  VARCHAR(200) NULL
);
GO

CREATE TABLE Users (
    UserID        INT IDENTITY(1,1) PRIMARY KEY,
    FullName      VARCHAR(100) NOT NULL,
    Email         VARCHAR(120) NOT NULL UNIQUE,
    Username      VARCHAR(50)  NOT NULL UNIQUE,
    PasswordHash  VARCHAR(255) NOT NULL,
    RoleID        INT NOT NULL FOREIGN KEY REFERENCES Roles(RoleID),
    IsActive      BIT NOT NULL DEFAULT 1,
    CreatedAt     DATETIME NOT NULL DEFAULT GETDATE()
);
GO

/* ---------------------------------------------------------------------
   2. Room Management module
   --------------------------------------------------------------------- */
CREATE TABLE RoomTypes (
    RoomTypeID   INT IDENTITY(1,1) PRIMARY KEY,
    TypeName     VARCHAR(50) NOT NULL,
    BaseRate     DECIMAL(10,2) NOT NULL,
    MaxOccupancy INT NOT NULL DEFAULT 2,
    Description  VARCHAR(255) NULL
);
GO

CREATE TABLE Rooms (
    RoomID       INT IDENTITY(1,1) PRIMARY KEY,
    RoomNumber   VARCHAR(10) NOT NULL UNIQUE,
    RoomTypeID   INT NOT NULL FOREIGN KEY REFERENCES RoomTypes(RoomTypeID),
    Floor        INT NOT NULL DEFAULT 1,
    Status       VARCHAR(20) NOT NULL DEFAULT 'Available'
                 CHECK (Status IN ('Available','Occupied','Maintenance','Cleaning')),
    Notes        VARCHAR(255) NULL
);
GO

/* ---------------------------------------------------------------------
   3. Guest Management module (CRM concepts)
   --------------------------------------------------------------------- */
CREATE TABLE Guests (
    GuestID      INT IDENTITY(1,1) PRIMARY KEY,
    FullName     VARCHAR(100) NOT NULL,
    Email        VARCHAR(120) NULL,
    Phone        VARCHAR(30) NULL,
    Address      VARCHAR(255) NULL,
    IDNumber     VARCHAR(50) NULL,
    LoyaltyTier  VARCHAR(20) NOT NULL DEFAULT 'Standard'
                 CHECK (LoyaltyTier IN ('Standard','Silver','Gold','Platinum')),
    CreatedAt    DATETIME NOT NULL DEFAULT GETDATE()
);
GO

/* ---------------------------------------------------------------------
   4. Reservations module (core business process)
   --------------------------------------------------------------------- */
CREATE TABLE Reservations (
    ReservationID  INT IDENTITY(1,1) PRIMARY KEY,
    GuestID        INT NOT NULL FOREIGN KEY REFERENCES Guests(GuestID),
    RoomID         INT NOT NULL FOREIGN KEY REFERENCES Rooms(RoomID),
    CheckInDate    DATE NOT NULL,
    CheckOutDate   DATE NOT NULL,
    ActualCheckIn  DATETIME NULL,
    ActualCheckOut DATETIME NULL,
    NumGuests      INT NOT NULL DEFAULT 1,
    Status         VARCHAR(20) NOT NULL DEFAULT 'Booked'
                   CHECK (Status IN ('Booked','CheckedIn','CheckedOut','Cancelled')),
    CreatedBy      INT NULL FOREIGN KEY REFERENCES Users(UserID),
    CreatedAt      DATETIME NOT NULL DEFAULT GETDATE(),
    CHECK (CheckOutDate > CheckInDate)
);
GO

/* ---------------------------------------------------------------------
   5. Supply Chain module (Suppliers / Inventory / Purchase Orders)
   --------------------------------------------------------------------- */
CREATE TABLE Suppliers (
    SupplierID     INT IDENTITY(1,1) PRIMARY KEY,
    SupplierName   VARCHAR(100) NOT NULL,
    ContactPerson  VARCHAR(100) NULL,
    Phone          VARCHAR(30) NULL,
    Email          VARCHAR(120) NULL,
    Address        VARCHAR(255) NULL
);
GO

CREATE TABLE InventoryItems (
    ItemID          INT IDENTITY(1,1) PRIMARY KEY,
    ItemName        VARCHAR(100) NOT NULL,
    Category        VARCHAR(50) NOT NULL DEFAULT 'General',
    UnitOfMeasure   VARCHAR(20) NOT NULL DEFAULT 'unit',
    QuantityOnHand  INT NOT NULL DEFAULT 0,
    ReorderLevel    INT NOT NULL DEFAULT 10,
    UnitCost        DECIMAL(10,2) NOT NULL DEFAULT 0,
    SupplierID      INT NULL FOREIGN KEY REFERENCES Suppliers(SupplierID)
);
GO

CREATE TABLE PurchaseOrders (
    PurchaseOrderID INT IDENTITY(1,1) PRIMARY KEY,
    SupplierID      INT NOT NULL FOREIGN KEY REFERENCES Suppliers(SupplierID),
    OrderDate       DATETIME NOT NULL DEFAULT GETDATE(),
    Status          VARCHAR(20) NOT NULL DEFAULT 'Pending'
                    CHECK (Status IN ('Pending','Approved','Received','Cancelled')),
    TotalAmount     DECIMAL(10,2) NOT NULL DEFAULT 0,
    CreatedBy       INT NULL FOREIGN KEY REFERENCES Users(UserID)
);
GO

CREATE TABLE PurchaseOrderItems (
    POItemID         INT IDENTITY(1,1) PRIMARY KEY,
    PurchaseOrderID  INT NOT NULL FOREIGN KEY REFERENCES PurchaseOrders(PurchaseOrderID) ON DELETE CASCADE,
    ItemID           INT NOT NULL FOREIGN KEY REFERENCES InventoryItems(ItemID),
    Quantity         INT NOT NULL,
    UnitCost         DECIMAL(10,2) NOT NULL
);
GO

/* ---------------------------------------------------------------------
   6. Billing module (Finance)
   --------------------------------------------------------------------- */
CREATE TABLE Invoices (
    InvoiceID      INT IDENTITY(1,1) PRIMARY KEY,
    ReservationID  INT NOT NULL FOREIGN KEY REFERENCES Reservations(ReservationID),
    GuestID        INT NOT NULL FOREIGN KEY REFERENCES Guests(GuestID),
    IssueDate      DATETIME NOT NULL DEFAULT GETDATE(),
    RoomCharges    DECIMAL(10,2) NOT NULL DEFAULT 0,
    ServiceCharges DECIMAL(10,2) NOT NULL DEFAULT 0,
    TaxAmount      DECIMAL(10,2) NOT NULL DEFAULT 0,
    TotalAmount    DECIMAL(10,2) NOT NULL DEFAULT 0,
    Status         VARCHAR(20) NOT NULL DEFAULT 'Unpaid'
                   CHECK (Status IN ('Unpaid','Partial','Paid'))
);
GO

CREATE TABLE InvoiceItems (
    InvoiceItemID INT IDENTITY(1,1) PRIMARY KEY,
    InvoiceID     INT NOT NULL FOREIGN KEY REFERENCES Invoices(InvoiceID) ON DELETE CASCADE,
    ItemID        INT NULL FOREIGN KEY REFERENCES InventoryItems(ItemID),
    Description   VARCHAR(150) NOT NULL,
    Quantity      INT NOT NULL DEFAULT 1,
    Amount        DECIMAL(10,2) NOT NULL
);
GO

CREATE TABLE Payments (
    PaymentID    INT IDENTITY(1,1) PRIMARY KEY,
    InvoiceID    INT NOT NULL FOREIGN KEY REFERENCES Invoices(InvoiceID),
    PaymentDate  DATETIME NOT NULL DEFAULT GETDATE(),
    Amount       DECIMAL(10,2) NOT NULL,
    Method       VARCHAR(20) NOT NULL DEFAULT 'Cash'
                 CHECK (Method IN ('Cash','Card','BankTransfer')),
    ReceivedBy   INT NULL FOREIGN KEY REFERENCES Users(UserID)
);
GO

/* ---------------------------------------------------------------------
   7. HR module
   --------------------------------------------------------------------- */
CREATE TABLE Employees (
    EmployeeID   INT IDENTITY(1,1) PRIMARY KEY,
    UserID       INT NULL FOREIGN KEY REFERENCES Users(UserID),
    FullName     VARCHAR(100) NOT NULL,
    Position     VARCHAR(80) NOT NULL,
    Department   VARCHAR(60) NOT NULL,
    HireDate     DATE NOT NULL DEFAULT GETDATE(),
    Phone        VARCHAR(30) NULL,
    Email        VARCHAR(120) NULL
);
GO

/* ---------------------------------------------------------------------
   8. Audit / Security log (cross-cutting)
   --------------------------------------------------------------------- */
CREATE TABLE AuditLog (
    LogID      INT IDENTITY(1,1) PRIMARY KEY,
    UserID     INT NULL FOREIGN KEY REFERENCES Users(UserID),
    Action     VARCHAR(50) NOT NULL,
    TableName  VARCHAR(50) NOT NULL,
    RecordID   INT NULL,
    Details    VARCHAR(400) NULL,
    LogTime    DATETIME NOT NULL DEFAULT GETDATE()
);
GO

/* ---------------------------------------------------------------------
   Helpful indexes for the most common lookups
   --------------------------------------------------------------------- */
CREATE INDEX IX_Reservations_RoomID ON Reservations(RoomID);
CREATE INDEX IX_Reservations_GuestID ON Reservations(GuestID);
CREATE INDEX IX_Invoices_GuestID ON Invoices(GuestID);
CREATE INDEX IX_InventoryItems_SupplierID ON InventoryItems(SupplierID);
GO
