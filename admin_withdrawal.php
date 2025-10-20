<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/controllers/WithdrawalController.php';

use function Auth\startSessionIfNeeded;
use Controllers\WithdrawalController;

startSessionIfNeeded();

$controller = new WithdrawalController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'process':
        $controller->process();
        break;
    case 'index':
    default:
        $controller->index();
        break;
}
?>
