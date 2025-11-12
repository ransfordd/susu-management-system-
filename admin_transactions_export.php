<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);

// Get export parameters
$format = $_GET['format'] ?? 'pdf';
$type = $_GET['type'] ?? 'all';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';

// Validate format
if (!in_array($format, ['pdf', 'excel', 'csv'])) {
    die('Invalid export format');
}

// Get transactions using the same logic as TransactionController
$pdo = Database::getConnection();

// Build where conditions
$whereConditions = [];
$params = [];

if ($fromDate && $toDate) {
    $whereConditions[] = "transaction_date BETWEEN ? AND ?";
    $params[] = $fromDate;
    $params[] = $toDate;
}

if ($type !== 'all') {
    $whereConditions[] = "transaction_type = ?";
    $params[] = $type;
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get transactions query (same as TransactionController)
$transactionsQuery = "
    SELECT 
        transaction_type,
        transaction_id,
        transaction_date,
        transaction_time,
        amount,
        client_name,
        client_code,
        client_email,
        client_phone,
        agent_name,
        notes
    FROM (
        SELECT 
            'susu_collection' as transaction_type,
            dc.id as transaction_id,
            dc.collection_date as transaction_date,
            dc.collection_time as transaction_time,
            dc.collected_amount as amount,
            CONCAT(u.first_name, ' ', u.last_name) as client_name,
            c.client_code,
            u.email as client_email,
            u.phone as client_phone,
            COALESCE(CONCAT(ag_u.first_name, ' ', ag_u.last_name), 'System Admin') as agent_name,
            COALESCE(dc.notes, '') as notes
        FROM daily_collections dc
        JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
        JOIN clients c ON sc.client_id = c.id
        JOIN users u ON c.user_id = u.id
        LEFT JOIN agents a ON dc.collected_by = a.id
        LEFT JOIN users ag_u ON a.user_id = ag_u.id
        WHERE dc.collected_amount > 0
        
        UNION ALL
        
        SELECT 
            'loan_payment' as transaction_type,
            lp.id as transaction_id,
            lp.payment_date as transaction_date,
            lp.payment_time as transaction_time,
            lp.amount_paid as amount,
            CONCAT(u.first_name, ' ', u.last_name) as client_name,
            c.client_code,
            u.email as client_email,
            u.phone as client_phone,
            COALESCE(CONCAT(ag_u.first_name, ' ', ag_u.last_name), 'System Admin') as agent_name,
            COALESCE(lp.notes, '') as notes
        FROM loan_payments lp
        JOIN loans l ON lp.loan_id = l.id
        JOIN clients c ON l.client_id = c.id
        JOIN users u ON c.user_id = u.id
        LEFT JOIN agents a ON lp.collected_by = a.id
        LEFT JOIN users ag_u ON a.user_id = ag_u.id
        WHERE lp.amount_paid > 0
        
        UNION ALL
        
        SELECT 
            'loan_disbursement' as transaction_type,
            l.id as transaction_id,
            l.disbursement_date as transaction_date,
            l.disbursement_time as transaction_time,
            l.principal_amount as amount,
            CONCAT(u.first_name, ' ', u.last_name) as client_name,
            c.client_code,
            u.email as client_email,
            u.phone as client_phone,
            COALESCE(CONCAT(ag_u.first_name, ' ', ag_u.last_name), 'System Admin') as agent_name,
            'Loan Disbursement' as notes
        FROM loans l
        JOIN clients c ON l.client_id = c.id
        JOIN users u ON c.user_id = u.id
        LEFT JOIN agents a ON l.disbursed_by = a.id
        LEFT JOIN users ag_u ON a.user_id = ag_u.id
        WHERE l.loan_status = 'active'
        
        UNION ALL
        
        SELECT 
            'savings_deposit' as transaction_type,
            st.id as transaction_id,
            DATE(st.created_at) as transaction_date,
            COALESCE(TIME(st.created_at), '00:00:00') as transaction_time,
            st.amount as amount,
            CONCAT(u.first_name, ' ', u.last_name) as client_name,
            c.client_code,
            u.email as client_email,
            u.phone as client_phone,
            COALESCE(CONCAT(ag_u.first_name, ' ', ag_u.last_name), 'System Admin') as agent_name,
            COALESCE(st.description, 'Savings Deposit') as notes
        FROM savings_transactions st
        JOIN savings_accounts sa ON st.savings_account_id = sa.id
        JOIN clients c ON sa.client_id = c.id
        JOIN users u ON c.user_id = u.id
        LEFT JOIN agents a ON c.agent_id = a.id
        LEFT JOIN users ag_u ON a.user_id = ag_u.id
        WHERE st.transaction_type = 'deposit'
    ) t
    $whereClause
    ORDER BY transaction_date DESC, transaction_time DESC
    LIMIT 1000";

$stmt = $pdo->prepare($transactionsQuery);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Generate filename
$filename = 'transactions_report_' . date('Y-m-d') . '_' . time();

switch ($format) {
    case 'pdf':
        generatePDF($transactions, $filename, $fromDate, $toDate, $type);
        break;
    case 'excel':
        generateExcel($transactions, $filename);
        break;
    case 'csv':
        generateCSV($transactions, $filename);
        break;
}

function generatePDF($transactions, $filename, $fromDate, $toDate, $type) {
    // For now, generate an HTML report that can be printed as PDF
    // Set headers for HTML that can be printed as PDF
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $filename . '.html"');
    
    $html = generateHTMLReport($transactions, $fromDate, $toDate, $type);
    
    // Add print-specific CSS
    $html = str_replace('<style>', '<style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none !important; }
            table { page-break-inside: avoid; }
            .page-break { page-break-before: always; }
        }
        @page { margin: 1cm; }', $html);
    
    // Add print button
    $html = str_replace('</body>', '
        <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
            <button onclick="window.print()" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                <i class="fas fa-print"></i> Print PDF
            </button>
            <button onclick="window.close()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-left: 10px;">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
        <script>
            // Auto-print after 1 second
            setTimeout(function() {
                window.print();
            }, 1000);
        </script>
    </body>', $html);
    
    echo $html;
}

function generateExcel($transactions, $filename) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr>";
    echo "<th>Type</th>";
    echo "<th>Date</th>";
    echo "<th>Client</th>";
    echo "<th>Amount</th>";
    echo "<th>Agent</th>";
    echo "</tr>";
    
    foreach ($transactions as $transaction) {
        echo "<tr>";
        echo "<td>" . ucfirst(str_replace('_', ' ', $transaction['transaction_type'])) . "</td>";
        echo "<td>" . $transaction['transaction_date'] . " " . $transaction['transaction_time'] . "</td>";
        echo "<td>" . htmlspecialchars($transaction['client_name']) . "</td>";
        echo "<td>GHS " . number_format($transaction['amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($transaction['agent_name']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

function generateCSV($transactions, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['Type', 'Date', 'Client', 'Amount', 'Agent']);
    
    // CSV data
    foreach ($transactions as $transaction) {
        fputcsv($output, [
            ucfirst(str_replace('_', ' ', $transaction['transaction_type'])),
            $transaction['transaction_date'] . ' ' . $transaction['transaction_time'],
            $transaction['client_name'],
            'GHS ' . number_format($transaction['amount'], 2),
            $transaction['agent_name']
        ]);
    }
    
    fclose($output);
}

function generateHTMLReport($transactions, $fromDate, $toDate, $type) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <title>Transaction Report</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #333; margin-bottom: 10px; }
            .header p { color: #666; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .amount { text-align: right; }
            .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Transaction Report</h1>
            <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    
    if ($fromDate && $toDate) {
        $html .= '<p>Date Range: ' . $fromDate . ' to ' . $toDate . '</p>';
    }
    
    if ($type !== 'all') {
        $html .= '<p>Transaction Type: ' . ucfirst($type) . '</p>';
    }
    
    $html .= '</div>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Amount</th>
                    <th>Agent</th>
                </tr>
            </thead>
            <tbody>';
    
    $totalAmount = 0;
    foreach ($transactions as $transaction) {
        $totalAmount += $transaction['amount'];
        $html .= '<tr>';
        $html .= '<td>' . ucfirst(str_replace('_', ' ', $transaction['transaction_type'])) . '</td>';
        $html .= '<td>' . $transaction['transaction_date'] . ' ' . $transaction['transaction_time'] . '</td>';
        $html .= '<td>' . htmlspecialchars($transaction['client_name']) . '</td>';
        $html .= '<td class="amount">GHS ' . number_format($transaction['amount'], 2) . '</td>';
        $html .= '<td>' . htmlspecialchars($transaction['agent_name']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>Total</strong></td>
                    <td class="amount"><strong>GHS ' . number_format($totalAmount, 2) . '</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="footer">
            <p>Report generated by Susu & Loan Management System</p>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>
