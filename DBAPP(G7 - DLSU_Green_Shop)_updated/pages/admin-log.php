<?php
// pages/admin-log.php
$title = 'Admin — Transaction Log';
require_once __DIR__ . '/../app/session.php';
require_login();
require_role(['Staff','Manager','Admin']);

$pdo = require __DIR__ . '/../app/db.php';

// --- Read filters from query string ---
$actionFilter = trim($_GET['action'] ?? '');
$dateFrom     = trim($_GET['date_from'] ?? '');
$dateTo       = trim($_GET['date_to'] ?? '');

// --- Fetch distinct actions for dropdown ---
$actions = [];
try {
  $stmtA = $pdo->query("SELECT DISTINCT action FROM Transaction_Log ORDER BY action");
  $actions = $stmtA->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
  $actions = [];
}

// --- Build filtered query ---
$sql = "
  SELECT 
    l.log_id,
    l.created_at,
    l.action,
    l.details,
    u.name AS user_name,
    o.order_id
  FROM Transaction_Log l
  LEFT JOIN Users  u ON l.user_id = u.user_id
  LEFT JOIN Orders o ON l.order_id = o.order_id
  WHERE 1=1
";
$params = [];

// Filter by action
if ($actionFilter !== '') {
  $sql .= " AND l.action = ? ";
  $params[] = $actionFilter;
}

// Filter by date range (using DATE() on created_at)
if ($dateFrom !== '') {
  $sql .= " AND DATE(l.created_at) >= ? ";
  $params[] = $dateFrom;
}
if ($dateTo !== '') {
  $sql .= " AND DATE(l.created_at) <= ? ";
  $params[] = $dateTo;
}

$sql .= " ORDER BY l.created_at DESC LIMIT 200";

$logs = [];
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $logs = [];
}

include __DIR__ . '/../partials/header.php';
?>

<section class="py-5" id="adminLog">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2 class="mb-0">Transaction Log</h2>
        <small class="text-muted">
          View order and payment events captured by MySQL triggers.
        </small>
      </div>
      <a href="/pages/admin.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Admin Panel
      </a>
    </div>

    <!-- Filters -->
    <form method="get" class="row g-3 mb-4">
      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">From date</label>
        <input
          type="date"
          name="date_from"
          value="<?= htmlspecialchars($dateFrom) ?>"
          class="form-control form-control-sm"
        >
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">To date</label>
        <input
          type="date"
          name="date_to"
          value="<?= htmlspecialchars($dateTo) ?>"
          class="form-control form-control-sm"
        >
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted mb-1">Action type</label>
        <select name="action" class="form-select form-select-sm">
          <option value="">All actions</option>
          <?php foreach ($actions as $act): ?>
            <option
              value="<?= htmlspecialchars($act) ?>"
              <?= $act === $actionFilter ? 'selected' : '' ?>
            >
              <?= htmlspecialchars($act) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end gap-2">
        <button class="btn btn-success btn-sm" type="submit">
          <i class="bi bi-funnel me-1"></i>Filter
        </button>
        <a href="/pages/admin-log.php" class="btn btn-outline-secondary btn-sm">
          Reset
        </a>
      </div>
    </form>

    <?php if (empty($logs)): ?>
      <div class="alert alert-info">
        No log entries found for the selected filters.
      </div>
    <?php else: ?>

      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:70px;">ID</th>
              <th style="width:150px;">Timestamp</th>
              <th style="width:140px;">Action</th>
              <th>Details</th>
              <th style="width:140px;">User</th>
              <th style="width:100px;">Order #</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $log): ?>
              <tr>
                <td><?= (int)$log['log_id'] ?></td>
                <td class="small"><?= htmlspecialchars($log['created_at']) ?></td>
                <td>
                  <span class="badge text-bg-secondary">
                    <?= htmlspecialchars($log['action']) ?>
                  </span>
                </td>
                <td class="small"><?= nl2br(htmlspecialchars($log['details'] ?? '')) ?></td>
                <td class="small">
                  <?= $log['user_name'] ? htmlspecialchars($log['user_name']) : '—' ?>
                </td>
                <td class="small">
                  <?= $log['order_id'] ? '#'.(int)$log['order_id'] : '—' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <p class="text-muted small mt-2 mb-0">
        Showing up to 200 most recent entries based on your filters.
      </p>

    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
