</main>
<footer class="footer py-4 mt-auto"><!-- mt-auto pushes footer to bottom on short pages -->
  <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>© <span id="year"></span> DLSU Green Shop</div>
  </div>
</footer>

<!-- QUICK VIEW MODAL -->
<div class="modal fade" id="quickView" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="quickViewTitle">Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-4">
          <div class="col-md-6"><img id="quickViewImg" class="img-fluid rounded" alt="Product image" /></div>
          <div class="col-md-6">
            <p class="text-muted" id="quickViewDesc"></p>
            <div class="h4" id="quickViewPrice"></div>
            <div class="mt-3">
              <button class="btn btn-success" id="quickViewAdd">Add to Cart</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- CART OFFCANVAS -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartDrawer">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title"><i class="bi bi-bag me-2"></i>Your Cart</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column">
    <ul class="list-group list-group-flush mb-3" id="cartItems"></ul>
    <div class="mt-auto">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="fw-semibold">Subtotal:</span>
        <span class="h5 mb-0" id="cartSubtotal">—</span>
      </div>
      <button class="btn btn-success w-100" disabled>Checkout (placeholder)</button>
      <div class="form-text mt-2">Payments, order tracking, and user accounts will be wired up later with PHP/MySQL.</div>
    </div>
  </div>
  <div class="offcanvas-footer p-3"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
<script>document.getElementById('year').textContent = new Date().getFullYear();</script>
</body>
</html>
