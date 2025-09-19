<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/controllers/ManualTransactionController.php';

use function Auth\startSessionIfNeeded;
use function Auth\requireRole;
use Controllers\ManualTransactionController;

startSessionIfNeeded();
requireRole(['business_admin']);

$controller = new ManualTransactionController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'create':
        $controller->create();
        break;
    case 'delete':
        $controller->delete();
        break;
    default:
        $controller->index();
        break;
}
?>