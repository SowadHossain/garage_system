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
        $where_conditions[] = "a.appointment_datetime >= ?";
        $types .= 's';
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $where_conditions[] = "a.appointment_datetime <= ?";
        $types .= 's';
        $params[] = $date_to;
    }
    
    if ($mechanic_id > 0) {
        $where_conditions[] = "EXISTS (SELECT 1 FROM jobs WHERE appointment_id = a.appointment_id AND mechanic_id = ?)";
        $types .= 'i';
        $params[] = $mechanic_id;
    }
    
    if ($service_id > 0) {
        $where_conditions[] = "EXISTS (SELECT 1 FROM jobs j JOIN job_services js ON j.job_id = js.job_id WHERE j.appointment_id = a.appointment_id AND js.service_id = ?)";
        $types .= 'i';
        $params[] = $service_id;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get appointment status distribution
    $query = "
        SELECT 
            a.status,
            COUNT(*) as count
        FROM appointments a
        $where_clause
        GROUP BY a.status
        ORDER BY count DESC
    ";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $statuses = [];
    $counts = [];
    
    while ($row = $result->fetch_assoc()) {
        $statuses[] = ucfirst($row['status']);
        $counts[] = (int)$row['count'];
    }
    
    // If no data, add placeholder
    if (empty($statuses)) {
        $statuses[] = 'No Appointments';
        $counts[] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'statuses' => $statuses,
        'counts' => $counts
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
