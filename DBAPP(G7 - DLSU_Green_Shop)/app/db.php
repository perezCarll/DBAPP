<?php
// app/db.php

$host   = '127.0.0.1';       // prefer 127.0.0.1 over 'localhost' to force TCP
$port   = 3306;
$db     = 'merch_shop';
$user   = 'root';       // <-- use the new app user
$pass   = 'Root123!';  // <-- set the password you created
$socket = '';                // e.g. '/tmp/mysql.sock' or '/var/run/mysqld/mysqld.sock' if needed

try {
  if ($socket) {
    // Use UNIX socket if your MySQL is socket-only
    $dsn = "mysql:unix_socket={$socket};dbname={$db};charset=utf8mb4";
  } else {
    // Standard TCP
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
  }

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  return $pdo;
} catch (PDOException $e) {
  // Minimal safe output for prod; verbose message helps during dev
  http_response_code(500);
  echo "Database connection failed.";
  echo "<pre style='white-space:pre-wrap;color:#555;'>".$e->getMessage()."</pre>";
  exit;
}
