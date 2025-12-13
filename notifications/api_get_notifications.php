<?php
// API: Get Notifications for Current User
// Returns unread count and recent notifications

session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Not authenticated',
        'notifications' => [],
        'unread_count' => 0
    ]);
    exit;
}

$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

try {
    // Get unread count
    $count_query = "SELECT COUNT(*) as unread_count 
                    FROM notifications 
                    WHERE user_type = ? 
                    AND user_id = ? 
                    AND is_read = FALSE";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("si", $user_type, $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $unread_count = $count_result->fetch_assoc()['unread_count'];
    
    // Get recent notifications (last 20)
    $notif_query = "SELECT 
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
                    WHERE n.user_type = ? 
                    AND n.user_id = ?
                    ORDER BY n.is_read ASC, n.created_at DESC
                    LIMIT 20";
    
    $notif_stmt = $conn->prepare($notif_query);
    $notif_stmt->bind_param("si", $user_type, $user_id);
    $notif_stmt->execute();
    $notif_result = $notif_stmt->get_result();
    
    $notifications = [];
    while ($row = $notif_result->fetch_assoc()) {
        $notifications[] = [
            'notification_id' => $row['notification_id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'link_url' => $row['link_url'],
            'is_read' => (bool)$row['is_read'],
            'priority' => $row['priority'],
            'created_at' => $row['created_at'],
            'read_at' => $row['read_at'],
            'type_code' => $row['type_code'],
            'type_description' => $row['type_description']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count
    ]);
    
} catch (Exception $e) {
    error_log("Notification API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load notifications',
        'notifications' => [],
        'unread_count' => 0
    ]);
}

$conn->close();
?>
