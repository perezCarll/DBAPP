<?php
require_once __DIR__.'/../app/csrf.php';
require_once __DIR__.'/../app/db.php';
require_once __DIR__.'/../app/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /pages/admin-login.php');
  exit;
}

if (!csrf_verify($_POST['csrf'] ?? '')) {
  die('Invalid CSRF token');
}

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($pass, $user['password_hash'])) {
  auth_login($user);
  header('Location: /index.php');
  exit;
}
header('Location: /pages/admin-login.php?error=1');
exit;
