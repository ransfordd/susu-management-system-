<?php
// Enhanced Navigation Component
// Usage: include this file and call renderEnhancedNavigation($userRole, $currentPage)

function renderEnhancedNavigation($userRole, $currentPage = '') {
    $user = $_SESSION['user'] ?? null;
    
    // Get user name with fallbacks
    if ($user) {
        $firstName = trim($user['first_name'] ?? '');
        $lastName = trim($user['last_name'] ?? '');
        
        if ($firstName && $lastName) {
            $userName = $firstName . ' ' . $lastName;
        } elseif ($firstName) {
            $userName = $firstName;
        } elseif ($lastName) {
            $userName = $lastName;
        } elseif (isset($user['username'])) {
            $userName = $user['username'];
        } else {
            $userName = 'User';
        }
    } else {
        $userName = 'User';
    }
    $userAvatar = $user && isset($user['profile_picture']) ? $user['profile_picture'] : '/assets/images/default-avatar.png';
    
    // Check if admin is impersonating
    $isImpersonating = isset($_SESSION['impersonating']) && $_SESSION['impersonating'] === true;
    $originalAdmin = $_SESSION['original_admin'] ?? null;
    
    // Define navigation items based on user role
    $navigationItems = getNavigationItems($userRole);
    ?>

<!-- Enhanced Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <!-- Brand/Logo -->
        <a class="navbar-brand d-flex align-items-center" href="/index.php">
            <img src="/assets/images/company-logo.png" alt="Company Logo" height="40" class="me-2" 
                 onerror="this.src='/assets/images/default-logo.png'">
            <span class="fw-bold">The Determiners Susu System</span>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($navigationItems as $item): ?>
                    <?php if (isset($item['submenu']) && !empty($item['submenu'])): ?>
                        <!-- Dropdown Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo $currentPage === $item['key'] ? 'active' : ''; ?>" 
                               href="#" id="<?php echo $item['key']; ?>Dropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="<?php echo $item['icon']; ?> me-1"></i>
                                <?php echo $item['label']; ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="<?php echo $item['key']; ?>Dropdown">
                                <?php foreach ($item['submenu'] as $subItem): ?>
                                    <li>
                                        <a class="dropdown-item <?php echo $currentPage === $subItem['key'] ? 'active' : ''; ?>" 
                                           href="<?php echo $subItem['url']; ?>">
                                            <i class="<?php echo $subItem['icon']; ?> me-2"></i>
                                            <?php echo $subItem['label']; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Single Menu Item -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === $item['key'] ? 'active' : ''; ?>" 
                               href="<?php echo $item['url']; ?>">
                                <i class="<?php echo $item['icon']; ?> me-1"></i>
                                <?php echo $item['label']; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <!-- Right Side Items -->
            <ul class="navbar-nav">
                <!-- Notifications -->
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" href="#" id="notificationsDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                              id="notificationCount" style="display: none;">
                            0
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" 
                        aria-labelledby="notificationsDropdown">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="notificationList">
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-bell-slash fa-2x mb-2"></i>
                                <p class="mb-0">No new notifications</p>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-center" href="/notifications.php">
                                <i class="fas fa-eye me-1"></i> View All Notifications
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Quick Actions -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="quickActionsDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="quickActionsDropdown">
                        <?php echo getQuickActions($userRole); ?>
                    </ul>
                </li>

                <!-- User Profile -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" 
                       id="userProfileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo $userAvatar; ?>" alt="Profile" class="rounded-circle me-2" 
                             width="32" height="32" style="object-fit: cover;">
                        <span class="d-none d-md-inline">
                            <?php echo htmlspecialchars($userName); ?>
                            <?php if ($isImpersonating): ?>
                                <span class="badge bg-warning text-dark ms-1" title="Impersonating">
                                    <i class="fas fa-user-secret"></i>
                                </span>
                            <?php endif; ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userProfileDropdown">
                        <?php if ($isImpersonating && $originalAdmin): ?>
                        <li>
                            <div class="dropdown-header bg-warning text-dark">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-secret me-2"></i>
                                    <div>
                                        <div class="fw-bold">Impersonating</div>
                                        <small>Logged in as: <?php echo htmlspecialchars($userName); ?></small>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li>
                            <a class="dropdown-item text-warning" href="/index.php?action=logout">
                                <i class="fas fa-arrow-left me-2"></i> Return to Admin
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li>
                            <div class="dropdown-header">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $userAvatar; ?>" alt="Profile" class="rounded-circle me-2" 
                                         width="40" height="40" style="object-fit: cover;">
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($userName); ?></div>
                                        <small class="text-muted"><?php echo ucfirst($userRole); ?></small>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/account_settings.php">
                                <i class="fas fa-user-cog me-2"></i> Account Settings
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/profile.php">
                                <i class="fas fa-user me-2"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/change_password.php">
                                <i class="fas fa-key me-2"></i> Change Password
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="/help.php">
                                <i class="fas fa-question-circle me-2"></i> Help & Support
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/index.php?action=logout">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Breadcrumb Navigation -->
<nav aria-label="breadcrumb" class="bg-light border-bottom">
    <div class="container-fluid">
        <ol class="breadcrumb mb-0 py-2">
            <li class="breadcrumb-item">
                <a href="/index.php">
                    <i class="fas fa-home"></i> Home
                </a>
            </li>
            <?php echo generateBreadcrumbs($currentPage); ?>
        </ol>
    </div>
</nav>

<style>
/* Enhanced Navigation Styles */
.navbar-brand img {
    transition: transform 0.3s ease;
}

.navbar-brand:hover img {
    transform: scale(1.05);
}

.nav-link {
    transition: all 0.3s ease;
    border-radius: 0.375rem;
    margin: 0 0.25rem;
}

.nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    transform: translateY(-1px);
}

.nav-link.active {
    background-color: rgba(255, 255, 255, 0.2);
    font-weight: 600;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border-radius: 0.5rem;
    margin-top: 0.5rem;
}

.dropdown-item {
    transition: all 0.2s ease;
    border-radius: 0.375rem;
    margin: 0.125rem 0.5rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.notification-dropdown {
    min-width: 300px;
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 0.75rem;
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s ease;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item:last-child {
    border-bottom: none;
}

.breadcrumb {
    font-size: 0.875rem;
}

.breadcrumb-item a {
    color: #6c757d;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: #495057;
}

/* Mobile Responsiveness */
@media (max-width: 991.98px) {
    .navbar-nav {
        margin-top: 1rem;
    }
    
    .nav-link {
        margin: 0.25rem 0;
        padding: 0.75rem 1rem;
    }
    
    .dropdown-menu {
        margin-top: 0;
        border-radius: 0;
        box-shadow: none;
        border: 1px solid #dee2e6;
    }
}

/* Animation for notifications */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.badge.bg-danger {
    animation: pulse 2s infinite;
}

/* Profile picture styling */
.navbar-nav .dropdown-toggle img {
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: border-color 0.3s ease;
}

.navbar-nav .dropdown-toggle:hover img {
    border-color: rgba(255, 255, 255, 0.6);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load notifications
    loadNotifications();
    
    // Set up notification polling
    setInterval(loadNotifications, 30000); // Check every 30 seconds
    
    // Mobile menu improvements
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!navbarCollapse.contains(e.target) && !navbarToggler.contains(e.target)) {
            if (navbarCollapse.classList.contains('show')) {
                navbarToggler.click();
            }
        }
    });
});

function loadNotifications() {
    fetch('/api/notifications.php?action=get_recent')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationUI(data.notifications);
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
        });
}

function updateNotificationUI(notifications) {
    const notificationCount = document.getElementById('notificationCount');
    const notificationList = document.getElementById('notificationList');
    
    const unreadCount = notifications.filter(n => !n.is_read).length;
    
    if (unreadCount > 0) {
        notificationCount.textContent = unreadCount;
        notificationCount.style.display = 'block';
    } else {
        notificationCount.style.display = 'none';
    }
    
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <div class="text-center text-muted py-3">
                <i class="fas fa-bell-slash fa-2x mb-2"></i>
                <p class="mb-0">No new notifications</p>
            </div>
        `;
    } else {
        notificationList.innerHTML = notifications.slice(0, 5).map(notification => `
            <div class="notification-item ${!notification.is_read ? 'bg-light' : ''}">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-${getNotificationIcon(notification.notification_type)} text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <div class="fw-bold">${notification.title}</div>
                        <div class="text-muted small">${notification.message}</div>
                        <div class="text-muted small">${formatTime(notification.created_at)}</div>
                    </div>
                </div>
            </div>
        `).join('');
    }
}

function getNotificationIcon(type) {
    const icons = {
        'payment_due': 'exclamation-triangle',
        'payment_overdue': 'exclamation-circle',
        'loan_approved': 'check-circle',
        'loan_rejected': 'times-circle',
        'cycle_completed': 'trophy',
        'system_alert': 'info-circle'
    };
    return icons[type] || 'bell';
}

function formatTime(dateString) {
    // Server already applies timezone conversion, so we can use the timestamp directly
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 10000) return 'Just now';
    if (diff < 60000) return Math.floor(diff / 1000) + 's ago';
    if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
    return Math.floor(diff / 86400000) + 'd ago';
}
</script>

<?php
}

function getNavigationItems($userRole) {
    switch ($userRole) {
        case 'business_admin':
            return [
                [
                    'key' => 'dashboard',
                    'label' => 'Dashboard',
                    'url' => '/index.php',
                    'icon' => 'fas fa-tachometer-alt'
                ],
                [
                    'key' => 'users',
                    'label' => 'User Management',
                    'url' => '#',
                    'icon' => 'fas fa-users',
                    'submenu' => [
                        [
                            'key' => 'clients',
                            'label' => 'Clients',
                            'url' => '/admin_clients.php',
                            'icon' => 'fas fa-user-friends'
                        ],
                        [
                            'key' => 'agents',
                            'label' => 'Agents',
                            'url' => '/admin_agents.php',
                            'icon' => 'fas fa-user-tie'
                        ],
                        [
                            'key' => 'users',
                            'label' => 'All Users',
                            'url' => '/admin_users.php',
                            'icon' => 'fas fa-users'
                        ]
                    ]
                ],
                [
                    'key' => 'transactions',
                    'label' => 'Transactions',
                    'url' => '#',
                    'icon' => 'fas fa-exchange-alt',
                    'submenu' => [
                        [
                            'key' => 'all_transactions',
                            'label' => 'All Transactions',
                            'url' => '/admin_transactions.php',
                            'icon' => 'fas fa-list'
                        ],
                        [
                            'key' => 'user_transactions',
                            'label' => 'User Transactions',
                            'url' => '/admin_user_transactions.php',
                            'icon' => 'fas fa-user-clock'
                        ],
                        [
                            'key' => 'manual_transactions',
                            'label' => 'Manual Transactions',
                            'url' => '/admin_manual_transactions.php',
                            'icon' => 'fas fa-hand-holding-usd'
                        ]
                    ]
                ],
                [
                    'key' => 'loans',
                    'label' => 'Loans',
                    'url' => '#',
                    'icon' => 'fas fa-money-bill-wave',
                    'submenu' => [
                        [
                            'key' => 'loan_applications',
                            'label' => 'Loan Applications',
                            'url' => '/admin_loan_applications.php',
                            'icon' => 'fas fa-file-alt'
                        ],
                        [
                            'key' => 'loan_products',
                            'label' => 'Loan Products',
                            'url' => '/admin_loan_products.php',
                            'icon' => 'fas fa-box'
                        ],
                        [
                            'key' => 'active_loans',
                            'label' => 'Active Loans',
                            'url' => '/admin_active_loans.php',
                            'icon' => 'fas fa-clipboard-list'
                        ]
                    ]
                ],
                [
                    'key' => 'reports',
                    'label' => 'Reports',
                    'url' => '#',
                    'icon' => 'fas fa-chart-bar',
                    'submenu' => [
                        [
                            'key' => 'agent_reports',
                            'label' => 'Agent Reports',
                            'url' => '/admin_agent_reports.php',
                            'icon' => 'fas fa-user-chart'
                        ],
                        [
                            'key' => 'financial_reports',
                            'label' => 'Financial Reports',
                            'url' => '/admin_financial_reports.php',
                            'icon' => 'fas fa-chart-line'
                        ],
                        [
                            'key' => 'commission_reports',
                            'label' => 'Commission Reports',
                            'url' => '/admin_commission_reports.php',
                            'icon' => 'fas fa-percentage'
                        ]
                    ]
                ],
                [
                    'key' => 'settings',
                    'label' => 'Settings',
                    'url' => '#',
                    'icon' => 'fas fa-cog',
                    'submenu' => [
                        [
                            'key' => 'company_settings',
                            'label' => 'Company Settings',
                            'url' => '/admin_company_settings.php',
                            'icon' => 'fas fa-building'
                        ],
                        [
                            'key' => 'system_settings',
                            'label' => 'System Settings',
                            'url' => '/admin_system_settings.php',
                            'icon' => 'fas fa-sliders-h'
                        ]
                    ]
                ]
            ];
            
        case 'agent':
            return [
                [
                    'key' => 'dashboard',
                    'label' => 'Dashboard',
                    'url' => '/views/agent/dashboard.php',
                    'icon' => 'fas fa-tachometer-alt'
                ],
                [
                    'key' => 'clients',
                    'label' => 'My Clients',
                    'url' => '/views/agent/clients.php',
                    'icon' => 'fas fa-user-friends'
                ],
                [
                    'key' => 'collections',
                    'label' => 'Collections',
                    'url' => '#',
                    'icon' => 'fas fa-hand-holding-usd',
                    'submenu' => [
                        [
                            'key' => 'record_payment',
                            'label' => 'Record Payment',
                            'url' => '/views/agent/collect.php',
                            'icon' => 'fas fa-plus-circle'
                        ],
                        [
                            'key' => 'collection_history',
                            'label' => 'Collection History',
                            'url' => '/views/agent/collection_history.php',
                            'icon' => 'fas fa-history'
                        ],
                        [
                            'key' => 'susu_tracker',
                            'label' => 'Susu Tracker',
                            'url' => '/views/agent/susu_tracker.php',
                            'icon' => 'fas fa-calendar-check'
                        ]
                    ]
                ],
                [
                    'key' => 'loans',
                    'label' => 'Loans',
                    'url' => '#',
                    'icon' => 'fas fa-money-bill-wave',
                    'submenu' => [
                        [
                            'key' => 'loan_applications',
                            'label' => 'Loan Applications',
                            'url' => '/agent_apps.php',
                            'icon' => 'fas fa-file-alt'
                        ],
                        [
                            'key' => 'create_application',
                            'label' => 'Create Application',
                            'url' => '/agent_app_create.php',
                            'icon' => 'fas fa-plus'
                        ]
                    ]
                ],
                [
                    'key' => 'reports',
                    'label' => 'My Reports',
                    'url' => '/views/agent/reports.php',
                    'icon' => 'fas fa-chart-bar'
                ]
            ];
            
        case 'client':
            return [
                [
                    'key' => 'dashboard',
                    'label' => 'Dashboard',
                    'url' => '/views/client/dashboard.php',
                    'icon' => 'fas fa-tachometer-alt'
                ],
                [
                    'key' => 'susu',
                    'label' => 'My Susu',
                    'url' => '#',
                    'icon' => 'fas fa-piggy-bank',
                    'submenu' => [
                        [
                            'key' => 'susu_tracker',
                            'label' => 'Susu Tracker',
                            'url' => '/views/client/susu_tracker.php',
                            'icon' => 'fas fa-calendar-check'
                        ],
                        [
                            'key' => 'susu_history',
                            'label' => 'Collection History',
                            'url' => '/views/client/susu_history.php',
                            'icon' => 'fas fa-history'
                        ]
                    ]
                ],
                [
                    'key' => 'loans',
                    'label' => 'My Loans',
                    'url' => '#',
                    'icon' => 'fas fa-money-bill-wave',
                    'submenu' => [
                        [
                            'key' => 'loan_applications',
                            'label' => 'My Applications',
                            'url' => '/views/client/loan_applications.php',
                            'icon' => 'fas fa-file-alt'
                        ],
                        [
                            'key' => 'apply_loan',
                            'label' => 'Apply for Loan',
                            'url' => '/views/client/apply_loan.php',
                            'icon' => 'fas fa-plus'
                        ],
                        [
                            'key' => 'loan_history',
                            'label' => 'Loan History',
                            'url' => '/views/client/loan_history.php',
                            'icon' => 'fas fa-history'
                        ]
                    ]
                ],
                [
                    'key' => 'transactions',
                    'label' => 'Transactions',
                    'url' => '/views/client/transactions.php',
                    'icon' => 'fas fa-exchange-alt'
                ]
            ];
            
        default:
            return [];
    }
}

function getQuickActions($userRole) {
    switch ($userRole) {
        case 'business_admin':
            return '
                <li><h6 class="dropdown-header">Quick Actions</h6></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/admin_users.php?action=create"><i class="fas fa-user-plus me-2"></i> Add User</a></li>
                <li><a class="dropdown-item" href="/admin_manual_transactions.php?action=create"><i class="fas fa-hand-holding-usd me-2"></i> Manual Transaction</a></li>
                <li><a class="dropdown-item" href="/admin_loan_applications.php?action=create"><i class="fas fa-file-plus me-2"></i> Create Loan Application</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/admin_reports.php"><i class="fas fa-chart-bar me-2"></i> Generate Report</a></li>
            ';
            
        case 'agent':
            return '
                <li><h6 class="dropdown-header">Quick Actions</h6></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/views/agent/collect.php"><i class="fas fa-plus-circle me-2"></i> Record Payment</a></li>
                <li><a class="dropdown-item" href="/agent_app_create.php"><i class="fas fa-file-plus me-2"></i> Create Loan Application</a></li>
                <li><a class="dropdown-item" href="/views/agent/clients.php"><i class="fas fa-user-friends me-2"></i> View Clients</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/views/agent/reports.php"><i class="fas fa-chart-bar me-2"></i> My Reports</a></li>
            ';
            
        case 'client':
            return '
                <li><h6 class="dropdown-header">Quick Actions</h6></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/views/client/apply_loan.php"><i class="fas fa-plus me-2"></i> Apply for Loan</a></li>
                <li><a class="dropdown-item" href="/views/client/susu_tracker.php"><i class="fas fa-calendar-check me-2"></i> View Susu Tracker</a></li>
                <li><a class="dropdown-item" href="/views/client/transactions.php"><i class="fas fa-exchange-alt me-2"></i> View Transactions</a></li>
            ';
            
        default:
            return '';
    }
}

function generateBreadcrumbs($currentPage) {
    $breadcrumbs = [];
    
    // Define breadcrumb mappings
    $breadcrumbMap = [
        'dashboard' => ['Dashboard'],
        'clients' => ['User Management', 'Clients'],
        'agents' => ['User Management', 'Agents'],
        'users' => ['User Management', 'All Users'],
        'all_transactions' => ['Transactions', 'All Transactions'],
        'user_transactions' => ['Transactions', 'User Transactions'],
        'manual_transactions' => ['Transactions', 'Manual Transactions'],
        'loan_applications' => ['Loans', 'Loan Applications'],
        'loan_products' => ['Loans', 'Loan Products'],
        'active_loans' => ['Loans', 'Active Loans'],
        'agent_reports' => ['Reports', 'Agent Reports'],
        'financial_reports' => ['Reports', 'Financial Reports'],
        'commission_reports' => ['Reports', 'Commission Reports'],
        'company_settings' => ['Settings', 'Company Settings'],
        'system_settings' => ['Settings', 'System Settings'],
        'my_clients' => ['My Clients'],
        'record_payment' => ['Collections', 'Record Payment'],
        'collection_history' => ['Collections', 'Collection History'],
        'susu_tracker' => ['Collections', 'Susu Tracker'],
        'my_susu' => ['My Susu'],
        'susu_history' => ['My Susu', 'Collection History'],
        'my_loans' => ['My Loans'],
        'apply_loan' => ['My Loans', 'Apply for Loan'],
        'loan_history' => ['My Loans', 'Loan History'],
        'transactions' => ['Transactions']
    ];
    
    if (isset($breadcrumbMap[$currentPage])) {
        foreach ($breadcrumbMap[$currentPage] as $crumb) {
            $breadcrumbs[] = '<li class="breadcrumb-item active">' . htmlspecialchars($crumb) . '</li>';
        }
    }
    
    return implode('', $breadcrumbs);
}
?>
