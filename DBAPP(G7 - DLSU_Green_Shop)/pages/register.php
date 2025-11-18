<?php
$title = 'Register';
require_once __DIR__ . '/../app/session.php';
$pdo = require __DIR__ . '/../app/db.php';

// Redirect logged-in users to dashboard/home


$errors = [];
$name = $email = $address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $address  = trim($_POST['address'] ?? '');

    // --- Validation ---
    if ($name === '') $errors[] = "Name is required.";
    if ($email === '') $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    if ($password === '') $errors[] = "Password is required.";
    if ($password !== $confirm) $errors[] = "Passwords do not match.";

    // Check for existing email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) $errors[] = "Email is already registered.";

    // --- Insert user ---
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, address, role_id) VALUES (?, ?, ?, ?, ?)");
        // Default role_id = 1 (customer)
        if ($stmt->execute([$name, $email, $hashed, $address, 3])) {
            header('Location: /index.php?registered=1');
            exit;
        } else {
            $errors[] = "Registration failed. Try again later.";
        }
    }
}

include __DIR__ . '/../partials/header.php';
?>

<section class="d-flex justify-content-center align-items-center" style="min-height:80vh;">
  <div class="card shadow p-4" style="width:500px;">
    <h2 class=" text-center mb-2">Register</h2>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err) echo "<li>" . htmlspecialchars($err) . "</li>"; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($address) ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control">
      </div>

      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control">
      </div>

      <button class="btn btn-success w-100">Register</button>
      <p class="mt-3 text-muted text-center">
        Already have an account? <a href="/pages/admin-login.php">Login here</a>.
      </p>
    </form>
  </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>