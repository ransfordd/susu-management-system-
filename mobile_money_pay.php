<?php
require_once __DIR__ . '/config/auth.php';

use function Auth\requireRole;

requireRole(['agent','business_admin']);

// Placeholder: simulate async mobile money success
sleep(1);
echo 'Payment initiated (simulation). Reference: ' . htmlspecialchars($_POST['reference'] ?? '', ENT_QUOTES);









