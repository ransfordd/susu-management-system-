<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/RevenueController.php';

use Controllers\RevenueController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

if (!Auth\isAuthenticated() || ($_SESSION['user']['role'] ?? '') !== 'business_admin') {
    header('Location: /login.php');
    exit;
}

$controller = new RevenueController();
$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'dashboard':
    default:
        $controller->dashboard();
        break;
}
?>

