--  STORED PROCEDURES & TRIGGERS FOR merch_shop

USE merch_shop;

-- Safety: drop existing procedures & triggers if they exist
DROP PROCEDURE IF EXISTS sp_add_user;
DROP PROCEDURE IF EXISTS sp_get_user_by_email;
DROP PROCEDURE IF EXISTS sp_get_products;
DROP PROCEDURE IF EXISTS sp_get_products_by_category;
DROP PROCEDURE IF EXISTS sp_get_order_history_by_user;
DROP PROCEDURE IF EXISTS sp_update_product_stock;
DROP PROCEDURE IF EXISTS sp_create_order;
DROP PROCEDURE IF EXISTS sp_log_action;

DROP TRIGGER IF EXISTS trg_after_order_insert_log;
DROP TRIGGER IF EXISTS trg_after_order_update_status_log;
DROP TRIGGER IF EXISTS trg_after_payment_update_log;
DROP TRIGGER IF EXISTS trg_after_orderitems_insert_decrease_stock;
DROP TRIGGER IF EXISTS trg_after_orderitems_update_adjust_stock;
DROP TRIGGER IF EXISTS trg_after_orderitems_delete_restore_stock;
DROP TRIGGER IF EXISTS trg_before_products_update_prevent_negative_stock;

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


-- 5) Get order history by user
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


-- 6) Update product stock by delta (can be + or -)
CREATE PROCEDURE sp_update_product_stock (
    IN p_product_id INT,
    IN p_delta      INT
)
BEGIN
    UPDATE Products
    SET stock_quantity = stock_quantity + p_delta
    WHERE product_id = p_product_id;
END$$


-- 7) Create a basic order (app can add items afterwards)
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


-- 8) Generic logger into Transaction_Log
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

DELIMITER ;


USE merch_shop;

DROP PROCEDURE IF EXISTS sp_checkout;

DELIMITER $$

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
            NULL,  -- we do not always know which user triggered this
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
    -- If NEW > OLD, stock goes down; if NEW < OLD, stock goes up

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

DELIMITER ;
