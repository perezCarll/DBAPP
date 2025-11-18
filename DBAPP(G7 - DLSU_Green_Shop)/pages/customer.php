<?php
// pages/customer.php
$title = 'My Account — Customer';

// Auth guards
require_once __DIR__ . '/../app/session.php';
require_login();                       // must be logged in
if (!is_role(['Customer'])) {          // only Customer role
  header('HTTP/1.1 403 Forbidden');
  echo '<h1>403 Forbidden</h1>';
  exit;
}

require_once __DIR__ . '/../app/csrf.php';
$pdo = require __DIR__ . '/../app/db.php';

$me = auth_user();

// 1) Fetch user details (with branch)
$userRow = null;
try {
  $stmt = $pdo->prepare("
    SELECT u.user_id,
           u.name,
           u.email,
           u.address,
           u.branch_id,
           b.branch_name
    FROM Users u
    LEFT JOIN Branches b ON u.branch_id = b.branch_id
    WHERE u.user_id = :uid
    LIMIT 1
  ");
  $stmt->execute([':uid' => $me['user_id']]);
  $userRow = $stmt->fetch();
} catch (PDOException $e) {
  $userRow = null;
}

$displayName   = $userRow['name']        ?? ($me['full_name'] ?? 'Customer');
$displayEmail  = $userRow['email']       ?? ($me['email'] ?? '');
$displayAddr   = $userRow['address']     ?? null;
$displayBranch = $userRow['branch_name'] ?? 'Online';

// 2) Handle cart actions (session-based)
if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];  // [product_id => quantity]
}
$cart = &$_SESSION['cart']; // reference

$feedback = null;
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify($_POST['csrf'] ?? '')) {
    $errorMsg = 'Security check failed. Please try again.';
  } else {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_to_cart') {
      $productId = (int)($_POST['product_id'] ?? 0);
      $qty       = max(1, min(10, (int)($_POST['quantity'] ?? 1)));

      // Check product exists & in stock
      $stmt = $pdo->prepare("
        SELECT product_id, name, price, stock_quantity
        FROM Products
        WHERE product_id = :pid AND stock_quantity > 0
      ");
      $stmt->execute([':pid' => $productId]);
      $p = $stmt->fetch();

      if ($p) {
        $current = $cart[$productId] ?? 0;
        $newQty  = min($p['stock_quantity'], $current + $qty);
        $cart[$productId] = $newQty;
        $feedback = 'Added to cart.';
      } else {
        $errorMsg = 'Product not available.';
      }
    } elseif ($action === 'remove_from_cart') {
      $productId = (int)($_POST['product_id'] ?? 0);
      unset($cart[$productId]);
      $feedback = 'Item removed from cart.';
    } elseif ($action === 'checkout') {
      if (empty($cart)) {
        $errorMsg = 'Your cart is empty.';
      } else {
        // Minimal checkout: create order + order_items
        try {
          $pdo->beginTransaction();

          // Get current product data for all cart items
          $ids = array_keys($cart);
          $placeholders = implode(',', array_fill(0, count($ids), '?'));
          $stmt = $pdo->prepare("
            SELECT product_id, name, price, stock_quantity
            FROM Products
            WHERE product_id IN ($placeholders)
          ");
          $stmt->execute($ids);
          $productsById = [];
          while ($row = $stmt->fetch()) {
            $productsById[$row['product_id']] = $row;
          }

          // Compute total and validate stock
          $total = 0;
          foreach ($cart as $pid => $qty) {
            if (!isset($productsById[$pid])) {
              throw new RuntimeException('One of the products no longer exists.');
            }
            $prod = $productsById[$pid];
            if ($qty > $prod['stock_quantity']) {
              throw new RuntimeException('Not enough stock for ' . $prod['name']);
            }
            $total += $prod['price'] * $qty;
          }

          // For now, use PHP (currency_id = 1 from your seed)
          $currencyId = 1;
          $branchId = $userRow['branch_id'] ?? null;

          // Create order
          $stmt = $pdo->prepare("
            INSERT INTO Orders (user_id, branch_id, currency_id, total_amount, status)
            VALUES (:uid, :bid, :cid, :total, 'Pending')
          ");
          $stmt->execute([
            ':uid'   => $me['user_id'],
            ':bid'   => $branchId,
            ':cid'   => $currencyId,
            ':total' => $total,
          ]);

          $orderId = (int)$pdo->lastInsertId();

          // Insert order items
          $stmtItem = $pdo->prepare("
            INSERT INTO Order_Items (order_id, product_id, quantity, unit_price)
            VALUES (:oid, :pid, :qty, :price)
          ");

          foreach ($cart as $pid => $qty) {
            $prod = $productsById[$pid];
            $stmtItem->execute([
              ':oid'   => $orderId,
              ':pid'   => $pid,
              ':qty'   => $qty,
              ':price' => $prod['price'],
            ]);
          }

          $pdo->commit();
          $cart = []; // clear cart
          $feedback = 'Order placed successfully! Your order number is #' . $orderId;
        } catch (Throwable $e) {
          if ($pdo->inTransaction()) {
            $pdo->rollBack();
          }
          $errorMsg = 'Checkout failed: ' . $e->getMessage();
        }
      }
    }
  }
}

// Refresh cart reference (in case it was reset)
$cart = $_SESSION['cart'];

// 3) Fetch products that are in stock (browse view)
$products = [];
try {
  $stmt = $pdo->query("
    SELECT p.product_id,
           p.name,
           p.description,
           p.price,
           p.stock_quantity,
           c.category_name
    FROM Products p
    LEFT JOIN Categories c ON p.category_id = c.category_id
    WHERE p.stock_quantity > 0
    ORDER BY p.name
  ");
  $products = $stmt->fetchAll();
} catch (PDOException $e) {
  $products = [];
}

// Build cart details for display
$cartDetails = [];
$cartTotal   = 0.0;

if (!empty($cart)) {
  $ids = array_keys($cart);
  $ph  = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $pdo->prepare("
    SELECT product_id, name, price
    FROM Products
    WHERE product_id IN ($ph)
  ");
  $stmt->execute($ids);
  $rows = $stmt->fetchAll();
  $byId = [];
  foreach ($rows as $r) {
    $byId[$r['product_id']] = $r;
  }

  foreach ($cart as $pid => $qty) {
    if (!isset($byId[$pid])) continue;
    $prod = $byId[$pid];
    $lineTotal = $prod['price'] * $qty;
    $cartTotal += $lineTotal;
    $cartDetails[] = [
      'product_id' => $pid,
      'name'       => $prod['name'],
      'qty'        => $qty,
      'price'      => $prod['price'],
      'line_total' => $lineTotal,
    ];
  }
}

include __DIR__ . '/../partials/header.php';
?>
<section class="py-5" id="customer-dashboard">
  <div class="container">

    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="mb-0">Customer Dashboard</h2>
        <small class="text-muted">Browse products, check stock, and place orders.</small>
      </div>
    </div>

    <?php if ($feedback): ?>
      <div class="alert alert-success py-2"><?= htmlspecialchars($feedback) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Profile / summary -->
      <div class="col-lg-3">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex align-items-start gap-3 mb-3">
              <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                <i class="bi bi-person-fill fs-4"></i>
              </div>
              <div>
                <h5 class="mb-1"><?= htmlspecialchars($displayName) ?></h5>
                <div class="text-muted small mb-1">
                  <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($displayEmail) ?>
                </div>
                <?php if ($displayAddr): ?>
                  <div class="text-muted small mb-1">
                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($displayAddr) ?>
                  </div>
                <?php endif; ?>
                <div class="text-muted small">
                  <i class="bi bi-shop-window me-1"></i>Branch: <?= htmlspecialchars($displayBranch) ?>
                </div>
              </div>
            </div>
            <hr>
            <div class="small text-muted mb-1">Cart items</div>
            <div class="h4 mb-0"><?= count($cartDetails) ?></div>
            <div class="small text-muted">Subtotal: ₱ <?= number_format($cartTotal, 2) ?></div>
          </div>
        </div>
      </div>

      <!-- Products list -->
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title mb-3">Products in Stock</h5>
            <p class="text-muted small">Only items with available stock are shown. Choose a quantity and add to cart.</p>

            <div class="table-responsive" style="max-height:420px;overflow:auto;">
              <table class="table table-sm align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price (PHP)</th>
                    <th>In Stock</th>
                    <th style="width:130px;">Add</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($products)): ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted">No products are currently in stock.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($products as $p): ?>
                      <tr>
                        <td>
                          <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                          <?php if (!empty($p['description'])): ?>
                            <div class="small text-muted"><?= htmlspecialchars($p['description']) ?></div>
                          <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                        <td>₱ <?= number_format((float)$p['price'], 2) ?></td>
                        <td><?= (int)$p['stock_quantity'] ?></td>
                        <td>
                          <form method="post" class="d-flex gap-1">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                            <input type="number" name="quantity" value="1" min="1" max="<?= (int)$p['stock_quantity'] ?>" class="form-control form-control-sm" style="width:60px;">
                            <button class="btn btn-success btn-sm" type="submit">
                              <i class="bi bi-cart-plus"></i>
                            </button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div>

      <!-- Cart / checkout -->
      <div class="col-lg-3">
        <div class="card h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title mb-3">Cart & Checkout</h5>

            <?php if (empty($cartDetails)): ?>
              <p class="text-muted small mb-0">Your cart is currently empty. Browse products and add items to get started.</p>
            <?php else: ?>
              <div class="mb-3" style="max-height:260px;overflow:auto;">
                <ul class="list-group list-group-flush">
                  <?php foreach ($cartDetails as $item): ?>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-start">
                      <div>
                        <div class="fw-semibold small"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="small text-muted">
                          x<?= (int)$item['qty'] ?> • ₱ <?= number_format($item['price'], 2) ?>
                        </div>
                      </div>
                      <div class="text-end">
                        <div class="small mb-1">₱ <?= number_format($item['line_total'], 2) ?></div>
                        <form method="post">
                          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                          <input type="hidden" name="action" value="remove_from_cart">
                          <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                          <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-x"></i>
                          </button>
                        </form>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <div class="mt-auto">
                <div class="d-flex justify-content-between mb-2">
                  <span class="fw-semibold">Subtotal</span>
                  <span class="fw-semibold">₱ <?= number_format($cartTotal, 2) ?></span>
                </div>
                <form method="post">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="action" value="checkout">
                  <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-check2-circle me-1"></i>Place Order
                  </button>
                </form>
                <div class="small text-muted mt-2">
                  Orders will be created in PHP currency and marked as <strong>Pending</strong> for processing.
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
