<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['agent']);
include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Applications Header -->
<div class="applications-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-file-alt text-primary me-2"></i>
                    Loan Applications
                </h2>
                <p class="page-subtitle">Manage your submitted loan applications</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/agent_app_create.php" class="btn btn-primary modern-btn">
                    <i class="fas fa-plus"></i> New Application
                </a>
                <a href="/index.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modern Applications Card -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Applications List</h5>
                <p class="header-subtitle">View and track your loan applications</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <?php if (empty($apps)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h5 class="empty-title">No Applications Yet</h5>
                <p class="empty-text">You haven't submitted any loan applications yet. Create your first application to get started.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>#</th>
                            <th><i class="fas fa-file-alt me-1"></i>Application #</th>
                            <th><i class="fas fa-user me-1"></i>Client</th>
                            <th><i class="fas fa-box me-1"></i>Product</th>
                            <th><i class="fas fa-money-bill-wave me-1"></i>Requested</th>
                            <th><i class="fas fa-calendar-alt me-1"></i>Term</th>
                            <th><i class="fas fa-toggle-on me-1"></i>Status</th>
                            <th><i class="fas fa-calendar me-1"></i>Applied</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apps as $a): ?>
                        <tr>
                            <td>
                                <span class="app-id"><?php echo htmlspecialchars($a['id']); ?></span>
                            </td>
                            <td>
                                <span class="application-number"><?php echo htmlspecialchars($a['application_number']); ?></span>
                            </td>
                            <td>
                                <span class="client-id"><?php echo htmlspecialchars($a['client_id']); ?></span>
                            </td>
                            <td>
                                <span class="product-id"><?php echo htmlspecialchars($a['loan_product_id']); ?></span>
                            </td>
                            <td>
                                <span class="amount-value">GHS <?php echo htmlspecialchars(number_format($a['requested_amount'], 2)); ?></span>
                            </td>
                            <td>
                                <span class="term-value"><?php echo htmlspecialchars($a['requested_term_months']); ?> months</span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($a['application_status']); ?>">
                                    <i class="fas fa-<?php echo $a['application_status'] === 'approved' ? 'check-circle' : ($a['application_status'] === 'pending' ? 'clock' : 'times-circle'); ?>"></i>
                                    <?php echo htmlspecialchars(ucfirst($a['application_status'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="date-value"><?php echo htmlspecialchars(date('M d, Y', strtotime($a['applied_date']))); ?></span>
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
/* Applications List Page Styles */
.applications-header {
	background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
	color: #28a745;
	background: rgba(40, 167, 69, 0.1);
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
.app-id {
	background: linear-gradient(135deg, #28a745, #20c997);
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

.application-number {
	background: #f8f9fa;
	color: #495057;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.client-id {
	background: linear-gradient(135deg, #007bff, #0056b3);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.product-id {
	background: linear-gradient(135deg, #fd7e14, #e55a00);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.amount-value {
	background: linear-gradient(135deg, #17a2b8, #138496);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.term-value {
	background: #e9ecef;
	color: #495057;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.date-value {
	background: #f8f9fa;
	color: #495057;
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

.status-approved {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	color: white;
}

.status-pending {
	background: linear-gradient(135deg, #ffc107, #e0a800);
	color: #212529;
}

.status-rejected {
	background: linear-gradient(135deg, #dc3545, #c82333);
	color: white;
}

/* Modern Buttons */
.modern-btn {
	background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
	background: linear-gradient(135deg, #20c997 0%, #1e7e34 100%);
	color: white;
	text-decoration: none;
}

/* Responsive Design */
@media (max-width: 768px) {
	.applications-header {
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
	
	.modern-btn, .btn-light {
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
	
	.app-id {
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







