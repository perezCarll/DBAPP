<?php
// app/csrf.php
declare(strict_types=1);

require_once __DIR__ . '/session.php'; // ensures session is started once

const CSRF_KEY = 'csrf_token';

function csrf_token(): string {
  if (empty($_SESSION[CSRF_KEY])) {
    $_SESSION[CSRF_KEY] = bin2hex(random_bytes(32));
  }
  return $_SESSION[CSRF_KEY];
}

function csrf_verify(?string $token): bool {
  return isset($_SESSION[CSRF_KEY]) && is_string($token) && hash_equals($_SESSION[CSRF_KEY], $token);
}
