<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AgentReportController.php';

use Controllers\AgentReportController;
use function Auth\startSessionIfNeeded;

try {

    startSessionIfNeeded();

    if (!Auth\isAuthenticated() || ($_SESSION['user']['role'] ?? '') !== 'business_admin') {
        header('Location: /login.php');
        exit;
    }

    $controller = new AgentReportController();
    $action = $_GET['action'] ?? 'consolidated';

    switch ($action) {
        case 'individual':
            $controller->individualReport($_GET['agent_id'] ?? 0);
            break;
        case 'daily':
            $controller->dailyReport();
            break;
        default:
            $controller->consolidatedReport();
            break;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}
?>