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
$mechanic_id = (int)($_GET['mechanic_id'] ?? 0);
$service_id = (int)($_GET['service_id'] ?? 0);

try {
    // Build WHERE clause for filtering
    $where_conditions = [];
    $types = '';
    $params = [];
    
    if ($date_from) {
        $where_conditions[] = "b.bill_date >= ?";
        $types .= 's';
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $where_conditions[] = "b.bill_date <= ?";
        $types .= 's';
        $params[] = $date_to;
    }
    
    if ($mechanic_id > 0) {
        $where_conditions[] = "j.mechanic_id = ?";
        $types .= 'i';
        $params[] = $mechanic_id;
    }
    
    if ($service_id > 0) {
        $where_conditions[] = "EXISTS (SELECT 1 FROM job_services WHERE job_id = j.job_id AND service_id = ?)";
        $types .= 'i';
        $params[] = $service_id;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get payment status counts
    $query = "
        SELECT 
            COUNT(CASE WHEN b.payment_status = 'paid' THEN 1 END) as paid_count,
            COUNT(CASE WHEN b.payment_status != 'paid' THEN 1 END) as unpaid_count
        FROM bills b
        JOIN jobs j ON b.job_id = j.job_id
        JOIN appointments a ON j.appointment_id = a.appointment_id
        $where_clause
    ";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'paid_count' => (int)$row['paid_count'],
        'unpaid_count' => (int)$row['unpaid_count']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
