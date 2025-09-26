<?php

namespace Controllers;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

use function Auth\startSessionIfNeeded;
use function Auth\isAuthenticated;
use function Auth\requireRole;

class RevenueController {
    
    public function dashboard(): void {
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
        $transactionType = $_GET['transaction_type'] ?? 'all';
        
        // Validate dates
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            $fromDate = date('Y-m-01');
            $toDate = date('Y-m-d');
        }
        
        // Get revenue data
        $revenueData = $this->getRevenueData($pdo, $fromDate, $toDate, $transactionType);
        
        // Get monthly revenue trends
        $monthlyTrends = $this->getMonthlyTrends($pdo, $fromDate, $toDate);
        
        // Get agent revenue breakdown
        $agentRevenue = $this->getAgentRevenue($pdo, $fromDate, $toDate);
        
        // Get transaction type breakdown
        $transactionBreakdown = $this->getTransactionBreakdown($pdo, $fromDate, $toDate);
        
        include __DIR__ . '/../views/admin/revenue_dashboard.php';
    }
    
    private function getRevenueData($pdo, $fromDate, $toDate, $transactionType) {
        $whereConditions = [];
        $params = [];
        
        // Add date filter
        $whereConditions[] = "((dc.collection_date BETWEEN ? AND ?) OR (lp.payment_date BETWEEN ? AND ?) OR (mt.created_at BETWEEN ? AND ?))";
        $params = array_merge($params, [$fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate]);
        
        // Add transaction type filter
        if ($transactionType !== 'all') {
            switch ($transactionType) {
                case 'susu_collection':
                    $whereConditions[] = "(dc.collected_amount IS NOT NULL)";
                    break;
                case 'loan_payment':
                    $whereConditions[] = "(lp.amount_paid IS NOT NULL)";
                    break;
                case 'manual_transaction':
                    $whereConditions[] = "(mt.id IS NOT NULL)";
                    break;
            }
        }
        
        $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
        
        // Get Susu collections revenue
        $susuQuery = "
            SELECT 
                COALESCE(SUM(dc.collected_amount), 0) as total_amount,
                COUNT(*) as transaction_count,
                COALESCE(AVG(dc.collected_amount), 0) as avg_amount
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            WHERE dc.collection_status = 'collected'
            AND dc.collection_date BETWEEN ? AND ?
        ";
        
        $susuParams = [$fromDate, $toDate];
        if ($transactionType !== 'all' && $transactionType !== 'susu_collection') {
            $susuQuery .= " AND 1=0";
        }
        
        $stmt = $pdo->prepare($susuQuery);
        $stmt->execute($susuParams);
        $susuRevenue = $stmt->fetch();
        
        // Get loan payments revenue
        $loanQuery = "
            SELECT 
                COALESCE(SUM(lp.amount_paid), 0) as total_amount,
                COUNT(*) as transaction_count,
                COALESCE(AVG(lp.amount_paid), 0) as avg_amount
            FROM loan_payments lp
            WHERE lp.payment_status = 'completed'
            AND lp.payment_date BETWEEN ? AND ?
        ";
        
        $loanParams = [$fromDate, $toDate];
        if ($transactionType !== 'all' && $transactionType !== 'loan_payment') {
            $loanQuery .= " AND 1=0";
        }
        
        $stmt = $pdo->prepare($loanQuery);
        $stmt->execute($loanParams);
        $loanRevenue = $stmt->fetch();
        
        // Get manual transactions revenue
        $manualQuery = "
            SELECT 
                COALESCE(SUM(CASE WHEN mt.transaction_type = 'deposit' THEN mt.amount ELSE 0 END), 0) as deposit_amount,
                COALESCE(SUM(CASE WHEN mt.transaction_type = 'withdrawal' THEN mt.amount ELSE 0 END), 0) as withdrawal_amount,
                COUNT(*) as transaction_count
            FROM manual_transactions mt
            WHERE mt.created_at BETWEEN ? AND ?
        ";
        
        $manualParams = [$fromDate, $toDate];
        if ($transactionType !== 'all' && $transactionType !== 'manual_transaction') {
            $manualQuery .= " AND 1=0";
        }
        
        $stmt = $pdo->prepare($manualQuery);
        $stmt->execute($manualParams);
        $manualRevenue = $stmt->fetch();
        
        return [
            'susu' => $susuRevenue,
            'loan' => $loanRevenue,
            'manual' => $manualRevenue,
            'total_revenue' => $susuRevenue['total_amount'] + $loanRevenue['total_amount'] + $manualRevenue['deposit_amount'],
            'total_transactions' => $susuRevenue['transaction_count'] + $loanRevenue['transaction_count'] + $manualRevenue['transaction_count']
        ];
    }
    
    private function getMonthlyTrends($pdo, $fromDate, $toDate) {
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(dc.collection_date, '%Y-%m') as month,
                SUM(dc.collected_amount) as susu_revenue,
                COUNT(*) as susu_count
            FROM daily_collections dc
            WHERE dc.collection_status = 'collected'
            AND dc.collection_date BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(dc.collection_date, '%Y-%m')
            
            UNION ALL
            
            SELECT 
                DATE_FORMAT(lp.payment_date, '%Y-%m') as month,
                SUM(lp.amount_paid) as loan_revenue,
                COUNT(*) as loan_count
            FROM loan_payments lp
            WHERE lp.payment_status = 'completed'
            AND lp.payment_date BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(lp.payment_date, '%Y-%m')
            
            ORDER BY month DESC
            LIMIT 12
        ");
        $stmt->execute([$fromDate, $toDate, $fromDate, $toDate]);
        return $stmt->fetchAll();
    }
    
    private function getAgentRevenue($pdo, $fromDate, $toDate) {
        $stmt = $pdo->prepare("
            SELECT 
                a.agent_code,
                CONCAT(u.first_name, ' ', u.last_name) as agent_name,
                COALESCE(SUM(dc.collected_amount), 0) as susu_revenue,
                COALESCE(SUM(lp.amount_paid), 0) as loan_revenue,
                COUNT(DISTINCT dc.id) as susu_count,
                COUNT(DISTINCT lp.id) as loan_count,
                (COALESCE(SUM(dc.collected_amount), 0) + COALESCE(SUM(lp.amount_paid), 0)) as total_revenue
            FROM agents a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN daily_collections dc ON a.id = dc.collected_by 
                AND dc.collection_status = 'collected' 
                AND dc.collection_date BETWEEN ? AND ?
            LEFT JOIN loan_payments lp ON a.id = lp.collected_by 
                AND lp.payment_status = 'completed' 
                AND lp.payment_date BETWEEN ? AND ?
            WHERE a.status = 'active'
            GROUP BY a.id, a.agent_code, u.first_name, u.last_name
            HAVING (susu_revenue > 0 OR loan_revenue > 0)
            ORDER BY total_revenue DESC
        ");
        $stmt->execute([$fromDate, $toDate, $fromDate, $toDate]);
        return $stmt->fetchAll();
    }
    
    private function getTransactionBreakdown($pdo, $fromDate, $toDate) {
        $stmt = $pdo->prepare("
            SELECT 
                'susu_collection' as transaction_type,
                COUNT(*) as count,
                SUM(dc.collected_amount) as total_amount,
                AVG(dc.collected_amount) as avg_amount,
                MIN(dc.collected_amount) as min_amount,
                MAX(dc.collected_amount) as max_amount
            FROM daily_collections dc
            WHERE dc.collection_status = 'collected'
            AND dc.collection_date BETWEEN ? AND ?
            
            UNION ALL
            
            SELECT 
                'loan_payment' as transaction_type,
                COUNT(*) as count,
                SUM(lp.amount_paid) as total_amount,
                AVG(lp.amount_paid) as avg_amount,
                MIN(lp.amount_paid) as min_amount,
                MAX(lp.amount_paid) as max_amount
            FROM loan_payments lp
            WHERE lp.payment_status = 'completed'
            AND lp.payment_date BETWEEN ? AND ?
            
            UNION ALL
            
            SELECT 
                'manual_deposit' as transaction_type,
                COUNT(*) as count,
                SUM(mt.amount) as total_amount,
                AVG(mt.amount) as avg_amount,
                MIN(mt.amount) as min_amount,
                MAX(mt.amount) as max_amount
            FROM manual_transactions mt
            WHERE mt.transaction_type = 'deposit'
            AND mt.created_at BETWEEN ? AND ?
            
            ORDER BY total_amount DESC
        ");
        $stmt->execute([$fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate]);
        return $stmt->fetchAll();
    }
}
?>
