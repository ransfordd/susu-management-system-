<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/SecurityController.php';

use Controllers\SecurityController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

if (!Auth\isAuthenticated() || ($_SESSION['user']['role'] ?? '') !== 'business_admin') {
    header('Location: /login.php');
    exit;
}

$controller = new SecurityController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'update_settings':
        $controller->updateSettings();
        break;
    case 'unlock_account':
        $controller->unlockAccount();
        break;
    case 'reset_password':
        $controller->resetPassword();
        break;
    case 'generate_report':
        $controller->generateReport();
        break;
    default:
        $controller->index();
        break;
}
?>



