<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/runtime.php';
require_once __DIR__ . '/includes/maintenance_gate.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';

use Controllers\AuthController;
use Controllers\DashboardController;
use function Auth\startSessionIfNeeded;

startSessionIfNeeded();
applyRuntimeSettings();
enforceMaintenanceIfEnabled();

$action = $_GET['action'] ?? '';

if ($action === 'logout') {
	(new AuthController())->logout();
	exit;
}

if ($action === 'home') {
	include __DIR__ . '/homepage.php';
	exit;
}

if ($action === 'dashboard') {
	if (Auth\isAuthenticated()) {
		(new DashboardController())->index();
		exit;
	} else {
		header('Location: /login.php');
		exit;
	}
}

// If user is authenticated, redirect to dashboard
if (Auth\isAuthenticated()) {
	header('Location: /index.php?action=dashboard');
	exit;
}

// Show homepage for non-authenticated users
include __DIR__ . '/homepage.php';
?>




