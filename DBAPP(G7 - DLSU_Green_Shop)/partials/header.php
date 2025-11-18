<?php
  $title = $title ?? 'DLSU Green Shop';
  require_once __DIR__ . '/../app/session.php';
  $me = auth_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($title) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="/assets/css/styles.css" rel="stylesheet" />
</head>

<body class="d-flex flex-column min-vh-100 pt-5"><!-- offset for fixed navbar -->

<nav class="navbar navbar-expand-lg navbar-dark fixed-top nav-solid" id="mainNavbar">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/index.php">DLSU Green Shop</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"
            aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <!-- Public links -->
        <li class="nav-item">
          <a class="nav-link<?= ($_SERVER['PHP_SELF'] === '/index.php' ? ' active' : '') ?>"
             href="/index.php">Home</a>
        </li>

        <li class="nav-item">
          <a class="nav-link<?= (str_contains($_SERVER['PHP_SELF'],'products') ? ' active' : '') ?>"
             href="/pages/products.php">Products</a>
        </li>

        <li class="nav-item">
          <a class="nav-link<?= (str_contains($_SERVER['PHP_SELF'],'branches') ? ' active' : '') ?>"
             href="/pages/branches.php">Branches</a>
        </li>

        <li class="nav-item">
          <a class="nav-link<?= (str_contains($_SERVER['PHP_SELF'],'about') ? ' active' : '') ?>"
             href="/pages/about.php">About</a>
        </li>

        <li class="nav-item">
          <a class="nav-link<?= (str_contains($_SERVER['PHP_SELF'],'contact') ? ' active' : '') ?>"
             href="/pages/contact.php">Contact</a>
        </li>

        <!-- Top-nav Checkout ONLY for Customer role -->
        <?php if ($me && ($me['role'] ?? '') === 'Customer'): ?>
          <li class="nav-item">
            <a class="nav-link<?= (str_contains($_SERVER['PHP_SELF'],'checkout') ? ' active' : '') ?>"
               href="/pages/checkout.php">
              Checkout
            </a>
          </li>
        <?php endif; ?>

        <!-- Role-specific dashboard links (top nav) -->
        <?php if (is_role('Admin')): ?>
          <li class="nav-item">
            <a class="nav-link<?= (str_contains($_SERVER['PHP_SELF'],'admin.php') ? ' active' : '') ?>"
               href="/pages/admin.php">
              <i class="bi bi-speedometer2 me-1"></i>Admin
            </a>
          </li>

        <?php elseif (is_role('Manager')): ?>
          <li class="nav-item">
            <a class="nav-link<?= (str_contains($_SERVER['PHP_SELF'],'manager-dashboard') ? ' active' : '') ?>"
               href="/pages/manager-dashboard.php">
              <i class="bi bi-speedometer2 me-1"></i>Manager
            </a>
          </li>

        <?php elseif (is_role('Staff')): ?>
          <li class="nav-item">
            <a class="nav-link<?= (str_contains($_SERVER['PHP_SELF'],'admin-orders') ? ' active' : '') ?>"
               href="/pages/admin-orders.php">
              <i class="bi bi-speedometer2 me-1"></i>Staff
            </a>
          </li>
        <?php endif; ?>
      </ul>

      <div class="d-flex ms-lg-3 gap-2 align-items-center">
        <!-- Currency -->
        <div class="dropdown">
          <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" id="currencyBtn">
            <span class="currency-flag" id="currencyFlag"></span>
            <span id="currencyLabel">PHP</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item currency-option" data-code="PHP" href="#">PHP – Philippine Peso</a></li>
            <li><a class="dropdown-item currency-option" data-code="USD" href="#">USD – US Dollar</a></li>
            <li><a class="dropdown-item currency-option" data-code="EUR" href="#">EUR – Euro</a></li>
          </ul>
        </div>

        <!-- Cart -->
        <button class="btn btn-outline-light position-relative" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer">
          <i class="bi bi-bag"></i>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount">0</span>
        </button>

        <!-- Auth -->
        <?php if (!$me): ?>
          <a class="btn btn-success" href="/pages/admin-login.php">
            <i class="bi bi-box-arrow-in-right me-1"></i>Login
          </a>
        <?php else: ?>
          <div class="dropdown">
            <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($me['full_name'] ?? $me['name'] ?? 'User') ?>
              <span class="text-muted ms-1">(<?= htmlspecialchars($me['role']) ?>)</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <!-- Customer self-service links -->
              <?php if (($me['role'] ?? '') === 'Customer'): ?>
                <li>
                  <a class="dropdown-item" href="/pages/checkout.php">
                    <i class="bi bi-bag-check me-2"></i>Checkout
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="/pages/order-history.php">
                    <i class="bi bi-clock-history me-2"></i>My Orders
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>

              <!-- Admin-only dropdown links -->
              <?php if (is_role('Admin')): ?>
                <li>
                  <a class="dropdown-item" href="/pages/admin.php">
                    <i class="bi bi-speedometer2 me-2"></i>Admin Panel
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="/pages/admin-log.php">
                    <i class="bi bi-journal-text me-2"></i>Transaction Log
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>

              <!-- Manager-only dropdown link -->
              <?php elseif (is_role('Manager')): ?>
                <li>
                  <a class="dropdown-item" href="/pages/manager-dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>Manager Dashboard
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>

              <!-- Staff-only dropdown link -->
              <?php elseif (is_role('Staff')): ?>
                <li>
                  <a class="dropdown-item" href="/pages/admin-orders.php">
                    <i class="bi bi-speedometer2 me-2"></i>Staff Area
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>

              <li>
                <form action="/auth/logout.php" method="post" class="px-3 py-1">
                  <button class="btn btn-link p-0">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                  </button>
                </form>
              </li>
            </ul>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</nav>

<main class="flex-fill">

<script>
  (function () {
    const nav = document.getElementById('mainNavbar');
    const onScroll = () => {
      if (window.scrollY > 12) nav.classList.add('scrolled');
      else nav.classList.remove('scrolled');
    };
    document.addEventListener('DOMContentLoaded', onScroll);
    window.addEventListener('scroll', onScroll, { passive: true });
  })();
</script>
