<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/controllers/AdminPaymentController.php';

use function Auth\startSessionIfNeeded;
use Controllers\AdminPaymentController;

startSessionIfNeeded();

$controller = new AdminPaymentController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'record':
        $controller->record();
        break;
    case 'index':
    default:
        $controller->index();
        break;
}
?>
