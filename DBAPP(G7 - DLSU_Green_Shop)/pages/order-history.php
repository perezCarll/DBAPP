<?php
// pages/order-history.php
$title = 'My Orders';
require_once __DIR__ . '/../app/session.php';
require_login();
$me  = auth_user();
$pdo = require __DIR__ . '/../app/db.php';

$userId = (int)($me['user_id'] ?? 0);

// ---- 1) Fetch order headers via stored procedure ----
$orders = [];
try {
  $stmt = $pdo->prepare("CALL sp_get_order_history_by_user(?)");
  $stmt->execute([$userId]);
  $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor(); // important after CALL
} catch (PDOException $e) {
  $orders = [];
}

// ---- 2) Fetch line items for all these orders ----
$itemsByOrder = [];

if (!empty($orders)) {
  $orderIds = array_column($orders, 'order_id');
  $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

  $sqlItems = "
    SELECT 
      oi.order_id,
      p.name       AS product_name,
      oi.quantity,
      oi.unit_price
    FROM Order_Items oi
    JOIN Products p ON oi.product_id = p.product_id
    WHERE oi.order_id IN ($placeholders)
    ORDER BY oi.order_id, p.name
  ";

  $stmtItems = $pdo->prepare($sqlItems);
  $stmtItems->execute($orderIds);

  while ($row = $stmtItems->fetch(PDO::FETCH_ASSOC)) {
    $oid = (int)$row['order_id'];
    if (!isset($itemsByOrder[$oid])) {
      $itemsByOrder[$oid] = [];
    }
    $itemsByOrder[$oid][] = $row;
  }
}

include __DIR__ . '/../partials/header.php';
?>

<section class="py-5" id="orderHistory">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-0">My Orders</h2>
        <small class="text-muted">
          View your past orders and their status.
        </small>
      </div>
      <span class="badge text-bg-light">
        <?= htmlspecialchars($me['full_name'] ?? $me['name'] ?? 'Customer') ?>
      </span>
    </div>

    <?php if (empty($orders)): ?>
      <div class="alert alert-info">
        You don’t have any orders yet. Visit the
        <a href="/pages/products.php">Products</a> page to start shopping.
      </div>
    <?php else: ?>

      <div class="row g-3">
        <?php foreach ($orders as $o): ?>
          <?php
            $oid     = (int)$o['order_id'];
            $date    = $o['order_date'];
            $total   = (float)$o['total_amount'];
            $status  = $o['status'] ?? 'Pending';
            $ccode   = $o['currency_code'] ?? 'PHP';
            $branch  = $o['branch_name'] ?? '—';
            $items   = $itemsByOrder[$oid] ?? [];
          ?>
          <div class="col-12">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div>
                    <div class="fw-semibold">
                      Order #<?= htmlspecialchars((string)$oid) ?>
                    </div>
                    <div class="text-muted small">
                      Placed on <?= htmlspecialchars($date) ?> · Branch: <?= htmlspecialchars($branch) ?>
                    </div>
                  </div>
                  <div class="text-end">
                    <span class="badge <?= $status === 'Paid' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                      <?= htmlspecialchars($status) ?>
                    </span>
                    <div class="fw-semibold mt-1">
                      <?= htmlspecialchars($ccode) ?> <?= number_format($total, 2) ?>
                    </div>
                  </div>
                </div>

                <?php if (!empty($items)): ?>
                  <div class="table-responsive small mt-3">
                    <table class="table table-sm align-middle mb-0">
                      <thead>
                        <tr>
                          <th>Product</th>
                          <th class="text-center" style="width:90px;">Qty</th>
                          <th class="text-end" style="width:120px;">Unit Price</th>
                          <th class="text-end" style="width:120px;">Line Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($items as $it): ?>
                          <?php
                            $qty   = (int)$it['quantity'];
                            $up    = (float)$it['unit_price'];
                            $lnTot = $qty * $up;
                          ?>
                          <tr>
                            <td><?= htmlspecialchars($it['product_name']) ?></td>
                            <td class="text-center"><?= $qty ?></td>
                            <td class="text-end"><?= htmlspecialchars($ccode) ?> <?= number_format($up, 2) ?></td>
                            <td class="text-end"><?= htmlspecialchars($ccode) ?> <?= number_format($lnTot, 2) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <p class="text-muted small mt-2 mb-0">
                    No line items found for this order.
                  </p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
