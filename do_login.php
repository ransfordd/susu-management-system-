<?php
require_once __DIR__ . '/includes/runtime.php';
require_once __DIR__ . '/includes/maintenance_gate.php';
require_once __DIR__ . '/controllers/AuthController.php';

use Controllers\AuthController;

// Apply runtime/debug flags and enforce maintenance gate here as well.
applyRuntimeSettings();
enforceMaintenanceIfEnabled();

(new AuthController())->login();

