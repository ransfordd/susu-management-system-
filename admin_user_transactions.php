<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/UserTransactionController.php';

use Controllers\UserTransactionController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

$userRole = $_SESSION['user']['role'] ?? '';
if (!Auth\isAuthenticated() || !in_array($userRole, ['business_admin', 'manager'])) {
    header('Location: /login.php');
    exit;
}

$controller = new UserTransactionController();
$action = $_GET['action'] ?? 'history';

switch ($action) {
    case 'print':
        $controller->printTransaction();
        break;
    default:
        $controller->history();
        break;
}
?>