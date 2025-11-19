<?php
// pages/checkout.php
$title = 'Checkout';
require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/csrf.php';
require_login();              // must be logged in
$me  = auth_user();
$pdo = require __DIR__ . '/../app/db.php';

// Load branches from DB
$branches = [];
try {
  $stmt = $pdo->query("SELECT branch_id, branch_name FROM Branches ORDER BY branch_name");
  $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $branches = [];
}

include __DIR__ . '/../partials/header.php';
?>

<section class="py-5" id="checkout">
  <div class="container">

    <!-- Wrap whole checkout in a form -->
    <form method="post" action="/auth/checkout-process.php" id="checkoutForm" novalidate>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <!-- cart from JS -->
      <input type="hidden" name="cart_json" id="checkoutCartJson">
      <!-- Add currency_id field -->
      <input type="hidden" name="currency_id" id="checkoutCurrencyId" value="">
      <!-- currency code (for reference, though we store totals in PHP) -->
      <input type="hidden" name="currency_code" id="checkoutCurrencyCode" value="">
      <input type="hidden" name="total_amount" id="checkoutTotalAmount" value="">
      <!-- Page title + hint -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-0">Checkout</h2>
          <small class="text-muted">
            Review your order and confirm your details before placing your order.
          </small>
        </div>
        <span class="badge text-bg-light">
          <?= htmlspecialchars($me['full_name'] ?? $me['name'] ?? 'Customer') ?>
          &nbsp;•&nbsp;
          <?= htmlspecialchars($me['email'] ?? '') ?>
        </span>
      </div>

      <div class="row g-4">
        <!-- LEFT: Customer & Shipping Details -->
        <div class="col-lg-7">
          <div class="card mb-3">
            <div class="card-body">
              <h5 class="card-title">Contact details</h5>
              <p class="text-muted small mb-3">
                We’ll use this information to send your order updates.
              </p>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small">Full name</label>
                  <input type="text" class="form-control" id="checkoutName" name="name"
                    value="<?= htmlspecialchars($me['full_name'] ?? $me['name'] ?? '') ?>"
                    placeholder="Your name" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Email</label>
                  <input type="email" class="form-control" id="checkoutEmail" name="email"
                    value="<?= htmlspecialchars($me['email'] ?? '') ?>"
                    placeholder="you@example.com" required>
                </div>
                <div class="col-12">
                  <label class="form-label small">Mobile number</label>
                  <input type="tel" class="form-control" id="checkoutPhone" name="phone"
                    placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" required>
                  <div class="form-text small">Format: 09XXXXXXXXX</div>
                </div>
              </div>
            </div>
          </div>

          <div class="card mb-3">
            <div class="card-body">
              <h5 class="card-title">Shipping / pickup details</h5>
              <p class="text-muted small mb-3">
                Choose where you want to receive your order.
              </p>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small">Fulfilment type</label>
                  <select id="checkoutFulfilment" name="fulfilment" class="form-select form-select-sm"
                    required>
                    <option value="pickup">Campus pick-up</option>
                    <option value="delivery">Delivery (Metro Manila)</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Preferred branch</label>
                  <select id="checkoutBranch" name="branch_id" class="form-select form-select-sm"
                    required>
                    <?php if (empty($branches)): ?>
                      <option value="">(No branches found)</option>
                    <?php else: ?>
                      <?php foreach ($branches as $b): ?>
                        <option value="<?= (int)$b['branch_id'] ?>">
                          <?= htmlspecialchars($b['branch_name']) ?>
                        </option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small">Address / pick-up notes</label>
                  <textarea id="checkoutAddress" name="address" class="form-control" rows="3"
                    placeholder="Building, street, subdivision, city or pick-up instructions"><?= htmlspecialchars($me['address'] ?? '') ?></textarea>
                  <div class="form-text small">
                    Required if you choose delivery.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card mb-3">
            <div class="card-body">
              <h5 class="card-title">Payment method</h5>
              <p class="text-muted small mb-3">
                For now this is for record-keeping; actual payment can be handled on pick-up or via your
                chosen channel.
              </p>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small">Payment option</label>
                  <select id="checkoutPaymentMethod" name="payment_method"
                    class="form-select form-select-sm" required>
                    <option value="Cash">Cash on pick-up</option>
                    <option value="GCash">GCash</option>
                    <option value="Card">Debit/Credit card</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small">Reference (optional)</label>
                  <input type="text" id="checkoutReference" name="payment_reference"
                    class="form-control" placeholder="GCash ref # / last 4 digits">
                </div>
              </div>
            </div>
          </div>

          <div class="alert alert-info small">
            <strong>Note:</strong> This demo version creates an order record in the database
            and deducts stock from the selected branch using your <code>Product_Stock</code> table.
            You can extend it later for email notifications or payment integration.
          </div>
        </div>

        <!-- RIGHT: Order Summary -->
        <div class="col-lg-5">
          <div class="card mb-3">
            <div class="card-body">
              <h5 class="card-title d-flex justify-content-between align-items-center">
                <span>Order summary</span>
                <span class="badge text-bg-secondary" id="checkoutItemCount">0 items</span>
              </h5>

              <!-- Cart line items (filled by JS later) -->
              <ul class="list-group list-group-flush small mb-3" id="checkoutItems">
                <li class="list-group-item text-muted text-center" id="checkoutEmpty">
                  Your cart is empty. Go back to <a href="/pages/products.php">Products</a> to add
                  items.
                </li>
              </ul>

              <!-- Totals -->
              <div class="border-top pt-3 small">
                <div class="d-flex justify-content-between mb-1">
                  <span>Subtotal</span>
                  <span id="checkoutSubtotal">₱0</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>Estimated shipping</span>
                  <span id="checkoutShipping">₱0</span>
                </div>
                <div class="d-flex justify-content-between fw-semibold">
                  <span>Total</span>
                  <span id="checkoutTotal">₱0</span>
                </div>
                <div class="text-muted mt-1">
                  <small>Totals are stored in PHP; currency selector only affects display.</small>
                </div>
              </div>

              <button class="btn btn-success w-100 mt-3" id="checkoutPlaceOrder" type="submit">
                Place order
              </button>
              <div class="text-center mt-2">
                <a href="/pages/products.php" class="small">← Continue shopping</a>
              </div>
            </div>
          </div>

          <!-- Order notes / policies -->
          <div class="card border-0 bg-light small">
            <div class="card-body">
              <h6 class="fw-semibold mb-2">Before you confirm</h6>
              <ul class="mb-0 ps-3">
                <li>Stock availability is based on current inventory per branch.</li>
                <li>Campus pick-up orders are usually ready within 1–2 working days.</li>
                <li>You’ll receive a confirmation message once your order is recorded.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </form>

  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>