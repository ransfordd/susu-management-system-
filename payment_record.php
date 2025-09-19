<?php
require_once __DIR__ . '/controllers/PaymentController.php';

use Controllers\PaymentController;

$controller = new PaymentController();
$controller->record();
?>