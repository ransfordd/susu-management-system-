<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AdminReportController.php';

use Controllers\AdminReportController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

if (!Auth\isAuthenticated() || ($_SESSION['user']['role'] ?? '') !== 'business_admin') {
    header('Location: /login.php');
    exit;
}

$controller = new AdminReportController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'financial':
        $controller->financialReport();
        break;
    case 'agent_performance':
        $controller->agentPerformanceReport();
        break;
    case 'export':
        $controller->exportReport();
        break;
    default:
        $controller->index();
        break;
}
?>