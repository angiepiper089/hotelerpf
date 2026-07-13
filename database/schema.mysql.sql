-- =====================================================================
-- Grand Horizon Hotel ERP — MySQL/MariaDB variant of schema.sql
-- Use this for local development (XAMPP/WAMP) with the pdo_mysql
-- fallback in config/db.php. Production target is still Azure SQL
-- (see schema.sql) — keep both in sync when the model changes.
-- =====================================================================



CREATE TABLE Roles (
    RoleID       INT AUTO_INCREMENT PRIMARY KEY,
    RoleName     VARCHAR(30) NOT NULL UNIQUE,
    Description  VARCHAR(200) NULL
) ENGINE=InnoDB;

CREATE TABLE Users (
    UserID        INT AUTO_INCREMENT PRIMARY KEY,
    FullName      VARCHAR(100) NOT NULL,
    Email         VARCHAR(120) NOT NULL UNIQUE,
    Username      VARCHAR(50)  NOT NULL UNIQUE,
    PasswordHash  VARCHAR(255) NOT NULL,
    RoleID        INT NOT NULL,
    IsActive      TINYINT(1) NOT NULL DEFAULT 1,
    CreatedAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (RoleID) REFERENCES Roles(RoleID)
) ENGINE=InnoDB;

CREATE TABLE RoomTypes (
    RoomTypeID   INT AUTO_INCREMENT PRIMARY KEY,
    TypeName     VARCHAR(50) NOT NULL,
    BaseRate     DECIMAL(10,2) NOT NULL,
    MaxOccupancy INT NOT NULL DEFAULT 2,
    Description  VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE Rooms (
    RoomID       INT AUTO_INCREMENT PRIMARY KEY,
    RoomNumber   VARCHAR(10) NOT NULL UNIQUE,
    RoomTypeID   INT NOT NULL,
    Floor        INT NOT NULL DEFAULT 1,
    Status       ENUM('Available','Occupied','Maintenance','Cleaning') NOT NULL DEFAULT 'Available',
    Notes        VARCHAR(255) NULL,
    FOREIGN KEY (RoomTypeID) REFERENCES RoomTypes(RoomTypeID)
) ENGINE=InnoDB;

CREATE TABLE Guests (
    GuestID      INT AUTO_INCREMENT PRIMARY KEY,
    FullName     VARCHAR(100) NOT NULL,
    Email        VARCHAR(120) NULL,
    Phone        VARCHAR(30) NULL,
    Address      VARCHAR(255) NULL,
    IDNumber     VARCHAR(50) NULL,
    LoyaltyTier  ENUM('Standard','Silver','Gold','Platinum') NOT NULL DEFAULT 'Standard',
    CreatedAt    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE Reservations (
    ReservationID  INT AUTO_INCREMENT PRIMARY KEY,
    GuestID        INT NOT NULL,
    RoomID         INT NOT NULL,
    CheckInDate    DATE NOT NULL,
    CheckOutDate   DATE NOT NULL,
    ActualCheckIn  DATETIME NULL,
    ActualCheckOut DATETIME NULL,
    NumGuests      INT NOT NULL DEFAULT 1,
    Status         ENUM('Booked','CheckedIn','CheckedOut','Cancelled') NOT NULL DEFAULT 'Booked',
    CreatedBy      INT NULL,
    CreatedAt      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (GuestID) REFERENCES Guests(GuestID),
    FOREIGN KEY (RoomID) REFERENCES Rooms(RoomID),
    FOREIGN KEY (CreatedBy) REFERENCES Users(UserID)
) ENGINE=InnoDB;

CREATE TABLE Suppliers (
    SupplierID     INT AUTO_INCREMENT PRIMARY KEY,
    SupplierName   VARCHAR(100) NOT NULL,
    ContactPerson  VARCHAR(100) NULL,
    Phone          VARCHAR(30) NULL,
    Email          VARCHAR(120) NULL,
    Address        VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE InventoryItems (
    ItemID          INT AUTO_INCREMENT PRIMARY KEY,
    ItemName        VARCHAR(100) NOT NULL,
    Category        VARCHAR(50) NOT NULL DEFAULT 'General',
    UnitOfMeasure   VARCHAR(20) NOT NULL DEFAULT 'unit',
    QuantityOnHand  INT NOT NULL DEFAULT 0,
    ReorderLevel    INT NOT NULL DEFAULT 10,
    UnitCost        DECIMAL(10,2) NOT NULL DEFAULT 0,
    SupplierID      INT NULL,
    FOREIGN KEY (SupplierID) REFERENCES Suppliers(SupplierID)
) ENGINE=InnoDB;

CREATE TABLE PurchaseOrders (
    PurchaseOrderID INT AUTO_INCREMENT PRIMARY KEY,
    SupplierID      INT NOT NULL,
    OrderDate       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Status          ENUM('Pending','Approved','Received','Cancelled') NOT NULL DEFAULT 'Pending',
    TotalAmount     DECIMAL(10,2) NOT NULL DEFAULT 0,
    CreatedBy       INT NULL,
    FOREIGN KEY (SupplierID) REFERENCES Suppliers(SupplierID),
    FOREIGN KEY (CreatedBy) REFERENCES Users(UserID)
) ENGINE=InnoDB;

CREATE TABLE PurchaseOrderItems (
    POItemID         INT AUTO_INCREMENT PRIMARY KEY,
    PurchaseOrderID  INT NOT NULL,
    ItemID           INT NOT NULL,
    Quantity         INT NOT NULL,
    UnitCost         DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (PurchaseOrderID) REFERENCES PurchaseOrders(PurchaseOrderID) ON DELETE CASCADE,
    FOREIGN KEY (ItemID) REFERENCES InventoryItems(ItemID)
) ENGINE=InnoDB;

CREATE TABLE Invoices (
    InvoiceID      INT AUTO_INCREMENT PRIMARY KEY,
    ReservationID  INT NOT NULL,
    GuestID        INT NOT NULL,
    IssueDate      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    RoomCharges    DECIMAL(10,2) NOT NULL DEFAULT 0,
    ServiceCharges DECIMAL(10,2) NOT NULL DEFAULT 0,
    TaxAmount      DECIMAL(10,2) NOT NULL DEFAULT 0,
    TotalAmount    DECIMAL(10,2) NOT NULL DEFAULT 0,
    Status         ENUM('Unpaid','Partial','Paid') NOT NULL DEFAULT 'Unpaid',
    FOREIGN KEY (ReservationID) REFERENCES Reservations(ReservationID),
    FOREIGN KEY (GuestID) REFERENCES Guests(GuestID)
) ENGINE=InnoDB;

CREATE TABLE InvoiceItems (
    InvoiceItemID INT AUTO_INCREMENT PRIMARY KEY,
    InvoiceID     INT NOT NULL,
    ItemID        INT NULL,
    Description   VARCHAR(150) NOT NULL,
    Quantity      INT NOT NULL DEFAULT 1,
    Amount        DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (InvoiceID) REFERENCES Invoices(InvoiceID) ON DELETE CASCADE,
    FOREIGN KEY (ItemID) REFERENCES InventoryItems(ItemID)
) ENGINE=InnoDB;

CREATE TABLE Payments (
    PaymentID    INT AUTO_INCREMENT PRIMARY KEY,
    InvoiceID    INT NOT NULL,
    PaymentDate  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Amount       DECIMAL(10,2) NOT NULL,
    Method       ENUM('Cash','Card','BankTransfer') NOT NULL DEFAULT 'Cash',
    ReceivedBy   INT NULL,
    FOREIGN KEY (InvoiceID) REFERENCES Invoices(InvoiceID),
    FOREIGN KEY (ReceivedBy) REFERENCES Users(UserID)
) ENGINE=InnoDB;

CREATE TABLE Employees (
    EmployeeID   INT AUTO_INCREMENT PRIMARY KEY,
    UserID       INT NULL,
    FullName     VARCHAR(100) NOT NULL,
    Position     VARCHAR(80) NOT NULL,
    Department   VARCHAR(60) NOT NULL,
    HireDate     DATE NOT NULL,
    Phone        VARCHAR(30) NULL,
    Email        VARCHAR(120) NULL,
    FOREIGN KEY (UserID) REFERENCES Users(UserID)
) ENGINE=InnoDB;

CREATE TABLE AuditLog (
    LogID      INT AUTO_INCREMENT PRIMARY KEY,
    UserID     INT NULL,
    Action     VARCHAR(50) NOT NULL,
    TableName  VARCHAR(50) NOT NULL,
    RecordID   INT NULL,
    Details    VARCHAR(400) NULL,
    LogTime    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID)
) ENGINE=InnoDB;

CREATE INDEX IX_Reservations_RoomID ON Reservations(RoomID);
CREATE INDEX IX_Reservations_GuestID ON Reservations(GuestID);
CREATE INDEX IX_Invoices_GuestID ON Invoices(GuestID);
CREATE INDEX IX_InventoryItems_SupplierID ON InventoryItems(SupplierID);
