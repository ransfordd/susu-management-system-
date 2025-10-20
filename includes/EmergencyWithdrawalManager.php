<?php

class EmergencyWithdrawalManager {
    private $pdo;
    
    public function __construct($pdo = null) {
        if ($pdo === null) {
            require_once __DIR__ . '/../config/database.php';
            $this->pdo = \Database::getConnection();
        } else {
            $this->pdo = $pdo;
        }
    }
    
    /**
     * Check if client is eligible for emergency withdrawal
     */
    public function checkEligibility($clientId, $cycleId) {
        // Check if cycle is active
        $cycleStmt = $this->pdo->prepare('
            SELECT sc.*, COUNT(dc.id) as days_collected
            FROM susu_cycles sc
            LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id 
                AND dc.collection_status = "collected"
            WHERE sc.id = ? AND sc.client_id = ? AND sc.status = "active"
            GROUP BY sc.id
        ');
        $cycleStmt->execute([$cycleId, $clientId]);
        $cycle = $cycleStmt->fetch();
        
        if (!$cycle) {
            return ['eligible' => false, 'reason' => 'Cycle not found or not active'];
        }
        
        // Check minimum days requirement
        if ($cycle['days_collected'] < 3) {
            return ['eligible' => false, 'reason' => 'Must have paid at least 3 days'];
        }
        
        // Check if already requested in this cycle
        $existingStmt = $this->pdo->prepare('
            SELECT id FROM emergency_withdrawal_requests 
            WHERE client_id = ? AND susu_cycle_id = ? AND status != "rejected"
        ');
        $existingStmt->execute([$clientId, $cycleId]);
        if ($existingStmt->fetch()) {
            return ['eligible' => false, 'reason' => 'Emergency withdrawal already requested for this cycle'];
        }
        
        // Calculate available amount
        $totalCollected = $cycle['days_collected'] * $cycle['daily_amount'];
        $commission = $cycle['daily_amount']; // 1 day commission
        $availableAmount = $totalCollected - $commission;
        
        return [
            'eligible' => true,
            'cycle' => $cycle,
            'available_amount' => $availableAmount,
            'days_collected' => $cycle['days_collected'],
            'commission' => $commission
        ];
    }
    
    /**
     * Create emergency withdrawal request
     */
    public function createRequest($clientId, $cycleId, $requestedAmount, $requestedBy) {
        $eligibility = $this->checkEligibility($clientId, $cycleId);
        
        if (!$eligibility['eligible']) {
            return ['success' => false, 'error' => $eligibility['reason']];
        }
        
        if ($requestedAmount > $eligibility['available_amount']) {
            return ['success' => false, 'error' => 'Requested amount exceeds available amount'];
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Create request
            $stmt = $this->pdo->prepare('
                INSERT INTO emergency_withdrawal_requests 
                (client_id, susu_cycle_id, requested_amount, available_amount, 
                 days_collected, commission_amount, requested_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ');
            $stmt->execute([
                $clientId, $cycleId, $requestedAmount, $eligibility['available_amount'],
                $eligibility['days_collected'], $eligibility['commission'], $requestedBy
            ]);
            
            $requestId = $this->pdo->lastInsertId();
            
            // Send notifications to managers/admins
            $this->sendRequestNotifications($requestId, $clientId);
            
            $this->pdo->commit();
            return ['success' => true, 'request_id' => $requestId];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Approve emergency withdrawal request
     */
    public function approveRequest($requestId, $approvedBy) {
        try {
            $this->pdo->beginTransaction();
            
            // Get request details
            $stmt = $this->pdo->prepare('
                SELECT ewr.*, u.first_name, u.last_name, sc.daily_amount
                FROM emergency_withdrawal_requests ewr
                JOIN clients c ON ewr.client_id = c.id
                JOIN users u ON c.user_id = u.id
                JOIN susu_cycles sc ON ewr.susu_cycle_id = sc.id
                WHERE ewr.id = ? AND ewr.status = "pending"
            ');
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();
            
            if (!$request) {
                return ['success' => false, 'error' => 'Request not found or already processed'];
            }
            
            // Update request status
            $updateStmt = $this->pdo->prepare('
                UPDATE emergency_withdrawal_requests 
                SET status = "approved", approved_by = ?, approved_at = NOW()
                WHERE id = ?
            ');
            $updateStmt->execute([$approvedBy, $requestId]);
            
            // Create withdrawal transaction
            $netAmount = $request['requested_amount'] - $request['commission_amount'];
            $reference = 'EWR-' . str_pad($requestId, 6, '0', STR_PAD_LEFT);
            
            $transactionStmt = $this->pdo->prepare('
                INSERT INTO emergency_withdrawal_transactions
                (request_id, client_id, amount, commission_deducted, net_amount, reference, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ');
            $transactionStmt->execute([
                $requestId, $request['client_id'], $request['requested_amount'],
                $request['commission_amount'], $netAmount, $reference
            ]);
            
            // Create manual transaction record
            $manualStmt = $this->pdo->prepare('
                INSERT INTO manual_transactions
                (client_id, transaction_type, amount, description, reference, created_at)
                VALUES (?, "emergency_withdrawal", ?, ?, ?, NOW())
            ');
            $manualStmt->execute([
                $request['client_id'], 
                $netAmount, 
                "Emergency withdrawal from cycle - Commission: GHS " . number_format($request['commission_amount'], 2),
                $reference
            ]);
            
            // Send completion notifications
            $this->sendCompletionNotifications($requestId, $request);
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Emergency withdrawal approved and processed'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Reject emergency withdrawal request
     */
    public function rejectRequest($requestId, $rejectedBy, $reason) {
        try {
            $this->pdo->beginTransaction();
            
            // Update request status
            $updateStmt = $this->pdo->prepare('
                UPDATE emergency_withdrawal_requests 
                SET status = "rejected", approved_by = ?, rejection_reason = ?, approved_at = NOW()
                WHERE id = ? AND status = "pending"
            ');
            $updateStmt->execute([$rejectedBy, $reason, $requestId]);
            
            if ($updateStmt->rowCount() == 0) {
                return ['success' => false, 'error' => 'Request not found or already processed'];
            }
            
            // Send rejection notification
            $this->sendRejectionNotifications($requestId, $reason);
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Emergency withdrawal request rejected'];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get pending requests for managers/admins
     */
    public function getPendingRequests() {
        $stmt = $this->pdo->prepare('
            SELECT ewr.*, u.first_name, u.last_name, u.phone,
                   sc.daily_amount, sc.start_date, sc.end_date,
                   ru.first_name as requested_by_name, ru.last_name as requested_by_surname
            FROM emergency_withdrawal_requests ewr
            JOIN clients c ON ewr.client_id = c.id
            JOIN users u ON c.user_id = u.id
            JOIN susu_cycles sc ON ewr.susu_cycle_id = sc.id
            JOIN users ru ON ewr.requested_by = ru.id
            WHERE ewr.status = "pending"
            ORDER BY ewr.created_at DESC
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get client's emergency withdrawal history
     */
    public function getClientHistory($clientId) {
        $stmt = $this->pdo->prepare('
            SELECT ewr.*, sc.daily_amount, sc.start_date, sc.end_date,
                   u.first_name as approved_by_name, u.last_name as approved_by_surname
            FROM emergency_withdrawal_requests ewr
            JOIN susu_cycles sc ON ewr.susu_cycle_id = sc.id
            LEFT JOIN users u ON ewr.approved_by = u.id
            WHERE ewr.client_id = ?
            ORDER BY ewr.created_at DESC
        ');
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Send request notifications to managers/admins
     */
    private function sendRequestNotifications($requestId, $clientId) {
        // Get client details
        $clientStmt = $this->pdo->prepare('
            SELECT u.first_name, u.last_name, u.id as user_id
            FROM clients c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ');
        $clientStmt->execute([$clientId]);
        $client = $clientStmt->fetch();
        
        // Notify client
        $clientNotifStmt = $this->pdo->prepare('
            INSERT INTO notifications (user_id, notification_type, title, message, created_at)
            VALUES (?, "emergency_withdrawal_requested", "Emergency Withdrawal Requested", ?, NOW())
        ');
        $clientNotifStmt->execute([
            $client['user_id'],
            "Your emergency withdrawal request has been submitted and is pending approval."
        ]);
        
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
                VALUES (?, "emergency_withdrawal_pending", "Emergency Withdrawal Pending", ?, NOW())
            ');
            $adminNotifStmt->execute([
                $admin['id'],
                "Emergency withdrawal request from {$client['first_name']} {$client['last_name']} requires approval."
            ]);
        }
    }
    
    /**
     * Send completion notifications
     */
    private function sendCompletionNotifications($requestId, $request) {
        // Get client user ID
        $clientUserStmt = $this->pdo->prepare('SELECT user_id FROM clients WHERE id = ?');
        $clientUserStmt->execute([$request['client_id']]);
        $clientUser = $clientUserStmt->fetch();
        
        if ($clientUser) {
            // Notify client
            $clientNotifStmt = $this->pdo->prepare('
                INSERT INTO notifications (user_id, notification_type, title, message, created_at)
                VALUES (?, "emergency_withdrawal_approved", "Emergency Withdrawal Approved", ?, NOW())
            ');
            $clientNotifStmt->execute([
                $clientUser['user_id'],
                "Your emergency withdrawal of GHS " . number_format($request['requested_amount'], 2) . " has been approved and processed."
            ]);
        }
    }
    
    /**
     * Send rejection notifications
     */
    private function sendRejectionNotifications($requestId, $reason) {
        // Get request details
        $stmt = $this->pdo->prepare('
            SELECT ewr.client_id, u.first_name, u.last_name, u.id as user_id
            FROM emergency_withdrawal_requests ewr
            JOIN clients c ON ewr.client_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE ewr.id = ?
        ');
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if ($request) {
            // Notify client
            $clientNotifStmt = $this->pdo->prepare('
                INSERT INTO notifications (user_id, notification_type, title, message, created_at)
                VALUES (?, "emergency_withdrawal_rejected", "Emergency Withdrawal Rejected", ?, NOW())
            ');
            $clientNotifStmt->execute([
                $request['user_id'],
                "Your emergency withdrawal request has been rejected. Reason: " . $reason
            ]);
        }
    }
}
