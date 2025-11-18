<?php
// pages/admin-orders.php
$title = 'Admin — Orders';
require_once __DIR__ . '/../app/session.php';
require_login();
require_role(['Staff','Manager','Admin']);

$pdo = require __DIR__ . '/../app/db.php';

$me        = auth_user();
$isAdmin   = ($me['role'] ?? '') === 'Admin';
$isManager = ($me['role'] ?? '') === 'Manager';

// --- Filters from GET ---
$userQ    = trim($_GET['user'] ?? '');
$statusQ  = trim($_GET['status'] ?? '');
$branchId = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? (int)$_GET['branch_id'] : null;
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to'] ?? '');

// --- Branch list for dropdown ---
$branches = [];
try {
  $stmtB = $pdo->query("SELECT branch_id, branch_name FROM Branches ORDER BY branch_name");
  $branches = $stmtB->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $branches = [];
}

// --- Distinct statuses for dropdown ---
$statuses = [];
try {
  $stmtS = $pdo->query("SELECT DISTINCT status FROM Orders ORDER BY status");
  $statuses = $stmtS->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
  $statuses = [];
}

// --- Build orders query with filters ---
$sql = "
  SELECT
    o.order_id,
    o.order_date,
    o.total_amount,
    o.status,
    cur.currency_code,
    u.name AS user_name,
    u.email,
    b.branch_name
  FROM Orders o
  JOIN Users u        ON o.user_id = u.user_id
  JOIN Currencies cur ON o.currency_id = cur.currency_id
  LEFT JOIN Branches b ON o.branch_id = b.branch_id
  WHERE 1=1
";

$params = [];

// Filter by user name or email
if ($userQ !== '') {
  $sql .= " AND (u.name LIKE ? OR u.email LIKE ?) ";
  $like = '%'.$userQ.'%';
  $params[] = $like;
  $params[] = $like;
}

// Filter by branch
if ($branchId !== null) {
  $sql .= " AND o.branch_id = ? ";
  $params[] = $branchId;
}

// Filter by status
if ($statusQ !== '') {
  $sql .= " AND o.status = ? ";
  $params[] = $statusQ;
}

// Filter by date range
if ($dateFrom !== '') {
  $sql .= " AND DATE(o.order_date) >= ? ";
  $params[] = $dateFrom;
}
if ($dateTo !== '') {
  $sql .= " AND DATE(o.order_date) <= ? ";
  $params[] = $dateTo;
}

$sql .= " ORDER BY o.order_date DESC, o.order_id DESC LIMIT 200";

$orders = [];
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $orders = [];
}

// --- Fetch line items for these orders ---
$itemsByOrder = [];
if (!empty($orders)) {
  $orderIds = array_column($orders, 'order_id');
  $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

  $sqlItems = "
    SELECT 
      oi.order_id,
      p.name AS product_name,
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

<section class="py-5" id="adminOrders">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2 class="mb-0">Orders</h2>
        <small class="text-muted">
          Search and review orders by customer, branch, and status.
        </small>
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

    <!-- Filters -->
    <form method="get" class="row g-3 mb-4">
      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Customer (name or email)</label>
        <input
          type="text"
          name="user"
          value="<?= htmlspecialchars($userQ) ?>"
          class="form-control form-control-sm"
          placeholder="e.g. Juan, @example.com"
        >
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">Branch</label>
        <select name="branch_id" class="form-select form-select-sm">
          <option value="">All branches</option>
          <?php foreach ($branches as $b): ?>
            <option
              value="<?= (int)$b['branch_id'] ?>"
              <?= $branchId === (int)$b['branch_id'] ? 'selected' : '' ?>
            >
              <?= htmlspecialchars($b['branch_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">All statuses</option>
          <?php foreach ($statuses as $st): ?>
            <option
              value="<?= htmlspecialchars($st) ?>"
              <?= $statusQ === $st ? 'selected' : '' ?>
            >
              <?= htmlspecialchars($st) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">From date</label>
        <input
          type="date"
          name="date_from"
          value="<?= htmlspecialchars($dateFrom) ?>"
          class="form-control form-control-sm"
        >
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted mb-1">To date</label>
        <input
          type="date"
          name="date_to"
          value="<?= htmlspecialchars($dateTo) ?>"
          class="form-control form-control-sm"
        >
      </div>
      <div class="col-md-1 d-flex align-items-end gap-2">
        <button class="btn btn-success btn-sm w-100" type="submit">
          <i class="bi bi-funnel me-1"></i>Go
        </button>
      </div>
      <div class="col-12 col-md-2 mt-2 mt-md-0 d-flex align-items-end">
        <a href="/pages/admin-orders.php" class="btn btn-outline-secondary btn-sm w-100">
          Reset
        </a>
      </div>
    </form>

    <?php if (empty($orders)): ?>
      <div class="alert alert-info">
        No orders found for the selected filters.
      </div>
    <?php else: ?>

      <p class="text-muted small mb-2">
        Showing up to 200 most recent orders based on your filters.
      </p>

      <div class="row g-3">
        <?php foreach ($orders as $o): ?>
          <?php
            $oid    = (int)$o['order_id'];
            $date   = $o['order_date'];
            $total  = (float)$o['total_amount'];
            $status = $o['status'] ?? 'Pending';
            $ccode  = $o['currency_code'] ?? 'PHP';
            $uname  = $o['user_name'] ?? '—';
            $email  = $o['email'] ?? '—';
            $bname  = $o['branch_name'] ?? '—';
            $items  = $itemsByOrder[$oid] ?? [];
          ?>
          <div class="col-12">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <div class="fw-semibold">
                      Order #<?= htmlspecialchars((string)$oid) ?>
                    </div>
                    <div class="text-muted small">
                      <?= htmlspecialchars($date) ?> · Branch: <?= htmlspecialchars($bname) ?>
                    </div>
                    <div class="text-muted small">
                      Customer: <?= htmlspecialchars($uname) ?> (<?= htmlspecialchars($email) ?>)
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
                          <th class="text-center" style="width:80px;">Qty</th>
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
