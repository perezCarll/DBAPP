<?php
// pages/products.php
$title = 'Products';
require_once __DIR__ . '/../app/session.php';
$pdo = require __DIR__ . '/../app/db.php';

include __DIR__ . '/../partials/header.php';

//  1) Fetch products from MySQL 
try {
  $stmt = $pdo->query("
    SELECT 
      p.product_id,
      p.name,
      p.description,
      p.price,           -- PHP price
      p.stock_quantity,
      c.category_name
    FROM Products p
    LEFT JOIN Categories c ON p.category_id = c.category_id
    ORDER BY p.name
  ");
  $products = $stmt->fetchAll();
} catch (PDOException $e) {
  $products = [];
}

//  2) Meta map (images + branches, from your JSON) 
$productMeta = [
  'DLSU Classic Hoodie' => [
    'img'      => '/images/Hoodie(Green).png',
    'branches' => ['Taft', 'Laguna'],
  ],
  'DLSU Green Cap' => [
    'img'      => '/images/Cap(Green).png',
    'branches' => ['Taft'],
  ],
  'DLSU Varsity Jacket' => [
    'img'      => '/images/VarsityJacket(Green).png',
    'branches' => ['Taft', 'Laguna'],
  ],
  'Varsity Jersey' => [
    'img'      => '/images/Jersey(Green).png',
    'branches' => ['Taft', 'Laguna'],
  ],
  'Sticker Pack' => [
    'img'      => '/images/Stickerpack.png',
    'branches' => ['Taft', 'Laguna'],
  ],
  'Zip Hoodie' => [
    'img'      => '/images/ZipHoodie(Green).png',
    'branches' => ['Laguna'],
  ],
];

// Collect unique categories for filter dropdown
$categorySet = [];
foreach ($products as $p) {
  if (!empty($p['category_name'])) {
    $categorySet[$p['category_name']] = true;
  }
}
$categories = array_keys($categorySet);
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
          <option value="Taft">Taft</option>
          <option value="Laguna">Laguna</option>
        </select>
      </div>
    </div>

    <?php if (empty($products)): ?>
      <div class="alert alert-info">
        No products found. Please seed your <code>Products</code> table.
      </div>
    <?php else: ?>

      <div class="row g-4" id="productGrid">
        <?php foreach ($products as $p): ?>
          <?php
            $name        = $p['name'];
            $description = $p['description'] ?? '';
            $category    = $p['category_name'] ?? 'Uncategorized';
            $pricePhp    = (float)$p['price'];
            $stock       = (int)$p['stock_quantity'];

            $meta         = $productMeta[$name] ?? null;
            $img          = $meta['img']      ?? '/assets/img/placeholder-product.png';
            $branchesArr  = $meta['branches'] ?? ['Taft', 'Laguna']; // fallback
            $branchesStr  = implode(', ', $branchesArr);
            $branchesAttr = implode(',', $branchesArr);
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
                  <span class="badge text-bg-secondary"><?= htmlspecialchars($branchesStr) ?></span>
                </div>
                <?php if ($category): ?>
                  <div class="small text-muted mb-1"><?= htmlspecialchars($category) ?></div>
                <?php endif; ?>
                <?php if ($description): ?>
                  <p class="card-text text-muted small mb-2"><?= htmlspecialchars($description) ?></p>
                <?php endif; ?>

                <div class="mt-auto d-flex justify-content-between align-items-center">
                  <span class="price-value"
                        data-price-php="<?= htmlspecialchars($pricePhp, ENT_QUOTES) ?>">
                    ₱ <?= number_format($pricePhp, 0) ?>
                  </span>
                  <span class="small text-muted">In stock: <strong><?= $stock ?></strong></span>
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
