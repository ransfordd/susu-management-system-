<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AutoTransferController.php';

use Controllers\AutoTransferController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

if (!Auth\isAuthenticated() || ($_SESSION['user']['role'] ?? '') !== 'business_admin') {
    header('Location: /login.php');
    exit;
}

$controller = new AutoTransferController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'process_susu':
        $results = $controller->processPendingTransfers();
        $_SESSION['transfer_results'] = $results;
        header('Location: /admin_auto_transfers.php');
        exit;
        break;
    case 'process_loans':
        $results = $controller->processLoanAutoDeductions();
        $_SESSION['deduction_results'] = $results;
        header('Location: /admin_auto_transfers.php');
        exit;
        break;
    default:
        include __DIR__ . '/views/admin/auto_transfers_index.php';
        break;
}
?>




