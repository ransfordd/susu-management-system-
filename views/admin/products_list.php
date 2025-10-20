<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin', 'manager']);
include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern Loan Products Header -->
<div class="products-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-box text-primary me-2"></i>
                    Loan Products Management
                </h2>
                <p class="page-subtitle">Manage loan products and interest rates</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/admin_product_create.php" class="btn btn-primary modern-btn">
                    <i class="fas fa-plus"></i> New Product
                </a>
                <a href="/index.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modern Products Table -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-table"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">All Loan Products</h5>
                <p class="header-subtitle">Complete list of available loan products</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag me-1"></i>ID</th>
                        <th><i class="fas fa-tag me-1"></i>Product Name</th>
                        <th><i class="fas fa-code me-1"></i>Code</th>
                        <th><i class="fas fa-percentage me-1"></i>Interest Rate</th>
                        <th><i class="fas fa-calculator me-1"></i>Type</th>
                        <th><i class="fas fa-money-bill-wave me-1"></i>Amount Range</th>
                        <th><i class="fas fa-calendar-alt me-1"></i>Terms</th>
                        <th><i class="fas fa-cogs me-1"></i>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><span class="product-id"><?php echo e($p['id']); ?></span></td>
                        <td>
                            <div class="product-name">
                                <strong><?php echo e($p['product_name']); ?></strong>
                            </div>
                        </td>
                        <td><code><?php echo e($p['product_code']); ?></code></td>
                        <td><span class="rate-value"><?php echo e($p['interest_rate']); ?>%</span></td>
                        <td><span class="type-badge"><?php echo e($p['interest_type']); ?></span></td>
                        <td>
                            <span class="amount-range">
                                GHS <?php echo e(number_format($p['min_amount'])); ?> - 
                                GHS <?php echo e(number_format($p['max_amount'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="term-range">
                                <?php echo e($p['min_term_months']); ?> - 
                                <?php echo e($p['max_term_months']); ?> months
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="/admin_product_edit.php?id=<?php echo e($p['id']); ?>" 
                                   class="btn btn-sm btn-outline-primary action-btn" 
                                   title="Edit Product">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="/admin_product_delete.php?id=<?php echo e($p['id']); ?>" 
                                   class="btn btn-sm btn-outline-danger action-btn" 
                                   title="Delete Product"
                                   onclick="return confirm('Are you sure you want to delete this product?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Loan Products Page Styles */
.products-header {
	background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
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
	color: #fd7e14;
	background: rgba(253, 126, 20, 0.1);
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
.product-id {
	background: #e9ecef;
	color: #495057;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.product-name strong {
	color: #2c3e50;
	font-weight: 600;
}

.rate-value {
	background: linear-gradient(135deg, #fd7e14, #e55a00);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.type-badge {
	background: #f8f9fa;
	color: #495057;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
	text-transform: capitalize;
}

.amount-range, .term-range {
	background: #f8f9fa;
	color: #495057;
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

/* Modern Button */
.modern-btn {
	background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
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
	box-shadow: 0 8px 25px rgba(253, 126, 20, 0.3);
	background: linear-gradient(135deg, #e55a00 0%, #cc4a00 100%);
	color: white;
	text-decoration: none;
}

/* Responsive Design */
@media (max-width: 768px) {
	.products-header {
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
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>







