<?php
// app/session.php
declare(strict_types=1);

// Start the session only if not already active
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,   // set true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function auth_login(array $user): void {
  $_SESSION['user'] = [
    'user_id'   => $user['user_id'],
    'full_name' => $user['full_name'] ?? ($user['name'] ?? ''),
    'email'     => $user['email'],
    'role'      => $user['role'],
  ];
}

function auth_user(): ?array {
  return $_SESSION['user'] ?? null;
}

function auth_logout(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
}

function is_role($roles): bool {
  $u = auth_user();
  if (!$u) return false;
  $roles = (array)$roles;
  return in_array($u['role'] ?? '', $roles, true);
}

function require_login(): void {
  if (!auth_user()) {
    header('Location: /pages/admin-login.php?denied=1');
    exit;
  }
}

function require_role($roles): void {
  if (!is_role($roles)) {
    header('HTTP/1.1 403 Forbidden');
    echo '<h1>403 Forbidden</h1>';
    exit;
  }
}
