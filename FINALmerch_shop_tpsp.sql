--  STORED PROCEDURES & TRIGGERS FOR merch_shop

USE merch_shop;

-- Safety: drop existing procedures & triggers if they exist
DROP PROCEDURE IF EXISTS sp_add_user;
DROP PROCEDURE IF EXISTS sp_get_user_by_email;
DROP PROCEDURE IF EXISTS sp_get_products;
DROP PROCEDURE IF EXISTS sp_get_products_by_category;
DROP PROCEDURE IF EXISTS sp_get_order_items;
DROP PROCEDURE IF EXISTS sp_get_order_history_by_user;
DROP PROCEDURE IF EXISTS sp_update_product_stock;
DROP PROCEDURE IF EXISTS sp_create_order;
DROP PROCEDURE IF EXISTS sp_log_action;
DROP PROCEDURE IF EXISTS sp_total_amount_orders;
DROP PROCEDURE IF EXISTS sp_total_amount_branches_orders;
DROP PROCEDURE IF EXISTS sp_checkout;
DROP PROCEDURE IF EXISTS sp_get_orders_admin;
DROP PROCEDURE IF EXISTS sp_get_payments_with_user;
DROP PROCEDURE IF EXISTS sp_update_product;

DROP TRIGGER IF EXISTS trg_after_order_insert_log;
DROP TRIGGER IF EXISTS trg_after_order_update_status_log;
DROP TRIGGER IF EXISTS trg_after_payment_update_log;
DROP TRIGGER IF EXISTS trg_after_orderitems_insert_decrease_stock;
DROP TRIGGER IF EXISTS trg_after_orderitems_update_adjust_stock;
DROP TRIGGER IF EXISTS trg_after_orderitems_delete_restore_stock;
DROP TRIGGER IF EXISTS trg_before_products_update_prevent_negative_stock;
DROP TRIGGER IF EXISTS trg_before_orderitem_insert_prevent_negative_quantity;
DROP TRIGGER IF EXISTS trg_before_payments_insert_prevent_negative_amount;
DROP TRIGGER IF EXISTS trg_before_products_insert_prevent_negative_price;


--  STORED PROCEDURES
DELIMITER $$

-- 1) Add a new user
CREATE PROCEDURE sp_add_user (
    IN p_name      VARCHAR(100),
    IN p_email     VARCHAR(100),
    IN p_password  VARCHAR(255),
    IN p_address   VARCHAR(255),
    IN p_role_id   INT,
    IN p_branch_id INT
)
BEGIN
    INSERT INTO Users (name, email, password, address, role_id, branch_id)
    VALUES (p_name, p_email, p_password, p_address, p_role_id, p_branch_id);
END$$


-- 2) Get user by email (for login)
CREATE PROCEDURE sp_get_user_by_email (
    IN p_email VARCHAR(100)
)
BEGIN
    SELECT u.user_id,
           u.name,
           u.email,
           u.password,
           u.address,
           u.role_id,
           r.role_name,
           u.branch_id
    FROM Users u
    JOIN Roles r ON u.role_id = r.role_id
    WHERE u.email = p_email;
END$$


-- 3) Get all products
CREATE PROCEDURE sp_get_products ()
BEGIN
    SELECT p.product_id,
           p.name,
           p.description,
           p.price,
           p.stock_quantity,
           c.category_name
    FROM Products p
    LEFT JOIN Categories c ON p.category_id = c.category_id
    ORDER BY p.name;
END$$


-- 4) Get products by category
CREATE PROCEDURE sp_get_products_by_category (
    IN p_category_id INT
)
BEGIN
    SELECT p.product_id,
           p.name,
           p.description,
           p.price,
           p.stock_quantity,
           c.category_name
    FROM Products p
    LEFT JOIN Categories c ON p.category_id = c.category_id
    WHERE p.category_id = p_category_id
    ORDER BY p.name;
END$$


-- 5) Get order items by order ID, including product details and converted prices
CREATE PROCEDURE sp_get_order_items (
    IN p_order_id INT
)
BEGIN
    SELECT 
        oi.order_item_id,
        oi.order_id,
        oi.product_id,

        p.name        AS product_name,
        p.description AS product_description,

        oi.quantity,

        -- Base PHP price stored in DB
        oi.unit_price AS base_unit_price_php,

        -- Unit price converted to the order currency
        CASE o.currency_id
            WHEN 1 THEN oi.unit_price * 1          -- PHP
            WHEN 2 THEN oi.unit_price * (1/59)     -- USD (PHP → USD)
            WHEN 3 THEN oi.unit_price * (1/68)     -- EUR (PHP → EUR)
        END AS unit_price_converted,

        -- Line Total (converted to order currency)
        CASE o.currency_id
            WHEN 1 THEN oi.unit_price * oi.quantity * 1
            WHEN 2 THEN oi.unit_price * oi.quantity * (1/59)
            WHEN 3 THEN oi.unit_price * oi.quantity * (1/68)
        END AS line_total_converted,

        -- Base line total in PHP
        (oi.unit_price * oi.quantity) AS base_line_total_php,

        -- Order metadata
        o.user_id,
        o.branch_id,
        o.currency_id,
        o.order_date,
        o.total_amount,
        o.status,
        cur.currency_code

    FROM 
        Order_Items oi
    JOIN Products   p   ON oi.product_id = p.product_id
    JOIN Orders     o   ON oi.order_id   = o.order_id
    JOIN Currencies cur ON o.currency_id = cur.currency_id

    WHERE 
        oi.order_id = p_order_id;
END$$


-- 6) Get order history by user
CREATE PROCEDURE sp_get_order_history_by_user (
    IN p_user_id INT
)
BEGIN
    SELECT o.order_id,
           o.order_date,
           o.total_amount,
           cur.currency_code,
           o.status,
           b.branch_name
    FROM Orders o
    JOIN Currencies cur ON o.currency_id = cur.currency_id
    LEFT JOIN Branches b ON o.branch_id = b.branch_id
    WHERE o.user_id = p_user_id
    ORDER BY o.order_date DESC;
END$$


-- 7) Update product stock by delta (can be + or -)
CREATE PROCEDURE sp_update_product_stock (
    IN p_product_id INT,
    IN p_delta      INT
)
BEGIN
    UPDATE Products
    SET stock_quantity = stock_quantity + p_delta
    WHERE product_id = p_product_id;
END$$


-- 8) Create a basic order (app can add items afterwards)
CREATE PROCEDURE sp_create_order (
    IN  p_user_id      INT,
    IN  p_branch_id    INT,
    IN  p_currency_id  INT,
    IN  p_total_amount DECIMAL(10,2),
    OUT p_new_order_id INT
)
BEGIN
    INSERT INTO Orders (user_id, branch_id, currency_id, total_amount, status)
    VALUES (p_user_id, p_branch_id, p_currency_id, p_total_amount, 'Pending');

    SET p_new_order_id = LAST_INSERT_ID();
END$$


-- 9) Generic logger into Transaction_Log
CREATE PROCEDURE sp_log_action (
    IN p_user_id INT,
    IN p_order_id INT,
    IN p_action  VARCHAR(100),
    IN p_details TEXT
)
BEGIN
    INSERT INTO Transaction_Log (user_id, order_id, action, details)
    VALUES (p_user_id, p_order_id, p_action, p_details);
END$$


-- 10) Get the sum of the amount from the orders
CREATE PROCEDURE sp_total_amount_orders ()
BEGIN
    SELECT SUM(total_amount) AS sum_amounts FROM Orders;
END$$


-- 11) Get the sum of the amounts by branch
CREATE PROCEDURE sp_total_amount_branches_orders ()
BEGIN
    SELECT b.branch_id, b.branch_name, SUM(o.total_amount) AS total
    FROM Orders o
    JOIN Branches b ON b.branch_id = o.branch_id
    GROUP BY b.branch_id;
END$$


-- 12) Admin: list all orders with customer & branch
CREATE PROCEDURE sp_get_orders_admin ()
BEGIN
    SELECT
        o.order_id,
        o.order_date,
        o.total_amount,
        o.status,

        u.user_id      AS customer_id,
        u.name         AS customer_name,
        u.email        AS customer_email,

        b.branch_name,
        c.currency_code
    FROM Orders o
    JOIN Users      u ON o.user_id    = u.user_id
    LEFT JOIN Branches b ON o.branch_id = b.branch_id
    JOIN Currencies c  ON o.currency_id = c.currency_id
    ORDER BY o.order_date DESC, o.order_id DESC;
END$$


-- 13) Admin: list all payments with customer & branch
CREATE PROCEDURE sp_get_payments_with_user ()
BEGIN
    SELECT 
        p.payment_id,
        p.order_id,
        p.amount,
        p.method,
        p.status,
        p.payment_date,

        u.user_id      AS customer_id,
        u.name         AS customer_name,
        u.email        AS customer_email,

        b.branch_name  AS branch_name,
        c.currency_code
    FROM Payments p
    JOIN Orders     o ON p.order_id    = o.order_id
    JOIN Users      u ON o.user_id     = u.user_id
    LEFT JOIN Branches b ON o.branch_id = b.branch_id
    JOIN Currencies c   ON p.currency_id = c.currency_id
    ORDER BY p.payment_date DESC, p.payment_id DESC;
END$$


-- 14) Update an existing product (for admin product editing)
CREATE PROCEDURE sp_update_product (
    IN p_product_id INT,
    IN p_name VARCHAR(100),
    IN p_description TEXT,
    IN p_price DECIMAL(10,2),
    IN p_stock INT,
    IN p_category_id INT
)
BEGIN
    UPDATE Products
    SET 
        name           = p_name,
        description    = p_description,
        price          = p_price,
        stock_quantity = p_stock,
        category_id    = p_category_id
    WHERE product_id = p_product_id;
END$$


-- 15) Checkout: create order + payment in one transaction
CREATE PROCEDURE sp_checkout (
    IN  p_user_id        INT,
    IN  p_branch_id      INT,
    IN  p_currency_id    INT,
    IN  p_total_amount   DECIMAL(10,2),
    IN  p_payment_amount DECIMAL(10,2),
    IN  p_payment_method VARCHAR(50),

    OUT p_new_order_id   INT,
    OUT p_new_payment_id INT
)
BEGIN
    DECLARE v_order_status   VARCHAR(50) DEFAULT 'Pending';
    DECLARE v_payment_status VARCHAR(50);

    -- Decide payment status based on amount paid vs order total
    IF p_payment_amount >= p_total_amount THEN
        SET v_order_status   = 'Paid';
        SET v_payment_status = 'Paid';
    ELSEIF p_payment_amount > 0 THEN
        SET v_order_status   = 'Partially Paid';
        SET v_payment_status = 'Partially Paid';
    ELSE
        SET v_order_status   = 'Pending';
        SET v_payment_status = 'Unpaid';
    END IF;

    START TRANSACTION;

      -- 1) Create order
      INSERT INTO Orders (
          user_id,
          branch_id,
          currency_id,
          order_date,
          total_amount,
          status
      ) VALUES (
          p_user_id,
          p_branch_id,
          p_currency_id,
          NOW(),
          p_total_amount,
          v_order_status
      );

      SET p_new_order_id = LAST_INSERT_ID();

      -- 2) Create payment record (optional: amount can be 0)
      INSERT INTO Payments (
          order_id,
          currency_id,
          amount,
          method,
          status,
          payment_date
      ) VALUES (
          p_new_order_id,
          p_currency_id,
          p_payment_amount,
          p_payment_method,
          v_payment_status,
          CASE 
            WHEN p_payment_amount > 0 THEN NOW()
            ELSE NULL
          END
      );

      SET p_new_payment_id = LAST_INSERT_ID();

    COMMIT;
END$$

DELIMITER ;


--  TRIGGERS
DELIMITER $$

-- 1) Log when a new order is created
CREATE TRIGGER trg_after_order_insert_log
AFTER INSERT ON Orders
FOR EACH ROW
BEGIN
    INSERT INTO Transaction_Log (user_id, order_id, action, details)
    VALUES (
        NEW.user_id,
        NEW.order_id,
        'CREATE_ORDER',
        CONCAT('Order created with status ', NEW.status, ' and total ', NEW.total_amount)
    );
END$$


-- 2) Log when an order status changes
CREATE TRIGGER trg_after_order_update_status_log
AFTER UPDATE ON Orders
FOR EACH ROW
BEGIN
    IF NEW.status <> OLD.status THEN
        INSERT INTO Transaction_Log (user_id, order_id, action, details)
        VALUES (
            NEW.user_id,
            NEW.order_id,
            'UPDATE_ORDER_STATUS',
            CONCAT('Status changed from ', OLD.status, ' to ', NEW.status)
        );
    END IF;
END$$


-- 3) Log payment status changes
CREATE TRIGGER trg_after_payment_update_log
AFTER UPDATE ON Payments
FOR EACH ROW
BEGIN
    IF NEW.status <> OLD.status THEN
        INSERT INTO Transaction_Log (user_id, order_id, action, details)
        VALUES (
            NULL,
            NEW.order_id,
            'PAYMENT_STATUS_CHANGE',
            CONCAT('Payment ', NEW.payment_id,
                   ' status changed from ', OLD.status,
                   ' to ', NEW.status,
                   ', amount ', NEW.amount)
        );
    END IF;
END$$


-- 4) After inserting an order item, decrease product stock
CREATE TRIGGER trg_after_orderitems_insert_decrease_stock
AFTER INSERT ON Order_Items
FOR EACH ROW
BEGIN
    UPDATE Products
    SET stock_quantity = stock_quantity - NEW.quantity
    WHERE product_id = NEW.product_id;
END$$


-- 5) After updating an order item, adjust product stock by the difference
CREATE TRIGGER trg_after_orderitems_update_adjust_stock
AFTER UPDATE ON Order_Items
FOR EACH ROW
BEGIN
    DECLARE v_diff INT;
    SET v_diff = OLD.quantity - NEW.quantity; 

    UPDATE Products
    SET stock_quantity = stock_quantity + v_diff
    WHERE product_id = NEW.product_id;
END$$


-- 6) After deleting an order item, restore stock
CREATE TRIGGER trg_after_orderitems_delete_restore_stock
AFTER DELETE ON Order_Items
FOR EACH ROW
BEGIN
    UPDATE Products
    SET stock_quantity = stock_quantity + OLD.quantity
    WHERE product_id = OLD.product_id;
END$$


-- 7) Prevent negative stock on Products
CREATE TRIGGER trg_before_products_update_prevent_negative_stock
BEFORE UPDATE ON Products
FOR EACH ROW
BEGIN
    IF NEW.stock_quantity < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock quantity cannot be negative.';
    END IF;
END$$


-- 8) Prevent negative quantity in Order Items
CREATE TRIGGER trg_before_orderitem_insert_prevent_negative_quantity
BEFORE INSERT ON Order_Items
FOR EACH ROW
BEGIN
    IF NEW.quantity < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Order quantity cannot be negative.';
    END IF;
END$$


-- 9) Prevent negative amount in Payments
CREATE TRIGGER trg_before_payments_insert_prevent_negative_amount
BEFORE INSERT ON Payments
FOR EACH ROW
BEGIN
    IF NEW.amount < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Amount cannot be negative.';
    END IF;
END$$


-- 10) Prevent negative price in Products
CREATE TRIGGER trg_before_products_insert_prevent_negative_price
BEFORE INSERT ON Products
FOR EACH ROW
BEGIN
    IF NEW.price < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'The price cannot be negative.';
    END IF;
END$$

DELIMITER ;
