<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

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
                <button class="btn btn-success modern-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Print All
                </button>
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
                        <th><i class="fas fa-receipt me-1"></i>Receipt</th>
                        <th><i class="fas fa-user-tie me-1"></i>Agent</th>
                        <th><i class="fas fa-sticky-note me-1"></i>Notes</th>
                        <th><i class="fas fa-cogs me-1"></i>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td>
                            <span class="transaction-type-badge type-<?php echo str_replace('_', '-', $transaction['transaction_type']); ?>">
                                <i class="fas fa-<?php echo $transaction['transaction_type'] === 'susu_collection' ? 'coins' : ($transaction['transaction_type'] === 'loan_payment' ? 'hand-holding-usd' : 'money-bill-wave'); ?>"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?>
                            </span>
                        </td>
                        <td><span class="date-value"><?php 
                            $dateTime = $transaction['transaction_date'] . ' ' . $transaction['transaction_time'];
                            echo date('M d, Y H:i', strtotime($dateTime)); 
                        ?></span></td>
                        <td><span class="client-name"><?php echo e($transaction['client_name']); ?></span></td>
                        <td><span class="amount-value">GHS <?php echo number_format($transaction['amount'], 2); ?></span></td>
                        <td><code><?php echo e($transaction['receipt_number'] ?? ''); ?></code></td>
                        <td><span class="agent-code"><?php echo e($transaction['agent_code'] ?? 'N/A'); ?></span></td>
                        <td><span class="notes-text"><?php echo e($transaction['notes'] ?? ''); ?></span></td>
                        <td>
                            <div class="action-buttons">
                                <a href="/admin_transactions.php?action=edit&id=<?php echo $transaction['transaction_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary action-btn" 
                                   title="Edit Transaction">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="/admin_transactions.php?action=delete&id=<?php echo $transaction['transaction_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger action-btn"
                                   title="Delete Transaction"
                                   onclick="return confirm('Are you sure you want to delete this transaction?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-info action-btn" 
                                        title="Print Transaction"
                                        onclick="printTransaction(<?php echo $transaction['transaction_id']; ?>)">
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
function printTransaction(id) {
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Transaction Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
                .transaction-details { margin: 20px 0; }
                .detail-row { margin: 10px 0; }
                .label { font-weight: bold; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>Transaction Receipt</h2>
                <p>Generated on: ${new Date().toLocaleString()}</p>
            </div>
            <div class="transaction-details">
                <div class="detail-row">
                    <span class="label">Transaction ID:</span> ${id}
                </div>
                <div class="detail-row">
                    <span class="label">Date:</span> ${new Date().toLocaleDateString()}
                </div>
                <div class="detail-row">
                    <span class="label">Amount:</span> GHS ${document.querySelector(`tr:has(td:contains('${id}')) td:nth-child(4)`).textContent}
                </div>
            </div>
            <div class="footer">
                <p>This is a computer-generated receipt.</p>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
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

.agent-code {
	background: #e9ecef;
	color: #495057;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.notes-text {
	color: #6c757d;
	font-size: 0.9rem;
	max-width: 200px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
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

.action-btn.btn-outline-info:hover {
	background: #17a2b8;
	border-color: #17a2b8;
	color: white;
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