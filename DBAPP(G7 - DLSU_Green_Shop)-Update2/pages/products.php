<?php
// pages/products.php
$title = 'Products';
require_once __DIR__ . '/../app/session.php';
$pdo = require __DIR__ . '/../app/db.php';

include __DIR__ . '/../partials/header.php';

/**
 * 1) Fetch products + per-branch stock from MySQL
 *    Using Products + Categories + Product_Stock + Branches
 */
try {
  $stmt = $pdo->query("
    SELECT 
      p.product_id,
      p.name,
      p.description,
      p.price,                 -- PHP price
      c.category_name,
      ps.branch_id,
      ps.stock_quantity,
      b.branch_name
    FROM Products p
    LEFT JOIN Categories c    ON p.category_id = c.category_id
    LEFT JOIN Product_Stock ps ON ps.product_id = p.product_id
    LEFT JOIN Branches b      ON ps.branch_id = b.branch_id
    ORDER BY p.name, b.branch_name
  ");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $rows = [];
}

// Re-shape into per-product structure
$productsById = [];   // [product_id => [base info + branches[]]]
$categorySet  = [];   // for category filter

foreach ($rows as $r) {
  $pid = (int)$r['product_id'];
  if (!$pid) continue;

  if (!isset($productsById[$pid])) {
    $productsById[$pid] = [
      'product_id'    => $pid,
      'name'          => $r['name'],
      'description'   => $r['description'],
      'price'         => $r['price'],
      'category_name' => $r['category_name'],
      'branches'      => [], // will be array of [branch_id, branch_name, stock_quantity]
    ];
    if (!empty($r['category_name'])) {
      $categorySet[$r['category_name']] = true;
    }
  }

  if (!empty($r['branch_id'])) {
    $productsById[$pid]['branches'][] = [
      'branch_id'      => (int)$r['branch_id'],
      'branch_name'    => $r['branch_name'],
      'stock_quantity' => (int)$r['stock_quantity'],
    ];
  }
}

// Final arrays
$products   = array_values($productsById);
$categories = array_keys($categorySet);

/**
 * 2) Fetch all branches for the filter dropdown
 */
try {
  $stmtB = $pdo->query("SELECT branch_id, branch_name FROM Branches ORDER BY branch_name");
  $allBranches = $stmtB->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $allBranches = [];
}

/**
 * 3) Meta map (images only; availability is from DB now)
 */
$productMeta = [
  'DLSU Classic Hoodie' => [
    'img' => '/images/Hoodie(Green).png',
  ],
  'DLSU Green Cap' => [
    'img' => '/images/Cap(Green).png',
  ],
  'DLSU Varsity Jacket' => [
    'img' => '/images/VarsityJacket(Green).png',
  ],
  'Varsity Jersey' => [
    'img' => '/images/Jersey(Green).png',
  ],
  'Sticker Pack' => [
    'img' => '/images/Stickerpack.png',
  ],
  'Zip Hoodie' => [
    'img' => '/images/ZipHoodie(Green).png',
  ],
];
?>

<section class="py-5" id="products">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2 class="mb-0">Products</h2>
        <small class="text-muted">Official DLSU apparel, accessories, and collectibles.</small>
      </div>
    </div>

    <!-- Filters (category + branch) -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <label for="filterCategory" class="form-label small text-muted mb-1">Filter by category</label>
        <select id="filterCategory" class="form-select form-select-sm">
          <option value="all">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label for="filterBranch" class="form-label small text-muted mb-1">Filter by branch</label>
        <select id="filterBranch" class="form-select form-select-sm">
          <option value="all">All Branches</option>
          <?php foreach ($allBranches as $b): ?>
            <option value="<?= htmlspecialchars($b['branch_name']) ?>">
              <?= htmlspecialchars($b['branch_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <?php if (empty($products)): ?>
      <div class="alert alert-info">
        No products found. Please seed your <code>Products</code> and <code>Product_Stock</code> tables.
      </div>
    <?php else: ?>

      <div class="row g-4" id="productGrid">
        <?php foreach ($products as $p): ?>
          <?php
            $name        = $p['name'];
            $description = $p['description'] ?? '';
            $category    = $p['category_name'] ?? 'Uncategorized';
            $pricePhp    = (float)$p['price'];

            $branches    = $p['branches'];

            // Compute total stock and nice branch string
            $totalStock   = 0;
            $branchLabels = [];   // e.g. ["Taft (10)", "Laguna (5)"]
            $branchNames  = [];   // e.g. ["Taft", "Laguna"] for data-branches

            if (!empty($branches)) {
              foreach ($branches as $br) {
                $totalStock   += $br['stock_quantity'];
                $branchLabels[] = sprintf(
                  '%s (%d)',
                  $br['branch_name'],
                  $br['stock_quantity']
                );
                $branchNames[] = $br['branch_name'];
              }
            }

            $branchesStr  = $branchLabels ? implode(' · ', $branchLabels) : 'No branch stock yet';
            $branchesAttr = $branchNames ? implode(',', $branchNames) : '';
            
            $meta = $productMeta[$name] ?? null;
            $img  = $meta['img'] ?? '/assets/img/placeholder-product.png';
          ?>
          <div class="col-12 col-sm-6 col-lg-4">
            <div class="card h-100 product-card"
                 data-product-id="<?= (int)$p['product_id'] ?>"
                 data-name="<?= htmlspecialchars($name, ENT_QUOTES) ?>"
                 data-description="<?= htmlspecialchars($description, ENT_QUOTES) ?>"
                 data-category="<?= htmlspecialchars($category, ENT_QUOTES) ?>"
                 data-branches="<?= htmlspecialchars($branchesAttr, ENT_QUOTES) ?>"
                 data-img="<?= htmlspecialchars($img, ENT_QUOTES) ?>"
                 data-price-php="<?= htmlspecialchars($pricePhp, ENT_QUOTES) ?>"
            >
              <div class="ratio ratio-4x3">
                <img src="<?= htmlspecialchars($img) ?>"
                     class="card-img-top object-fit-cover"
                     alt="<?= htmlspecialchars($name) ?>">
              </div>
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h5 class="card-title mb-0"><?= htmlspecialchars($name) ?></h5>
                  <span class="badge text-bg-secondary">
                    <?= htmlspecialchars($category ?: 'Uncategorized') ?>
                  </span>
                </div>

                <?php if ($description): ?>
                  <p class="card-text text-muted small mb-2"><?= htmlspecialchars($description) ?></p>
                <?php endif; ?>

                <!-- Stock info -->
                <div class="small mb-1">
                  <strong>Total stock:</strong> <?= (int)$totalStock ?>
                </div>
                <div class="small text-muted mb-2">
                  <?= htmlspecialchars($branchesStr) ?>
                </div>

                <div class="mt-auto d-flex justify-content-between align-items-center">
                  <span class="price-value"
                        data-price-php="<?= htmlspecialchars($pricePhp, ENT_QUOTES) ?>">
                    ₱ <?= number_format($pricePhp, 0) ?>
                  </span>
                </div>

                <div class="mt-2 d-flex justify-content-between">
                  <button class="btn btn-sm btn-outline-success btn-quick-view" type="button">
                    Details
                  </button>
                  <button class="btn btn-sm btn-success btn-add-to-cart" type="button">
                    <i class="bi bi-bag-plus"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
