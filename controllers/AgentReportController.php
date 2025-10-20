<?php
namespace Controllers;

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

use function Auth\requireRole;

class AgentReportController {
    public function consolidatedReport(): void {
        requireRole(['business_admin', 'manager']);
        
        $pdo = \Database::getConnection();
        
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-d');
        
        $stmt = $pdo->prepare("
            SELECT a.id, a.agent_code, u.first_name, u.last_name, u.email, u.phone,
                   COUNT(dc.id) as total_collections,
                   COALESCE(SUM(dc.collected_amount), 0) as total_collected,
                   COUNT(DISTINCT sc.id) as cycles_completed,
                   COALESCE(AVG(dc.collected_amount), 0) as avg_collection,
                   MAX(dc.collection_date) as last_collection
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
        
        include __DIR__ . '/../views/admin/agent_report_consolidated.php';
    }

    public function individualReport(int $agentId): void {
        requireRole(['business_admin', 'manager']);
        
        $pdo = \Database::getConnection();
        
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-d');
        
        $stmt = $pdo->prepare("
            SELECT a.*, u.first_name, u.last_name, u.email, u.phone
            FROM agents a
            JOIN users u ON a.user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$agentId]);
        $agent = $stmt->fetch();
        
        if (!$agent) {
            header('Location: /admin_agent_reports.php');
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT dc.*, 
                   CONCAT(c.first_name, ' ', c.last_name) as client_name,
                   sc.cycle_number
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            JOIN clients cl ON sc.client_id = cl.id
            JOIN users c ON cl.user_id = c.id
            WHERE dc.collected_by = ? AND dc.collection_date BETWEEN ? AND ?
            ORDER BY dc.collection_date DESC
        ");
        $stmt->execute([$agentId, $fromDate, $toDate]);
        $collections = $stmt->fetchAll();
        
        include __DIR__ . '/../views/admin/agent_report_individual.php';
    }

    public function dailyReport(): void {
        requireRole(['business_admin', 'manager']);
        
        $pdo = \Database::getConnection();
        
        $date = $_GET['date'] ?? date('Y-m-d');
        $month = $_GET['month'] ?? date('Y-m');
        
        if ($date) {
            $collections = $pdo->query("
                SELECT dc.*, 
                       CONCAT(c.first_name, ' ', c.last_name) as client_name,
                       a.agent_code,
                       sc.cycle_number
                FROM daily_collections dc
                JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
                JOIN clients cl ON sc.client_id = cl.id
                JOIN users c ON cl.user_id = c.id
                LEFT JOIN agents a ON dc.collected_by = a.id
                WHERE dc.collection_date = ?
                ORDER BY dc.collection_time DESC
            ")->execute([$date])->fetchAll();
        } else {
            $collections = $pdo->query("
                SELECT DATE(dc.collection_date) as date,
                       COUNT(*) as collections_count,
                       SUM(dc.collected_amount) as total_collected,
                       GROUP_CONCAT(DISTINCT a.agent_code) as agents
                FROM daily_collections dc
                LEFT JOIN agents a ON dc.collected_by = a.id
                WHERE dc.collection_date LIKE ?
                GROUP BY DATE(dc.collection_date)
                ORDER BY date DESC
            ")->execute([$month . '%'])->fetchAll();
        }
        
        include __DIR__ . '/../views/admin/agent_report_daily.php';
    }
}
?>