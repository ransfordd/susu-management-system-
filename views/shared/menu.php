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
           role="button" data-bs-toggle="dropdown" aria-expanded="false" onclick="onNotificationDropdownOpen()">
            <i class="fas fa-bell"></i>
            <span class="notification-badge" 
                  id="notificationCount" style="display: none; opacity: 0;">
                0
            </span>
        </a>
        <ul class="dropdown-menu dropdown-menu-start notification-dropdown" 
            aria-labelledby="notificationsDropdown" style="display: none;">
            <li><h6 class="dropdown-header">Notifications</h6></li>
            <li><hr class="dropdown-divider"></li>
            <li id="notificationList">
                <div class="text-center text-muted py-3">
                    <i class="fas fa-bell-slash fa-2x mb-2"></i>
                    <p class="mb-0">No new notifications</p>
                </div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li class="dropdown-footer">
                <a class="dropdown-item text-center" href="/notifications.php">
                    <i class="fas fa-eye me-1"></i> View All Notifications
                </a>
                <a class="dropdown-item text-center" href="#" onclick="markAllNotificationsAsRead()">
                    <i class="fas fa-check-double me-1"></i> Mark All as Read
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
/* Notification Badge Styles - Updated 2025-09-24 12:45 */
.navbar-nav .dropdown-toggle img {
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: border-color 0.3s ease;
}

.navbar-nav .dropdown-toggle:hover img {
    border-color: rgba(255, 255, 255, 0.6);
}

.notification-dropdown {
    min-width: 300px !important;
    width: 300px !important;
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    background: #fff;
    display: none !important;
    flex-direction: column;
    right: 0 !important;
    left: auto !important;
    transform: translateX(0) !important;
}

.notification-dropdown.show {
    display: flex !important;
}

.notification-dropdown #notificationList {
    flex: 1;
    overflow-y: auto;
    max-height: 350px;
}

.notification-dropdown .dropdown-footer {
    border-top: 1px solid #f0f0f0;
    background: #f8f9fa;
    padding: 0;
    margin: 0;
}

.notification-item {
    padding: 0.75rem;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s ease;
    cursor: pointer;
    position: relative;
    width: 100%;
    box-sizing: border-box;
}

.notification-item:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-content {
    font-size: 0.8rem;
    line-height: 1.3;
    padding-right: 10px;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.notification-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.2rem;
    font-size: 0.85rem;
}

.notification-message {
    color: #666;
    font-size: 0.75rem;
    word-wrap: break-word;
    overflow-wrap: break-word;
    hyphens: auto;
}

.notification-time {
    color: #999;
    font-size: 0.7rem;
    margin-top: 0.2rem;
}

.notification-new {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    width: 8px;
    height: 8px;
    background: #dc3545;
    border-radius: 50%;
    animation: pulse 1.5s infinite;
}

.notification-dropdown .dropdown-footer .dropdown-item {
    padding: 0.75rem 1rem !important;
    border-bottom: 1px solid #e9ecef;
    transition: all 0.2s ease;
    background: #f8f9fa !important;
    white-space: nowrap !important;
    overflow: visible !important;
    text-overflow: unset !important;
    width: 100% !important;
    display: block !important;
    text-align: center !important;
    min-width: 100% !important;
    max-width: none !important;
}

.notification-dropdown .dropdown-footer .dropdown-item:hover {
    background-color: #e9ecef;
    color: #007bff;
}

.dropdown-menu.notification-dropdown {
    min-width: 300px !important;
    width: 300px !important;
    max-width: none !important;
    right: 0 !important;
    left: auto !important;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

#notificationCount.notification-badge {
    position: absolute !important;
    top: -2px !important;
    right: -2px !important;
    animation: pulse 2s infinite !important;
    font-size: 0.5rem !important;
    font-weight: 600 !important;
    min-width: 14px !important;
    height: 14px !important;
    line-height: 14px !important;
    padding: 0 4px !important;
    border: 1px solid #fff !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 4px rgba(52, 144, 220, 0.4), 0 1px 2px rgba(0, 0, 0, 0.2) !important;
    background: linear-gradient(135deg, #3490dc, #2980b9) !important;
    color: #fff !important;
    text-align: center !important;
    transform: scale(1) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    letter-spacing: 0.3px !important;
    z-index: 1000 !important;
}

#notificationCount.notification-badge:hover {
    animation: none !important;
    transform: scale(1.1) !important;
    box-shadow: 0 3px 6px rgba(52, 144, 220, 0.6), 0 1px 3px rgba(0, 0, 0, 0.3) !important;
    background: linear-gradient(135deg, #4a9eff, #3490dc) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Notification system initialized');
    
    // Load notifications immediately
    loadNotifications();
    
    // Set up notification polling
    setInterval(loadNotifications, 30000); // Check every 30 seconds
    
    // Also check every 5 seconds for the first minute to ensure it works
    let quickCheckCount = 0;
    const quickCheckInterval = setInterval(() => {
        loadNotifications();
        quickCheckCount++;
        if (quickCheckCount >= 12) { // 12 * 5 seconds = 1 minute
            clearInterval(quickCheckInterval);
        }
    }, 5000);
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.querySelector('.notification-dropdown');
        const bellIcon = document.querySelector('#notificationsDropdown');
        
        if (dropdown && bellIcon && !bellIcon.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
        }
    });
    
    // Close dropdown when navigating away
    window.addEventListener('beforeunload', function() {
        const dropdown = document.querySelector('.notification-dropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
    });
    
    // Initialize Bootstrap dropdown properly
    const dropdownElement = document.querySelector('#notificationsDropdown');
    if (dropdownElement && typeof bootstrap !== 'undefined') {
        new bootstrap.Dropdown(dropdownElement);
    }
});

function loadNotifications() {
    console.log('Loading notifications...');
    
    fetch('/notifications.php?action=get_recent')
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Notification data received:', data);
            if (data && data.success) {
                updateNotificationUI(data.notifications);
            } else {
                console.error('Invalid response format:', data);
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            // Don't show error to user, just log it
        });
}

function onNotificationDropdownOpen() {
    // Only mark as read when dropdown is actually opened by user click
    // Don't auto-mark as read on page load
}

function updateNotificationUI(notifications) {
    const notificationCount = document.getElementById('notificationCount');
    const notificationList = document.getElementById('notificationList');
    
    // Handle both boolean and integer values for is_read
    const unreadCount = notifications.filter(n => {
        const isRead = n.is_read;
        return isRead === false || isRead === 0 || isRead === '0' || isRead === null;
    }).length;
    
    console.log('Notifications:', notifications);
    console.log('Unread count:', unreadCount);
    
    if (unreadCount > 0) {
        notificationCount.textContent = unreadCount;
        notificationCount.style.display = 'block';
        notificationCount.style.opacity = '1';
        console.log('Badge shown with count:', unreadCount);
    } else {
        notificationCount.style.display = 'none';
        notificationCount.style.opacity = '0';
        console.log('Badge hidden');
    }
    
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <div class="text-center text-muted py-3">
                <i class="fas fa-bell-slash fa-2x mb-2"></i>
                <p class="mb-0">No new notifications</p>
            </div>
        `;
    } else {
        notificationList.innerHTML = notifications.slice(0, 3).map(notification => {
            const isRead = notification.is_read === true || notification.is_read === 1 || notification.is_read === '1';
            return `
            <div class="notification-item ${!isRead ? 'bg-light' : ''}" 
                 data-notification-id="${notification.id}" 
                 data-is-read="${isRead}">
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${formatTime(notification.created_at)}</div>
                </div>
                ${!isRead ? '<div class="notification-new"></div>' : ''}
            </div>
            `;
        }).join('');
        
        // Add click handlers to mark notifications as read
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.notificationId;
                const isRead = this.dataset.isRead === 'true' || this.dataset.isRead === '1';
                
                if (!isRead) {
                    markNotificationAsRead(notificationId);
                }
            });
        });
    }
}

function markNotificationAsRead(notificationId) {
    fetch('/notifications.php?action=mark_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${notificationId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload notifications to update the UI
            loadNotifications();
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

function markAllNotificationsAsRead() {
    fetch('/notifications.php?action=mark_all_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide the notification badge immediately
            const notificationCount = document.getElementById('notificationCount');
            notificationCount.style.display = 'none';
            notificationCount.style.opacity = '0';
            
            // Close the dropdown
            const dropdown = document.querySelector('.notification-dropdown');
            if (dropdown) {
                dropdown.classList.remove('show');
            }
            
            // Reload notifications to update the UI
            setTimeout(() => {
                loadNotifications();
            }, 100);
            
            console.log('All notifications marked as read');
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
    });
}

function getNotificationIcon(type) {
    const icons = {
        'payment_due': 'exclamation-triangle',
        'payment_overdue': 'exclamation-circle',
        'loan_approved': 'check-circle',
        'loan_rejected': 'times-circle',
        'loan_application': 'file-alt',
        'loan_approval': 'check-circle',
        'loan_rejection': 'times-circle',
        'agent_assignment': 'user-plus',
        'collection_reminder': 'bell',
        'payment_confirmation': 'check-circle',
        'cycle_completion': 'trophy',
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






