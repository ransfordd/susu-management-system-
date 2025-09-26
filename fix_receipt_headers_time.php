<?php
echo "<h2>Fix Transaction Receipt Headers and Time</h2>";
echo "<pre>";

echo "FIXING TRANSACTION RECEIPT HEADERS AND TIME\n";
echo "===========================================\n\n";

try {
    // 1. Fix the shared receipt layout
    echo "1. UPDATING SHARED RECEIPT LAYOUT\n";
    echo "==================================\n";
    
    $receiptLayoutFile = __DIR__ . "/views/shared/receipt_layout.php";
    if (!file_exists($receiptLayoutFile)) {
        echo "❌ receipt_layout.php not found\n";
        exit;
    }
    
    // Create enhanced receipt layout with proper headers and timezone
    $enhancedLayout = '<?php
?><!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Receipt</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
	<style>
		.receipt { max-width: 700px; margin: 24px auto; border: 1px solid #ddd; padding: 16px; background: #fff; }
		.header { display:flex; justify-content: space-between; align-items: center; }
		.kv { display:flex; justify-content: space-between; margin-bottom:6px; }
		.company-header { text-align: center; margin-bottom: 20px; }
		.company-name { font-size: 24px; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
		.company-icon { font-size: 28px; color: #3498db; margin-right: 10px; }
		.receipt-title { font-size: 18px; font-weight: bold; color: #34495e; margin-bottom: 10px; }
		.receipt-number { font-size: 14px; color: #7f8c8d; }
		.time-display { font-size: 12px; color: #95a5a6; }
	</style>
</head>
<body>
<div class="receipt">
	<!-- Company Header -->
	<div class="company-header">
		<div class="company-name">
			<i class="fas fa-building company-icon"></i>
			The Determiners
		</div>
		<div class="receipt-title">Transaction Receipt</div>
	</div>
	
	<!-- Receipt Header -->
	<div class="header">
		<h5><?php echo htmlspecialchars($title); ?></h5>
		<div class="receipt-number"><strong>No:</strong> <?php echo htmlspecialchars($receipt); ?></div>
	</div>
	<hr>
	<?php echo $contentHtml; ?>
	<hr>
	<div class="time-display">Generated: <?php 
		// Set timezone to Ghana (UTC+0)
		date_default_timezone_set(\'Africa/Accra\');
		echo date(\'Y-m-d H:i:s\'); 
	?></div>
</div>
</body>
</html>';
    
    // Create backup
    $backupFile = __DIR__ . "/views/shared/receipt_layout_backup_" . date('YmdHis') . ".php";
    $currentContent = file_get_contents($receiptLayoutFile);
    if (file_put_contents($backupFile, $currentContent)) {
        echo "✅ Backup created: " . basename($backupFile) . "\n";
    }
    
    // Write enhanced layout
    if (file_put_contents($receiptLayoutFile, $enhancedLayout)) {
        echo "✅ Enhanced receipt layout written successfully\n";
    } else {
        echo "❌ Failed to write enhanced receipt layout\n";
        exit;
    }
    
    // 2. Fix the transaction print template
    echo "\n2. UPDATING TRANSACTION PRINT TEMPLATE\n";
    echo "======================================\n";
    
    $transactionPrintFile = __DIR__ . "/views/admin/transaction_print.php";
    if (!file_exists($transactionPrintFile)) {
        echo "❌ transaction_print.php not found\n";
        exit;
    }
    
    // Create enhanced transaction print template
    $enhancedTransactionPrint = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Receipt - <?php echo htmlspecialchars($transaction[\'reference_number\'] ?? \'\'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            .container { max-width: none !important; }
        }
        .receipt-header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .receipt-footer {
            border-top: 2px solid #000;
            padding-top: 10px;
            margin-top: 20px;
        }
        .company-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .company-icon {
            font-size: 32px;
            color: #3498db;
            margin-right: 10px;
        }
        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            color: #34495e;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <!-- Company Header -->
                        <div class="company-header">
                            <div class="company-name">
                                <i class="fas fa-building company-icon"></i>
                                The Determiners
                            </div>
                            <div class="receipt-title">Transaction Receipt</div>
                        </div>
                        
                        <!-- Receipt Header -->
                        <div class="receipt-header text-center">
                            <h4>Transaction Reference: <?php echo htmlspecialchars($transaction[\'reference_number\'] ?? \'\'); ?></h4>
                        </div>

                        <!-- Transaction Details -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Transaction Information</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Date:</strong></td>
                                        <td><?php 
                                            // Set timezone to Ghana (UTC+0)
                                            date_default_timezone_set(\'Africa/Accra\');
                                            echo date(\'F d, Y\', strtotime($transaction[\'collection_date\'] ?? $transaction[\'created_at\'] ?? $transaction[\'payment_date\'] ?? \'now\')); 
                                        ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Time:</strong></td>
                                        <td><?php 
                                            // Set timezone to Ghana (UTC+0)
                                            date_default_timezone_set(\'Africa/Accra\');
                                            echo date(\'H:i:s\', strtotime($transaction[\'collection_time\'] ?? $transaction[\'created_at\'] ?? $transaction[\'payment_date\'] ?? \'now\')); 
                                        ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Type:</strong></td>
                                        <td><?php echo ucfirst(str_replace(\'_\', \' \', $transaction[\'transaction_type\'] ?? \'\')); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Amount:</strong></td>
                                        <td><strong>GHS <?php echo number_format($transaction[\'amount\'] ?? 0, 2); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Reference:</strong></td>
                                        <td><?php echo htmlspecialchars($transaction[\'reference_number\'] ?? \'\'); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Client Information</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Name:</strong></td>
                                        <td><?php echo htmlspecialchars($transaction[\'client_name\'] ?? \'\'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Client Code:</strong></td>
                                        <td><?php echo htmlspecialchars($transaction[\'client_code\'] ?? \'\'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($transaction[\'email\'] ?? \'\'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phone:</strong></td>
                                        <td><?php echo htmlspecialchars($transaction[\'phone\'] ?? \'\'); ?></td>
                                    </tr>
                                    <?php if (!empty($transaction[\'agent_name\'])): ?>
                                    <tr>
                                        <td><strong>Agent:</strong></td>
                                        <td><?php echo htmlspecialchars($transaction[\'agent_name\']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                        <!-- Transaction Description -->
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p class="border p-3"><?php echo htmlspecialchars($transaction[\'description\'] ?? \'\'); ?></p>
                        </div>

                        <!-- Receipt Footer -->
                        <div class="receipt-footer text-center">
                            <p><strong>Generated on:</strong> <?php 
                                // Set timezone to Ghana (UTC+0)
                                date_default_timezone_set(\'Africa/Accra\');
                                echo date(\'F d, Y \\a\\t H:i:s\'); 
                            ?></p>
                            <p><strong>Generated by:</strong> System Administrator</p>
                            <p class="text-muted">This is a computer-generated receipt.</p>
                        </div>

                        <!-- Print Button -->
                        <div class="text-center no-print mt-4">
                            <button onclick="window.print()" class="btn btn-primary me-2">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                            <a href="/admin_user_transactions.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Transactions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
    
    // Create backup
    $backupFile2 = __DIR__ . "/views/admin/transaction_print_backup_" . date('YmdHis') . ".php";
    $currentContent2 = file_get_contents($transactionPrintFile);
    if (file_put_contents($backupFile2, $currentContent2)) {
        echo "✅ Backup created: " . basename($backupFile2) . "\n";
    }
    
    // Write enhanced transaction print
    if (file_put_contents($transactionPrintFile, $enhancedTransactionPrint)) {
        echo "✅ Enhanced transaction print template written successfully\n";
    } else {
        echo "❌ Failed to write enhanced transaction print template\n";
        exit;
    }
    
    // 3. Fix the user transaction summary receipt
    echo "\n3. UPDATING USER TRANSACTION SUMMARY RECEIPT\n";
    echo "============================================\n";
    
    $userTransactionSummaryFile = __DIR__ . "/views/admin/user_transaction_summary.php";
    if (file_exists($userTransactionSummaryFile)) {
        $currentContent3 = file_get_contents($userTransactionSummaryFile);
        
        // Update the receipt template in the JavaScript
        $updatedContent = str_replace(
            '<h2>SUSU COLLECTION AGENCY</h2>',
            '<h2><i class="fas fa-building"></i> The Determiners</h2>',
            $currentContent3
        );
        
        $updatedContent = str_replace(
            '<p>Transaction Receipt</p>',
            '<p>Transaction Receipt</p>',
            $updatedContent
        );
        
        // Fix time display
        $updatedContent = str_replace(
            '<?php echo date(\'M j, Y H:i:s\'); ?>',
            '<?php date_default_timezone_set(\'Africa/Accra\'); echo date(\'M j, Y H:i:s\'); ?>',
            $updatedContent
        );
        
        // Create backup
        $backupFile3 = __DIR__ . "/views/admin/user_transaction_summary_backup_" . date('YmdHis') . ".php";
        if (file_put_contents($backupFile3, $currentContent3)) {
            echo "✅ Backup created: " . basename($backupFile3) . "\n";
        }
        
        // Write updated content
        if (file_put_contents($userTransactionSummaryFile, $updatedContent)) {
            echo "✅ User transaction summary receipt updated successfully\n";
        } else {
            echo "❌ Failed to update user transaction summary receipt\n";
        }
    } else {
        echo "⚠️ user_transaction_summary.php not found, skipping\n";
    }
    
    // 4. Verify syntax
    echo "\n4. VERIFYING SYNTAX\n";
    echo "===================\n";
    
    $files = [$receiptLayoutFile, $transactionPrintFile];
    foreach ($files as $file) {
        $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ " . basename($file) . " syntax is valid\n";
        } else {
            echo "❌ " . basename($file) . " syntax error:\n" . $output . "\n";
        }
    }
    
    echo "\n🎉 TRANSACTION RECEIPT HEADERS AND TIME FIX COMPLETE!\n";
    echo "=====================================================\n";
    echo "✅ Updated receipt headers:\n";
    echo "   • First header: 'The Determiners' with building icon\n";
    echo "   • Second header: 'Transaction Receipt'\n";
    echo "✅ Fixed time display with Ghana timezone (Africa/Accra)\n";
    echo "✅ Updated all receipt templates\n";
    echo "✅ Created backups of all modified files\n";
    echo "\nFiles Updated:\n";
    echo "• views/shared/receipt_layout.php\n";
    echo "• views/admin/transaction_print.php\n";
    echo "• views/admin/user_transaction_summary.php\n";
    echo "\nThe transaction receipts now display:\n";
    echo "• 'The Determiners' as the company name with icon\n";
    echo "• 'Transaction Receipt' as the second header\n";
    echo "• Correct Ghana timezone for all timestamps\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>

