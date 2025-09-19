<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';

use Controllers\AuthController;
use Controllers\DashboardController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();

$action = $_GET['action'] ?? '';

if ($action === 'logout') {
	(new AuthController())->logout();
	exit;
}

if ($action === 'home') {
	include __DIR__ . '/home.php';
	exit;
}

if (Auth\isAuthenticated()) {
	(new DashboardController())->index();
	exit;
}

header('Location: /login.php');
?>




