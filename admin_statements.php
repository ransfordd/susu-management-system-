<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/StatementController.php';

use Controllers\StatementController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

if (!Auth\isAuthenticated() || ($_SESSION['user']['role'] ?? '') !== 'business_admin') {
    header('Location: /login.php');
    exit;
}

$controller = new StatementController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'generate':
        $controller->generate();
        break;
    case 'bulk_generate':
        $controller->bulkGenerate();
        break;
    case 'bulk_view':
        $controller->bulkView();
        break;
    default:
        $controller->index();
        break;
}

