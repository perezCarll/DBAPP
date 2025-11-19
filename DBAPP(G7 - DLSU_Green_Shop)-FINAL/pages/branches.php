<?php
  $title = 'Branches';
  include __DIR__ . '/../partials/header.php';
?>

<section class="py-5 bg-white" id="branches">
  <div class="container">
    <div class="row align-items-center g-4">

      <!-- Branch List -->
      <div class="col-lg-6">
        <h2>Branches</h2>
        <p class="text-muted">
          We currently operate two branches. Product availability can vary by branch.
        </p>

        <div class="list-group shadow-sm" id="branchSelector">

          <!-- Taft Card -->
          <button class="list-group-item d-flex align-items-start active"
                  data-map="taft"
                  style="cursor:pointer;">
            <i class="bi bi-geo-alt fs-4 me-3 text-success"></i>
            <div>
              <div class="fw-semibold">Taft</div>
              <small class="text-muted">Open Mon–Sat, 10:00–19:00</small>
            </div>
          </button>

          <!-- Laguna Card -->
          <button class="list-group-item d-flex align-items-start"
                  data-map="laguna"
                  style="cursor:pointer;">
            <i class="bi bi-geo-alt fs-4 me-3 text-success"></i>
            <div>
              <div class="fw-semibold">Laguna</div>
              <small class="text-muted">Open Mon–Sat, 10:00–19:00</small>
            </div>
          </button>

        </div>
      </div>

      <!-- Map -->
      <div class="col-lg-6">
        <div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm">
          <iframe
            id="branchMap"
            title="DLSU Green Shop Branch Map"
            src="https://www.google.com/maps/embed?pb=TAFT_MAP_EMBED_CODE"
            style="border:0;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>

    </div>
  </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const mapFrame = document.getElementById("branchMap");
  const selectors = document.querySelectorAll("#branchSelector button");

  const MAP_URLS = {
    taft: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2418.529089582059!2d120.9907833983946!3d14.5647642!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c97ed286459b%3A0x5927068d997eae2a!2sDe%20La%20Salle%20University%20Manila!5e1!3m2!1sen!2sph!4v1763546503410!5m2!1sen!2sph",
    laguna: "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4843.617975516181!2d121.04031407599281!3d14.26264098618459!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd7d7af18405ff%3A0x8d40985968975a91!2sDe%20La%20Salle%20University%20%E2%80%93%20Laguna%20Campus!5e1!3m2!1sen!2sph!4v1763546697827!5m2!1sen!2sph"
  };

  selectors.forEach(btn => {
    btn.addEventListener("click", () => {
      // highlight correct card
      selectors.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");

      // update map src
      const mapKey = btn.getAttribute("data-map");
      mapFrame.src = MAP_URLS[mapKey];
    });
  });
});
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
