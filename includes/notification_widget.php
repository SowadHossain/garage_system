<!-- Notification Center Widget -->
<!-- Include this in header.php for all dashboards -->
<style>
.notification-bell {
    position: relative;
    cursor: pointer;
    font-size: 1.5rem;
    color: white;
    transition: all 0.3s;
    padding: 5px 10px;
    border-radius: 8px;
}

.notification-bell:hover {
    background-color: rgba(255, 255, 255, 0.1);
    transform: scale(1.05);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.75rem;
    font-weight: bold;
    min-width: 20px;
    text-align: center;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 400px;
    max-height: 500px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1050;
    display: none;
    margin-top: 10px;
}

.notification-dropdown.show {
    display: block;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-header {
    padding: 15px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h6 {
    margin: 0;
    font-weight: 600;
}

.notification-list {
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e7f3ff;
}

.notification-item.unread:hover {
    background-color: #d0e9ff;
}

.notification-item-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 5px;
}

.notification-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #212529;
    flex: 1;
}

.notification-time {
    font-size: 0.75rem;
    color: #6c757d;
    white-space: nowrap;
    margin-left: 10px;
}

.notification-message {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.notification-priority {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-urgent {
    background-color: #dc3545;
    color: white;
}

.priority-high {
    background-color: #fd7e14;
    color: white;
}

.priority-normal {
    background-color: #0dcaf0;
    color: white;
}

.priority-low {
    background-color: #6c757d;
    color: white;
}

.notification-footer {
    padding: 10px 15px;
    border-top: 1px solid #dee2e6;
    text-align: center;
}

.notification-footer a {
    color: #0d6efd;
    text-decoration: none;
    font-size: 0.9rem;
}

.notification-footer a:hover {
    text-decoration: underline;
}

.empty-notifications {
    padding: 40px 20px;
    text-align: center;
    color: #6c757d;
}

.empty-notifications i {
    font-size: 3rem;
    margin-bottom: 10px;
    opacity: 0.3;
}
</style>

<div class="notification-center" style="position: relative; display: inline-block;">
    <div class="notification-bell" id="notificationBell">
        <i class="bi bi-bell"></i>
        <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
    </div>
    
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <h6>Notifications</h6>
            <button class="btn btn-sm btn-link text-primary" id="markAllRead" style="text-decoration: none; padding: 0;">
                Mark all as read
            </button>
        </div>
        
        <div class="notification-list" id="notificationList">
            <div class="empty-notifications">
                <i class="bi bi-bell-slash"></i>
                <p>No notifications</p>
            </div>
        </div>
        
        <div class="notification-footer">
            <a href="/garage_system/notifications/list.php">View all notifications</a>
        </div>
    </div>
</div>

<script>
// Notification Center JavaScript
(function() {
    const bell = document.getElementById('notificationBell');
    const dropdown = document.getElementById('notificationDropdown');
    const count = document.getElementById('notificationCount');
    const list = document.getElementById('notificationList');
    const markAllBtn = document.getElementById('markAllRead');
    
    // Toggle dropdown
    bell.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('show');
        if (dropdown.classList.contains('show')) {
            loadNotifications();
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && e.target !== bell) {
            dropdown.classList.remove('show');
        }
    });
    
    // Load notifications via AJAX
    function loadNotifications() {
        fetch('/garage_system/notifications/api_get_notifications.php')
            .then(response => response.json())
            .then(data => {
                renderNotifications(data.notifications);
                updateBadge(data.unread_count);
            })
            .catch(error => console.error('Error loading notifications:', error));
    }
    
    // Render notifications
    function renderNotifications(notifications) {
        if (!notifications || notifications.length === 0) {
            list.innerHTML = `
                <div class="empty-notifications">
                    <i class="bi bi-bell-slash"></i>
                    <p>No notifications</p>
                </div>
            `;
            return;
        }
        
        const html = notifications.map(notif => `
            <div class="notification-item ${notif.is_read ? '' : 'unread'}" 
                 data-id="${notif.notification_id}"
                 onclick="handleNotificationClick(${notif.notification_id}, '${notif.link_url || ''}')">
                <div class="notification-item-header">
                    <div class="notification-title">${notif.title}</div>
                    <div class="notification-time">${formatTime(notif.created_at)}</div>
                </div>
                <div class="notification-message">${notif.message}</div>
                ${notif.priority !== 'normal' ? `<span class="notification-priority priority-${notif.priority}">${notif.priority}</span>` : ''}
            </div>
        `).join('');
        
        list.innerHTML = html;
    }
    
    // Update badge count
    function updateBadge(unreadCount) {
        if (unreadCount > 0) {
            count.textContent = unreadCount > 99 ? '99+' : unreadCount;
            count.style.display = 'block';
        } else {
            count.style.display = 'none';
        }
    }
    
    // Format timestamp
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // seconds
        
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
        
        return date.toLocaleDateString();
    }
    
    // Handle notification click
    window.handleNotificationClick = function(notificationId, linkUrl) {
        // Mark as read
        fetch('/garage_system/notifications/api_mark_read.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({notification_id: notificationId})
        }).then(() => {
            if (linkUrl && linkUrl !== 'null' && linkUrl !== '') {
                window.location.href = linkUrl;
            } else {
                loadNotifications();
            }
        });
    };
    
    // Mark all as read
    markAllBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        fetch('/garage_system/notifications/api_mark_all_read.php', {
            method: 'POST'
        }).then(() => {
            loadNotifications();
        });
    });
    
    // Poll for new notifications every 30 seconds
    loadNotifications(); // Initial load
    setInterval(loadNotifications, 30000);
    
    // Server-Sent Events for real-time updates (optional enhancement)
    if (typeof(EventSource) !== "undefined") {
        const evtSource = new EventSource('/garage_system/notifications/sse_stream.php');
        evtSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            if (data.new_notification) {
                loadNotifications();
                // Optional: Show browser notification
                if ("Notification" in window && Notification.permission === "granted") {
                    new Notification(data.title, {
                        body: data.message,
                        icon: '/garage_system/assets/img/notification-icon.png'
                    });
                }
            }
        };
    }
})();
</script>
