<?php $title = 'Products'; include __DIR__ . '/../partials/header.php'; ?>
<section class="py-5" id="products">
  <div class="container">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
      <div>
        <h2 class="mb-0">Products</h2>
        <small class="text-muted">Filter by category, branch, and currency</small>
      </div>
      <div class="d-flex gap-2">
        <select class="form-select" id="filterCategory" style="min-width:180px">
          <option value="all" selected>All Categories</option>
          <option value="Apparel">Apparel</option>
          <option value="Accessories">Accessories</option>
          <option value="Collectibles">Collectibles</option>
        </select>
        <select class="form-select" id="filterBranch" style="min-width:180px">
          <option value="all" selected>All Branches</option>
          <option value="Taft">Taft</option>
          <option value="Canlubang">Canlubang</option>
        </select>
      </div>
    </div>
    <div class="row g-4" id="productGrid"></div>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>