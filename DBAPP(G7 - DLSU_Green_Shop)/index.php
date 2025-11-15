<?php $title = 'DLSU Green Shop — Home'; include __DIR__ . '/partials/header.php'; ?>
<header class="hero" id="home">
  <div class="container py-5">
    <div class="row align-items-center">
      <div class="col-12 col-lg-7">
        <h1 class="display-4 fw-bold">Official DLSU Merchandise</h1>
        <p class="lead mb-4">Show your Lasallian pride with apparel, accessories, and collectibles. Shop online or visit our branches.</p>
        <div class="d-flex gap-2">
          <a href="/pages/products.php" class="btn btn-success btn-lg"><i class="bi bi-shop me-2"></i>Shop Now</a>
          <a href="/pages/branches.php" class="btn btn-outline-light btn-lg"><i class="bi bi-geo-alt me-2"></i>Find a Branch</a>
        </div>
      </div>
    </div>
  </div>
</header>

<section class="py-4 bg-white">
  <div class="container">
    <div class="row g-3 g-lg-4">
      <div class="col-6 col-lg-3">
        <div class="d-flex align-items-center gap-3">
          <div class="feature-icon"><i class="bi bi-bag-check"></i></div>
          <div>
            <div class="fw-semibold">Online Ordering</div>
            <small class="text-muted">Browse, details, add to cart</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="d-flex align-items-center gap-3">
          <div class="feature-icon"><i class="bi bi-currency-exchange"></i></div>
          <div>
            <div class="fw-semibold">Multi‑Currency</div>
            <small class="text-muted">PHP / USD / EUR</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="d-flex align-items-center gap-3">
          <div class="feature-icon"><i class="bi bi-building"></i></div>
          <div>
            <div class="fw-semibold">Branch Aware</div>
            <small class="text-muted">Per‑branch availability</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="d-flex align-items-center gap-3">
          <div class="feature-icon"><i class="bi bi-people"></i></div>
          <div>
            <div class="fw-semibold">Role‑Ready UI</div>
            <small class="text-muted">Admin/Manager/Customer</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/partials/footer.php'; ?>