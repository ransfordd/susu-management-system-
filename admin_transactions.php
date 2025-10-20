<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/TransactionController.php';

use Controllers\TransactionController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

$userRole = $_SESSION['user']['role'] ?? '';
if (!Auth\isAuthenticated() || !in_array($userRole, ['business_admin', 'manager'])) {
    header('Location: /login.php');
    exit;
}

$controller = new TransactionController();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'edit':
        $controller->edit($_GET['id'] ?? 0);
        break;
    case 'update':
        $controller->update($_GET['id'] ?? 0);
        break;
    case 'delete':
        $controller->delete($_GET['id'] ?? 0);
        break;
    default:
        $controller->index();
        break;
}
?>
