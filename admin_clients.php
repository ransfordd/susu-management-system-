<?php
require_once __DIR__ . '/controllers/ClientManagementController.php';

use Controllers\ClientManagementController;

$controller = new ClientManagementController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'create':
        $controller->create();
        break;
    case 'edit':
        $controller->edit();
        break;
    case 'update':
        $controller->update();
        break;
    case 'toggle':
        $controller->toggleStatus();
        break;
    case 'delete':
        $controller->delete();
        break;
    case 'impersonate':
        $controller->impersonate();
        break;
    default:
        $controller->index();
        break;
}
?>
