<?php
$title = 'Admin — Orders';
require_once __DIR__ . '/../app/session.php';
require_login();
require_role(['Staff','Manager','Admin']);

$pdo = require __DIR__ . '/../app/db.php';

$me        = auth_user();
$isAdmin   = ($me['role'] ?? '') === 'Admin';
$isManager = ($me['role'] ?? '') === 'Manager';

// ---- 1) Fetch orders via stored procedure ----
$orders = [];
try {
    $stmt = $pdo->query("CALL sp_get_orders_admin()");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    $orders = [];
}

// ---- 2) Fetch items per order via stored procedure ----
$itemsByOrder = [];

foreach ($orders as $row) {
    $oid = (int)$row['order_id'];

    $stmtItems = $pdo->prepare("CALL sp_get_order_items(?)");
    $stmtItems->execute([$oid]);
    $itemsByOrder[$oid] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    $stmtItems->closeCursor();
}

include __DIR__ . '/../partials/header.php';
?>

<section class="py-5" id="adminOrders">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2 class="mb-0">Orders</h2>
        <small class="text-muted">All orders across all branches.</small>
      </div>

      <?php if ($isManager): ?>
        <a href="/pages/manager-dashboard.php" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Back to Manager Dashboard
        </a>
      <?php elseif ($isAdmin): ?>
        <a href="/pages/admin.php" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Back to Admin Panel
        </a>
      <?php endif; ?>
    </div>

    <?php if (empty($orders)): ?>
      <div class="alert alert-info">No orders found.</div>
    <?php else: ?>

      <div class="row g-3">
        <?php foreach ($orders as $o): ?>
          <?php
            $oid      = (int)$o['order_id'];
            $date     = $o['order_date'];
            $branch   = $o['branch_name'] ?? '—';
            $status   = $o['status'] ?? 'Pending';
            $ccode    = $o['currency_code'] ?? 'PHP';
            $total    = (float)$o['total_amount'];
            $custName = $o['customer_name'] ?? '—';
            $custEmail= $o['customer_email'] ?? '—';
            $items    = $itemsByOrder[$oid] ?? [];
          ?>
          <div class="col-12">
            <div class="card h-100">

              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="fw-semibold">Order #<?= htmlspecialchars((string)$oid) ?></div>
                    <div class="text-muted small">
                      <?= htmlspecialchars($date) ?> — Branch: <?= htmlspecialchars($branch) ?>
                    </div>
                    <div class="text-muted small">
                      Customer: <?= htmlspecialchars($custName) ?>
                      (<?= htmlspecialchars($custEmail) ?>)
                    </div>
                  </div>

                  <div class="text-end">
                    <span class="badge <?= $status==='Paid' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                      <?= htmlspecialchars($status) ?>
                    </span>
                    <div class="fw-bold mt-1">
                      <?= htmlspecialchars($ccode) ?> <?= number_format($total, 2) ?>
                    </div>
                  </div>
                </div>

                <div class="table-responsive small mt-3">
                  <table class="table table-sm mb-0">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Unit</th>
                        <th class="text-end">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($items as $it): ?>
                        <?php
                          $qty   = (int)$it['quantity'];
                          $unit  = (float)$it['unit_price'];
                          $line  = $qty * $unit;
                        ?>
                        <tr>
                          <td><?= htmlspecialchars($it['product_name']) ?></td>
                          <td class="text-center"><?= $qty ?></td>
                          <td class="text-end"><?= number_format($unit, 2) ?></td>
                          <td class="text-end"><?= number_format($line, 2) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
          </div>

        <?php endforeach; ?>
      </div>

    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
