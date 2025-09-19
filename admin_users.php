<?php
require_once __DIR__ . '/controllers/UserManagementController.php';

use Controllers\UserManagementController;

$controller = new UserManagementController();
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
    default:
        $controller->index();
        break;
}
?>




