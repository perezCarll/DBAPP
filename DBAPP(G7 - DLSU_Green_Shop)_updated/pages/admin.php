<?php
// pages/admin.php
$title = 'Admin — Dashboard';

require_once __DIR__ . '/../app/session.php';
require_once __DIR__ . '/../app/csrf.php';
require_login();

// Only allow Admin / Staff in here
if (!is_role(['Admin', 'Manager', 'Staff'])) {
  header('HTTP/1.1 403 Forbidden');
  echo '<h1>403 Forbidden</h1>';
  exit;
}

$me  = auth_user();
$pdo = require __DIR__ . '/../app/db.php';

$errors      = [];
$success     = null;
$editProduct = null;

//  HANDLE POST (create / update / delete product)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify($_POST['csrf'] ?? '')) {
    $errors[] = 'Invalid form token. Please try again.';
  } else {
    $action = $_POST['action'] ?? '';

    //  Save (create/update) product 
    if ($action === 'save_product') {
      $id       = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
      $name     = trim($_POST['name'] ?? '');
      $catId    = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
      $pricePhp = (float)($_POST['price_php'] ?? 0);
      $stock    = (int)($_POST['stock_qty'] ?? 0);
      $desc     = trim($_POST['description'] ?? '');

      if ($name === '') {
        $errors[] = 'Product name is required.';
      } else {
        try {
          if ($id > 0) {
            // UPDATE
            $stmt = $pdo->prepare("
              UPDATE Products
              SET name = ?, description = ?, price = ?, stock_quantity = ?, category_id = ?
              WHERE product_id = ?
            ");
            $stmt->execute([$name, $desc, $pricePhp, $stock, $catId, $id]);
            $success = 'Product updated successfully.';
          } else {
            // INSERT
            $stmt = $pdo->prepare("
              INSERT INTO Products (name, description, price, stock_quantity, category_id)
              VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $desc, $pricePhp, $stock, $catId]);
            $success = 'Product added successfully.';
          }
        } catch (PDOException $e) {
          $errors[] = 'Database error while saving product.';
        }
      }

    //  Delete product 
    } elseif ($action === 'delete_product') {
      $id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

      if ($id <= 0) {
        $errors[] = 'Invalid product ID.';
      } else {
        try {
          $stmt = $pdo->prepare("DELETE FROM Products WHERE product_id = ?");
          $stmt->execute([$id]);

          if ($stmt->rowCount() > 0) {
            $success = 'Product deleted.';
          } else {
            $errors[] = 'Product not found or already deleted.';
          }
        } catch (PDOException $e) {
          // Likely FK constraint (existing Order_Items rows)
          $errors[] = 'Unable to delete product because it is used in existing orders.';
        }
      }
    }
  }
}

//  FETCH DATA FOR PAGE
// Categories for dropdown
$catStmt = $pdo->query("
  SELECT category_id, category_name
  FROM Categories
  ORDER BY category_name
");
$categories = $catStmt->fetchAll();

// Products table
$prodStmt = $pdo->query("
  SELECT
    p.product_id,
    p.name,
    p.description,
    p.price,
    p.stock_quantity,
    c.category_name
  FROM Products p
  LEFT JOIN Categories c ON p.category_id = c.category_id
  ORDER BY p.product_id
");
$products = $prodStmt->fetchAll();

// Orders table (latest 50)
$orderStmt = $pdo->query("
  SELECT
    o.order_id,
    o.order_date,
    o.total_amount,
    o.status,
    u.name AS customer_name,
    b.branch_name,
    cur.currency_code
  FROM Orders o
  JOIN Users u      ON o.user_id = u.user_id
  LEFT JOIN Branches b ON o.branch_id = b.branch_id
  JOIN Currencies cur ON o.currency_id = cur.currency_id
  ORDER BY o.order_date DESC
  LIMIT 50
");
$orders = $orderStmt->fetchAll();

// If editing (?edit=ID), preload product into form
if (isset($_GET['edit'])) {
  $editId = (int)$_GET['edit'];
  foreach ($products as $p) {
    if ((int)$p['product_id'] === $editId) {
      $editProduct = $p;
      break;
    }
  }
}

include __DIR__ . '/../partials/header.php';
?>

<section class="py-4">
  <div class="container">
    <h2 class="mb-3">Admin Panel</h2>
    <p class="text-muted mb-4">Manage products and view orders.</p>

    <!-- Alerts -->
    <?php if ($success): ?>
      <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger py-2">
        <ul class="mb-0 small">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Left: Products -->
      <div class="col-lg-6">
        <div class="card mb-3">
          <div class="card-body d-flex align-items-center">
            <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;">
              <i class="bi bi-person-fill"></i>
            </div>
            <div class="flex-grow-1">
              <div class="fw-semibold"><?= htmlspecialchars($me['full_name'] ?? $me['name'] ?? 'Admin User') ?></div>
              <div class="small text-muted">Role: <?= htmlspecialchars($me['role']) ?></div>
              <div class="small text-muted"><?= htmlspecialchars($me['email']) ?></div>
            </div>
            <form action="/auth/logout.php" method="post">
              <button class="btn btn-outline-danger btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
              </button>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Products</h5>
            <?php if ($editProduct): ?>
              <span class="badge text-bg-warning">Editing: <?= htmlspecialchars($editProduct['name']) ?></span>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <p class="small text-muted">
              Add new items or edit existing ones. These changes are reflected on the public Products page.
            </p>

            <!-- Product form -->
            <form method="post" class="mb-3">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="action" value="save_product">
              <input type="hidden" name="product_id" value="<?= $editProduct['product_id'] ?? '' ?>">

              <div class="mb-2">
                <label class="form-label small">Name</label>
                <input type="text" name="name"
                       class="form-control form-control-sm"
                       value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>"
                       required>
              </div>

              <div class="row g-2 mb-2">
                <div class="col-md-6">
                  <label class="form-label small">Category</label>
                  <select name="category_id" class="form-select form-select-sm">
                    <option value="">(None)</option>
                    <?php foreach ($categories as $c): ?>
                      <option value="<?= (int)$c['category_id'] ?>"
                        <?= isset($editProduct['category_name'], $c['category_name']) && $editProduct['category_name'] === $c['category_name'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['category_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small">Price (PHP)</label>
                  <input type="number" step="0.01" min="0"
                         name="price_php"
                         class="form-control form-control-sm"
                         value="<?= htmlspecialchars($editProduct['price'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label small">Stock qty</label>
                  <input type="number" min="0"
                         name="stock_qty"
                         class="form-control form-control-sm"
                         value="<?= htmlspecialchars($editProduct['stock_quantity'] ?? '') ?>">
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label small">Description</label>
                <textarea name="description" rows="2"
                          class="form-control form-control-sm"><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success btn-sm">
                  <?= $editProduct ? 'Update product' : 'Add product' ?>
                </button>
                <?php if ($editProduct): ?>
                  <a href="/pages/admin.php" class="btn btn-outline-secondary btn-sm">Cancel edit</a>
                <?php endif; ?>
              </div>
            </form>

            <!-- Products table -->
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price (PHP)</th>
                    <th>Stock</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($products as $p): ?>
                    <tr>
                      <td><?= (int)$p['product_id'] ?></td>
                      <td><?= htmlspecialchars($p['name']) ?></td>
                      <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                      <td>₱ <?= number_format((float)$p['price'], 2) ?></td>
                      <td><?= (int)$p['stock_quantity'] ?></td>
                      <td class="text-end">
                        <a href="/pages/admin.php?edit=<?= (int)$p['product_id'] ?>"
                           class="btn btn-sm btn-outline-primary me-1">
                          Edit
                        </a>

                        <form method="post" action="/pages/admin.php"
                              class="d-inline"
                              onsubmit="return confirm('Delete this product? This cannot be undone.');">
                          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                          <input type="hidden" name="action" value="delete_product">
                          <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger">
                            Delete
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

          </div>
        </div>
      </div>

      <!-- Right: Orders -->
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0">Orders</h5>
          </div>
          <div class="card-body">
            <p class="small text-muted">
              Latest 50 orders across all branches.
            </p>

            <?php if (empty($orders)): ?>
              <div class="alert alert-light border small">No orders yet.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Date</th>
                      <th>Customer</th>
                      <th>Branch</th>
                      <th class="text-end">Total</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($orders as $o): ?>
                      <tr>
                        <td><?= (int)$o['order_id'] ?></td>
                        <td class="small">
                          <?= htmlspecialchars(date('Y-m-d H:i', strtotime($o['order_date']))) ?>
                        </td>
                        <td><?= htmlspecialchars($o['customer_name']) ?></td>
                        <td><?= htmlspecialchars($o['branch_name'] ?? '—') ?></td>
                        <td class="text-end">
                          <?= htmlspecialchars($o['currency_code']) ?>
                          <?= number_format((float)$o['total_amount'], 2) ?>
                        </td>
                        <td>
                          <?php
                            $status = $o['status'] ?? 'Pending';
                            $badgeClass = 'secondary';
                            if ($status === 'Paid')   $badgeClass = 'success';
                            if ($status === 'Pending') $badgeClass = 'warning';
                            if ($status === 'Cancelled') $badgeClass = 'danger';
                          ?>
                          <span class="badge text-bg-<?= $badgeClass ?>">
                            <?= htmlspecialchars($status) ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
