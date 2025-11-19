<?php
// dev/seed-admin.php
$pdo = require __DIR__.'/../app/db.php';

$name  = 'Site Admin';
$email = 'admin@greenshop.local';
$pass  = 'Admin@123'; // change after first login
$hash  = password_hash($pass, PASSWORD_DEFAULT);
$role  = 'Admin';

$stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?,?,?,?)');
$stmt->execute([$name, $email, $hash, $role]);

echo "<h3>✅ Admin account created!</h3>";
echo "<p><b>Email:</b> $email</p>";
echo "<p><b>Password:</b> $pass</p>";
