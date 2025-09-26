<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AgentController.php';

use Controllers\AgentController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

if (!Auth\isAuthenticated() || ($_SESSION['user']['role'] ?? '') !== 'business_admin') {
    header('Location: /login.php');
    exit;
}

$controller = new AgentController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'create':
        $controller->create();
        break;
    case 'store':
        $controller->store();
        break;
    case 'edit':
        $controller->edit($_GET['id'] ?? 0);
        break;
    case 'update':
        $controller->update($_GET['id'] ?? 0);
        break;
    case 'delete':
        $controller->delete($_GET['id'] ?? 0);
        break;
    case 'assign_client':
        $controller->assignClient();
        break;
    case 'remove_client':
        $controller->removeClient();
        break;
    case 'impersonate':
        $controller->impersonate();
        break;
    default:
        $controller->index();
        break;
}
?>