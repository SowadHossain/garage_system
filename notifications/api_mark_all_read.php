<?php
// API: Mark All Notifications as Read
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

try {
    // Update all unread notifications for this user
    $query = "UPDATE notifications 
              SET is_read = TRUE, read_at = NOW()
              WHERE user_type = ? 
              AND user_id = ?
              AND is_read = FALSE";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $user_type, $user_id);
    $stmt->execute();
    
    $affected = $stmt->affected_rows;
    
    echo json_encode([
        'success' => true, 
        'message' => "$affected notifications marked as read",
        'count' => $affected
    ]);
    
} catch (Exception $e) {
    error_log("Mark All Read Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to mark notifications']);
}

$conn->close();
?>
