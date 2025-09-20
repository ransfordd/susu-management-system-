<?php
$user = $_SESSION['user'] ?? null;
$userRole = $user['role'] ?? 'client';
$userName = $user ? ($user['name'] ?? 'User') : 'User';
$userAvatar = $user && isset($user['profile_picture']) ? $user['profile_picture'] : '/assets/images/default-avatar.png';
?>

<!-- Enhanced Navigation -->
<div class="navbar-nav ms-auto">
    <!-- Notifications -->
    <div class="nav-item dropdown">
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
    </div>

    <!-- User Profile -->
    <div class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" 
           id="userProfileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="<?php echo $userAvatar; ?>" alt="Profile" class="rounded-circle me-2" 
                 width="32" height="32" style="object-fit: cover;" 
                 onerror="this.src='/assets/images/default-avatar.png'">
            <span class="d-none d-md-inline"><?php echo htmlspecialchars($userName); ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userProfileDropdown">
            <li>
                <div class="dropdown-header">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo $userAvatar; ?>" alt="Profile" class="rounded-circle me-2" 
                             width="40" height="40" style="object-fit: cover;"
                             onerror="this.src='/assets/images/default-avatar.png'">
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($userName); ?></div>
                            <small class="text-muted"><?php echo ucfirst($userRole); ?></small>
                        </div>
                    </div>
                </div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="/index.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/account_settings.php">
                    <i class="fas fa-user-cog me-2"></i> Account Settings
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/change_password.php">
                    <i class="fas fa-key me-2"></i> Change Password
                </a>
            </li>
            <?php if ($userRole === 'business_admin'): ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="/views/admin/company_settings.php">
                        <i class="fas fa-building me-2"></i> Company Settings
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="/views/admin/document_manager.php">
                        <i class="fas fa-file-alt me-2"></i> Document Manager
                    </a>
                </li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="/index.php?action=logout">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
.navbar-nav .dropdown-toggle img {
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: border-color 0.3s ease;
}

.navbar-nav .dropdown-toggle:hover img {
    border-color: rgba(255, 255, 255, 0.6);
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

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.badge.bg-danger {
    animation: pulse 2s infinite;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load notifications
    loadNotifications();
    
    // Set up notification polling
    setInterval(loadNotifications, 30000); // Check every 30 seconds
});

function loadNotifications() {
    fetch('/notifications.php?action=get_recent')
        .then(response => response.json())
        .then(data => {
            if (data && data.success) {
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
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) return 'Just now';
    if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
    return Math.floor(diff / 86400000) + 'd ago';
}
</script>






