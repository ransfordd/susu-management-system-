<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/controllers/NotificationController.php';

use function Auth\requireRole;
use Controllers\NotificationController;

requireRole(['business_admin']);

$pdo = Database::getConnection();

// Handle application approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $applicationId = (int)$_POST['application_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        // Create loan from application
        $app = $pdo->prepare('SELECT * FROM loan_applications WHERE id = :id');
        $app->execute([':id' => $applicationId]);
        $application = $app->fetch();
        
        if ($application) {
            $pdo->beginTransaction();
            try {
                // Update application status
                $pdo->prepare('UPDATE loan_applications SET application_status = "approved", approved_date = CURRENT_DATE() WHERE id = :id')
                    ->execute([':id' => $applicationId]);
                
                // Create loan
                $pdo->prepare('INSERT INTO loans (client_id, product_id, loan_amount, interest_rate, term_months, disbursement_date, loan_status, current_balance, application_id) VALUES (:c, :p, :amt, :rate, :term, CURRENT_DATE(), "active", :bal, :app)')
                    ->execute([
                        ':c' => $application['client_id'],
                        ':p' => $application['loan_product_id'],
                        ':amt' => $application['requested_amount'],
                        ':rate' => 24.0, // Default rate
                        ':term' => $application['requested_term_months'],
                        ':bal' => $application['requested_amount'],
                        ':app' => $applicationId
                    ]);
                
                // Send notifications
                
                // Get client and agent user IDs
                $userQuery = $pdo->prepare('
                    SELECT c.id as client_user_id, a.id as agent_user_id,
                           CONCAT(c.first_name, " ", c.last_name) as client_name,
                           CONCAT(a.first_name, " ", a.last_name) as agent_name
                    FROM clients cl
                    JOIN users c ON cl.user_id = c.id
                    JOIN agents ag ON cl.agent_id = ag.id
                    JOIN users a ON ag.user_id = a.id
                    WHERE cl.id = :client_id
                ');
                $userQuery->execute([':client_id' => $application['client_id']]);
                $userData = $userQuery->fetch();
                
                if ($userData) {
                    // Notify the client
                    NotificationController::createNotification(
                        $userData['client_user_id'],
                        'loan_approval',
                        'Loan Application Approved',
                        'Congratulations! Your loan application for GHS ' . number_format($application['requested_amount'], 2) . ' has been approved.',
                        $applicationId
                    );
                    
                    // Notify the agent
                    NotificationController::createNotification(
                        $userData['agent_user_id'],
                        'loan_approval',
                        'Client Loan Application Approved',
                        'The loan application for ' . $userData['client_name'] . ' (GHS ' . number_format($application['requested_amount'], 2) . ') has been approved.',
                        $applicationId
                    );
                }
                
                $pdo->commit();
                header('Location: /admin_applications.php?success=1');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                header('Location: /admin_applications.php?error=' . urlencode($e->getMessage()));
                exit;
            }
        }
    } elseif ($action === 'reject') {
        // Get application details for notifications
        $app = $pdo->prepare('
            SELECT la.*, 
                   CONCAT(c.first_name, " ", c.last_name) as client_name,
                   CONCAT(a.first_name, " ", a.last_name) as agent_name,
                   c.id as client_user_id,
                   a.id as agent_user_id
            FROM loan_applications la
            JOIN clients cl ON la.client_id = cl.id
            JOIN users c ON cl.user_id = c.id
            JOIN agents ag ON cl.agent_id = ag.id
            JOIN users a ON ag.user_id = a.id
            WHERE la.id = :id
        ');
        $app->execute([':id' => $applicationId]);
        $application = $app->fetch();
        
        if ($application) {
            // Update application status
            $pdo->prepare('UPDATE loan_applications SET application_status = "rejected", approved_date = CURRENT_DATE() WHERE id = :id')
                ->execute([':id' => $applicationId]);
            
            // Send notifications
            
            // Notify the client
            NotificationController::createNotification(
                $application['client_user_id'],
                'loan_rejection',
                'Loan Application Rejected',
                'Your loan application for GHS ' . number_format($application['requested_amount'], 2) . ' has been rejected. Please contact your agent for more information.',
                $applicationId
            );
            
            // Notify the agent
            NotificationController::createNotification(
                $application['agent_user_id'],
                'loan_rejection',
                'Client Loan Application Rejected',
                'The loan application for ' . $application['client_name'] . ' (GHS ' . number_format($application['requested_amount'], 2) . ') has been rejected.',
                $applicationId
            );
        }
        
        header('Location: /admin_applications.php?success=1');
        exit;
    }
}

// Get all applications
$applications = $pdo->query("
    SELECT la.*, 
           CONCAT(c.first_name, ' ', c.last_name) as client_name,
           cl.client_code,
           lp.product_name,
           lp.product_code,
           CONCAT(a.first_name, ' ', a.last_name) as agent_name
    FROM loan_applications la
    JOIN clients cl ON la.client_id = cl.id
    JOIN users c ON cl.user_id = c.id
    JOIN loan_products lp ON la.loan_product_id = lp.id
    JOIN agents ag ON cl.agent_id = ag.id
    JOIN users a ON ag.user_id = a.id
    ORDER BY la.applied_date DESC
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Modern Loan Applications Header -->
<div class="applications-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="page-title-section">
                <h2 class="page-title">
                    <i class="fas fa-file-alt text-primary me-2"></i>
                    Loan Applications Management
                </h2>
                <p class="page-subtitle">Review, approve, and manage loan applications</p>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <a href="/index.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Modern Alerts -->
<?php if (isset($_GET['success'])): ?>
<div class="modern-alert alert-success">
    <div class="alert-content">
        <i class="fas fa-check-circle"></i>
        <span>Application processed successfully!</span>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="modern-alert alert-danger">
    <div class="alert-content">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo e($_GET['error']); ?></span>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Modern Applications Table -->
<div class="modern-card">
    <div class="card-header-modern">
        <div class="header-content">
            <div class="header-icon">
                <i class="fas fa-table"></i>
            </div>
            <div class="header-text">
                <h5 class="header-title">All Loan Applications</h5>
                <p class="header-subtitle">Review and manage loan applications from clients</p>
            </div>
        </div>
    </div>
    <div class="card-body-modern">
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag me-1"></i>ID</th>
                        <th><i class="fas fa-user me-1"></i>Client</th>
                        <th><i class="fas fa-user-tie me-1"></i>Agent</th>
                        <th><i class="fas fa-box me-1"></i>Product</th>
                        <th><i class="fas fa-money-bill-wave me-1"></i>Amount</th>
                        <th><i class="fas fa-calendar-alt me-1"></i>Term</th>
                        <th><i class="fas fa-calendar me-1"></i>Date</th>
                        <th><i class="fas fa-circle me-1"></i>Status</th>
                        <th><i class="fas fa-cogs me-1"></i>Actions</th>
                        <th><i class="fas fa-eye me-1"></i>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><span class="application-id"><?php echo e($app['id']); ?></span></td>
                        <td>
                            <div class="client-info">
                                <strong><?php echo e($app['client_name']); ?></strong>
                                <br><code><?php echo e($app['client_code']); ?></code>
                            </div>
                        </td>
                        <td><span class="agent-name"><?php echo e($app['agent_name']); ?></span></td>
                        <td>
                            <div class="product-info">
                                <strong><?php echo e($app['product_name']); ?></strong>
                                <br><code><?php echo e($app['product_code']); ?></code>
                            </div>
                        </td>
                        <td><span class="amount-value">GHS <?php echo e(number_format($app['requested_amount'], 2)); ?></span></td>
                        <td><span class="term-value"><?php echo e($app['requested_term_months']); ?> months</span></td>
                        <td><span class="date-value"><?php echo e(date('M j, Y', strtotime($app['applied_date']))); ?></span></td>
                        <td>
                            <span class="status-badge status-<?php echo $app['application_status']; ?>">
                                <i class="fas fa-circle"></i>
                                <?php echo e(ucfirst($app['application_status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($app['application_status'] === 'pending'): ?>
                            <div class="action-buttons">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="application_id" value="<?php echo e($app['id']); ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-sm btn-success action-btn" 
                                            title="Approve Application"
                                            onclick="return confirm('Approve this loan application?')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="application_id" value="<?php echo e($app['id']); ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-sm btn-danger action-btn" 
                                            title="Reject Application"
                                            onclick="return confirm('Reject this loan application?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                            <?php else: ?>
                            <span class="processed-indicator">
                                <i class="fas fa-check-circle text-success"></i> Processed
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-info action-btn" 
                                        title="View Details"
                                        onclick="viewApplicationDetails(<?php echo $app['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($app['application_status'] === 'pending'): ?>
                                <a href="/admin_application_edit.php?id=<?php echo $app['id']; ?>" 
                                   class="btn btn-sm btn-warning action-btn" 
                                   title="Edit Application">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Application Details Modal -->
<div class="modal fade" id="applicationDetailsModal" tabindex="-1" aria-labelledby="applicationDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="applicationDetailsModalLabel">Loan Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="applicationDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewApplicationDetails(applicationId) {
    const modal = new bootstrap.Modal(document.getElementById('applicationDetailsModal'));
    const content = document.getElementById('applicationDetailsContent');
    
    // Show loading spinner
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch application details
    fetch(`/admin_application_details.php?id=${applicationId}`)
        .then(response => response.text())
        .then(html => {
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading application details: ${error.message}
                </div>
            `;
        });
}
</script>

<style>
/* Loan Applications Page Styles */
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
.application-id {
	background: #e9ecef;
	color: #495057;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.client-info, .product-info {
	line-height: 1.4;
}

.client-info strong, .product-info strong {
	color: #2c3e50;
	font-weight: 600;
}

.client-info code, .product-info code {
	background: #f8f9fa;
	color: #6c757d;
	padding: 0.125rem 0.25rem;
	border-radius: 3px;
	font-size: 0.75rem;
}

.agent-name {
	font-weight: 500;
	color: #495057;
}

.amount-value {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	color: white;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

.term-value, .date-value {
	background: #f8f9fa;
	color: #495057;
	padding: 0.25rem 0.5rem;
	border-radius: 4px;
	font-size: 0.85rem;
	font-weight: 600;
}

/* Status Badges */
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

.status-pending {
	background: linear-gradient(135deg, #ffc107, #e0a800);
	color: white;
}

.status-approved {
	background: linear-gradient(135deg, #28a745, #1e7e34);
	color: white;
}

.status-rejected {
	background: linear-gradient(135deg, #dc3545, #c82333);
	color: white;
}

.status-badge i {
	font-size: 0.6rem;
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

.action-btn.btn-success:hover {
	background: #28a745;
	border-color: #28a745;
	color: white;
}

.action-btn.btn-danger:hover {
	background: #dc3545;
	border-color: #dc3545;
	color: white;
}

.action-btn.btn-info:hover {
	background: #17a2b8;
	border-color: #17a2b8;
	color: white;
}

.action-btn.btn-warning:hover {
	background: #ffc107;
	border-color: #ffc107;
	color: white;
}

.processed-indicator {
	color: #28a745;
	font-weight: 500;
	font-size: 0.9rem;
}

/* Modal Enhancements */
.modal-content {
	border-radius: 15px;
	border: none;
	box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-header {
	background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
	border-bottom: 1px solid #e9ecef;
	border-radius: 15px 15px 0 0;
}

.modal-title {
	font-weight: 600;
	color: #2c3e50;
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

<?php include __DIR__ . '/includes/footer.php'; ?>
