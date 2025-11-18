<?php
  $title = 'DLSU Green Shop — Home';
  include __DIR__ . '/partials/header.php';
?>

<header id="home">
  <div id="heroCarousel"
       class="carousel slide hero-carousel"
       data-bs-ride="carousel"
       data-bs-interval="7000"
       data-bs-pause="hover">

    <div class="carousel-indicators">
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
      <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    </div>

    <div class="carousel-inner">

      <!-- Slide 1 – Hoodie -->
      <div class="carousel-item active">
        <div class="hero-slide">
          <div class="container">
            <div class="row align-items-center">

              <div class="col-12 col-lg-7">
                <span class="badge bg-light text-dark mb-3">New • DLSU Green Shop</span>
                <h1 class="display-4 fw-bold">Official DLSU Merchandise</h1>
                <p class="lead mb-4">
                  Show your Lasallian pride with apparel, accessories, and collectibles.
                  Shop online or visit our branches.
                </p>

                <div class="d-flex gap-2 flex-wrap">
                  <a href="/pages/products.php" class="btn btn-success btn-lg">
                    <i class="bi bi-shop me-2"></i>Shop Now
                  </a>
                  <a href="/pages/branches.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-geo-alt me-2"></i>Find a Branch
                  </a>
                </div>
              </div>

              <div class="col-lg-5 d-none d-lg-flex justify-content-center">
                <div class="hero-image-frame">
                  <img src="/images/Hoodie(Green).png"
                       class="hero-product-img"
                       alt="Hoodie Green">
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- Slide 2 – Cap -->
      <div class="carousel-item">
        <div class="hero-slide">
          <div class="container">
            <div class="row align-items-center">

              <div class="col-12 col-lg-7">
                <span class="badge bg-light text-dark mb-3">Campus Essentials</span>
                <h1 class="display-4 fw-bold">DLSU Green Cap</h1>
                <p class="lead mb-4">
                  Everyday classic cap with stitched Lasallian logo — perfect for school days and game days.
                </p>

                <div class="d-flex gap-2 flex-wrap">
                  <a href="/pages/products.php?category=Accessories" class="btn btn-success btn-lg">
                    Browse Accessories
                  </a>
                  <a href="/pages/products.php" class="btn btn-outline-light btn-lg">
                    View All Products
                  </a>
                </div>
              </div>

              <div class="col-lg-5 d-none d-lg-flex justify-content-center">
                <div class="hero-image-frame">
                  <img src="/images/Cap(Green).png"
                       class="hero-product-img"
                       alt="DLSU Cap">
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- Slide 3 – Varsity Jacket only -->
      <div class="carousel-item">
        <div class="hero-slide">
          <div class="container">
            <div class="row align-items-center">

              <div class="col-12 col-lg-7">
                <span class="badge bg-light text-dark mb-3">Limited Drop</span>
                <h1 class="display-4 fw-bold">Varsity Collection</h1>
                <p class="lead mb-4">
                  Hoodies, jerseys, jackets — inspired by classic Lasallian athletics.
                </p>

                <div class="d-flex gap-2 flex-wrap">
                  <a href="/pages/products.php?tag=varsity" class="btn btn-success btn-lg">
                    Shop Varsity
                  </a>
                  <a href="/pages/order-history.php" class="btn btn-outline-light btn-lg">
                    Track My Orders
                  </a>
                </div>
              </div>

              <div class="col-lg-5 d-none d-lg-flex justify-content-center">
                <div class="hero-image-frame">
                  <img src="/images/Jersey(Green).png"
                       class="hero-product-img"
                       alt="Varsity Jersey">
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Controls -->
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
    </button>

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
            <div class="fw-semibold">Multi-Currency</div>
            <small class="text-muted">PHP / USD / EUR</small>
          </div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="d-flex align-items-center gap-3">
          <div class="feature-icon"><i class="bi bi-building"></i></div>
          <div>
            <div class="fw-semibold">Branch Aware</div>
            <small class="text-muted">Per-branch availability</small>
          </div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="d-flex align-items-center gap-3">
          <div class="feature-icon"><i class="bi bi-people"></i></div>
          <div>
            <div class="fw-semibold">Role-Ready UI</div>
            <small class="text-muted">Admin/Manager/Customer</small>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>
