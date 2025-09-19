<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

use function Auth\requireRole;

requireRole(['business_admin']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Transaction Management</h2>
                <div>
                    <button class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print"></i> Print All
                    </button>
                </div>
            </div>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Transactions</li>
                </ol>
            </nav>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo e($_SESSION['success']); unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo e($_SESSION['error']); unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filter Transactions</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Transaction Type</label>
                            <select class="form-select" name="type">
                                <option value="all" <?php echo ($_GET['type'] ?? '') === 'all' ? 'selected' : ''; ?>>All Transactions</option>
                                <option value="susu" <?php echo ($_GET['type'] ?? '') === 'susu' ? 'selected' : ''; ?>>Susu Collections</option>
                                <option value="loan" <?php echo ($_GET['type'] ?? '') === 'loan' ? 'selected' : ''; ?>>Loan Payments</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" name="from_date" value="<?php echo $_GET['from_date'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" name="to_date" value="<?php echo $_GET['to_date'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="/admin_transactions.php" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Transactions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Receipt</th>
                                    <th>Agent</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['type'] === 'susu' ? 'primary' : 'success'; ?>">
                                            <?php echo ucfirst($transaction['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($transaction['collection_time'])); ?></td>
                                    <td><?php echo e($transaction['client_name']); ?></td>
                                    <td>GHS <?php echo number_format($transaction['amount'], 2); ?></td>
                                    <td><?php echo e($transaction['receipt_number']); ?></td>
                                    <td><?php echo e($transaction['agent_code'] ?? 'N/A'); ?></td>
                                    <td><?php echo e($transaction['notes'] ?? ''); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="/admin_transaction_edit.php?action=edit&id=<?php echo $transaction['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/admin_transaction_edit.php?action=delete&id=<?php echo $transaction['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this transaction?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-info" onclick="printTransaction(<?php echo $transaction['id']; ?>)">
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>