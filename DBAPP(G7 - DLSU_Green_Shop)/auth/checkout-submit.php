<?php
// auth/checkout-submit.php
declare(strict_types=1);

require_once __DIR__ . '/../app/csrf.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/session.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /pages/checkout.php');
  exit;
}

if (!csrf_verify($_POST['csrf'] ?? '')) {
  http_response_code(400);
  echo 'Invalid CSRF token';
  exit;
}

$pdo = require __DIR__ . '/../app/db.php';
$me  = auth_user();
$userId = (int)($me['user_id'] ?? 0);

if ($userId <= 0) {
  header('Location: /pages/checkout.php?error=no_user');
  exit;
}

// 1) Parse cart
$cartJson = $_POST['cart_json'] ?? '';
$items = json_decode($cartJson, true);

if (!is_array($items) || count($items) === 0) {
  header('Location: /pages/checkout.php?error=empty_cart');
  exit;
}

// 2) Basic form fields
$fulfilment = $_POST['fulfilment'] ?? 'pickup';
$branchName = $_POST['branch'] ?? 'Taft';
$address    = trim($_POST['address'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? 'Cash';
$paymentRef = trim($_POST['payment_reference'] ?? '');
$currencyCode = $_POST['currency_code'] ?? 'PHP'; // display-only for now

try {
  // 3) Resolve branch_id
  $stmt = $pdo->prepare("SELECT branch_id FROM Branches WHERE branch_name = ? LIMIT 1");
  $stmt->execute([$branchName]);
  $branchId = (int)($stmt->fetchColumn() ?: 0);
  if ($branchId <= 0) {
    // fallback: null branch
    $branchId = null;
  }

  // 4) Resolve PHP currency_id (we store totals in PHP)
  $stmt = $pdo->prepare("SELECT currency_id FROM Currencies WHERE currency_code = 'PHP' LIMIT 1");
  $stmt->execute();
  $currencyId = (int)($stmt->fetchColumn() ?: 0);
  if ($currencyId <= 0) {
    throw new RuntimeException('PHP currency not found in Currencies table.');
  }

  // 5) Fetch product prices from DB to compute total safely
  $productIds = [];
  foreach ($items as $it) {
    if (!isset($it['product_id'], $it['qty'])) continue;
    $pid = (int)$it['product_id'];
    if ($pid > 0) {
      $productIds[$pid] = true;
    }
  }
  $productIds = array_keys($productIds);

  if (empty($productIds)) {
    header('Location: /pages/checkout.php?error=invalid_cart');
    exit;
  }

  $placeholders = implode(',', array_fill(0, count($productIds), '?'));
  $stmt = $pdo->prepare("SELECT product_id, price FROM Products WHERE product_id IN ($placeholders)");
  $stmt->execute($productIds);
  $priceById = [];
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $priceById[(int)$row['product_id']] = (float)$row['price'];
  }

  $orderItems = [];
  $totalAmountPhp = 0.0;

  foreach ($items as $it) {
    $pid = (int)($it['product_id'] ?? 0);
    $qty = max(1, (int)($it['qty'] ?? 0));

    if ($pid <= 0 || !isset($priceById[$pid])) {
      // skip unknown products
      continue;
    }

    $unitPrice = $priceById[$pid]; // PHP
    $lineTotal = $unitPrice * $qty;
    $totalAmountPhp += $lineTotal;

    $orderItems[] = [
      'product_id' => $pid,
      'quantity'   => $qty,
      'unit_price' => $unitPrice,
    ];
  }

  if ($totalAmountPhp <= 0 || empty($orderItems)) {
    header('Location: /pages/checkout.php?error=invalid_total');
    exit;
  }

  // For this demo, treat payment_amount = totalAmountPhp (fully paid)
  $paymentAmount = $totalAmountPhp;

  // 6) Call stored procedure sp_checkout
  //    IN  p_user_id, p_branch_id, p_currency_id, p_total_amount,
  //        p_payment_amount, p_payment_method
  //    OUT p_new_order_id, p_new_payment_id
  $stmt = $pdo->prepare("
    CALL sp_checkout(?, ?, ?, ?, ?, ?, @new_order_id, @new_payment_id)
  ");
  $stmt->execute([
    $userId,
    $branchId,
    $currencyId,
    $totalAmountPhp,
    $paymentAmount,
    $paymentMethod
  ]);

  // Get OUT params
  $row = $pdo->query("SELECT @new_order_id AS order_id, @new_payment_id AS payment_id")->fetch();
  $orderId   = (int)($row['order_id'] ?? 0);
  $paymentId = (int)($row['payment_id'] ?? 0);

  if ($orderId <= 0) {
    throw new RuntimeException('Failed to create order via sp_checkout.');
  }

  // 7) Insert Order_Items (this will trigger stock changes)
  $stmtItem = $pdo->prepare("
    INSERT INTO Order_Items (order_id, product_id, quantity, unit_price)
    VALUES (?, ?, ?, ?)
  ");

  foreach ($orderItems as $oi) {
    $stmtItem->execute([
      $orderId,
      $oi['product_id'],
      $oi['quantity'],
      $oi['unit_price']
    ]);
  }

  // Optional: you could also store address or fulfilment in Transaction_Log via sp_log_action
  // or update Users.address here if you want it saved.

  // 8) Redirect to success page
  header('Location: /pages/order-success.php?order_id=' . urlencode((string)$orderId));
  exit;

} catch (Throwable $e) {
  // For dev: show error; for prod: log and show generic message
  http_response_code(500);
  echo "<h1>Checkout error</h1>";
  echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
