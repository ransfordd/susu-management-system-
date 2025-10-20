<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/NotificationController.php';

use function Auth\requireRole;
use Controllers\NotificationController;

class WithdrawalController {
    public function index(): void {
        requireRole(['business_admin', 'manager']);
        
        // Get clients for dropdown
        $pdo = \Database::getConnection();
        $clients = $pdo->query("
            SELECT c.id, c.client_code, 
                   CONCAT(u.first_name, ' ', u.last_name) as client_name,
                   u.email, u.phone
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.status = 'active'
            ORDER BY u.first_name, u.last_name
        ")->fetchAll();

        // Get recent withdrawals
        $recentWithdrawals = $pdo->query("
            SELECT mt.*, c.client_code,
                   CONCAT(u.first_name, ' ', u.last_name) as client_name
            FROM manual_transactions mt
            JOIN clients c ON mt.client_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE mt.transaction_type = 'withdrawal'
            ORDER BY mt.created_at DESC
            LIMIT 10
        ")->fetchAll();

        include __DIR__ . '/../views/admin/withdrawal.php';
    }

    public function process(): void {
        requireRole(['business_admin', 'manager']);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin_withdrawal.php');
            exit;
        }

        $clientId = $_POST['client_id'];
        $withdrawalType = $_POST['withdrawal_type'];
        $amount = floatval($_POST['amount']);
        $description = $_POST['description'];
        $reference = $_POST['reference'] ?: 'WTH-' . date('Ymd') . '-' . rand(1000, 9999);
        
        try {
            $pdo = \Database::getConnection();
            $pdo->beginTransaction();
            
            // Insert withdrawal record
            $stmt = $pdo->prepare("
                INSERT INTO manual_transactions 
                (client_id, transaction_type, amount, reference, description, processed_by, created_at)
                VALUES (?, 'withdrawal', ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$clientId, $amount, $reference, $description, $_SESSION['user']['id']]);
            
            // Get client and agent information for notifications
            $clientStmt = $pdo->prepare("
                SELECT c.user_id, u.first_name, u.last_name, c.agent_id, a.user_id as agent_user_id
                FROM clients c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN agents a ON c.agent_id = a.id
                WHERE c.id = ?
            ");
            $clientStmt->execute([$clientId]);
            $clientInfo = $clientStmt->fetch();
            
            if ($clientInfo) {
                // Notify client
                NotificationController::createNotification(
                    $clientInfo['user_id'],
                    'withdrawal_processed',
                    'Withdrawal Processed',
                    "Your withdrawal of GHS " . number_format($amount, 2) . " has been processed successfully. Reference: " . $reference,
                    null,
                    'withdrawal'
                );
                
                // Notify agent if assigned
                if ($clientInfo['agent_user_id']) {
                    NotificationController::createNotification(
                        $clientInfo['agent_user_id'],
                        'client_withdrawal',
                        'Client Withdrawal Processed',
                        "Withdrawal of GHS " . number_format($amount, 2) . " has been processed for client " . $clientInfo['first_name'] . " " . $clientInfo['last_name'] . ". Reference: " . $reference,
                        $clientId,
                        'client'
                    );
                }
            }
            
            $pdo->commit();
            $_SESSION['success'] = 'Withdrawal processed successfully. Reference: ' . $reference;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error processing withdrawal: ' . $e->getMessage();
        }
        
        header('Location: /admin_withdrawal.php');
        exit;
    }
}
?>
