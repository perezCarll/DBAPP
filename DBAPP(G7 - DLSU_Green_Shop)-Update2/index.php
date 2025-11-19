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

      <!-- Slide 1 – All Merch -->
      <div class="carousel-item active">
        <div class="hero-slide hero-slide--photo">
          <div class="container">
            <div class="row align-items-center gy-4">

              <div class="col-12 col-lg-6">
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

              <!-- FULL HEIGHT, FULL WIDTH PHOTO -->
              <div class="col-lg-6 d-none d-lg-block">
                <img src="/images/mockupslide1.png"
                     class="hero-lifestyle-img"
                     alt="Students wearing official DLSU merchandise">
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- Slide 2 – Cap -->
      <div class="carousel-item">
        <div class="hero-slide hero-slide--photo">
          <div class="container">
            <div class="row align-items-center gy-4">

              <div class="col-12 col-lg-6">
                <span class="badge bg-light text-dark mb-3">Campus Essentials</span>
                <h1 class="display-4 fw-bold">DLSU Green Cap</h1>
                <p class="lead mb-4">
                  Everyday classic cap with stitched Lasallian logo — perfect for walks across campus,
                  game days, and casual outfits.
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

              <!-- FULL HEIGHT, FULL WIDTH PHOTO -->
              <div class="col-lg-6 d-none d-lg-block">
                <img src="/images/mockupslide2.png"
                     class="hero-lifestyle-img"
                     alt="Student wearing DLSU cap on campus walkway">
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- Slide 3 – Varsity Collection (same layout as 1 & 2) -->
      <div class="carousel-item">
        <div class="hero-slide hero-slide--photo">
          <div class="container">
            <div class="row align-items-center gy-4">

              <div class="col-12 col-lg-6">
                <span class="badge bg-light text-dark mb-3">Limited Drop</span>
                <h1 class="display-4 fw-bold">Varsity Collection</h1>
                <p class="lead mb-4">
                  Court-ready jerseys and jackets inspired by classic Lasallian athletics.
                  Rep your number from the stands to the streets.
                </p>

                <div class="d-flex gap-2 flex-wrap">
                  <a href="/pages/products.php?tag=varsity" class="btn btn-success btn-lg">
                    Shop Varsity
                  </a>
                  <a href="/pages/products.php" class="btn btn-outline-light btn-lg">
                    View All Apparel
                  </a>
                </div>
              </div>

              <!-- FULL HEIGHT, FULL WIDTH PHOTO -->
              <div class="col-lg-6 d-none d-lg-block">
                <img src="/images/mockupslide3.png"
                     class="hero-lifestyle-img"
                     alt="Student wearing DLSU varsity jersey on the court">
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

<!-- Featured Items – reversed-color block -->
<section class="py-5" style="background-color: #ffffff;">
  <div class="container">

    <div class="text-center mb-4">
      <span class="badge" style="background-color:#0a3c2f; color:white;">Featured Items</span>
      <h2 class="fw-bold" style="color:#0a3c2f;">Discover This Week’s Top Picks</h2>
      <p class="mb-0" style="color:#0a3c2f;">
        Handpicked apparel and accessories — best sellers and newest drops.
      </p>
    </div>

    <div class="row g-4">

      <!-- Featured Item 1 -->
      <div class="col-6 col-lg-3">
        <div class="p-3 rounded shadow-sm text-center h-100"
             style="background:#ffffff; border:1px solid #e8e8e8;">
          <img src="/images/Hoodie(Green).png" class="img-fluid mb-2" alt="DLSU Classic Hoodie">
          <h6 class="fw-semibold mb-1" style="color:#0a3c2f;">DLSU Classic Hoodie</h6>
          <small class="text-muted">Best Seller</small>
        </div>
      </div>

      <!-- Featured Item 2 -->
      <div class="col-6 col-lg-3">
        <div class="p-3 rounded shadow-sm text-center h-100"
             style="background:#ffffff; border:1px solid #e8e8e8;">
          <img src="/images/Cap(Green).png" class="img-fluid mb-2" alt="DLSU Green Cap">
          <h6 class="fw-semibold mb-1" style="color:#0a3c2f;">DLSU Green Cap</h6>
          <small class="text-muted">Campus Favorite</small>
        </div>
      </div>

      <!-- Featured Item 3 -->
      <div class="col-6 col-lg-3">
        <div class="p-3 rounded shadow-sm text-center h-100"
             style="background:#ffffff; border:1px solid #e8e8e8;">
          <img src="/images/VarsityJacket(Green).png" class="img-fluid mb-2" alt="Varsity Jersey">
          <h6 class="fw-semibold mb-1" style="color:#0a3c2f;">Varsity Jacket</h6>
          <small class="text-muted">Limited Drop</small>
        </div>
      </div>

      <!-- Featured Item 4 -->
      <div class="col-6 col-lg-3">
        <div class="p-3 rounded shadow-sm text-center h-100"
             style="background:#ffffff; border:1px solid #e8e8e8;">
          <img src="/images/Stickerpack.png" class="img-fluid mb-2" alt="Sticker Pack">
          <h6 class="fw-semibold mb-1" style="color:#0a3c2f;">Sticker Pack</h6>
          <small class="text-muted">New Arrival</small>
        </div>
      </div>

    </div>
  </div>
</section>


<?php include __DIR__ . '/partials/footer.php'; ?>
