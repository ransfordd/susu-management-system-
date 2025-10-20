<?php
/**
 * Cron job to process automatic payout transfers
 * Run this daily to transfer completed cycle payouts to savings accounts
 * Created: 2024-12-19
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/SavingsAccount.php';
require_once __DIR__ . '/../includes/PayoutTransferManager.php';

// Set timezone
date_default_timezone_set('Africa/Accra');

// Log start
echo "[" . date('Y-m-d H:i:s') . "] Starting payout transfer processing...\n";

try {
    $payoutManager = new PayoutTransferManager(Database::getConnection());
    
    // Process automatic transfers
    echo "[" . date('Y-m-d H:i:s') . "] Processing automatic transfers...\n";
    $result = $payoutManager->processAutomaticTransfers();
    
    if ($result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] " . $result['message'] . "\n";
        echo "[" . date('Y-m-d H:i:s') . "] Transferred {$result['transferred']} payouts to savings accounts\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Error processing transfers: {$result['error']}\n";
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Payout transfer processing completed successfully\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Fatal error: " . $e->getMessage() . "\n";
    error_log("Payout transfer cron error: " . $e->getMessage());
}
