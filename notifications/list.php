<?php
// View All Notifications Page
session_start();
require_once __DIR__ . '/../config/db.php';

// Check authentication
if (!isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    header("Location: /garage_system/public/login.php");
    exit;
}

$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build WHERE clause
$where_conditions = ["n.user_type = ?", "n.user_id = ?"];
$params = [$user_type, $user_id];
$param_types = "si";

if ($filter === 'unread') {
    $where_conditions[] = "n.is_read = FALSE";
} elseif ($filter === 'urgent') {
    $where_conditions[] = "n.priority = 'urgent'";
} elseif ($filter === 'high') {
    $where_conditions[] = "n.priority IN ('urgent', 'high')";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total 
                FROM notifications n
                WHERE $where_clause";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($param_types, ...$params);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);

// Get notifications
$query = "SELECT 
            n.notification_id,
            n.title,
            n.message,
            n.link_url,
            n.is_read,
            n.priority,
            n.created_at,
            n.read_at,
            n.related_entity,
            n.related_id,
            nt.code as type_code,
            nt.description as type_description
          FROM notifications n
          INNER JOIN notification_types nt ON n.notification_type_id = nt.notification_type_id
          WHERE $where_clause
          ORDER BY n.is_read ASC, n.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$params[] = $per_page;
$params[] = $offset;
$param_types .= "ii";
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$page_title = "Notifications";
include __DIR__ . '/../includes/header.php';
?>

<style>
.notification-page {
    max-width: 900px;
    margin: 0 auto;
}

.notification-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.notification-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 12px;
    transition: all 0.3s;
    cursor: pointer;
}

.notification-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.notification-card.unread {
    background: linear-gradient(135deg, #e7f3ff 0%, #f0f9ff 100%);
    border-left: 4px solid #0d6efd;
}

.notification-card-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
}

.notification-card-title {
    font-weight: 600;
    font-size: 1.1rem;
    color: #212529;
    flex: 1;
}

.notification-card-meta {
    display: flex;
    gap: 10px;
    align-items: center;
}

.notification-card-time {
    font-size: 0.85rem;
    color: #6c757d;
}

.notification-card-message {
    color: #495057;
    margin-bottom: 10px;
}

.notification-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-urgent {
    background-color: #dc3545;
    color: white;
}

.badge-high {
    background-color: #fd7e14;
    color: white;
}

.badge-normal {
    background-color: #0dcaf0;
    color: white;
}

.badge-low {
    background-color: #6c757d;
    color: white;
}

.notification-actions {
    display: flex;
    gap: 10px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.3;
}
</style>

<div class="notification-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-bell"></i> Notifications</h2>
        <a href="settings.php" class="btn btn-outline-primary">
            <i class="bi bi-gear"></i> Settings
        </a>
    </div>
    
    <!-- Filters -->
    <div class="notification-filters">
        <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
            All (<?php echo $total; ?>)
        </a>
        <?php
        // Count unread
        $unread_query = "SELECT COUNT(*) as cnt FROM notifications WHERE user_type = ? AND user_id = ? AND is_read = FALSE";
        $unread_stmt = $conn->prepare($unread_query);
        $unread_stmt->bind_param("si", $user_type, $user_id);
        $unread_stmt->execute();
        $unread_count = $unread_stmt->get_result()->fetch_assoc()['cnt'];
        ?>
        <a href="?filter=unread" class="btn <?php echo $filter === 'unread' ? 'btn-primary' : 'btn-outline-primary'; ?>">
            Unread <?php if ($unread_count > 0) echo "($unread_count)"; ?>
        </a>
        <a href="?filter=urgent" class="btn <?php echo $filter === 'urgent' ? 'btn-danger' : 'btn-outline-danger'; ?>">
            Urgent
        </a>
        <a href="?filter=high" class="btn <?php echo $filter === 'high' ? 'btn-warning' : 'btn-outline-warning'; ?>">
            High Priority
        </a>
        
        <div class="ms-auto">
            <form method="POST" action="api_mark_all_read.php" style="display: inline;">
                <button type="submit" class="btn btn-outline-secondary" <?php echo $unread_count == 0 ? 'disabled' : ''; ?>>
                    <i class="bi bi-check-all"></i> Mark All Read
                </button>
            </form>
        </div>
    </div>
    
    <!-- Notifications List -->
    <?php if ($result->num_rows > 0): ?>
        <?php while ($notif = $result->fetch_assoc()): ?>
            <div class="notification-card <?php echo !$notif['is_read'] ? 'unread' : ''; ?>" 
                 onclick="handleNotificationClick(<?php echo $notif['notification_id']; ?>, '<?php echo htmlspecialchars($notif['link_url']); ?>')">
                
                <div class="notification-card-header">
                    <div class="notification-card-title">
                        <?php if (!$notif['is_read']): ?>
                            <i class="bi bi-dot text-primary" style="font-size: 1.5rem; vertical-align: middle;"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($notif['title']); ?>
                    </div>
                    
                    <div class="notification-card-meta">
                        <?php if ($notif['priority'] !== 'normal'): ?>
                            <span class="notification-badge badge-<?php echo $notif['priority']; ?>">
                                <?php echo strtoupper($notif['priority']); ?>
                            </span>
                        <?php endif; ?>
                        <span class="notification-card-time">
                            <i class="bi bi-clock"></i>
                            <?php
                            $time_ago = time() - strtotime($notif['created_at']);
                            if ($time_ago < 60) echo "Just now";
                            elseif ($time_ago < 3600) echo floor($time_ago / 60) . "m ago";
                            elseif ($time_ago < 86400) echo floor($time_ago / 3600) . "h ago";
                            else echo date('M d, Y', strtotime($notif['created_at']));
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="notification-card-message">
                    <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
                </div>
                
                <div class="notification-card-footer">
                    <small class="text-muted">
                        <i class="bi bi-tag"></i> <?php echo htmlspecialchars($notif['type_description']); ?>
                    </small>
                    
                    <?php if ($notif['read_at']): ?>
                        <small class="text-muted">
                            Read on <?php echo date('M d, Y', strtotime($notif['read_at'])); ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-bell-slash"></i>
            <h4>No notifications</h4>
            <p>You're all caught up!</p>
        </div>
    <?php endif; ?>
</div>

<script>
function handleNotificationClick(notificationId, linkUrl) {
    // Mark as read
    fetch('/garage_system/notifications/api_mark_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({notification_id: notificationId})
    }).then(() => {
        if (linkUrl && linkUrl !== 'null' && linkUrl !== '') {
            window.location.href = linkUrl;
        } else {
            location.reload();
        }
    });
}
</script>

<?php
include __DIR__ . '/../includes/footer.php';
$conn->close();
?>
