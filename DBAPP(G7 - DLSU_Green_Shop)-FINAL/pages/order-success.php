<?php
// pages/order-success.php
$title = 'Order Placed';
require_once __DIR__ . '/../app/session.php';
require_login();
$me = auth_user();

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

include __DIR__ . '/../partials/header.php';
?>

<section class="py-5">
  <div class="container" style="max-width: 640px;">
    <div class="card shadow-sm border-success">
      <div class="card-body text-center py-5">
        <div class="display-4 text-success mb-3">
          <i class="bi bi-check-circle"></i>
        </div>
        <h2 class="mb-2">Thank you for your order!</h2>
        <?php if ($orderId > 0): ?>
          <p class="text-muted mb-3">
            Your order number is <span class="fw-semibold">#<?= htmlspecialchars((string)$orderId) ?></span>.
          </p>
        <?php endif; ?>
        <p class="text-muted">
          We’ll review your order and send a confirmation update soon.
          For any questions, you may contact the DLSU Green Shop staff.
        </p>

        <div class="mt-4 d-flex justify-content-center gap-2">
          <a href="/pages/products.php" class="btn btn-outline-success">
            Continue shopping
          </a>
          <a href="/index.php" class="btn btn-success">
            Back to home
          </a>
        </div>
      </div>
    </div>

    <p class="text-muted small text-center mt-3">
      Logged in as <?= htmlspecialchars($me['full_name'] ?? $me['name'] ?? 'Customer') ?>.
    </p>
  </div>
</section>

<script>
// Clear cart on success page
localStorage.removeItem('greenshop_cart_v1');
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
