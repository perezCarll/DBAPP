<?php $title = 'Contact'; include __DIR__ . '/../partials/header.php'; ?>
<section class="py-5 bg-white" id="contact">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-7">
        <h2>Questions or feedback?</h2>
        <p class="text-muted">Send us a message. We usually reply within one business day.</p>
        <form class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" placeholder="Your full name" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" placeholder="name@lasalle.edu.ph" />
          </div>
          <div class="col-12">
            <label class="form-label">Message</label>
            <textarea class="form-control" rows="4" placeholder="How can we help?"></textarea>
          </div>
          <div class="col-12">
            <button type="button" class="btn btn-success">Send Message</button>
          </div>
        </form>
      </div>
      <div class="col-lg-5">
        <div class="p-4 bg-light rounded border">
          <div class="d-flex align-items-center mb-3">
            <i class="bi bi-shield-lock fs-3 text-success me-2"></i>
            <div>
              <div class="fw-semibold">Business Rules (Preview)</div>
              <small class="text-muted">Surface key rules in the UI copy</small>
            </div>
          </div>
          <ul class="small mb-0">
            <li>Unique product IDs & per‑branch stock</li>
            <li>Orders processed after payment validation</li>
            <li>Cancel within 24 hours (remorse period)</li>
            <li>Monthly branch sales reports</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../partials/footer.php'; ?>