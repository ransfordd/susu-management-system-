<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Withdrawal Header -->
<div class="withdrawal-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-hand-holding-usd text-primary me-2"></i>
                    Withdrawal Management
                </h2>
                <p class="page-subtitle">Process client withdrawals and Susu payouts</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/admin_manual_transactions.php" class="btn btn-primary modern-btn">
                    <i class="fas fa-list"></i> View All Withdrawals
                </a>
                <a href="/admin_reports.php?report_type=withdrawals" class="btn btn-success modern-btn">
                    <i class="fas fa-chart-bar"></i> Withdrawals Report
                </a>
                <a href="/index.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back
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

<!-- Withdrawal Form -->
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Process New Withdrawal</h5>
                        <p class="header-subtitle">Enter withdrawal details below</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern">
                <form method="POST" action="/admin_withdrawal.php?action=process">
                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i>Select Client
                        </label>
                        <select name="client_id" class="form-select modern-input" required>
                            <option value="">Choose client...</option>
                            <?php foreach ($clients as $client): ?>
                            <option value="<?php echo e($client['id']); ?>">
                                <?php echo e($client['client_name']); ?> (<?php echo e($client['client_code']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-list me-1"></i>Withdrawal Type
                        </label>
                        <select name="withdrawal_type" class="form-select modern-input" required>
                            <option value="susu_payout">Susu Payout</option>
                            <option value="savings_withdrawal">Savings Withdrawal</option>
                            <option value="emergency_withdrawal">Emergency Withdrawal</option>
                            <option value="refund">Refund</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-money-bill-wave me-1"></i>Amount (GHS)
                        </label>
                        <input type="number" step="0.01" name="amount" class="form-control modern-input" placeholder="0.00" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-hashtag me-1"></i>Reference Number (Optional)
                        </label>
                        <input type="text" name="reference" class="form-control modern-input" placeholder="Auto-generated if left blank">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">
                            <i class="fas fa-comment me-1"></i>Description
                        </label>
                        <textarea name="description" class="form-control modern-input" rows="3" placeholder="Enter withdrawal details..." required></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary modern-btn">
                            <i class="fas fa-check-circle"></i> Process Withdrawal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Recent Withdrawals -->
    <div class="col-lg-6">
        <div class="modern-card">
            <div class="card-header-modern">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="header-text">
                        <h5 class="header-title">Recent Withdrawals</h5>
                        <p class="header-subtitle">Last 10 withdrawal transactions</p>
                    </div>
                </div>
            </div>
            <div class="card-body-modern" style="max-height: 600px; overflow-y: auto;">
                <?php if (empty($recentWithdrawals)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No recent withdrawals found</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentWithdrawals as $withdrawal): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <i class="fas fa-user-circle text-primary me-1"></i>
                                    <?php echo e($withdrawal['client_name']); ?>
                                </h6>
                                <p class="mb-1 text-muted small"><?php echo e($withdrawal['description']); ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-hashtag"></i> <?php echo e($withdrawal['reference']); ?> |
                                    <i class="fas fa-calendar"></i> <?php echo e(date('M j, Y H:i', strtotime($withdrawal['created_at']))); ?>
                                </small>
                            </div>
                            <div class="text-end ms-3">
                                <span class="badge bg-warning text-dark">
                                    GHS <?php echo e(number_format($withdrawal['amount'], 2)); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Withdrawal Page Styles */
.withdrawal-header {
	background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
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
	gap: 0.5rem;
	flex-wrap: wrap;
	justify-content: flex-end;
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
	height: 100%;
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
	padding: 2rem;
}

/* Form Elements */
.form-label {
	font-weight: 600;
	color: #495057;
	margin-bottom: 0.5rem;
	display: flex;
	align-items: center;
}

.modern-input {
	border: 2px solid #e9ecef;
	border-radius: 10px;
	padding: 0.75rem 1rem;
	transition: all 0.3s ease;
	font-size: 0.95rem;
}

.modern-input:focus {
	border-color: #ffc107;
	box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
	outline: none;
}

/* Modern Buttons */
.modern-btn {
	border: none;
	border-radius: 10px;
	padding: 0.75rem 1.5rem;
	font-weight: 600;
	transition: all 0.3s ease;
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	text-decoration: none;
}

.modern-btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
	text-decoration: none;
}

.btn-primary.modern-btn {
	background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
	color: white;
}

.btn-primary.modern-btn:hover {
	background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
	color: white;
}

.btn-success.modern-btn {
	background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
	color: white;
}

.btn-success.modern-btn:hover {
	background: linear-gradient(135deg, #1e7e34 0%, #155724 100%);
	color: white;
}

/* List Group */
.list-group-item {
	border: none;
	border-bottom: 1px solid #f1f3f4;
	padding: 1rem;
}

.list-group-item:last-child {
	border-bottom: none;
}

.list-group-item:hover {
	background: #f8f9fa;
}

/* Responsive Design */
@media (max-width: 768px) {
	.withdrawal-header {
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
	
	.card-body-modern {
		padding: 1.5rem;
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








