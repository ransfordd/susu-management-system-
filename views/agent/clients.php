<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

use function Auth\requireRole;

requireRole(['agent']);
$pdo = Database::getConnection();

// Get agent ID
$agentRow = $pdo->prepare('SELECT a.id FROM agents a WHERE a.user_id = :uid');
$agentRow->execute([':uid' => (int)$_SESSION['user']['id']]);
$agentData = $agentRow->fetch();
if (!$agentData) {
    echo 'Agent not found. Please contact administrator.';
    exit;
}
$agentId = (int)$agentData['id'];

// Get clients assigned to this agent
$stmt = $pdo->prepare('
    SELECT c.*, u.first_name, u.last_name, u.email, u.phone, u.status as user_status
    FROM clients c 
    JOIN users u ON c.user_id = u.id
    WHERE c.agent_id = :agent_id
    ORDER BY c.client_code
');
$stmt->execute([':agent_id' => $agentId]);
$clients = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Modern My Clients Header -->
<div class="clients-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-users text-primary me-2"></i>
                    My Clients
                </h2>
                <p class="page-subtitle">Manage your assigned clients and their activities</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="header-actions">
                <a href="/views/agent/dashboard.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
<!-- Modern Clients Card -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">Client List (<?php echo count($clients); ?> clients)</h5>
                <p class="header-subtitle">View and manage your assigned clients</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <?php if (empty($clients)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h5 class="empty-title">No Clients Assigned</h5>
                <p class="empty-text">No clients have been assigned to you yet. Contact your administrator for client assignments.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>Client Code</th>
                            <th><i class="fas fa-user me-1"></i>Name</th>
                            <th><i class="fas fa-phone me-1"></i>Contact</th>
                            <th><i class="fas fa-money-bill-wave me-1"></i>Daily Amount</th>
                            <th><i class="fas fa-toggle-on me-1"></i>Status</th>
                            <th><i class="fas fa-cogs me-1"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>
                                <span class="client-code"><?php echo htmlspecialchars($client['client_code']); ?></span>
                            </td>
                            <td>
                                <span class="client-name"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></span>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <div class="phone-number">
                                        <i class="fas fa-phone"></i>
                                        <?php echo htmlspecialchars($client['phone']); ?>
                                    </div>
                                    <div class="email-address">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($client['email']); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="amount-value">GHS <?php echo number_format($client['daily_deposit_amount'], 2); ?></span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $client['status']; ?>">
                                    <i class="fas fa-<?php echo $client['status'] === 'active' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                                    <?php echo htmlspecialchars(ucfirst($client['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="/views/agent/collect.php?client_id=<?php echo $client['id']; ?>&account_type=susu_collection&amount=<?php echo $client['daily_deposit_amount']; ?>" 
                                       class="btn btn-sm btn-outline-primary action-btn" 
                                       title="Collect Payment">
                                        <i class="fas fa-hand-holding-usd"></i>
                                    </a>
                                    <a href="/views/agent/susu_calendar.php?client_id=<?php echo $client['id']; ?>" 
                                       class="btn btn-sm btn-outline-info action-btn"
                                       title="View Calendar">
                                        <i class="fas fa-calendar"></i>
                                    </a>
                                    <a href="/views/agent/susu_tracker.php?client_id=<?php echo $client['id']; ?>" 
                                       class="btn btn-sm btn-outline-success action-btn"
                                       title="View Tracker">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                    <a href="/agent_app_create.php?client_id=<?php echo $client['id']; ?>" 
                                       class="btn btn-sm btn-outline-warning action-btn"
                                       title="Apply for Loan">
                                        <i class="fas fa-file-alt"></i>
                                    </a>
                                </div>
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
/* My Clients Page Styles */
.clients-header {
	background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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
	color: #007bff;
	background: rgba(0, 123, 255, 0.1);
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
.client-code {
	background: linear-gradient(135deg, #007bff, #0056b3);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.client-name {
	font-weight: 500;
	color: #495057;
}

.contact-info {
	display: flex;
	flex-direction: column;
	gap: 0.25rem;
}

.phone-number, .email-address {
	display: flex;
	align-items: center;
	gap: 0.5rem;
	font-size: 0.85rem;
	color: #6c757d;
}

.phone-number i, .email-address i {
	color: #007bff;
	font-size: 0.75rem;
}

.amount-value {
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

.status-active {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	color: white;
}

.status-inactive {
	background: linear-gradient(135deg, #ffc107, #e0a800);
	color: #212529;
}

/* Action Buttons */
.action-buttons {
	display: flex;
	gap: 0.5rem;
	flex-wrap: wrap;
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

.action-btn.btn-outline-info:hover {
	background: #17a2b8;
	border-color: #17a2b8;
	color: white;
}

.action-btn.btn-outline-success:hover {
	background: #28a745;
	border-color: #28a745;
	color: white;
}

.action-btn.btn-outline-warning:hover {
	background: #ffc107;
	border-color: #ffc107;
	color: #212529;
}

/* Responsive Design */
@media (max-width: 768px) {
	.clients-header {
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
	
	.contact-info {
		gap: 0.5rem;
	}
	
	.phone-number, .email-address {
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