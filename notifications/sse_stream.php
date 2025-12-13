<?php
// Server-Sent Events Stream for Real-Time Notifications
session_start();
require_once __DIR__ . '/../config/db.php';

// Check authentication
if (!isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable Nginx buffering

// Prevent PHP output buffering
if (ob_get_level()) ob_end_clean();

// Track last notification ID to detect new ones
$last_notification_id = 0;

// Get initial last notification ID
$query = "SELECT MAX(notification_id) as last_id 
          FROM notifications 
          WHERE user_type = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $user_type, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$last_notification_id = $row['last_id'] ?? 0;

// Send initial connection message
echo "data: " . json_encode(['connected' => true, 'timestamp' => date('c')]) . "\n\n";
flush();

// Keep connection alive and check for new notifications
$check_interval = 5; // Check every 5 seconds
$max_duration = 300; // Close connection after 5 minutes (client will reconnect)
$start_time = time();

while (true) {
    // Check if connection is still alive
    if (connection_aborted()) {
        break;
    }
    
    // Check if max duration reached
    if (time() - $start_time > $max_duration) {
        echo "event: timeout\n";
        echo "data: " . json_encode(['message' => 'Connection timeout, reconnecting...']) . "\n\n";
        flush();
        break;
    }
    
    // Query for new notifications
    $query = "SELECT 
                n.notification_id,
                n.title,
                n.message,
                n.link_url,
                n.priority,
                n.created_at,
                nt.code as type_code
              FROM notifications n
              INNER JOIN notification_types nt ON n.notification_type_id = nt.notification_type_id
              WHERE n.user_type = ? 
              AND n.user_id = ?
              AND n.notification_id > ?
              ORDER BY n.notification_id ASC
              LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $user_type, $user_id, $last_notification_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $new_notifications = [];
    while ($row = $result->fetch_assoc()) {
        $new_notifications[] = $row;
        $last_notification_id = max($last_notification_id, $row['notification_id']);
    }
    
    // Send new notifications
    if (!empty($new_notifications)) {
        foreach ($new_notifications as $notif) {
            echo "event: notification\n";
            echo "data: " . json_encode([
                'new_notification' => true,
                'notification_id' => $notif['notification_id'],
                'title' => $notif['title'],
                'message' => $notif['message'],
                'link_url' => $notif['link_url'],
                'priority' => $notif['priority'],
                'type_code' => $notif['type_code'],
                'created_at' => $notif['created_at']
            ]) . "\n\n";
            flush();
        }
    }
    
    // Send heartbeat to keep connection alive
    if (time() % 30 == 0) {
        echo "event: heartbeat\n";
        echo "data: " . json_encode(['timestamp' => date('c')]) . "\n\n";
        flush();
    }
    
    // Sleep before next check
    sleep($check_interval);
}

$conn->close();
?>
