<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager', 'agent', 'client']);

require_once __DIR__ . '/controllers/NotificationController.php';

use Controllers\NotificationController;

$controller = new NotificationController();
$action = $_GET['action'] ?? 'get_recent';

switch ($action) {
    case 'get_recent':
        $controller->list();
        break;
    case 'mark_all_read':
        $controller->markAllAsRead();
        break;
    default:
        $controller->list();
}