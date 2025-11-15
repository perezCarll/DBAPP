<?php $title = 'About'; include __DIR__ . '/../partials/header.php'; ?>
<section class="py-5" id="about">
  <div class="container">
    <div class="row g-4 align-items-center">
      <div class="col-lg-6">
        <h2>About DLSU Green Shop</h2>
        <p class="text-muted">We offer official DLSU‑themed apparel, accessories, and memorabilia to strengthen school spirit and foster belonging among students, alumni, and supporters. Limited editions are released for anniversaries, athletics, and graduations, often in collaboration with student organizations.</p>
        <ul class="list-unstyled small">
          <li><i class="bi bi-check2-circle text-success me-1"></i> Official and authentic merchandise</li>
          <li><i class="bi bi-check2-circle text-success me-1"></i> Online ordering with branch‑aware availability</li>
          <li><i class="bi bi-check2-circle text-success me-1"></i> Multi‑currency viewing: PHP, USD, EUR</li>
        </ul>
      </div>
      <div class="col-lg-6">
        <div class="card border-0">
          <div class="card-body">
            <h5 class="card-title">Planned System Roles</h5>
            <p class="text-muted mb-2">UI accommodates role‑based features you can wire up later:</p>
            <div class="row g-3">
              <div class="col-md-3">
                <div class="p-3 bg-white rounded border text-center">
                  <div class="fw-semibold">Admin</div>
                  <small class="text-muted">Users, reports</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 bg-white rounded border text-center">
                  <div class="fw-semibold">Manager</div>
                  <small class="text-muted">Inventory, approvals</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 bg-white rounded border text-center">
                  <div class="fw-semibold">Staff</div>
                  <small class="text-muted">Orders, products</small>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3 bg-white rounded border text-center">
                  <div class="fw-semibold">Customer</div>
                  <small class="text-muted">Browse & checkout</small>
                </div>
              </div>
            </div>
            <div class="alert alert-success mt-3 mb-0"><i class="bi bi-info-circle me-2"></i>Hook these to real auth & data later with PHP/MySQL.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>