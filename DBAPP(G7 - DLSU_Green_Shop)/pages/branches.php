<?php $title = 'Branches'; include __DIR__ . '/../partials/header.php'; ?>
<section class="py-5 bg-white" id="branches">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <h2>Branches</h2>
        <p class="text-muted">We currently operate two branches. Product availability can vary by branch.</p>
        <div class="list-group shadow-sm">
          <div class="list-group-item d-flex align-items-start">
            <i class="bi bi-geo-alt fs-4 me-3 text-success"></i>
            <div>
              <div class="fw-semibold">Taft</div>
              <small class="text-muted">Open Mon–Sat, 10:00–19:00</small>
            </div>
          </div>
          <div class="list-group-item d-flex align-items-start">
            <i class="bi bi-geo-alt fs-4 me-3 text-success"></i>
            <div>
              <div class="fw-semibold">Laguna</div>
              <small class="text-muted">Open Mon–Sat, 10:00–19:00</small>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm">
          <iframe title="Map placeholder" srcdoc="<style>body{margin:0;display:flex;align-items:center;justify-content:center;font-family:system-ui;background:#f6f8f7;color:#14312b}</style><div><div style='font-size:24px'>Branch Map Placeholder</div><div style='opacity:.7'>Embed Google Maps later</div></div>"></iframe>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>