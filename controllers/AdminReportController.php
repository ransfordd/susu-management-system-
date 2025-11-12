<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use function Auth\requireRole;

class AdminReportController {
    public function index(): void {
        requireRole(['business_admin', 'manager']);
        
        // If parameters are present, redirect to financial report
        if (isset($_GET['from_date']) || isset($_GET['to_date']) || isset($_GET['report_type']) || isset($_GET['agent_id'])) {
            $this->financialReport();
            return;
        }
        
        include __DIR__ . '/../views/admin/reports_index.php';
    }

    public function financialReport(): void {
        requireRole(['business_admin', 'manager']);
        
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-d');
        $reportType = $_GET['report_type'] ?? 'all';
        $agentId = $_GET['agent_id'] ?? null;
        
        $pdo = \Database::getConnection();
        
        // Get selected agent details if agent_id is provided
        $data['selected_agent'] = null;
        if ($agentId) {
            $stmt = $pdo->prepare("
                SELECT a.*, u.first_name, u.last_name, u.email
                FROM agents a
                JOIN users u ON a.user_id = u.id
                WHERE a.id = ?
            ");
            $stmt->execute([$agentId]);
            $data['selected_agent'] = $stmt->fetch();
        }
        
        // Get financial data based on report type
        $data = array_merge($data, []);
        
        if ($reportType === 'all' || $reportType === 'deposits') {
            $agentFilter = $agentId ? "AND dc.collected_by = ?" : "";
            $params = $agentId ? [$fromDate, $toDate, $agentId] : [$fromDate, $toDate];
            
            $stmt = $pdo->prepare("
                SELECT DATE(dc.collection_date) as date, 
                       COALESCE(SUM(dc.collected_amount), 0) as total,
                       COUNT(*) as count
                FROM daily_collections dc
                WHERE dc.collection_date BETWEEN ? AND ? $agentFilter
                GROUP BY DATE(dc.collection_date)
                ORDER BY date DESC
            ");
            $stmt->execute($params);
            $data['deposits'] = $stmt->fetchAll();
            
            // Get detailed deposit transactions
            $stmt = $pdo->prepare("
                SELECT dc.*, 
                       CONCAT(c.first_name, ' ', c.last_name) as client_name,
                       a.agent_code,
                       sc.cycle_number,
                       'Deposit' as transaction_type
                FROM daily_collections dc
                JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
                JOIN clients cl ON sc.client_id = cl.id
                JOIN users c ON cl.user_id = c.id
                LEFT JOIN agents a ON dc.collected_by = a.id
                WHERE dc.collection_date BETWEEN ? AND ? $agentFilter
                ORDER BY dc.collection_time DESC
            ");
            $stmt->execute($params);
            $data['deposit_transactions'] = $stmt->fetchAll();
        }
        
        if ($reportType === 'all' || $reportType === 'withdrawals') {
            // Get Susu cycle withdrawals
            $susuAgentFilter = $agentId ? "AND EXISTS (SELECT 1 FROM daily_collections dc WHERE dc.susu_cycle_id = sc.id AND dc.collected_by = ?)" : "";
            $susuParams = [$fromDate, $toDate];
            if ($agentId) {
                $susuParams[] = $agentId;
            }
            
            $stmt = $pdo->prepare("
                SELECT DATE(sc.payout_date) as date,
                       COALESCE(SUM(sc.payout_amount), 0) as total,
                       COUNT(*) as count
                FROM susu_cycles sc
                WHERE sc.payout_date BETWEEN ? AND ? AND sc.status = 'completed' $susuAgentFilter
                GROUP BY DATE(sc.payout_date)
                ORDER BY date DESC
            ");
            $stmt->execute($susuParams);
            $susuWithdrawals = $stmt->fetchAll();
            
            // Get manual withdrawals
            $manualAgentFilter = $agentId ? "AND EXISTS (SELECT 1 FROM clients c WHERE c.id = mt.client_id AND c.agent_id = ?)" : "";
            $manualParams = [$fromDate, $toDate];
            if ($agentId) {
                $manualParams[] = $agentId;
            }
            
            $stmt = $pdo->prepare("
                SELECT DATE(mt.created_at) as date,
                       COALESCE(SUM(mt.amount), 0) as total,
                       COUNT(*) as count
                FROM manual_transactions mt
                WHERE mt.created_at BETWEEN ? AND ? AND mt.transaction_type = 'withdrawal' $manualAgentFilter
                GROUP BY DATE(mt.created_at)
                ORDER BY date DESC
            ");
            $stmt->execute($manualParams);
            $manualWithdrawals = $stmt->fetchAll();
            
            // Combine withdrawals by date
            $combinedWithdrawals = [];
            foreach ($susuWithdrawals as $withdrawal) {
                $date = $withdrawal['date'];
                if (!isset($combinedWithdrawals[$date])) {
                    $combinedWithdrawals[$date] = [
                        'date' => $date,
                        'total' => 0,
                        'count' => 0
                    ];
                }
                $combinedWithdrawals[$date]['total'] += $withdrawal['total'];
                $combinedWithdrawals[$date]['count'] += $withdrawal['count'];
            }
            
            foreach ($manualWithdrawals as $withdrawal) {
                $date = $withdrawal['date'];
                if (!isset($combinedWithdrawals[$date])) {
                    $combinedWithdrawals[$date] = [
                        'date' => $date,
                        'total' => 0,
                        'count' => 0
                    ];
                }
                $combinedWithdrawals[$date]['total'] += $withdrawal['total'];
                $combinedWithdrawals[$date]['count'] += $withdrawal['count'];
            }
            
            $data['withdrawals'] = array_values($combinedWithdrawals);
            
            // Get detailed withdrawal transactions (both Susu and manual)
            $withdrawalAgentFilter1 = $agentId ? "AND EXISTS (SELECT 1 FROM daily_collections dc WHERE dc.susu_cycle_id = sc.id AND dc.collected_by = ?)" : "";
            $withdrawalAgentFilter2 = $agentId ? "AND cl.agent_id = ?" : "";
            $withdrawalParams = [$fromDate, $toDate, $fromDate, $toDate];
            if ($agentId) {
                $withdrawalParams[] = $agentId;
                $withdrawalParams[] = $agentId;
            }
            
            $stmt = $pdo->prepare("
                SELECT sc.id, sc.payout_date, sc.payout_amount, sc.completion_date,
                       CONCAT(c.first_name, ' ', c.last_name) as client_name,
                       sc.cycle_number,
                       'Susu Withdrawal' as transaction_type,
                       sc.completion_date as transaction_time,
                       CONCAT('CYCLE-', sc.id) as receipt_number
                FROM susu_cycles sc
                JOIN clients cl ON sc.client_id = cl.id
                JOIN users c ON cl.user_id = c.id
                WHERE sc.payout_date BETWEEN ? AND ? AND sc.status = 'completed' $withdrawalAgentFilter1
                
                UNION ALL
                
                SELECT mt.id, DATE(mt.created_at) as payout_date, mt.amount as payout_amount, mt.created_at as completion_date,
                       CONCAT(c.first_name, ' ', c.last_name) as client_name,
                       NULL as cycle_number,
                       'Manual Withdrawal' as transaction_type,
                       mt.created_at as transaction_time,
                       mt.reference as receipt_number
                FROM manual_transactions mt
                JOIN clients cl ON mt.client_id = cl.id
                JOIN users c ON cl.user_id = c.id
                WHERE mt.created_at BETWEEN ? AND ? AND mt.transaction_type = 'withdrawal' $withdrawalAgentFilter2
                
                ORDER BY transaction_time DESC
            ");
            $stmt->execute($withdrawalParams);
            $data['withdrawal_transactions'] = $stmt->fetchAll();
        }
        
        if ($reportType === 'agent_performance') {
            $agentFilter = $agentId ? "AND a.id = ?" : "";
            $params = [$fromDate, $toDate, $fromDate, $toDate];
            if ($agentId) {
                $params[] = $agentId;
            }
            
            $stmt = $pdo->prepare("
                SELECT a.id, a.agent_code, u.first_name, u.last_name,
                       COUNT(dc.id) as collections_count,
                       COALESCE(SUM(dc.collected_amount), 0) as total_collected,
                       COUNT(DISTINCT sc.id) as cycles_completed
                FROM agents a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN daily_collections dc ON a.id = dc.collected_by 
                    AND dc.collection_date BETWEEN ? AND ?
                LEFT JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id 
                    AND sc.completion_date BETWEEN ? AND ?
                WHERE a.status = 'active' $agentFilter
                GROUP BY a.id
                ORDER BY total_collected DESC
            ");
            $stmt->execute($params);
            $data['agent_performance'] = $stmt->fetchAll();
        }
        
        include __DIR__ . '/../views/admin/reports_financial.php';
    }

    public function agentPerformanceReport(): void {
        requireRole(['business_admin', 'manager']);
        
        $agentId = $_GET['agent_id'] ?? null;
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-d');
        
        $pdo = \Database::getConnection();
        
        if ($agentId) {
            // Individual agent report
            $stmt = $pdo->prepare("
                SELECT a.*, u.first_name, u.last_name, u.email, u.phone
                FROM agents a
                JOIN users u ON a.user_id = u.id
                WHERE a.id = ?
            ");
            $stmt->execute([$agentId]);
            $agent = $stmt->fetch();
            
            if (!$agent) {
                header('Location: /admin_reports.php?action=agent_performance');
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT DATE(dc.collection_date) as date,
                       COUNT(*) as collections_count,
                       SUM(dc.collected_amount) as total_collected
                FROM daily_collections dc
                WHERE dc.collected_by = ? AND dc.collection_date BETWEEN ? AND ?
                GROUP BY DATE(dc.collection_date)
                ORDER BY date DESC
            ");
            $stmt->execute([$agentId, $fromDate, $toDate]);
            $performance = $stmt->fetchAll();
            
            include __DIR__ . '/../views/admin/reports_agent_individual.php';
        } else {
            // All agents report
            $stmt = $pdo->prepare("
                SELECT a.id, a.agent_code, u.first_name, u.last_name,
                       COUNT(dc.id) as collections_count,
                       COALESCE(SUM(dc.collected_amount), 0) as total_collected,
                       COUNT(DISTINCT sc.id) as cycles_completed
                FROM agents a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN daily_collections dc ON a.id = dc.collected_by 
                    AND dc.collection_date BETWEEN ? AND ?
                LEFT JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id 
                    AND sc.completion_date BETWEEN ? AND ?
                WHERE a.status = 'active'
                GROUP BY a.id
                ORDER BY total_collected DESC
            ");
            $stmt->execute([$fromDate, $toDate, $fromDate, $toDate]);
            $agents = $stmt->fetchAll();
            
            include __DIR__ . '/../views/admin/reports_agent_performance.php';
        }
    }

    public function exportReport(): void {
        requireRole(['business_admin', 'manager']);
        
        $format = $_GET['format'] ?? 'csv';
        $reportType = $_GET['report_type'] ?? 'financial';
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-d');
        $agentId = $_GET['agent_id'] ?? null;
        
        $pdo = \Database::getConnection();
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="report_' . $reportType . '_' . $fromDate . '_to_' . $toDate . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            if ($reportType === 'financial') {
                fputcsv($output, ['Date', 'Type', 'Amount', 'Count']);
                
                $stmt = $pdo->prepare("
                    SELECT DATE(dc.collection_date) as date, 
                           'Deposit' as type,
                           COALESCE(SUM(dc.collected_amount), 0) as total,
                           COUNT(*) as count
                    FROM daily_collections dc
                    WHERE dc.collection_date BETWEEN ? AND ?
                    GROUP BY DATE(dc.collection_date)
                    ORDER BY date DESC
                ");
                $stmt->execute([$fromDate, $toDate]);
                $deposits = $stmt->fetchAll();
                
                foreach ($deposits as $row) {
                    fputcsv($output, [$row['date'], $row['type'], $row['total'], $row['count']]);
                }
                
                $stmt = $pdo->prepare("
                    SELECT DATE(sc.payout_date) as date,
                           'Withdrawal' as type,
                           COALESCE(SUM(sc.payout_amount), 0) as total,
                           COUNT(*) as count
                    FROM susu_cycles sc
                    WHERE sc.payout_date BETWEEN ? AND ? AND sc.status = 'completed'
                    GROUP BY DATE(sc.payout_date)
                    ORDER BY date DESC
                ");
                $stmt->execute([$fromDate, $toDate]);
                $withdrawals = $stmt->fetchAll();
                
                foreach ($withdrawals as $row) {
                    fputcsv($output, [$row['date'], $row['type'], $row['total'], $row['count']]);
                }
            } elseif ($reportType === 'agent_performance') {
                // Agent performance CSV
                fputcsv($output, ['Agent', 'Agent Code', 'Collections', 'Total Collected', 'Cycles Completed']);

                $agentFilter = $agentId ? "AND a.id = ?" : "";
                $params = [$fromDate, $toDate, $fromDate, $toDate];
                if ($agentId) { $params[] = $agentId; }

                $stmt = $pdo->prepare("
                    SELECT a.id, a.agent_code, u.first_name, u.last_name,
                           COUNT(dc.id) as collections_count,
                           COALESCE(SUM(dc.collected_amount), 0) as total_collected,
                           COUNT(DISTINCT sc.id) as cycles_completed
                    FROM agents a
                    JOIN users u ON a.user_id = u.id
                    LEFT JOIN daily_collections dc ON a.id = dc.collected_by 
                        AND dc.collection_date BETWEEN ? AND ?
                    LEFT JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id 
                        AND sc.completion_date BETWEEN ? AND ?
                    WHERE a.status = 'active' $agentFilter
                    GROUP BY a.id
                    ORDER BY total_collected DESC
                ");
                $stmt->execute($params);
                foreach ($stmt->fetchAll() as $row) {
                    $agentName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                    fputcsv($output, [
                        $agentName,
                        $row['agent_code'],
                        $row['collections_count'],
                        number_format((float)$row['total_collected'], 2, '.', ''),
                        $row['cycles_completed']
                    ]);
                }
            }
            
            fclose($output);
            exit;
        }
        
        // Default redirect
        header('Location: /admin_reports.php');
        exit;
    }
}
?>
