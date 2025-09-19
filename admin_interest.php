<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/InterestController.php';

use Controllers\InterestController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

if (!Auth\isAuthenticated() || ($_SESSION['user']['role'] ?? '') !== 'business_admin') {
    header('Location: /login.php');
    exit;
}

$controller = new InterestController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'calculate':
        $controller->calculate();
        break;
    case 'bulk_calculate':
        $controller->bulkCalculate();
        break;
    case 'update_rates':
        $controller->updateRates();
        break;
    default:
        $controller->index();
        break;
}
?>



