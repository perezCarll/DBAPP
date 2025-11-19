<?php
// pages/account.php
$title = 'My Account';
require_once __DIR__ . '/../app/session.php';
require_login();

$me = auth_user();
include __DIR__ . '/../partials/header.php';

// In a later step you can actually load the full row from Users here
?>

<section class="py-5">
  <div class="container" style="max-width: 640px;">
    <h2 class="mb-3">Account Settings</h2>
    <p class="text-muted mb-4">
      Update your customer details used for online orders and checkout.
    </p>

    <form method="post" action="/auth/update-account.php">
      <div class="mb-3">
        <label class="form-label">Full name</label>
        <input type="text" class="form-control"
               value="<?= htmlspecialchars($me['full_name'] ?? $me['name'] ?? '') ?>"
               name="name" readonly>
        <div class="form-text">Name changes are managed by staff/admin.</div>
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control"
               value="<?= htmlspecialchars($me['email'] ?? '') ?>"
               name="email" readonly>
        <div class="form-text">Your login email cannot be changed here.</div>
      </div>

      <div class="mb-3">
        <label class="form-label">Shipping / Billing Address</label>
        <textarea class="form-control" name="address" rows="3"
                  placeholder="House/Unit, Street, Barangay, City, Province, ZIP">
<?php
// if you later fetch full user row with address, echo it here
?></textarea>
      </div>

      <button class="btn btn-success" type="submit" disabled>
        Save Changes (coming soon)
      </button>
      <div class="form-text mt-2">
        We’ll wire this button to actually update the <code>Users.address</code> field in the database later.
      </div>
    </form>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
