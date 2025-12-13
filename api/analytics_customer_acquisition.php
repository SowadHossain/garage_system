<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Validate session
if (!isset($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get filters from GET parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

try {
    // Build WHERE clause for filtering
    $where_conditions = [];
    $types = '';
    $params = [];
    
    if ($date_from) {
        $where_conditions[] = "c.created_at >= ?";
        $types .= 's';
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $where_conditions[] = "c.created_at <= ?";
        $types .= 's';
        $params[] = $date_to;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get monthly customer acquisition
    $query = "
        SELECT 
            DATE_FORMAT(c.created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM customers c
        $where_clause
        GROUP BY DATE_FORMAT(c.created_at, '%Y-%m')
        ORDER BY month ASC
    ";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $months = [];
    $customer_counts = [];
    
    while ($row = $result->fetch_assoc()) {
        $months[] = date('M Y', strtotime($row['month'] . '-01'));
        $customer_counts[] = (int)$row['count'];
    }
    
    // If no data, add current month as placeholder
    if (empty($months)) {
        $months[] = date('M Y');
        $customer_counts[] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'months' => $months,
        'customer_counts' => $customer_counts
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
