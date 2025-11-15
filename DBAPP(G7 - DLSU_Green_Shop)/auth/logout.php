<?php
require_once __DIR__.'/../app/session.php';
auth_logout();
header('Location: /index.php');
exit;
