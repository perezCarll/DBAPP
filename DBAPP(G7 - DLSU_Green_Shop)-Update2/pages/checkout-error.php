<?php include __DIR__.'/../partials/header.php'; ?>

<div class="container py-5">
  <h3>Checkout Error</h3>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <a href="/pages/checkout.php" class="btn btn-secondary">Back to Checkout</a>
</div>

<?php include __DIR__.'/../partials/footer.php'; ?>
