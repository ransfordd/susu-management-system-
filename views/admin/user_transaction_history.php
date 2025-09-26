<?php
include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>User Transaction History</h2>
        <a href="/admin_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <!-- User Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter User Transactions</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3" autocomplete="off" id="transactionFilterForm">
                <div class="col-md-4">
                    <label class="form-label">Select User</label>
                    <div class="position-relative">
                        <input type="text" id="user_search" class="form-control" placeholder="Search users by name or code..." autocomplete="off" value="<?php echo $selectedClient ? htmlspecialchars($selectedClient['client_name'] . ' (' . $selectedClient['client_code'] . ')') : ''; ?>">
                        <div id="user_dropdown" class="dropdown-menu" style="max-height: 300px; overflow-y: auto; display: none; width: 100%;">
                            <a class="dropdown-item user-option" href="#" data-value="" data-name="All Users" data-code="">
                                <strong>All Users</strong>
                            </a>
                            <?php foreach ($allClients as $client): ?>
                            <a class="dropdown-item user-option" href="#" 
                               data-value="<?php echo $client['id']; ?>"
                               data-name="<?php echo htmlspecialchars($client['client_name']); ?>"
                               data-code="<?php echo htmlspecialchars($client['client_code']); ?>">
                                <strong><?php echo htmlspecialchars($client['client_code']); ?></strong> - 
                                <?php echo htmlspecialchars($client['client_name']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($client['email'] ?? ''); ?></small>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="client_id" id="client_id" value="<?php echo $clientId ?? ''; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($fromDate ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="to_date" value="<?php echo htmlspecialchars($toDate ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="transaction_type">
                        <option value="all" <?php echo $transactionType === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="susu_collection" <?php echo $transactionType === 'susu_collection' ? 'selected' : ''; ?>>Susu</option>
                        <option value="loan_payment" <?php echo $transactionType === 'loan_payment' ? 'selected' : ''; ?>>Loan</option>
                        <option value="manual_transaction" <?php echo $transactionType === 'manual_transaction' ? 'selected' : ''; ?>>Manual</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filter Transactions</button>
                    <?php if ($clientId !== null): ?>
                    <a href="/admin_user_transactions.php" class="btn btn-outline-secondary ms-2">Clear Filter</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selectedClient): ?>
    <!-- Client Information -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Client Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <div class="text-center">
                        <?php if ($selectedClient['profile_picture']): ?>
                            <img src="<?php echo htmlspecialchars($selectedClient['profile_picture']); ?>" 
                                 alt="Profile Picture" class="img-thumbnail" 
                                 style="width: 100px; height: 100px; object-fit: cover;"
                                 onerror="this.src='/assets/images/default-avatar.png'">
                        <?php else: ?>
                            <img src="/assets/images/default-avatar.png" 
                                 alt="Default Avatar" class="img-thumbnail" 
                                 style="width: 100px; height: 100px; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-5">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($selectedClient['client_name'] ?? ''); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($selectedClient['email'] ?? ''); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($selectedClient['phone'] ?? ''); ?></p>
                </div>
                <div class="col-md-5">
                    <p><strong>Client Code:</strong> <?php echo htmlspecialchars($selectedClient['client_code'] ?? ''); ?></p>
                    <p><strong>Agent:</strong> <?php echo htmlspecialchars($selectedClient['agent_name'] ?? 'N/A'); ?></p>
                    <p><strong>Agent Code:</strong> <?php echo htmlspecialchars($selectedClient['agent_code'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Susu Collection Tracker -->
    <?php 
    // Include Susu tracker component
    require_once __DIR__ . '/../shared/susu_tracker.php';
    ?>
    <div class="row mb-4">
        <div class="col-12">
            <?php renderSusuTracker($selectedClient['id'], null, false, $fromDate, $toDate); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Transaction Totals -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Transaction Summary</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="text-primary">Total Transactions</h5>
                            <h3><?php echo number_format($totals['transaction_count']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="text-success">Total Deposits</h5>
                            <h3>GHS <?php echo number_format($totals['deposit_amount'], 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="text-warning">Total Withdrawals</h5>
                            <h3>GHS <?php echo number_format($totals['withdrawal_amount'], 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="text-info">Net Amount</h5>
                            <h3>GHS <?php echo number_format($totals['deposit_amount'] - $totals['withdrawal_amount'], 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <?php if ($selectedClient): ?>
                    Transactions for <?php echo htmlspecialchars($selectedClient['client_name']); ?> (<?php echo count($transactions); ?> transactions)
                <?php else: ?>
                    All Transactions (<?php echo count($transactions); ?> transactions)
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($transactions)): ?>
            <div class="text-center text-muted py-4">
                <p>No transactions found for the selected criteria.</p>
                <?php if (!$clientId): ?>
                <p>Please select a user to view their transaction history.</p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Agent Name</th>
                            <th>Client Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php 
                                // Fix time display - handle different time formats
                                $transactionTime = $transaction['transaction_time'];
                                $transactionDate = $transaction['transaction_date'];
                                
                                // If transaction_time is null or empty, try to extract time from created_at
                                if (empty($transactionTime) || $transactionTime === '00:00:00' || $transactionTime === null) {
                                    // Try to get time from created_at if available
                                    if (isset($transaction['created_at']) && !empty($transaction['created_at'])) {
                                        $createdAt = new DateTime($transaction['created_at']);
                                        $transactionTime = $createdAt->format('H:i:s');
                                    } else {
                                        $transactionTime = '12:00:00'; // Default to noon
                                    }
                                }
                                
                                // Combine date and time properly
                                $dateTime = $transactionDate . ' ' . $transactionTime;
                                $timestamp = strtotime($dateTime);
                                
                                // Only display if we have a valid timestamp
                                if ($timestamp && $timestamp > 0) {
                                    // Apply timezone conversion (UTC to Africa/Accra + 4 hours)
                                    $date = new DateTime($dateTime, new DateTimeZone('UTC'));
                                    $date->modify('+4 hours'); // Apply 4-hour offset
                                    $date->setTimezone(new DateTimeZone('Africa/Accra'));
                                    echo $date->format('M d, Y H:i');
                                } else {
                                    // Apply timezone conversion for date only
                                    $date = new DateTime($transactionDate, new DateTimeZone('UTC'));
                                    $date->modify('+4 hours'); // Apply 4-hour offset
                                    $date->setTimezone(new DateTimeZone('Africa/Accra'));
                                    echo $date->format('M d, Y');
                                }
                            ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($transaction['transaction_type']) {
                                        'susu_collection' => 'success',
                                        'loan_payment' => 'info',
                                        'susu_payout' => 'warning',
                                        'manual_transaction' => 'primary',
                                        'manual_deposit' => 'success',
                                        'manual_withdrawal' => 'danger',
                                        default => 'primary'
                                    };
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?>
                                </span>
                            </td>
                            <td>GHS <?php echo number_format($transaction['amount'], 2); ?></td>
                            <td>
                                <?php 
                                $agentName = $transaction['agent_name'] ?? 'N/A';
                                if ($agentName && $agentName !== 'N/A' && !empty(trim($agentName))) {
                                    echo htmlspecialchars($agentName);
                                } else {
                                    echo '<span class="text-muted">N/A</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($transaction['client_name'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="/admin_user_transactions.php?action=print&transaction_id=<?php echo $transaction['collection_id'] ?? $transaction['payment_id'] ?? $transaction['manual_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-print"></i> Print
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* User search dropdown styling */
#user_dropdown {
    z-index: 1050;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.user-option {
    display: block;
    padding: 8px 12px;
    text-decoration: none;
    color: #333;
    border-bottom: 1px solid #eee;
}

.user-option:hover {
    background-color: #f8f9fa;
    color: #333;
}

.user-option:last-child {
    border-bottom: none;
}

.user-option strong {
    color: #007bff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userSearch = document.getElementById('user_search');
    const userDropdown = document.getElementById('user_dropdown');
    const clientId = document.getElementById('client_id');
    const form = document.getElementById('transactionFilterForm');
    const userOptions = document.querySelectorAll('.user-option');
    
    // User search functionality
    userSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let hasMatches = false;
        
        userOptions.forEach(option => {
            const text = option.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                option.style.display = 'block';
                hasMatches = true;
            } else {
                option.style.display = 'none';
            }
        });
        
        if (searchTerm.length > 0 && hasMatches) {
            userDropdown.style.display = 'block';
        } else {
            userDropdown.style.display = 'none';
        }
    });
    
    // User selection
    userOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            const clientIdValue = this.dataset.value;
            const clientName = this.dataset.name;
            const clientCode = this.dataset.code;
            
            // Update search input with clean display text
            if (clientIdValue === '') {
                userSearch.value = 'All Users';
            } else {
                userSearch.value = `${clientCode} - ${clientName}`;
            }
            clientId.value = clientIdValue;
            
            // Hide dropdown
            userDropdown.style.display = 'none';
            
            // Auto-submit form when user is selected
            form.submit();
        });
    });
    
    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!userSearch.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.style.display = 'none';
        }
    });
    
    // Handle form submission to ensure clean URLs
    form.addEventListener('submit', function(e) {
        const clientIdValue = clientId.value;
        
        // If "All Users" is selected, ensure client_id is empty in URL
        if (clientIdValue === '' || clientIdValue === 'all') {
            // Remove client_id parameter from URL
            const url = new URL(window.location);
            url.searchParams.delete('client_id');
            url.searchParams.set('from_date', form.querySelector('input[name="from_date"]').value);
            url.searchParams.set('to_date', form.querySelector('input[name="to_date"]').value);
            url.searchParams.set('transaction_type', form.querySelector('select[name="transaction_type"]').value);
            
            // Redirect to clean URL
            window.location.href = url.toString();
            e.preventDefault();
        }
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>