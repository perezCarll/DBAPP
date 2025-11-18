<?php
// pages/admin-login.php
$title = 'Login';
require_once __DIR__.'/../app/csrf.php';
include __DIR__.'/../partials/header.php';
?>
<section class="d-flex justify-content-center align-items-center" style="min-height:80vh;">
  <div class="card shadow p-4" style="width:380px;">
    <h3 class="text-center mb-2">Sign in</h3>
    <?php if(isset($_GET['error'])): ?>
      <div class="alert alert-danger py-2">Invalid email or password.</div>
    <?php endif; ?>
    <?php if(isset($_GET['denied'])): ?>
      <div class="alert alert-warning py-2">Please sign in first.</div>
    <?php endif; ?>

    <form method="post" action="/auth/login.php" class="mt-3">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input name="email" type="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" required>
      </div>
      <button class="btn btn-success w-100">Login</button>
      <div class="text-center mt-3">
        <a href="/index.php">Back Home</a>
      </div>
    </form>
    <div class="text-center mt-3">
      <p class="text-muted">
        Don't have an account? <a href="/pages/register.php" class="text-decoration-none ">Register here</a>
      </p>
  </div>
</section>
<?php include __DIR__.'/../partials/footer.php'; ?>
