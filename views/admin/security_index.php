<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Security Center</h2>
        <a href="/admin_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['success'] ?? ''); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['error'] ?? ''); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Security Settings -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Security Settings</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/admin_security.php?action=update_settings">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                        
                        <div class="mb-3">
                            <label class="form-label">Max Login Attempts</label>
                            <input type="number" class="form-control" name="max_login_attempts" 
                                   value="5" min="1" max="10" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Lockout Duration (minutes)</label>
                            <input type="number" class="form-control" name="lockout_duration" 
                                   value="30" min="5" max="1440" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Session Timeout (minutes)</label>
                            <input type="number" class="form-control" name="session_timeout" 
                                   value="60" min="15" max="480" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password Min Length</label>
                            <input type="number" class="form-control" name="password_min_length" 
                                   value="8" min="6" max="20" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="require_2fa" id="require_2fa">
                            <label class="form-check-label" for="require_2fa">
                                Require Two-Factor Authentication
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="password_require_special" id="password_require_special" checked>
                            <label class="form-check-label" for="password_require_special">
                                Require Special Characters in Password
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update Security Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Security Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="text-primary">Recent Logins</h5>
                                    <h3><?php echo count($recentLogins); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="text-danger">Failed Logins</h5>
                                    <h3><?php echo count($failedLogins); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="text-warning">Locked Accounts</h5>
                                    <h3><?php echo count($lockedAccounts); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="text-info">Total Users</h5>
                                    <h3><?php echo count($allUsers); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Login Attempts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Login Attempts</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recentLogins, 0, 10) as $login): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($login['first_name'] . ' ' . $login['last_name']); ?></td>
                                    <td><?php echo date('M d H:i', strtotime($login['login_time'] ?? '')); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $login['login_status'] === 'success' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($login['login_status'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($login['ip_address'] ?? ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Locked Accounts</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($lockedAccounts)): ?>
                    <p class="text-muted">No locked accounts</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Locked Since</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lockedAccounts as $account): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($account['first_name'] . ' ' . $account['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($account['email'] ?? ''); ?></td>
                                    <td><?php echo date('M d H:i', strtotime($account['login_time'] ?? '')); ?></td>
                                    <td>
                                        <a href="/admin_security.php?action=unlock_account&user_id=<?php echo $account['id']; ?>" 
                                           class="btn btn-sm btn-success">Unlock</a>
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
    </div>

    <!-- Password Reset Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Reset User Password</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="/admin_security.php?action=reset_password">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" />
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Select User</label>
                        <select class="form-select" name="user_id" required>
                            <option value="">Choose a user...</option>
                            <?php foreach ($allUsers as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> (<?php echo htmlspecialchars($user['email'] ?? ''); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" 
                               minlength="8" required>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
