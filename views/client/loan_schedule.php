<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['client']);
include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Loan Schedule Header -->
<div class="loan-schedule-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-calendar-check text-primary me-2"></i>
                    Loan Schedule
                </h2>
                <p class="page-subtitle">Track your loan payment schedule and progress</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/index.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Modern Loan Schedule Card -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-table"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Payment Schedule</h5>
                <p class="header-subtitle">Your loan payment timeline and status</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <?php if (empty($rows)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h5 class="empty-title">No Active Loan</h5>
                <p class="empty-text">You don't have an active loan. Apply for a loan to see your payment schedule here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>#</th>
                            <th><i class="fas fa-calendar me-1"></i>Due Date</th>
                            <th><i class="fas fa-coins me-1"></i>Principal</th>
                            <th><i class="fas fa-percentage me-1"></i>Interest</th>
                            <th><i class="fas fa-money-bill-wave me-1"></i>Total Due</th>
                            <th><i class="fas fa-hand-holding-usd me-1"></i>Paid</th>
                            <th><i class="fas fa-toggle-on me-1"></i>Status</th>
                            <th><i class="fas fa-exclamation-triangle me-1"></i>Overdue Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td>
                                <span class="payment-number"><?php echo e($r['payment_number']); ?></span>
                            </td>
                            <td>
                                <span class="date-value"><?php echo e(date('M d, Y', strtotime($r['due_date']))); ?></span>
                            </td>
                            <td>
                                <span class="principal-amount">GHS <?php echo e(number_format($r['principal_amount'],2)); ?></span>
                            </td>
                            <td>
                                <span class="interest-amount">GHS <?php echo e(number_format($r['interest_amount'],2)); ?></span>
                            </td>
                            <td>
                                <span class="total-due">GHS <?php echo e(number_format($r['total_due'],2)); ?></span>
                            </td>
                            <td>
                                <span class="amount-paid">GHS <?php echo e(number_format($r['amount_paid'],2)); ?></span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($r['payment_status']); ?>">
                                    <i class="fas fa-<?php echo $r['payment_status'] === 'paid' ? 'check-circle' : ($r['payment_status'] === 'pending' ? 'clock' : 'times-circle'); ?>"></i>
                                    <?php echo e(ucfirst($r['payment_status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($r['days_overdue'] > 0): ?>
                                    <span class="overdue-days"><?php echo e($r['days_overdue']); ?> days</span>
                                <?php else: ?>
                                    <span class="no-overdue">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Loan Schedule Page Styles */
.loan-schedule-header {
	background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
	color: #dc3545;
	background: rgba(220, 53, 69, 0.1);
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

/* Empty State */
.empty-state {
	text-align: center;
	padding: 3rem 2rem;
	color: #6c757d;
}

.empty-icon {
	font-size: 4rem;
	color: #dee2e6;
	margin-bottom: 1rem;
}

.empty-title {
	font-size: 1.5rem;
	font-weight: 600;
	color: #495057;
	margin-bottom: 0.5rem;
}

.empty-text {
	font-size: 1rem;
	color: #6c757d;
	margin-bottom: 0;
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
.payment-number {
	background: linear-gradient(135deg, #dc3545, #c82333);
	color: white;
	padding: 0.5rem 0.75rem;
	border-radius: 50%;
	font-size: 0.9rem;
	font-weight: 600;
	min-width: 35px;
	height: 35px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
}

.date-value {
	background: #f8f9fa;
	color: #495057;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.principal-amount {
	background: linear-gradient(135deg, #007bff, #0056b3);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.interest-amount {
	background: linear-gradient(135deg, #ffc107, #e0a800);
	color: #212529;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.total-due {
	background: linear-gradient(135deg, #17a2b8, #138496);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.amount-paid {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.status-badge {
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

.status-paid {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	color: white;
}

.status-pending {
	background: linear-gradient(135deg, #ffc107, #e0a800);
	color: #212529;
}

.status-overdue {
	background: linear-gradient(135deg, #dc3545, #c82333);
	color: white;
}

.overdue-days {
	background: linear-gradient(135deg, #dc3545, #c82333);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.no-overdue {
	color: #6c757d;
	font-size: 0.85rem;
	font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
	.loan-schedule-header {
		padding: 1.5rem;
		text-align: center;
	}
	
	.page-title {
		font-size: 1.5rem;
		justify-content: center;
	}
	
	.card-body-modern {
		padding: 1.5rem;
	}
	
	.header-content {
		flex-direction: column;
		text-align: center;
		gap: 0.5rem;
	}
	
	.header-icon {
		margin: 0 auto;
	}
	
	.modern-table {
		font-size: 0.85rem;
	}
	
	.modern-table thead th,
	.modern-table tbody td {
		padding: 0.75rem 0.5rem;
	}
	
	.payment-number {
		min-width: 30px;
		height: 30px;
		font-size: 0.8rem;
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
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>









