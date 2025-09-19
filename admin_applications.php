<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

use function Auth\requireRole;

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
                $pdo->prepare('UPDATE loan_applications SET status = "approved", approved_date = CURRENT_DATE() WHERE id = :id')
                    ->execute([':id' => $applicationId]);
                
                // Create loan
                $pdo->prepare('INSERT INTO loans (client_id, product_id, loan_amount, interest_rate, term_months, disbursement_date, loan_status, current_balance, application_id) VALUES (:c, :p, :amt, :rate, :term, CURRENT_DATE(), "active", :bal, :app)')
                    ->execute([
                        ':c' => $application['client_id'],
                        ':p' => $application['product_id'],
                        ':amt' => $application['requested_amount'],
                        ':rate' => 24.0, // Default rate
                        ':term' => $application['requested_term_months'],
                        ':bal' => $application['requested_amount'],
                        ':app' => $applicationId
                    ]);
                
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
        $pdo->prepare('UPDATE loan_applications SET status = "rejected", approved_date = CURRENT_DATE() WHERE id = :id')
            ->execute([':id' => $applicationId]);
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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Loan Applications Management</h4>
    <a href="/index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    Application processed successfully!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo e($_GET['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">All Loan Applications</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Agent</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Term</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><?php echo e($app['id']); ?></td>
                        <td>
                            <div><?php echo e($app['client_name']); ?></div>
                            <small class="text-muted"><?php echo e($app['client_code']); ?></small>
                        </td>
                        <td><?php echo e($app['agent_name']); ?></td>
                        <td>
                            <div><?php echo e($app['product_name']); ?></div>
                            <small class="text-muted"><?php echo e($app['product_code']); ?></small>
                        </td>
                        <td>GHS <?php echo e(number_format($app['requested_amount'], 2)); ?></td>
                        <td><?php echo e($app['requested_term_months']); ?> months</td>
                        <td><?php echo e(date('M j, Y', strtotime($app['applied_date']))); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $app['application_status'] === 'approved' ? 'success' : ($app['application_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                <?php echo e(ucfirst($app['application_status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($app['application_status'] === 'pending'): ?>
                            <div class="btn-group btn-group-sm">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="application_id" value="<?php echo e($app['id']); ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this loan application?')">Approve</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="application_id" value="<?php echo e($app['id']); ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this loan application?')">Reject</button>
                                </form>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">Processed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
