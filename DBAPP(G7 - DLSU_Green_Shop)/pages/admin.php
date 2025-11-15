<?php
// pages/admin.php
$title = 'Admin — Dashboard';

// Auth guards
require_once __DIR__ . '/../app/session.php';
require_login();
require_role(['Staff','Manager','Admin']);

$me = auth_user();

include __DIR__ . '/../partials/header.php';
?>
<section class="py-5" id="admin">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="mb-0">Admin Panel</h2>
        <small class="text-muted">UI preview — data wiring to DB comes next</small>
      </div>
      <span class="badge text-bg-warning">Demo</span>
    </div>

    <!-- Who am I -->
    <div class="row g-4 mb-2">
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width:48px;height:48px;">
              <i class="bi bi-person-fill"></i>
            </div>
            <div>
              <div class="fw-semibold mb-1"><?= htmlspecialchars($me['full_name'] ?? 'User') ?></div>
              <div class="text-muted small">
                Role: <span class="badge text-bg-dark"><?= htmlspecialchars($me['role']) ?></span><br>
                Email: <?= htmlspecialchars($me['email'] ?? '') ?>
              </div>
            </div>
            <div class="ms-auto">
              <form action="/auth/logout.php" method="post">
                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick links (placeholders) -->
      <div class="col-lg-8">
        <div class="row g-3">
          <div class="col-md-4">
            <a class="text-decoration-none" href="/pages/products.php">
              <div class="card h-100 hover-shadow">
                <div class="card-body d-flex flex-column">
                  <div class="h5 mb-1"><i class="bi bi-bag me-2"></i>Products</div>
                  <small class="text-muted mt-auto">View catalog</small>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-4">
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <div class="h5 mb-1"><i class="bi bi-receipt me-2"></i>Orders</div>
                <small class="text-muted mt-auto">Manage orders (demo)</small>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <div class="h5 mb-1"><i class="bi bi-people me-2"></i>Users</div>
                <small class="text-muted mt-auto">User roles (coming soon)</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Demo forms/tables -->
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Products</h5>
            <p class="text-muted">Add/Edit products (placeholder form — hook to PHP/MySQL next).</p>
            <form class="row g-2">
              <div class="col-12"><input class="form-control" placeholder="Name" /></div>
              <div class="col-12"><input class="form-control" placeholder="Category" /></div>
              <div class="col-md-4"><input class="form-control" placeholder="Price PHP" /></div>
              <div class="col-md-4"><input class="form-control" placeholder="Price USD" /></div>
              <div class="col-md-4"><input class="form-control" placeholder="Price EUR" /></div>
              <div class="col-12">
                <button class="btn btn-success" type="button" disabled>Save (wire later)</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Orders</h5>
            <p class="text-muted">Manage orders (table preview — hook to DB later).</p>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th></tr>
                </thead>
                <tbody>
                  <tr><td>1001</td><td>Juan D.</td><td>₱ 1,495</td><td><span class="badge text-bg-secondary">Pending</span></td></tr>
                  <tr><td>1002</td><td>Ana S.</td><td>$ 25</td><td><span class="badge text-bg-success">Paid</span></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>
