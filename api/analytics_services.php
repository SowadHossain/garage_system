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
        $where_conditions[] = "j.job_date >= ?";
        $types .= 's';
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $where_conditions[] = "j.job_date <= ?";
        $types .= 's';
        $params[] = $date_to;
    }
    
    if ($mechanic_id > 0) {
        $where_conditions[] = "j.mechanic_id = ?";
        $types .= 'i';
        $params[] = $mechanic_id;
    }
    
    if ($service_id > 0) {
        $where_conditions[] = "js.service_id = ?";
        $types .= 'i';
        $params[] = $service_id;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get top services by count
    $query = "
        SELECT 
            s.name,
            COUNT(DISTINCT js.job_id) as count,
            COALESCE(SUM(b.total_amount), 0) as revenue
        FROM services s
        LEFT JOIN job_services js ON s.service_id = js.service_id
        LEFT JOIN jobs j ON js.job_id = j.job_id
        LEFT JOIN bills b ON j.job_id = b.job_id
        $where_clause
        GROUP BY s.service_id, s.name
        ORDER BY count DESC
        LIMIT 10
    ";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $services = [];
    $counts = [];
    
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['name'])) {
            $services[] = $row['name'];
            $counts[] = (int)$row['count'];
        }
    }
    
    // If no data, add placeholder
    if (empty($services)) {
        $services[] = 'No Services';
        $counts[] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'services' => $services,
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
