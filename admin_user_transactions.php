<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/UserTransactionController.php';

use Controllers\UserTransactionController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

if (!Auth\isAuthenticated() || ($_SESSION['user']['role'] ?? '') !== 'business_admin') {
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