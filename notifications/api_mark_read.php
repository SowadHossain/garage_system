<?php
// API: Mark Single Notification as Read
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['notification_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing notification_id']);
    exit;
}

$notification_id = intval($input['notification_id']);
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

try {
    // Update notification (verify ownership)
    $query = "UPDATE notifications 
              SET is_read = TRUE, read_at = NOW()
              WHERE notification_id = ? 
              AND user_type = ? 
              AND user_id = ?
              AND is_read = FALSE";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isi", $notification_id, $user_type, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Notification not found or already read']);
    }
    
} catch (Exception $e) {
    error_log("Mark Read Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to mark notification']);
}

$conn->close();
?>
