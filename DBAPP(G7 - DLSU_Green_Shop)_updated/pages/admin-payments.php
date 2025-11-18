<?php
$title = 'Payments';
require_once __DIR__.'/../app/session.php';
$pdo = require __DIR__.'/../app/db.php';

require_login();
require_role(['Admin','Manager']);

$me        = auth_user();
$isAdmin   = ($me['role'] ?? '') === 'Admin';
$isManager = ($me['role'] ?? '') === 'Manager';

// --- Fetch via STORED PROCEDURE ---
$rows = [];
try {
    $stmt = $pdo->query("CALL sp_get_payments_with_user()");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor(); // required after CALL
} catch (PDOException $e) {
    $rows = [];
}

include __DIR__.'/../partials/header.php';
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="mb-1">Payments</h1>
      <p class="text-muted small mb-0">
        Latest payments. Managers can view but not change system settings.
      </p>
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

  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Date</th>
          <th>Order #</th>
          <th>Customer</th>
          <th>Branch</th>
          <th class="text-end">Amount</th>
          <th>Method</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>

      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['payment_id'] ?></td>
          <td><?= htmlspecialchars($r['payment_date']) ?></td>
          <td><?= (int)$r['order_id'] ?></td>

          <td>
            <?= htmlspecialchars($r['customer_name']) ?><br>
            <small class="text-muted"><?= htmlspecialchars($r['customer_email']) ?></small>
          </td>

          <td><?= htmlspecialchars($r['branch_name'] ?? '—') ?></td>

          <td class="text-end">
            <?= htmlspecialchars($r['currency_code']) ?>
            <?= number_format($r['amount'], 2) ?>
          </td>

          <td><?= htmlspecialchars($r['method']) ?></td>

          <td>
            <span class="badge bg-<?= $r['status']==='Paid' ? 'success' : 'secondary' ?>">
              <?= htmlspecialchars($r['status']) ?>
            </span>
          </td>
        </tr>
      <?php endforeach; ?>

      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__.'/../partials/footer.php'; ?>
