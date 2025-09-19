<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/TransactionController.php';

use Controllers\TransactionController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

if (!Auth\isAuthenticated() || ($_SESSION['user']['role'] ?? '') !== 'business_admin') {
    header('Location: /login.php');
    exit;
}

$controller = new TransactionController();
$action = $_GET['action'] ?? 'index';

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