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
        
        // Pagination (50 per page)
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) FROM daily_collections dc
            WHERE dc.collected_by = ? AND dc.collection_date BETWEEN ? AND ?
        ");
        $countStmt->execute([$agentId, $fromDate, $toDate]);
        $totalRows = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalRows / $perPage));
        
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
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute([$agentId, $fromDate, $toDate]);
        $collections = $stmt->fetchAll();
        
        include __DIR__ . '/../views/admin/agent_report_individual.php';
    }

    public function dailyReport(): void {
        requireRole(['business_admin', 'manager']);
        
        $pdo = \Database::getConnection();
        
        $date = $_GET['date'] ?? '';
        $month = $_GET['month'] ?? date('Y-m');
        $agentId = $_GET['agent_id'] ?? '';
        
        if (!empty($date)) {
            $sql = "
                SELECT dc.*, 
                       CONCAT(c.first_name, ' ', c.last_name) as client_name,
                       a.agent_code,
                       sc.cycle_number
                FROM daily_collections dc
                JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
                JOIN clients cl ON sc.client_id = cl.id
                JOIN users c ON cl.user_id = c.id
                LEFT JOIN agents a ON dc.collected_by = a.id
                WHERE dc.collection_date = ?";
            $params = [$date];
            if (!empty($agentId)) { $sql .= " AND dc.collected_by = ?"; $params[] = $agentId; }
            $sql .= " ORDER BY dc.collection_time DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $collections = $stmt->fetchAll();
        } else {
            $sql = "
                SELECT DATE(dc.collection_date) as date,
                       COUNT(*) as collections_count,
                       SUM(dc.collected_amount) as total_collected,
                       GROUP_CONCAT(DISTINCT a.agent_code) as agents
                FROM daily_collections dc
                LEFT JOIN agents a ON dc.collected_by = a.id
                WHERE dc.collection_date LIKE ?";
            $params = [$month . '%'];
            if (!empty($agentId)) { $sql .= " AND dc.collected_by = ?"; $params[] = $agentId; }
            $sql .= " GROUP BY DATE(dc.collection_date) ORDER BY date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $collections = $stmt->fetchAll();
        }
        
        include __DIR__ . '/../views/admin/agent_report_daily.php';
    }

    public function export(): void {
        requireRole(['business_admin', 'manager']);
        $pdo = \Database::getConnection();
        $mode = $_GET['mode'] ?? 'consolidated';
        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-d');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="agent_' . $mode . '_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        
        if ($mode === 'consolidated') {
            fputcsv($out, ['Agent', 'Agent Code', 'Collections', 'Total Collected', 'Avg Collection', 'Cycles Completed', 'Last Collection']);
            $stmt = $pdo->prepare("
                SELECT a.agent_code, u.first_name, u.last_name,
                       COUNT(dc.id) as total_collections,
                       COALESCE(SUM(dc.collected_amount), 0) as total_collected,
                       COALESCE(AVG(dc.collected_amount), 0) as avg_collection,
                       COUNT(DISTINCT sc.id) as cycles_completed,
                       MAX(dc.collection_date) as last_collection
                FROM agents a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN daily_collections dc ON a.id = dc.collected_by AND dc.collection_date BETWEEN ? AND ?
                LEFT JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id AND sc.completion_date BETWEEN ? AND ?
                WHERE a.status = 'active'
                GROUP BY a.id
                ORDER BY total_collected DESC
            ");
            $stmt->execute([$fromDate, $toDate, $fromDate, $toDate]);
            foreach ($stmt->fetchAll() as $r) {
                $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                fputcsv($out, [$name, $r['agent_code'], $r['total_collections'], number_format((float)$r['total_collected'], 2, '.', ''), number_format((float)$r['avg_collection'], 2, '.', ''), $r['cycles_completed'], $r['last_collection']]);
            }
        } elseif ($mode === 'individual') {
            $agentId = (int)($_GET['agent_id'] ?? 0);
            fputcsv($out, ['Date', 'Client', 'Cycle', 'Amount', 'Receipt']);
            $stmt = $pdo->prepare("
                SELECT dc.collection_time, CONCAT(c.first_name,' ',c.last_name) as client_name, sc.cycle_number, dc.collected_amount, dc.receipt_number
                FROM daily_collections dc
                JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
                JOIN clients cl ON sc.client_id = cl.id
                JOIN users c ON cl.user_id = c.id
                WHERE dc.collected_by = ? AND dc.collection_date BETWEEN ? AND ?
                ORDER BY dc.collection_date DESC
            ");
            $stmt->execute([$agentId, $fromDate, $toDate]);
            foreach ($stmt->fetchAll() as $r) {
                fputcsv($out, [date('Y-m-d H:i', strtotime($r['collection_time'])), $r['client_name'], $r['cycle_number'], number_format((float)$r['collected_amount'], 2, '.', ''), $r['receipt_number']]);
            }
        } else { // daily
            $date = $_GET['date'] ?? '';
            $agentId = $_GET['agent_id'] ?? '';
            if (!empty($date)) {
                fputcsv($out, ['Time', 'Client', 'Agent', 'Cycle', 'Amount', 'Receipt']);
                $sql = "
                    SELECT dc.collection_time, CONCAT(c.first_name,' ',c.last_name) as client_name, a.agent_code, sc.cycle_number, dc.collected_amount, dc.receipt_number
                    FROM daily_collections dc
                    JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
                    JOIN clients cl ON sc.client_id = cl.id
                    JOIN users c ON cl.user_id = c.id
                    LEFT JOIN agents a ON dc.collected_by = a.id
                    WHERE dc.collection_date = ?";
                $params = [$date];
                if (!empty($agentId)) { $sql .= " AND dc.collected_by = ?"; $params[] = $agentId; }
                $sql .= " ORDER BY dc.collection_time DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                foreach ($stmt->fetchAll() as $r) {
                    fputcsv($out, [date('Y-m-d H:i', strtotime($r['collection_time'])), $r['client_name'], $r['agent_code'], $r['cycle_number'], number_format((float)$r['collected_amount'], 2, '.', ''), $r['receipt_number']]);
                }
            } else {
                fputcsv($out, ['Date', 'Collections', 'Total Collected', 'Agents']);
                $sql = "
                    SELECT DATE(dc.collection_date) as date, COUNT(*) as collections_count, SUM(dc.collected_amount) as total_collected, GROUP_CONCAT(DISTINCT a.agent_code) as agents
                    FROM daily_collections dc
                    LEFT JOIN agents a ON dc.collected_by = a.id
                    WHERE dc.collection_date LIKE ?";
                $params = [($_GET['month'] ?? date('Y-m')) . '%'];
                if (!empty($agentId)) { $sql .= " AND dc.collected_by = ?"; $params[] = $agentId; }
                $sql .= " GROUP BY DATE(dc.collection_date) ORDER BY date DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                foreach ($stmt->fetchAll() as $r) {
                    fputcsv($out, [$r['date'], $r['collections_count'], number_format((float)$r['total_collected'], 2, '.', ''), $r['agents']]);
                }
            }
        }
        fclose($out);
        exit;
    }
}
?>