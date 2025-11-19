--  MERCH SHOP DATABASE

-- 1) Create schema and use it
CREATE SCHEMA IF NOT EXISTS merch_shop;
USE merch_shop;

-- 2) Drop tables in safe order (children first)
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS Transaction_Log;
DROP TABLE IF EXISTS Payments;
DROP TABLE IF EXISTS Order_Items;
DROP TABLE IF EXISTS Orders;
DROP TABLE IF EXISTS Product_Stock;
DROP TABLE IF EXISTS Products;
DROP TABLE IF EXISTS Categories;
DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS Roles;
DROP TABLE IF EXISTS Branches;
DROP TABLE IF EXISTS Currencies;

SET FOREIGN_KEY_CHECKS = 1;

-- 3) Core reference tables

-- Roles: Admin / Manager / Staff / Customer
CREATE TABLE Roles (
  role_id INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL UNIQUE
);

-- Branches: at least 2
CREATE TABLE Branches (
  branch_id INT AUTO_INCREMENT PRIMARY KEY,
  branch_name VARCHAR(100) NOT NULL,
  location VARCHAR(255) NOT NULL
);

-- Currencies: PHP, USD, EUR
CREATE TABLE Currencies (
  currency_id INT AUTO_INCREMENT PRIMARY KEY,
  currency_code VARCHAR(10) NOT NULL UNIQUE,  -- PHP, USD, EUR
  currency_name VARCHAR(50) NOT NULL,
  symbol VARCHAR(5) NOT NULL,
  exchange_rate_to_php DECIMAL(10,4) NOT NULL  -- FX to PHP
);


-- 4) Users, Categories, Products

CREATE TABLE Users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  address VARCHAR(255),
  role_id INT NOT NULL,
  branch_id INT,
  CONSTRAINT fk_users_roles
    FOREIGN KEY (role_id) REFERENCES Roles(role_id)
      ON UPDATE CASCADE
      ON DELETE RESTRICT,
  CONSTRAINT fk_users_branches
    FOREIGN KEY (branch_id) REFERENCES Branches(branch_id)
      ON UPDATE CASCADE
      ON DELETE SET NULL
);

CREATE TABLE Categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL
);

-- Keep global stock_quantity for now (your PHP still uses it)
CREATE TABLE Products (
  product_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  stock_quantity INT NOT NULL,
  category_id INT,
  CONSTRAINT fk_products_categories
    FOREIGN KEY (category_id) REFERENCES Categories(category_id)
      ON UPDATE CASCADE
      ON DELETE SET NULL
);

-- NEW: per-branch stock table
CREATE TABLE Product_Stock (
  stock_id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  branch_id INT NOT NULL,
  stock_quantity INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_stock_product
      FOREIGN KEY (product_id) REFERENCES Products(product_id)
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_stock_branch
      FOREIGN KEY (branch_id) REFERENCES Branches(branch_id)
      ON UPDATE CASCADE ON DELETE CASCADE,
  UNIQUE (product_id, branch_id)  -- one row per product+branch
);

-- 5) Orders, Order_Items, Payments, Transaction_Log

CREATE TABLE Orders (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  branch_id INT,
  currency_id INT NOT NULL,
  order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  total_amount DECIMAL(10,2) NOT NULL,
  status VARCHAR(50) DEFAULT 'Pending',
  CONSTRAINT fk_orders_users
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
      ON UPDATE CASCADE
      ON DELETE RESTRICT,
  CONSTRAINT fk_orders_branches
    FOREIGN KEY (branch_id) REFERENCES Branches(branch_id)
      ON UPDATE CASCADE
      ON DELETE SET NULL,
  CONSTRAINT fk_orders_currencies
    FOREIGN KEY (currency_id) REFERENCES Currencies(currency_id)
      ON UPDATE CASCADE
      ON DELETE RESTRICT
);

CREATE TABLE Order_Items (
  order_item_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_orderitems_orders
    FOREIGN KEY (order_id) REFERENCES Orders(order_id)
      ON UPDATE CASCADE
      ON DELETE CASCADE,
  CONSTRAINT fk_orderitems_products
    FOREIGN KEY (product_id) REFERENCES Products(product_id)
      ON UPDATE CASCADE
      ON DELETE RESTRICT
);

CREATE TABLE Payments (
  payment_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  currency_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(50) NOT NULL,
  status VARCHAR(50) DEFAULT 'Unpaid',
  payment_date DATETIME,
  CONSTRAINT fk_payments_orders
    FOREIGN KEY (order_id) REFERENCES Orders(order_id)
      ON UPDATE CASCADE
      ON DELETE CASCADE,
  CONSTRAINT fk_payments_currencies
    FOREIGN KEY (currency_id) REFERENCES Currencies(currency_id)
      ON UPDATE CASCADE
      ON DELETE RESTRICT
);

CREATE TABLE Transaction_Log (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  order_id INT,
  action VARCHAR(100) NOT NULL,
  details TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_users
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
      ON UPDATE CASCADE
      ON DELETE SET NULL,
  CONSTRAINT fk_log_orders
    FOREIGN KEY (order_id) REFERENCES Orders(order_id)
      ON UPDATE CASCADE
      ON DELETE SET NULL
);

-- 6) Seed data

-- Roles (now includes Manager = 2)
INSERT INTO Roles (role_name) VALUES
('Admin'),     -- 1
('Manager'),   -- 2
('Staff'),     -- 3
('Customer');  -- 4

-- Branches
INSERT INTO Branches (branch_name, location) VALUES
('Taft',   'De La Salle University, Taft Avenue, Manila'),
('Laguna', 'De La Salle University - Laguna Campus, Laguna');

-- Currencies (includes EUR)
INSERT INTO Currencies (currency_code, currency_name, symbol, exchange_rate_to_php) VALUES
('PHP', 'Philippine Peso', '₱', 1.0000),
('USD', 'US Dollar',       '$', 59.0000),
('EUR', 'Euro',            '€', 68.0000);

-- Categories
INSERT INTO Categories (category_name) VALUES
('Apparel'),        -- 1
('Accessories'),    -- 2
('Collectibles');   -- 3

-- Users (Sample) – role_ids now line up with Roles seed above
INSERT INTO Users (name, email, password, address, role_id, branch_id) VALUES
('Admin User',   'admin@example.com',
 '$2a$12$c8GgO.mmsi/Q0RlpueT7DeL0KPT/iaZ/tcpDtW0OinguAyEiE5BdW', 'Taft, Manila',   1, 1),
('Manager User', 'manager@example.com',
 '$2a$12$.2Q3YUOy7XhyZOymCdUp7urc7FPs46LR8AiWBUhfqBuw6dB1uiUmW', 'Taft, Manila',   2, 1),
('Staff User',   'staff@example.com',
 '$2a$12$ZooItmS6SDC5ypKN6B5nD.syCsKzGjXHBvHYobOTENWG1JvJsW/GC', 'BGC, Taguig',    3, 2),
('Customer User','customer@example.com',
 '$2a$12$fMjAqHc96XVODl7eMiPKVuqiV9fGBsK1CwOiaHcFVz5iyDcYhlzd.', 'Makati City',    4, 1);

-- Products (based on your frontend JSON)
INSERT INTO Products (name, description, price, stock_quantity, category_id) VALUES
('DLSU Classic Hoodie',
 'Soft fleece hoodie with embroidered DLSU crest.',
 1495.00,
 50,
 1),

('DLSU Green Cap',
 'Classic cap with stitched logo and adjustable strap.',
 395.00,
 60,
 2),

('DLSU Varsity Jacket',
 'Green varsity jacket with white sleeves and bold embroidered DLSU lettering.',
 995.00,
 40,
 3),

('Varsity Jersey',
 'Lightweight jersey inspired by the Green Archers.',
 1295.00,
 50,
 1),

('Sticker Pack',
 'Vinyl sticker set with DLSU marks and icons.',
 50.00,
 59,
 3),

('Zip Hoodie',
 'Full-zip hoodie with side pockets and crest print.',
 1595.00,
 40,
 1);

-- 7) Structure checks (optional)
DESCRIBE Users;
DESCRIBE Products;
DESCRIBE Product_Stock;
DESCRIBE Orders;
DESCRIBE Order_Items;
DESCRIBE Currencies;
DESCRIBE Transaction_Log;

-- 8) Quick data checks (optional)
SELECT * FROM Roles;
SELECT * FROM Users;
SELECT * FROM Products;
SELECT * FROM Product_Stock;
SELECT * FROM Orders;

