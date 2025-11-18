<?php
$title = 'Payments';
require_once __DIR__.'/../app/session.php';
$pdo = require __DIR__.'/../app/db.php';

require_login();
require_role(['Admin','Manager']); // both Admin + Manager can access

$me        = auth_user();
$isAdmin   = ($me['role'] ?? '') === 'Admin';
$isManager = ($me['role'] ?? '') === 'Manager';

// Fetch payments + orders + users + branch name
$sql = "
  SELECT 
      p.payment_id,
      p.payment_date,
      p.amount,
      p.method,
      p.status,

      o.order_id,
      u.name AS customer_name,
      b.branch_name AS branch_name

  FROM Payments p
  JOIN Orders o        ON p.order_id = o.order_id
  JOIN Users u         ON o.user_id = u.user_id
  LEFT JOIN Branches b ON o.branch_id = b.branch_id

  ORDER BY p.payment_date DESC
  LIMIT 100
";

$rows = $pdo->query($sql)->fetchAll();

include __DIR__.'/../partials/header.php';
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="mb-1">Payments</h1>
      <p class="text-muted small mb-0">
        Latest 100 payments. Managers can view but not change system settings.
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
          <td><?= htmlspecialchars($r['customer_name']) ?></td>
          <td><?= htmlspecialchars($r['branch_name'] ?? '—') ?></td>
          <td class="text-end">₱ <?= number_format($r['amount'], 2) ?></td>
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
