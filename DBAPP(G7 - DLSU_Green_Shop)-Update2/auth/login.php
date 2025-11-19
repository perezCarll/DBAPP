<?php
require_once __DIR__.'/../app/csrf.php';
$pdo = require __DIR__.'/../app/db.php';
require_once __DIR__.'/../app/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /pages/admin-login.php');
  exit;
}

if (!csrf_verify($_POST['csrf'] ?? '')) {
  header('Location: /pages/admin-login.php?error=1');
  exit;
}

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($email === '' || $pass === '') {
  header('Location: /pages/admin-login.php?error=1');
  exit;
}

$sql = "
  SELECT 
    u.user_id,
    u.name,
    u.email,
    u.password,
    r.role_name AS role
  FROM Users u
  JOIN Roles r ON u.role_id = r.role_id
  WHERE u.email = ?
  LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($pass, $user['password'])) {
  auth_login($user);
  header('Location: /index.php');
  exit;
}

header('Location: /pages/admin-login.php?error=1');
exit;
