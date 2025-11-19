<?php
  $title = 'Contact';
  include __DIR__ . '/../partials/header.php';
?>

<section class="py-5 bg-white" id="contact">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7">

        <h2 class="mb-2">Questions or feedback?</h2>
        <p class="text-muted mb-4">
          Send us a message. We usually reply within one business day.
        </p>

        <form class="mb-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small text-muted">Name</label>
              <input type="text" class="form-control" placeholder="Your full name">
            </div>
            <div class="col-md-6">
              <label class="form-label small text-muted">Email</label>
              <input type="email" class="form-control" placeholder="name@lasalle.edu.ph">
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label small text-muted">Message</label>
            <textarea class="form-control" rows="5" placeholder="How can we help?"></textarea>
          </div>

          <button class="btn btn-success mt-4 px-4" type="submit">
            Send Message
          </button>
        </form>

        <!-- Optional Contact Info/Footer Section -->
        <div class="border-top pt-4">
          <h6 class="text-success small fw-bold mb-2">Other ways to reach us</h6>
          <p class="small text-muted mb-1">
            <i class="bi bi-envelope me-1 text-success"></i>
            support@dlsugreenshop.com
          </p>
          <p class="small text-muted mb-0">
            <i class="bi bi-clock me-1 text-success"></i>
            Monday–Saturday, 10:00–19:00
          </p>
        </div>

      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>
