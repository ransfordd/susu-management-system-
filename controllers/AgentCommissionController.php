<?php

namespace Controllers;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

use function Auth\startSessionIfNeeded;
use function Auth\isAuthenticated;
use function Auth\requireRole;
use function Auth\csrfToken;
use function Auth\verifyCsrf;

class AgentCommissionController {
    
    public function index(): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        // Get filter parameters
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-d');
        $agentId = $_GET['agent_id'] ?? '';
        
        // Validate dates
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            $fromDate = date('Y-m-01');
            $toDate = date('Y-m-d');
        }
        
        // Get agent commission data
        $agentCommissions = $this->getAgentCommissionData($pdo, $fromDate, $toDate, $agentId);
        
        // Get all agents for filter dropdown
        $agents = $pdo->query("
            SELECT a.id, a.agent_code, CONCAT(u.first_name, ' ', u.last_name) as agent_name
            FROM agents a
            JOIN users u ON a.user_id = u.id
            WHERE a.status = 'active'
            ORDER BY u.first_name, u.last_name
        ")->fetchAll();
        
        // Get summary statistics
        $summaryStats = $this->getCommissionSummary($pdo, $fromDate, $toDate);
        
        include __DIR__ . '/../views/admin/agent_commission.php';
    }
    
    private function getAgentCommissionData($pdo, $fromDate, $toDate, $agentId) {
        $whereClause = "WHERE dc.collection_date BETWEEN '$fromDate' AND '$toDate'";
        if ($agentId) {
            $whereClause .= " AND a.id = $agentId";
        }
        
        return $pdo->query("
            SELECT a.id, a.agent_code, CONCAT(u.first_name, ' ', u.last_name) as agent_name,
                   a.commission_rate,
                   COUNT(DISTINCT dc.id) as collection_count,
                   COALESCE(SUM(dc.collected_amount), 0) as total_collections,
                   COALESCE(SUM(dc.collected_amount) * (a.commission_rate / 100), 0) as calculated_commission,
                   COUNT(DISTINCT c.id) as client_count,
                   COUNT(DISTINCT sc.id) as susu_cycles_managed,
                   AVG(dc.collected_amount) as avg_collection_amount
            FROM agents a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN clients c ON a.id = c.agent_id
            LEFT JOIN susu_cycles sc ON c.id = sc.client_id AND sc.status = 'active'
            LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id
            $whereClause
            GROUP BY a.id
            ORDER BY calculated_commission DESC
        ")->fetchAll();
    }
    
    private function getCommissionSummary($pdo, $fromDate, $toDate) {
        $stats = $pdo->query("
            SELECT 
                COUNT(DISTINCT a.id) as total_agents,
                COUNT(DISTINCT dc.id) as total_collections,
                COALESCE(SUM(dc.collected_amount), 0) as total_collections_amount,
                COALESCE(SUM(dc.collected_amount * (a.commission_rate / 100)), 0) as total_commissions_payable,
                AVG(a.commission_rate) as avg_commission_rate
            FROM agents a
            LEFT JOIN clients c ON a.id = c.agent_id
            LEFT JOIN susu_cycles sc ON c.id = sc.client_id AND sc.status = 'active'
            LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id 
                AND dc.collection_date BETWEEN '$fromDate' AND '$toDate'
        ")->fetch();
        
        return $stats;
    }
    
    public function process(): void {
        startSessionIfNeeded();
        
        if (!isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
        
        requireRole(['business_admin']);
        
        $pdo = \Database::getConnection();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCommissionProcessing();
            return;
        }
        
        // Get pending commissions
        $pendingCommissions = $pdo->query("
            SELECT a.id, a.agent_code, CONCAT(u.first_name, ' ', u.last_name) as agent_name,
                   a.commission_rate,
                   COALESCE(SUM(dc.collected_amount), 0) as total_collections,
                   COALESCE(SUM(dc.collected_amount) * (a.commission_rate / 100), 0) as calculated_commission,
                   COUNT(DISTINCT dc.id) as collection_count
            FROM agents a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN clients c ON a.id = c.agent_id
            LEFT JOIN susu_cycles sc ON c.id = sc.client_id AND sc.status = 'active'
            LEFT JOIN daily_collections dc ON sc.id = dc.susu_cycle_id 
                AND dc.collection_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY a.id
            HAVING calculated_commission > 0
            ORDER BY calculated_commission DESC
        ")->fetchAll();
        
        include __DIR__ . '/../views/admin/agent_commission_process.php';
    }
    
    private function handleCommissionProcessing(): void {
        $pdo = \Database::getConnection();
        
        // Verify CSRF token
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verifyCsrf($csrf)) {
            $_SESSION['error'] = 'Invalid CSRF token';
            header('Location: /admin_agent_commissions.php?action=process');
            exit;
        }
        
        $agentIds = $_POST['agent_ids'] ?? [];
        $commissionAmounts = $_POST['commission_amounts'] ?? [];
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($agentIds)) {
            $_SESSION['error'] = 'No agents selected for commission processing';
            header('Location: /admin_agent_commissions.php?action=process');
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            foreach ($agentIds as $index => $agentId) {
                $commissionAmount = (float)($commissionAmounts[$index] ?? 0);
                
                if ($commissionAmount > 0) {
                    // Create commission payment record
                    $stmt = $pdo->prepare('
                        INSERT INTO agent_commission_payments (
                            agent_id, amount, payment_method, notes, processed_by, created_at
                        ) VALUES (?, ?, ?, ?, ?, NOW())
                    ');
                    $stmt->execute([
                        $agentId, $commissionAmount, $paymentMethod, $notes, $_SESSION['user']['id']
                    ]);
                    
                    // Create manual transaction record for the commission payment
                    $stmt = $pdo->prepare('
                        INSERT INTO manual_transactions (
                            client_id, transaction_type, amount, description, reference,
                            processed_by, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ');
                    
                    // Get agent's primary client for the transaction record
                    $primaryClient = $pdo->query("
                        SELECT c.id FROM clients c 
                        WHERE c.agent_id = $agentId 
                        ORDER BY c.created_at ASC 
                        LIMIT 1
                    ")->fetch();
                    
                    $clientId = $primaryClient ? $primaryClient['id'] : null;
                    $reference = 'COM' . date('Ymd') . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                    $description = "Agent commission payment - " . ($notes ?: 'Monthly commission');
                    
                    if ($clientId) {
                        $stmt->execute([
                            $clientId, 'withdrawal', $commissionAmount, $description, $reference, $_SESSION['user']['id']
                        ]);
                    }
                }
            }
            
            $pdo->commit();
            $_SESSION['success'] = 'Commissions processed successfully for ' . count($agentIds) . ' agents';
            header('Location: /admin_agent_commissions.php');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed to process commissions: ' . $e->getMessage();
            header('Location: /admin_agent_commissions.php?action=process');
            exit;
        }
    }
}



