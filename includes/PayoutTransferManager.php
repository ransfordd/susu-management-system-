<?php
/**
 * PayoutTransferManager
 * Handles automatic transfer of completed cycle payouts to savings accounts
 * Created: 2024-12-19
 */

require_once __DIR__ . '/SavingsAccount.php';

class PayoutTransferManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Process automatic transfers for completed cycles
     * Should be run daily via cron job
     */
    public function processAutomaticTransfers() {
        $this->pdo->beginTransaction();
        
        try {
            // Get completed cycles that need transfer (completed yesterday or earlier)
            $cyclesStmt = $this->pdo->prepare('
                SELECT sc.*, c.id as client_id, c.user_id,
                       CONCAT(u.first_name, " ", u.last_name) as client_name
                FROM susu_cycles sc
                JOIN clients c ON sc.client_id = c.id
                JOIN users u ON c.user_id = u.id
                WHERE sc.status = "completed" 
                AND sc.payout_amount > 0
                AND sc.completion_date < CURDATE()
                AND NOT EXISTS (
                    SELECT 1 FROM savings_transactions st
                    JOIN savings_accounts sa ON st.savings_account_id = sa.id
                    WHERE sa.client_id = sc.client_id
                    AND st.source = "cycle_completion"
                    AND st.reference_transaction_id = sc.id
                )
            ');
            $cyclesStmt->execute();
            $cycles = $cyclesStmt->fetchAll();
            
            $transferred = 0;
            $notifications = [];
            
            foreach ($cycles as $cycle) {
                // Transfer payout to savings
                $result = $this->transferPayoutToSavings($cycle);
                
                if ($result['success']) {
                    $transferred++;
                    $notifications[] = [
                        'client_id' => $cycle['client_id'],
                        'message' => "Cycle payout of GHS " . number_format($cycle['payout_amount'], 2) . " transferred to savings account",
                        'type' => 'payout_transferred'
                    ];
                }
            }
            
            // Send notifications
            $this->sendNotifications($notifications);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'transferred' => $transferred,
                'message' => "Transferred {$transferred} cycle payouts to savings accounts"
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Transfer individual cycle payout to savings
     */
    private function transferPayoutToSavings($cycle) {
        try {
            // Get or create savings account
            $savingsAccount = new SavingsAccount($this->pdo);
            $account = $savingsAccount->getOrCreateAccount($cycle['client_id']);
            
            // Add payout to savings
            $result = $savingsAccount->deposit(
                $cycle['client_id'],
                $cycle['payout_amount'],
                'cycle_completion',
                'savings_deposit',
                "Automatic transfer from completed cycle - " . date('F Y', strtotime($cycle['completion_date'])),
                $cycle['id'], // Reference to cycle
                'susu_cycles'
            );
            
            if ($result['success']) {
                // Update cycle status to indicate payout transferred
                $updateStmt = $this->pdo->prepare('
                    UPDATE susu_cycles 
                    SET payout_transferred = 1, payout_transferred_at = NOW()
                    WHERE id = ?
                ');
                $updateStmt->execute([$cycle['id']]);
            }
            
            return $result;
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send notifications to clients, managers, and admins
     */
    private function sendNotifications($notifications) {
        foreach ($notifications as $notification) {
            // Get client user ID
            $clientUserStmt = $this->pdo->prepare('SELECT user_id FROM clients WHERE id = ?');
            $clientUserStmt->execute([$notification['client_id']]);
            $clientUser = $clientUserStmt->fetch();
            
            if ($clientUser) {
            // Notify client
            $notifStmt = $this->pdo->prepare('
                INSERT INTO notifications (user_id, notification_type, title, message, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ');
            $notifStmt->execute([
                $clientUser['user_id'],
                'payout_transferred',
                'Payout Transferred to Savings',
                $notification['message']
            ]);
            }
            
            // Notify all managers and admins
            $adminStmt = $this->pdo->prepare('
                SELECT u.id FROM users u
                WHERE u.role IN ("business_admin", "manager")
            ');
            $adminStmt->execute();
            $admins = $adminStmt->fetchAll();
            
            foreach ($admins as $admin) {
                $adminNotifStmt = $this->pdo->prepare('
                    INSERT INTO notifications (user_id, notification_type, title, message, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ');
                $adminNotifStmt->execute([
                    $admin['id'],
                    'payout_transferred',
                    'Payout Transfer Completed',
                    "Client payout automatically transferred to savings account"
                ]);
            }
        }
    }
    
    /**
     * Get pending payouts for a client
     */
    public function getPendingPayouts($clientId) {
        $stmt = $this->pdo->prepare('
            SELECT sc.*, 
                   DATEDIFF(CURDATE(), sc.completion_date) as days_since_completion
            FROM susu_cycles sc
            WHERE sc.client_id = ? 
            AND sc.status = "completed"
            AND sc.payout_amount > 0
            AND sc.payout_transferred = 0
            ORDER BY sc.completion_date DESC
        ');
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Manually transfer payout to savings (client-initiated)
     */
    public function manualTransferPayout($clientId, $cycleId) {
        try {
            // Get cycle details
            $cycleStmt = $this->pdo->prepare('
                SELECT * FROM susu_cycles 
                WHERE id = ? AND client_id = ? AND status = "completed" 
                AND payout_amount > 0 AND (payout_transferred = 0 OR payout_transferred IS NULL)
            ');
            $cycleStmt->execute([$cycleId, $clientId]);
            $cycle = $cycleStmt->fetch();
            
            if (!$cycle) {
                throw new Exception('Cycle not found or already transferred');
            }
            
            // Transfer to savings (this method handles its own transaction)
            $result = $this->transferPayoutToSavings($cycle);
            
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Send notification
            $this->sendNotifications([[
                'client_id' => $clientId,
                'message' => "You manually transferred cycle payout of GHS " . number_format($cycle['payout_amount'], 2) . " to savings account",
                'type' => 'payout_manual_transfer'
            ]]);
            
            return [
                'success' => true,
                'message' => 'Payout transferred to savings account successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get transfer history for admin panel
     */
    public function getTransferHistory($limit = 50) {
        $stmt = $this->pdo->prepare('
            SELECT st.*, sa.client_id, 
                   CONCAT(u.first_name, " ", u.last_name) as client_name,
                   sc.cycle_number, sc.completion_date
            FROM savings_transactions st
            JOIN savings_accounts sa ON st.savings_account_id = sa.id
            JOIN clients c ON sa.client_id = c.id
            JOIN users u ON c.user_id = u.id
            LEFT JOIN susu_cycles sc ON st.reference_transaction_id = sc.id
            WHERE st.source = "cycle_completion"
            ORDER BY st.created_at DESC
            LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get pending transfers count for admin dashboard
     */
    public function getPendingTransfersCount() {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as count
            FROM susu_cycles sc
            WHERE sc.status = "completed"
            AND sc.payout_amount > 0
            AND sc.payout_transferred = 0
            AND sc.completion_date < CURDATE()
        ');
        $stmt->execute();
        $result = $stmt->fetch();
        return (int)$result['count'];
    }
}
