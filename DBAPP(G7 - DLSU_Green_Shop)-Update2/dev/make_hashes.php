<?php
$pwds = [
  'admin123',
  'manager123',
  'staff123',
  'customer123',
];

foreach ($pwds as $p) {
  echo $p . ' => ' . password_hash($p, PASSWORD_DEFAULT) . "<br>\n";
}
