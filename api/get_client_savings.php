<?php
/**
 * API endpoint to get client savings information
 * Created: 2024-12-19
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/SavingsAccount.php';

header('Content-Type: application/json');

try {
    $clientId = (int)($_GET['client_id'] ?? 0);
    
    if ($clientId <= 0) {
        throw new Exception('Invalid client ID');
    }
    
    $savingsAccount = new SavingsAccount(Database::getConnection());
    $result = $savingsAccount->getSavingsDetails($clientId);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'balance' => $result['account']['balance'],
            'transaction_count' => count($result['transactions']),
            'account_id' => $result['account']['id']
        ]);
    } else {
        throw new Exception($result['error']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
