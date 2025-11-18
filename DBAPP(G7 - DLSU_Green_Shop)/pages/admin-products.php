<?php
// pages/admin-products.php
$title = 'Manage Products';
require_once __DIR__.'/../app/session.php';
$pdo = require __DIR__.'/../app/db.php';

require_login();
require_role(['Admin','Manager']);  // both allowed

$me        = auth_user();
$isAdmin   = ($me['role'] ?? '') === 'Admin';
$isManager = ($me['role'] ?? '') === 'Manager';

// Handle form submit (add / update / delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // DELETE is admin-only
    if (isset($_POST['delete_id'])) {
        if (!$isAdmin) {
            http_response_code(403);
            die('Only Admin can delete products.');
        }

        $id = (int)$_POST['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM Products WHERE product_id = ?");
        $stmt->execute([$id]);
        header('Location: /pages/admin-products.php?deleted=1');
        exit;
    }

    // ADD / UPDATE – Manager + Admin allowed
    $name     = trim($_POST['name'] ?? '');
    $catId    = (int)($_POST['category_id'] ?? 0);
    $pricePhp = (float)($_POST['price_php'] ?? 0);
    $stock    = (int)($_POST['stock_qty'] ?? 0);
    $desc     = trim($_POST['description'] ?? '');
    $editId   = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;

    if ($editId) {
        $sql = "UPDATE Products
                SET name = ?, description = ?, price = ?, stock_quantity = ?, category_id = ?
                WHERE product_id = ?";
        $pdo->prepare($sql)->execute([$name, $desc, $pricePhp, $stock, $catId, $editId]);
        header('Location: /pages/admin-products.php?updated=1');
    } else {
        $sql = "INSERT INTO Products (name, description, price, stock_quantity, category_id)
                VALUES (?,?,?,?,?)";
        $pdo->prepare($sql)->execute([$name, $desc, $pricePhp, $stock, $catId]);
        header('Location: /pages/admin-products.php?created=1');
    }
    exit;
}

// Fetch categories + products
$cats = $pdo->query("SELECT category_id, category_name FROM Categories ORDER BY category_name")->fetchAll();
$products = $pdo->query("
    SELECT p.product_id, p.name, p.description, p.price, p.stock_quantity, c.category_name
    FROM Products p
    LEFT JOIN Categories c ON p.category_id = c.category_id
    ORDER BY p.product_id
")->fetchAll();

include __DIR__.'/../partials/header.php';
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0">Manage Products</h1>

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

  <?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success py-2">Product created.</div>
  <?php elseif (isset($_GET['updated'])): ?>
    <div class="alert alert-success py-2">Product updated.</div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert alert-warning py-2">Product deleted.</div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Form -->
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Add / Edit product</h5>
          <form method="post">
            <input type="hidden" name="product_id" id="product_id">
            <div class="mb-2">
              <label class="form-label small">Name</label>
              <input type="text" class="form-control form-control-sm" name="name" id="name" required>
            </div>
            <div class="mb-2">
              <label class="form-label small">Category</label>
              <select class="form-select form-select-sm" name="category_id" id="category_id">
                <option value="0">(None)</option>
                <?php foreach ($cats as $c): ?>
                  <option value="<?= (int)$c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label small">Price (PHP)</label>
                <input type="number" step="0.01" class="form-control form-control-sm" name="price_php" id="price_php" required>
              </div>
              <div class="col-6">
                <label class="form-label small">Stock qty</label>
                <input type="number" class="form-control form-control-sm" name="stock_qty" id="stock_qty" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label small">Description</label>
              <textarea class="form-control form-control-sm" rows="3" name="description" id="description"></textarea>
            </div>
            <button class="btn btn-success btn-sm">Save product</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="resetFormBtn">Clear</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Catalog</h5>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Category</th>
                  <th class="text-end">Price</th>
                  <th class="text-end">Stock</th>
                  <th></th>
                  <?php if ($isAdmin): ?><th></th><?php endif; ?>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($products as $p): ?>
                <tr>
                  <td><?= (int)$p['product_id'] ?></td>
                  <td><?= htmlspecialchars($p['name']) ?></td>
                  <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                  <td class="text-end">₱ <?= number_format($p['price'], 2) ?></td>
                  <td class="text-end"><?= (int)$p['stock_quantity'] ?></td>
                  <td>
                    <button class="btn btn-outline-primary btn-sm btn-edit"
                            data-id="<?= (int)$p['product_id'] ?>"
                            data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>"
                            data-desc="<?= htmlspecialchars($p['description'], ENT_QUOTES) ?>"
                            data-price="<?= htmlspecialchars($p['price'], ENT_QUOTES) ?>"
                            data-stock="<?= (int)$p['stock_quantity'] ?>"
                            data-cat="<?= htmlspecialchars($p['category_name'] ?? '', ENT_QUOTES) ?>">
                      Edit
                    </button>
                  </td>
                  <?php if ($isAdmin): ?>
                  <td>
                    <form method="post" onsubmit="return confirm('Delete this product?');" class="d-inline">
                      <input type="hidden" name="delete_id" value="<?= (int)$p['product_id'] ?>">
                      <button class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                  </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div><!-- /col -->
  </div><!-- /row -->
</div>

<script>
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('product_id').value = btn.dataset.id;
    document.getElementById('name').value       = btn.dataset.name;
    document.getElementById('description').value= btn.dataset.desc;
    document.getElementById('price_php').value  = btn.dataset.price;
    document.getElementById('stock_qty').value  = btn.dataset.stock;

    // simple category select by text label
    const catSel = document.getElementById('category_id');
    [...catSel.options].forEach(o => {
      o.selected = (o.text === btn.dataset.cat);
    });
  });
});

document.getElementById('resetFormBtn')?.addEventListener('click', () => {
  ['product_id','name','description','price_php','stock_qty'].forEach(id=>{
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  const catSel = document.getElementById('category_id');
  if (catSel) catSel.selectedIndex = 0;
});
</script>

<?php include __DIR__.'/../partials/footer.php'; ?>
