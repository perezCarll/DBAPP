<?php
$title = 'Manage Products';
require_once __DIR__.'/../app/session.php';
$pdo = require __DIR__.'/../app/db.php';

require_login();
require_role(['Admin','Manager']);

$isAdmin = is_role('Admin');

// --------------------
// Handle POST (Add / Edit / Delete)
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // DELETE (Admin only)
    if (isset($_POST['delete_id'])) {
        if (!$isAdmin) {
            http_response_code(403);
            die('Only Admin can delete products.');
        }

        $pid = (int)$_POST['delete_id'];
        $stmt = $pdo->prepare("CALL sp_delete_product(?)");
        $stmt->execute([$pid]);
        $stmt->closeCursor();

        header("Location: /pages/admin-products.php?deleted=1");
        exit;
    }

    // CREATE / UPDATE
    $pid   = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
    $name  = trim($_POST['name']);
    $desc  = trim($_POST['description']);
    $price = (float)$_POST['price_php'];
    $stock = (int)$_POST['stock_qty'];     // treated as “total” or initial stock
    $cat   = (int)$_POST['category_id'];

    if ($pid) {
        // UPDATE product details (price, name, etc.)
        $stmt = $pdo->prepare("CALL sp_update_product(?,?,?,?,?,?)");
        $stmt->execute([$pid, $name, $desc, $price, $stock, $cat]);
        $stmt->closeCursor();

        // OPTIONAL (for now we leave branch stock as-is; can add admin stock editing later)

        header("Location: /pages/admin-products.php?updated=1");
    } else {
        // CREATE
        $stmt = $pdo->prepare("CALL sp_create_product(?,?,?,?,?)");
        $stmt->execute([$name, $desc, $price, $stock, $cat]);
        $stmt->closeCursor();

        // If you want: seed Product_Stock for all branches for this new product
        // using the entered stock as starting value (simple approach):
        try {
            $newProductId = (int)$pdo->lastInsertId();
            $branchesStmt = $pdo->query("SELECT branch_id FROM Branches");
            $branches = $branchesStmt->fetchAll(PDO::FETCH_COLUMN);

            $ins = $pdo->prepare("
                INSERT INTO Product_Stock (product_id, branch_id, stock_quantity)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE stock_quantity = VALUES(stock_quantity)
            ");

            foreach ($branches as $bid) {
                $ins->execute([$newProductId, (int)$bid, $stock]);
            }
        } catch (PDOException $e) {
            // silently ignore for now; you can log this later
        }

        header("Location: /pages/admin-products.php?created=1");
    }
    exit;
}

// --------------------
// Load categories
// --------------------
$cats = $pdo->query("SELECT category_id, category_name FROM Categories ORDER BY category_name")
            ->fetchAll(PDO::FETCH_ASSOC);

// --------------------
// Load products via SP
// --------------------
$stmt = $pdo->query("CALL sp_get_products()");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

/**
 * NEW: Load live stock from Product_Stock
 *      We aggregate per product_id and use that
 *      instead of Products.stock_quantity.
 */
$stockByProduct = [];
try {
    $stmtStock = $pdo->query("
        SELECT product_id, SUM(stock_quantity) AS total_stock
        FROM Product_Stock
        GROUP BY product_id
    ");
    while ($row = $stmtStock->fetch(PDO::FETCH_ASSOC)) {
        $stockByProduct[(int)$row['product_id']] = (int)$row['total_stock'];
    }
} catch (PDOException $e) {
    // fallback: leave array empty, we’ll fall back to p['stock_quantity'] if present
}

include __DIR__.'/../partials/header.php';
?>

<div class="container py-4">
  <h1 class="mb-3">Manage Products</h1>

  <?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success py-2">Product created.</div>
  <?php elseif (isset($_GET['updated'])): ?>
    <div class="alert alert-success py-2">Product updated.</div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert alert-warning py-2">Product deleted.</div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- FORM -->
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3">Add / Edit Product</h5>

          <form method="post">
            <input type="hidden" name="product_id" id="product_id">

            <div class="mb-2">
              <label class="form-label small">Name</label>
              <input type="text" name="name" id="name" class="form-control form-control-sm" required>
            </div>

            <div class="mb-2">
              <label class="form-label small">Category</label>
              <select name="category_id" id="category_id" class="form-select form-select-sm">
                <?php foreach ($cats as $c): ?>
                  <option value="<?= $c['category_id'] ?>">
                      <?= htmlspecialchars($c['category_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label small">Price (PHP)</label>
                <input type="number" step="0.01" name="price_php" id="price_php"
                       class="form-control form-control-sm" required>
              </div>
              <div class="col-6">
                <label class="form-label small">Stock qty (total)</label>
                <input type="number" name="stock_qty" id="stock_qty"
                       class="form-control form-control-sm" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label small">Description</label>
              <textarea name="description" id="description"
                        class="form-control form-control-sm" rows="3"></textarea>
            </div>

            <button class="btn btn-success btn-sm">Save product</button>
            <button type="button" id="resetForm" class="btn btn-outline-secondary btn-sm">
              Clear
            </button>

          </form>
        </div>
      </div>
    </div>

    <!-- TABLE -->
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
                <?php
                  $pid          = (int)$p['product_id'];
                  // Prefer live stock from Product_Stock; fall back to Products.stock_quantity if needed
                  $liveStock    = $stockByProduct[$pid] ?? ($p['stock_quantity'] ?? 0);
                ?>
                <tr>
                  <td><?= $pid ?></td>
                  <td><?= htmlspecialchars($p['name']) ?></td>
                  <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                  <td class="text-end">₱ <?= number_format($p['price'],2) ?></td>
                  <td class="text-end"><?= $liveStock ?></td>

                  <td>
                    <button
                      class="btn btn-outline-primary btn-sm btn-edit"
                      data-id="<?= $pid ?>"
                      data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>"
                      data-desc="<?= htmlspecialchars($p['description'], ENT_QUOTES) ?>"
                      data-price="<?= $p['price'] ?>"
                      data-stock="<?= (int)$liveStock ?>"
                      data-cat="<?= $p['category_name'] ?>"
                    >
                      Edit
                    </button>
                  </td>

                  <?php if ($isAdmin): ?>
                  <td>
                    <form method="post" onsubmit="return confirm('Delete this?');">
                      <input type="hidden" name="delete_id" value="<?= $pid ?>">
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
    </div>

  </div><!-- row -->
</div>

<script>
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    product_id.value   = btn.dataset.id;
    name.value         = btn.dataset.name;
    description.value  = btn.dataset.desc;
    price_php.value    = btn.dataset.price;
    stock_qty.value    = btn.dataset.stock;

    // Set category by matching text
    [...category_id.options].forEach(o => {
      o.selected = (o.text === btn.dataset.cat);
    });
  });
});

document.getElementById('resetForm').addEventListener('click', () => {
  product_id.value   = '';
  name.value         = '';
  description.value  = '';
  price_php.value    = '';
  stock_qty.value    = '';
  category_id.selectedIndex = 0;
});
</script>

<?php include __DIR__.'/../partials/footer.php'; ?>
