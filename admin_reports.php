<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AdminReportController.php';

use Controllers\AdminReportController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

$userRole = $_SESSION['user']['role'] ?? '';
if (!Auth\isAuthenticated() || !in_array($userRole, ['business_admin', 'manager'])) {
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