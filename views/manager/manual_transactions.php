<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['manager']);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Manual Transactions Header -->
<div class="management-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-exchange-alt text-primary me-2"></i>
                    Withdrawal Transactions
                </h2>
                <p class="page-subtitle">Process and manage client withdrawals</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/admin_manual_transactions.php?action=create&type=withdrawal" class="btn btn-warning modern-btn">
                    <i class="fas fa-money-bill-wave"></i> New Withdrawal
                </a>
                <a href="/index.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
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

<!-- Recent Manual Transactions -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-table"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Recent Withdrawal Transactions</h5>
                <p class="header-subtitle">View and manage withdrawal transactions</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag me-1"></i>Reference</th>
                        <th><i class="fas fa-user me-1"></i>Client</th>
                        <th><i class="fas fa-tag me-1"></i>Type</th>
                        <th><i class="fas fa-money-bill-wave me-1"></i>Amount</th>
                        <th><i class="fas fa-comment me-1"></i>Description</th>
                        <th><i class="fas fa-calendar me-1"></i>Date</th>
                        <th><i class="fas fa-cogs me-1"></i>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $transaction): ?>
                    <tr>
                        <td><code><?php echo e($transaction['reference']); ?></code></td>
                        <td>
                            <div><strong><?php echo e($transaction['client_name']); ?></strong></div>
                            <small class="text-muted"><?php echo e($transaction['client_code']); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-warning">
                                <i class="fas fa-arrow-up me-1"></i>
                                <?php echo e(ucfirst(str_replace('_', ' ', $transaction['transaction_type']))); ?>
                            </span>
                        </td>
                        <td><span class="amount-value">GHS <?php echo e(number_format($transaction['amount'], 2)); ?></span></td>
                        <td><?php echo e($transaction['description']); ?></td>
                        <td><small><?php echo e(date('M j, Y H:i', strtotime($transaction['created_at']))); ?></small></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-outline-primary action-btn" onclick="printTransaction('<?php echo e($transaction['reference']); ?>')" title="Print Receipt">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger action-btn" onclick="deleteTransaction(<?php echo e($transaction['id']); ?>)" title="Delete">
                                    <i class="fas fa-trash"></i>
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

<script>
function printTransaction(ref) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Withdrawal Receipt - ${ref}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .details { margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; margin: 5px 0; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>SUSU COLLECTION AGENCY</h2>
                <p>Withdrawal Receipt</p>
            </div>
            <div class="details">
                <div class="detail-row">
                    <span><strong>Reference:</strong></span>
                    <span>${ref}</span>
                </div>
                <div class="detail-row">
                    <span><strong>Date:</strong></span>
                    <span><?php echo date('M j, Y H:i:s'); ?></span>
                </div>
            </div>
            <div class="footer">
                <p>Thank you for your business!</p>
                <p>Generated on <?php echo date('M j, Y H:i:s'); ?></p>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function deleteTransaction(id) {
    if (confirm('Are you sure you want to delete this withdrawal transaction? This action cannot be undone.')) {
        window.location.href = `/admin_manual_transactions.php?action=delete&id=${id}`;
    }
}
</script>

<style>
/* Manual Transactions Page Styles */
.management-header {
	background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
	color: #212529;
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
}

.header-actions {
	display: flex;
	gap: 0.75rem;
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
	color: #ffc107;
	background: rgba(255, 193, 7, 0.1);
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
	padding: 0;
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
.amount-value {
	background: linear-gradient(135deg, #ffc107, #e0a800);
	color: #212529;
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

/* Modern Buttons */
.modern-btn {
	background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
	border: none;
	border-radius: 10px;
	padding: 0.75rem 1.5rem;
	font-weight: 600;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	gap: 0.5rem;
	color: #212529;
	text-decoration: none;
}

.modern-btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(255, 193, 7, 0.3);
	background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
	color: #212529;
	text-decoration: none;
}

/* Responsive Design */
@media (max-width: 768px) {
	.management-header {
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
		margin-top: 1rem;
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










