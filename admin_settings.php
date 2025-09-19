<?php
require_once __DIR__ . '/controllers/SystemSettingsController.php';

use Controllers\SystemSettingsController;

$controller = new SystemSettingsController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'update':
        $controller->updateSettings();
        break;
    case 'add_holiday':
        $controller->addHoliday();
        break;
    case 'delete_holiday':
        $controller->deleteHoliday();
        break;
    case 'send_notification':
        $controller->sendNotification();
        break;
    default:
        $controller->index();
        break;
}
?>




