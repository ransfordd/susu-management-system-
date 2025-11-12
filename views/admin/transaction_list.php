<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Transaction Management Header -->
<div class="transactions-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-exchange-alt text-primary me-2"></i>
                    Transaction Management
                </h2>
                <p class="page-subtitle">View, edit, and manage all system transactions</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <div class="dropdown">
                    <button class="btn btn-success modern-btn dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download"></i> Export Report
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                        <li><a class="dropdown-item" href="#" onclick="exportReport('pdf')">
                            <i class="fas fa-file-pdf text-danger me-2"></i>Export as PDF
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportReport('excel')">
                            <i class="fas fa-file-excel text-success me-2"></i>Export as Excel
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportReport('csv')">
                            <i class="fas fa-file-csv text-info me-2"></i>Export as CSV
                        </a></li>
                    </ul>
                </div>
                <a href="/index.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modern Alerts -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="modern-alert alert-success">
        <div class="alert-content">
            <i class="fas fa-check-circle"></i>
            <span><?php echo e($_SESSION['success']); unset($_SESSION['success']); ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="modern-alert alert-danger">
        <div class="alert-content">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo e($_SESSION['error']); unset($_SESSION['error']); ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Modern Filters Card -->
<div class="modern-card mb-4">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-filter"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Filter Transactions</h5>
                <p class="header-subtitle">Search and filter transaction records</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">
                    <i class="fas fa-tag"></i> Transaction Type
                </label>
                <select class="form-select modern-input" name="type">
                    <option value="all" <?php echo ($_GET['type'] ?? '') === 'all' ? 'selected' : ''; ?>>All Transactions</option>
                    <option value="susu" <?php echo ($_GET['type'] ?? '') === 'susu' ? 'selected' : ''; ?>>Susu Collections</option>
                    <option value="loan" <?php echo ($_GET['type'] ?? '') === 'loan' ? 'selected' : ''; ?>>Loan Payments</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <i class="fas fa-calendar"></i> From Date
                </label>
                <input type="date" class="form-control modern-input" name="from_date" value="<?php echo $_GET['from_date'] ?? ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <i class="fas fa-calendar"></i> To Date
                </label>
                <input type="date" class="form-control modern-input" name="to_date" value="<?php echo $_GET['to_date'] ?? ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary modern-btn">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="/admin_transactions.php" class="btn btn-outline-secondary modern-btn-outline">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modern Transactions Table -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-table"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">All Transactions</h5>
                <p class="header-subtitle">Complete transaction history and management</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-tag me-1"></i>Type</th>
                        <th><i class="fas fa-calendar me-1"></i>Date</th>
                        <th><i class="fas fa-user me-1"></i>Client</th>
                        <th><i class="fas fa-money-bill-wave me-1"></i>Amount</th>
                        <th><i class="fas fa-user-tie me-1"></i>Agent</th>
                        <th><i class="fas fa-cogs me-1"></i>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td>
                            <span class="transaction-type-badge type-<?php echo str_replace('_', '-', $transaction['transaction_type']); ?>">
                                <?php 
                                $icon = match($transaction['transaction_type']) {
                                    'susu_collection' => 'fa-coins',
                                    'loan_payment' => 'fa-hand-holding-usd',
                                    'loan_disbursement' => 'fa-university',
                                    'savings_deposit' => 'fa-piggy-bank',
                                    'manual_transaction' => 'fa-edit',
                                    default => 'fa-money-bill-wave'
                                };
                                $label = match($transaction['transaction_type']) {
                                    'susu_collection' => 'Susu Collection',
                                    'loan_payment' => 'Loan Payment',
                                    'loan_disbursement' => 'Loan Disbursement',
                                    'savings_deposit' => 'Savings Deposit',
                                    'manual_transaction' => 'Manual Transaction',
                                    default => ucfirst(str_replace('_', ' ', $transaction['transaction_type']))
                                };
                                ?>
                                <i class="fas <?php echo $icon; ?>"></i>
                                <?php echo $label; ?>
                            </span>
                        </td>
                        <td><span class="date-value"><?php 
                            $dateTime = $transaction['transaction_date'] . ' ' . $transaction['transaction_time'];
                            echo date('M d, Y H:i', strtotime($dateTime)); 
                        ?></span></td>
                        <td><span class="client-name"><?php echo e($transaction['client_name']); ?></span></td>
                        <td><span class="amount-value">GHS <?php echo number_format($transaction['amount'], 2); ?></span></td>
                        <td><span class="agent-name"><?php echo e($transaction['agent_name'] ?? 'System Admin'); ?></span></td>
                        <td>
                            <div class="action-buttons">
                                <a href="/admin_transactions.php?action=edit&id=<?php echo $transaction['transaction_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary action-btn" 
                                   title="Edit Transaction"
                                   data-bs-toggle="tooltip" data-bs-placement="top">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="/admin_transactions.php?action=delete&id=<?php echo $transaction['transaction_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger action-btn"
                                   title="Delete Transaction"
                                   data-bs-toggle="tooltip" data-bs-placement="top"
                                   onclick="return confirm('⚠️ Are you sure you want to delete this transaction?\n\nThis action cannot be undone!')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-success action-btn" 
                                        title="Print Receipt"
                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                        onclick="printTransaction(<?php echo htmlspecialchars(json_encode($transaction)); ?>)">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
        </div>
    </div>
</div>

<script>
function printTransaction(transaction) {
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Transaction Receipt - ${transaction.reference_number || transaction.transaction_id}</title>
            <style>
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    margin: 0;
                    padding: 20px;
                    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                    min-height: 100vh;
                }
                .receipt-container {
                    max-width: 800px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                    overflow: hidden;
                }
                .header { 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-align: center; 
                    padding: 30px;
                    position: relative;
                }
                .receipt-title {
                    font-size: 32px;
                    font-weight: 700;
                    margin-bottom: 8px;
                    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                }
                .system-name {
                    font-size: 18px;
                    opacity: 0.9;
                    margin-bottom: 15px;
                }
                .reference-info {
                    font-size: 14px;
                    opacity: 0.8;
                    background: rgba(255,255,255,0.1);
                    padding: 8px 16px;
                    border-radius: 20px;
                    display: inline-block;
                }
                .divider {
                    height: 2px;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
                    margin: 20px 0;
                }
                .main-content {
                    padding: 40px;
                    background: white;
                }
                .info-sections {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 30px;
                    margin-bottom: 30px;
                }
                .info-section {
                    background: #f8f9fa;
                    border-radius: 12px;
                    padding: 25px;
                    border-left: 4px solid #667eea;
                }
                .section-title {
                    font-size: 18px;
                    font-weight: 700;
                    color: #495057;
                    margin-bottom: 20px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin: 12px 0;
                    padding: 8px 0;
                    border-bottom: 1px solid #e9ecef;
                }
                .info-row:last-child {
                    border-bottom: none;
                }
                .info-label {
                    font-weight: 600;
                    color: #495057;
                    font-size: 14px;
                }
                .info-value {
                    color: #212529;
                    font-size: 14px;
                    font-weight: 500;
                    text-align: right;
                    max-width: 60%;
                    word-wrap: break-word;
                }
                .amount-value {
                    font-size: 18px;
                    font-weight: 700;
                    color: #28a745;
                }
                .description-section {
                    margin-top: 30px;
                    padding: 25px;
                    background: #f8f9fa;
                    border-radius: 12px;
                    border-left: 4px solid #28a745;
                }
                .description-title {
                    font-size: 18px;
                    font-weight: 700;
                    color: #495057;
                    margin-bottom: 15px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .description-content {
                    background: white;
                    padding: 15px;
                    border-radius: 8px;
                    color: #495057;
                    font-size: 14px;
                    border: 1px solid #e9ecef;
                }
                .footer { 
                    background: #f8f9fa;
                    padding: 30px 40px;
                    text-align: center; 
                    border-top: 2px solid #e9ecef;
                }
                .generation-info {
                    margin-bottom: 20px;
                }
                .generation-info p {
                    margin: 5px 0;
                    font-size: 14px;
                    color: #6c757d;
                }
                .generation-info strong {
                    color: #495057;
                }
                .action-buttons {
                    display: flex;
                    justify-content: center;
                    gap: 15px;
                    margin-top: 20px;
                }
                .btn {
                    padding: 12px 24px;
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 14px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    text-decoration: none;
                    display: inline-block;
                }
                .btn-primary {
                    background: linear-gradient(135deg, #007bff, #0056b3);
                    color: white;
                }
                .btn-secondary {
                    background: linear-gradient(135deg, #6c757d, #495057);
                    color: white;
                }
                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                }
                @media print {
                    body { 
                        background: white;
                        margin: 0;
                        padding: 0;
                    }
                    .receipt-container {
                        box-shadow: none;
                        border-radius: 0;
                        max-width: none;
                    }
                    .header {
                        background: #667eea !important;
                        -webkit-print-color-adjust: exact;
                        color-adjust: exact;
                    }
                    .action-buttons {
                        display: none;
                    }
                }
                @media (max-width: 768px) {
                    .info-sections {
                        grid-template-columns: 1fr;
                        gap: 20px;
                    }
                    .main-content {
                        padding: 25px;
                    }
                    .action-buttons {
                        flex-direction: column;
                        align-items: center;
                    }
                }
            </style>
        </head>
        <body>
            <div class="receipt-container">
                <div class="header">
                    <div class="receipt-title">TRANSACTION RECEIPT</div>
                    <div class="system-name">Susu & Loan Management System</div>
                    <div class="reference-info">Transaction Reference: ${transaction.reference_number || transaction.transaction_id}</div>
                </div>
                
                <div class="main-content">
                    <div class="info-sections">
                        <div class="info-section">
                            <div class="section-title">Transaction Information</div>
                            <div class="info-row">
                                <span class="info-label">Date:</span>
                                <span class="info-value">${new Date(transaction.transaction_date || transaction.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Type:</span>
                                <span class="info-value">${transaction.transaction_type || transaction.type || 'N/A'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Amount:</span>
                                <span class="info-value amount-value">GHS ${parseFloat(transaction.amount || 0).toFixed(2)}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Reference:</span>
                                <span class="info-value">${transaction.reference_number || transaction.transaction_id}</span>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <div class="section-title">Client Information</div>
                            <div class="info-row">
                                <span class="info-label">Name:</span>
                                <span class="info-value">${transaction.client_name || 'N/A'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Client Code:</span>
                                <span class="info-value">${transaction.client_code || 'N/A'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-value">${transaction.client_email || 'N/A'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone:</span>
                                <span class="info-value">${transaction.client_phone || 'N/A'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Agent:</span>
                                <span class="info-value">${transaction.agent_name && transaction.agent_name !== 'N/A' && transaction.agent_name.trim() !== '' ? transaction.agent_name : 'System Admin'}</span>
                            </div>
                        </div>
                    </div>
                    
                    ${transaction.notes ? `
                    <div class="description-section">
                        <div class="description-title">Description</div>
                        <div class="description-content">${transaction.notes}</div>
                    </div>
                    ` : ''}
                </div>
                
                <div class="footer">
                    <div class="generation-info">
                        <p><strong>Generated on:</strong> ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })} at ${new Date().toLocaleTimeString()}</p>
                        <p><strong>Generated by:</strong> System Administrator</p>
                        <p style="font-size: 12px; color: #adb5bd;">This is a computer-generated receipt.</p>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
                        <button class="btn btn-secondary" onclick="window.close()">Back to Transactions</button>
                    </div>
                </div>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Export Report Function
function exportReport(format) {
    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const type = urlParams.get('type') || 'all';
    const fromDate = urlParams.get('from_date') || '';
    const toDate = urlParams.get('to_date') || '';
    
    // Show loading indicator
    const button = document.getElementById('exportDropdown');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
    button.disabled = true;
    
    // Create export URL with current filters
    const exportUrl = `/admin_transactions_export.php?format=${format}&type=${type}&from_date=${fromDate}&to_date=${toDate}`;
    
    if (format === 'pdf') {
        // For PDF, open in new window for printing
        window.open(exportUrl, '_blank');
    } else {
        // For Excel and CSV, download directly
        const link = document.createElement('a');
        link.href = exportUrl;
        link.download = `transactions_report_${new Date().toISOString().split('T')[0]}.${format}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // Reset button after a short delay
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 2000);
}
</script>

<style>
/* Transaction Management Page Styles */
.transactions-header {
	background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
	color: white;
	padding: 2rem;
	border-radius: 15px;
	margin-bottom: 2rem;
}

.page-title-section {
	margin-bottom: 0;
}

.page-title {
	font-size: 2rem;
	font-weight: 700;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
}

.page-subtitle {
	font-size: 1.1rem;
	opacity: 0.9;
	margin-bottom: 0;
	color: white !important;
}

.header-actions {
	display: flex;
	gap: 1rem;
	align-items: center;
}

.header-actions .dropdown-menu {
	border-radius: 10px;
	border: none;
	box-shadow: 0 4px 15px rgba(0,0,0,0.15);
	padding: 0.5rem 0;
}

.header-actions .dropdown-item {
	padding: 0.75rem 1rem;
	font-weight: 500;
	transition: all 0.3s ease;
}

.header-actions .dropdown-item:hover {
	background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
	color: #1976d2;
}

.header-actions .dropdown-item i {
	width: 20px;
}

/* Modern Alerts */
.modern-alert {
	border-radius: 10px;
	border: none;
	box-shadow: 0 4px 15px rgba(0,0,0,0.1);
	margin-bottom: 1.5rem;
	padding: 1rem 1.5rem;
	display: flex;
	align-items: center;
	justify-content: space-between;
}

.modern-alert.alert-success {
	background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
	color: #155724;
	border-left: 4px solid #28a745;
}

.modern-alert.alert-danger {
	background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
	color: #721c24;
	border-left: 4px solid #dc3545;
}

.alert-content {
	display: flex;
	align-items: center;
	gap: 0.75rem;
}

.alert-content i {
	font-size: 1.2rem;
}

/* Modern Cards */
.modern-card {
	background: white;
	border-radius: 15px;
	box-shadow: 0 4px 20px rgba(0,0,0,0.1);
	overflow: hidden;
	transition: all 0.3s ease;
	border: none;
}

.modern-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.card-header-modern {
	background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
	padding: 1.5rem;
	border-bottom: 1px solid #e9ecef;
}

.header-content {
	display: flex;
	align-items: center;
	gap: 1rem;
}

.header-icon {
	font-size: 1.5rem;
	color: #17a2b8;
	background: rgba(23, 162, 184, 0.1);
	padding: 0.75rem;
	border-radius: 10px;
	width: 50px;
	height: 50px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.header-text {
	flex: 1;
}

.header-title {
	font-size: 1.2rem;
	font-weight: 600;
	margin-bottom: 0.25rem;
	color: #2c3e50;
}

.header-subtitle {
	font-size: 0.9rem;
	color: #6c757d;
	margin-bottom: 0;
}

.card-body-modern {
	padding: 2rem;
}

/* Form Styling */
.form-label {
	font-weight: 600;
	color: #2c3e50;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.form-label i {
	color: #17a2b8;
	font-size: 0.9rem;
}

.modern-input {
	border: 2px solid #e9ecef;
	border-radius: 10px;
	padding: 0.75rem 1rem;
	font-size: 1rem;
	transition: all 0.3s ease;
	background: #f8f9fa;
}

.modern-input:focus {
	border-color: #17a2b8;
	background: white;
	box-shadow: 0 0 0 3px rgba(23, 162, 184, 0.1);
	outline: none;
}

/* Modern Table */
.modern-table {
	border: none;
	margin-bottom: 0;
}

.modern-table thead th {
	border: none;
	background: #f8f9fa;
	color: #6c757d;
	font-weight: 600;
	font-size: 0.9rem;
	padding: 1rem 0.75rem;
	border-bottom: 2px solid #e9ecef;
}

.modern-table tbody td {
	border: none;
	padding: 1rem 0.75rem;
	border-bottom: 1px solid #f1f3f4;
	vertical-align: middle;
}

.modern-table tbody tr:hover {
	background: #f8f9fa;
	transform: scale(1.01);
	transition: all 0.3s ease;
}

/* Table Elements */
.transaction-type-badge {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	padding: 0.5rem 0.75rem;
	border-radius: 20px;
	font-size: 0.8rem;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.type-susu-collection {
	background: linear-gradient(135deg, #007bff, #0056b3);
	color: white;
}

.type-loan-payment {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	color: white;
}

.type-loan-disbursement {
	background: linear-gradient(135deg, #ffc107, #e0a800);
	color: #212529;
}

.type-savings-deposit {
	background: linear-gradient(135deg, #20c997, #17a2b8);
	color: white;
}

.type-manual-transaction {
	background: linear-gradient(135deg, #6c757d, #495057);
	color: white;
}

.date-value {
	background: #f8f9fa;
	color: #495057;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.client-name {
	font-weight: 500;
	color: #495057;
}


.amount-value {
	background: linear-gradient(135deg, #17a2b8, #138496);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.agent-name {
	background: linear-gradient(135deg, #6f42c1, #5a32a3);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}


/* Action Buttons */
.action-buttons {
	display: flex;
	gap: 0.5rem;
}

.action-btn {
	border-radius: 8px;
	padding: 0.5rem 0.75rem;
	transition: all 0.3s ease;
	border-width: 2px;
	font-weight: 500;
}

.action-btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.action-btn.btn-outline-primary:hover {
	background: #007bff;
	border-color: #007bff;
	color: white;
}

.action-btn.btn-outline-danger:hover {
	background: #dc3545;
	border-color: #dc3545;
	color: white;
}

.action-btn.btn-outline-success:hover {
	background: #28a745;
	border-color: #28a745;
	color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
	.modern-table {
		font-size: 0.8rem;
	}
	
	.modern-table thead th,
	.modern-table tbody td {
		padding: 0.5rem 0.25rem;
	}
	
	.transaction-type-badge {
		font-size: 0.7rem;
		padding: 0.25rem 0.5rem;
	}
	
	.action-buttons {
		flex-direction: column;
		gap: 0.25rem;
	}
	
	.action-btn {
		padding: 0.25rem 0.5rem;
		font-size: 0.7rem;
	}
	
}

@media (max-width: 576px) {
	.modern-table {
		display: block;
		overflow-x: auto;
		white-space: nowrap;
	}
	
	.transactions-header {
		padding: 1rem;
	}
	
	.page-title {
		font-size: 1.5rem;
	}
	
	.header-actions {
		flex-direction: column;
		gap: 0.5rem;
	}
}

/* Enhanced Table Styling */
.modern-table tbody tr {
	transition: all 0.3s ease;
}

.modern-table tbody tr:nth-child(even) {
	background-color: #f8f9fa;
}

.modern-table tbody tr:nth-child(odd) {
	background-color: white;
}

.modern-table tbody tr:hover {
	background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%) !important;
	transform: scale(1.005);
	box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Modern Buttons */
.modern-btn {
	background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
	border: none;
	border-radius: 10px;
	padding: 0.75rem 1.5rem;
	font-weight: 600;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	gap: 0.5rem;
	color: white;
	text-decoration: none;
}

.modern-btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
	background: linear-gradient(135deg, #1e7e34 0%, #155724 100%);
	color: white;
	text-decoration: none;
}

.modern-btn-outline {
	border: 2px solid #6c757d;
	border-radius: 10px;
	padding: 0.75rem 1.5rem;
	font-weight: 600;
	transition: all 0.3s ease;
	background: transparent;
	color: #6c757d;
	text-decoration: none;
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.modern-btn-outline:hover {
	background: #6c757d;
	color: white;
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
	text-decoration: none;
}

/* Responsive Design */
@media (max-width: 768px) {
	.transactions-header {
		padding: 1.5rem;
		text-align: center;
	}
	
	.page-title {
		font-size: 1.5rem;
		justify-content: center;
	}
	
	.header-actions {
		flex-direction: column;
		gap: 0.5rem;
		width: 100%;
	}
	
	.modern-btn, .btn-light, .modern-btn-outline {
		width: 100%;
		justify-content: center;
	}
	
	.card-body-modern {
		padding: 1.5rem;
	}
	
	.modern-table {
		font-size: 0.85rem;
	}
	
	.modern-table thead th,
	.modern-table tbody td {
		padding: 0.75rem 0.5rem;
	}
	
	.action-buttons {
		flex-direction: column;
		gap: 0.25rem;
	}
	
	.action-btn {
		width: 100%;
		justify-content: center;
	}
}

/* Animation */
@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(20px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.modern-card {
	animation: fadeInUp 0.6s ease-out;
}

.modern-alert {
	animation: fadeInUp 0.4s ease-out;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>