<?php
// auth/checkout-process.php
declare(strict_types=1);

require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/csrf.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/checkout.php');
    exit;
}

$pdo = require __DIR__ . '/../app/db.php';
$me  = auth_user();
$userId = (int)($me['user_id'] ?? 0);

// ------------------------------
// 1. CSRF CHECK
// ------------------------------
if (!csrf_verify($_POST['csrf'] ?? '')) {
    http_response_code(400);
    echo 'Invalid CSRF token.';
    exit;
}

// ------------------------------
// 2. BASIC VALIDATION
// ------------------------------
$errors = [];

$name     = trim($_POST['name']     ?? '');
$email    = trim($_POST['email']    ?? '');
$phone    = trim($_POST['phone']    ?? '');
$address  = trim($_POST['address']  ?? '');
$fulfil   = trim($_POST['fulfilment'] ?? '');
$method   = trim($_POST['payment_method'] ?? '');
$branchId = (int)($_POST['branch_id'] ?? 0);
$cartJson = $_POST['cart_json'] ?? '';

if ($userId <= 0) {
    $errors[] = "User not found in session.";
}

if ($name === '')           $errors[] = "Full name is required.";
if ($email === '')          $errors[] = "Email is required.";
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}

if ($phone === '')          $errors[] = "Phone number is required.";
elseif (!preg_match('/^09\d{9}$/', $phone)) {
    $errors[] = "Phone number must be a valid PH mobile (09XXXXXXXXX).";
}

if (!in_array($fulfil, ['pickup', 'delivery'], true)) {
    $errors[] = "Invalid fulfilment option.";
}

if ($fulfil === 'delivery' && $address === '') {
    $errors[] = "Address is required for delivery.";
}

if (!in_array($method, ['Cash', 'GCash', 'Card'], true)) {
    $errors[] = "Invalid payment method.";
}

if ($branchId <= 0) {
    $errors[] = "Invalid branch selected.";
}

// ------------------------------
// 3. VALIDATE CART
// ------------------------------
$cart = json_decode($cartJson, true);

if (!is_array($cart) || count($cart) === 0) {
    $errors[] = "Your cart is empty.";
}

// Helper to render errors nicely
function checkout_render_errors(array $errors): void {
    $title = 'Checkout Error';
    include __DIR__ . '/../partials/header.php';
    ?>
    <section class="py-5">
      <div class="container">
        <h2 class="mb-3">Checkout error</h2>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
              <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <a href="/pages/checkout.php" class="btn btn-outline-secondary">Back to checkout</a>
      </div>
    </section>
    <?php
    include __DIR__ . '/../partials/footer.php';
    exit;
}

if (!empty($errors)) {
    checkout_render_errors($errors);
}

// ------------------------------
// 4. REBUILD TOTAL FROM DATABASE
//    & NORMALIZE CART ITEMS
// ------------------------------
$cartItems = [];  // [ ['product_id'=>.., 'qty'=>.., 'unit_price'=>..], ... ]
$total = 0.0;

try {
    foreach ($cart as $item) {
        $pid = (int)($item['product_id'] ?? 0);
        // JS sends "qty"; keep "quantity" as fallback just in case
        $qty = (int)($item['qty'] ?? $item['quantity'] ?? 0);

        if ($pid <= 0 || $qty <= 0) {
            $errors[] = "Invalid cart item.";
            continue;
        }

        // Fetch price securely
        $stmt = $pdo->prepare("SELECT price, name FROM Products WHERE product_id = ?");
        $stmt->execute([$pid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $errors[] = "Product not found (ID $pid).";
            continue;
        }

        $unitPrice = (float)$row['price'];
        $lineTotal = $qty * $unitPrice;
        $total    += $lineTotal;

        $cartItems[] = [
            'product_id' => $pid,
            'qty'        => $qty,
            'unit_price' => $unitPrice,
            'name'       => $row['name'],
        ];
    }
} catch (Exception $e) {
    $errors[] = "Error calculating cart totals.";
}

if ($total <= 0 || empty($cartItems)) {
    $errors[] = "Could not determine a valid total for this order.";
}

if (!empty($errors)) {
    checkout_render_errors($errors);
}

// ------------------------------
// 5. CHECK & DEDUCT STOCK PER BRANCH
//    using Product_Stock (product_id + branch_id)
// ------------------------------
foreach ($cartItems as $ci) {
    $pid = $ci['product_id'];
    $qty = $ci['qty'];

    // Get current stock in that branch
    $stmt = $pdo->prepare("
        SELECT stock_quantity 
        FROM Product_Stock 
        WHERE product_id = ? AND branch_id = ?
        LIMIT 1
    ");
    $stmt->execute([$pid, $branchId]);
    $stockRow = $stmt->fetch(PDO::FETCH_ASSOC);

    $currentStock = $stockRow ? (int)$stockRow['stock_quantity'] : 0;

    if ($currentStock < $qty) {
        $errors[] = "Not enough stock for '{$ci['name']}' in this branch (have $currentStock, need $qty).";
    }
}

if (!empty($errors)) {
    checkout_render_errors($errors);
}

// If we reach here: there is enough stock in the selected branch.
// Deduct from Product_Stock
foreach ($cartItems as $ci) {
    $pid = $ci['product_id'];
    $qty = $ci['qty'];

    $stmt = $pdo->prepare("
        UPDATE Product_Stock
        SET stock_quantity = stock_quantity - ?
        WHERE product_id = ? AND branch_id = ?
    ");
    $stmt->execute([$qty, $pid, $branchId]);
}

// ------------------------------
// 6. CALL STORED PROCEDURE sp_checkout
//    p_user_id, p_branch_id, p_currency_id, p_total_amount,
//    p_payment_amount, p_payment_method, OUT p_new_order_id, p_new_payment_id
// ------------------------------
$currencyId = 1; // PHP (assuming id=1 in Currencies)

try {
    $stmt = $pdo->prepare("CALL sp_checkout(?,?,?,?,?,?,@oid,@pid)");
    $stmt->execute([
        $userId,
        $branchId,
        $currencyId,
        $total,  // order total
        $total,  // payment amount (mark as fully paid for now)
        $method
    ]);
    $stmt->closeCursor();

    // retrieve OUT params
    $oid = (int)$pdo->query("SELECT @oid")->fetchColumn();
    $pid = (int)$pdo->query("SELECT @pid")->fetchColumn();

    if ($oid <= 0) {
        $errors[] = "Order was not created properly.";
    }
} catch (PDOException $e) {
    $errors[] = "Checkout failed: " . $e->getMessage();
}

if (!empty($errors)) {
    checkout_render_errors($errors);
}

// ------------------------------
// 7. INSERT ORDER LINE ITEMS
// ------------------------------
foreach ($cartItems as $ci) {
    $pid = $ci['product_id'];
    $qty = $ci['qty'];
    $up  = $ci['unit_price'];

    $stmt = $pdo->prepare("
        INSERT INTO Order_Items (order_id, product_id, quantity, unit_price)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$oid, $pid, $qty, $up]);
}

// ------------------------------
// 8. CLEAR CART (frontend localStorage)
// ------------------------------
// match the CART_KEY in app.js: 'greenshop_cart_v1'
echo "<script>try{localStorage.removeItem('greenshop_cart_v1');}catch(e){};</script>";

// ------------------------------
// 9. REDIRECT TO SUCCESS PAGE
// ------------------------------
header("Location: /pages/order-success.php?order_id=" . urlencode((string)$oid));
exit;
