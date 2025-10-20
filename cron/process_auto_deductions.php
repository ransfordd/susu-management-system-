<?php
/**
 * Cron job to process auto-deductions for expired loans
 * Run this daily to check for approved loan deductions
 * Created: 2024-12-19
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/SavingsController.php';

// Set timezone
date_default_timezone_set('Africa/Accra');

// Log start
echo "[" . date('Y-m-d H:i:s') . "] Starting auto-deduction processing...\n";

try {
    $savingsController = new SavingsController();
    
    // Check for expired loans and create notifications
    echo "[" . date('Y-m-d H:i:s') . "] Checking for expired loans...\n";
    $expiredResult = $savingsController->checkExpiredLoans();
    
    if ($expiredResult['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] Created {$expiredResult['notifications_created']} loan deduction notifications\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Error checking expired loans: {$expiredResult['error']}\n";
    }
    
    // Process auto-deductions for approved notifications
    echo "[" . date('Y-m-d H:i:s') . "] Processing auto-deductions...\n";
    $deductionResult = $savingsController->processAutoDeductions();
    
    if ($deductionResult['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] Processed {$deductionResult['processed']} auto-deductions\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Error processing auto-deductions: {$deductionResult['error']}\n";
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Auto-deduction processing completed successfully\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Fatal error: " . $e->getMessage() . "\n";
    error_log("Auto-deduction cron error: " . $e->getMessage());
}
