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
        $where_conditions[] = "EXISTS (SELECT 1 FROM job_services WHERE job_id = j.job_id AND service_id = ?)";
        $types .= 'i';
        $params[] = $service_id;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get mechanic performance
    $query = "
        SELECT 
            COALESCE(s.name, 'Unassigned') as mechanic_name,
            s.staff_id,
            COUNT(DISTINCT j.job_id) as job_count,
            SUM(CASE WHEN j.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            COALESCE(SUM(b.total_amount), 0) as revenue,
            AVG(TIMESTAMPDIFF(HOUR, j.job_date, j.completion_date)) as avg_hours
        FROM staff s
        LEFT JOIN jobs j ON s.staff_id = j.mechanic_id
        LEFT JOIN bills b ON j.job_id = b.job_id
        LEFT JOIN appointments a ON j.appointment_id = a.appointment_id
        WHERE s.role = 'mechanic' AND s.active = 1 $where_clause
        GROUP BY s.staff_id, s.name
        ORDER BY job_count DESC
    ";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $bound_types = 's';
        $bound_params = [$date_from];
        if ($date_to && count($params) > 1) {
            $bound_types .= 's';
            $bound_params[] = $date_to;
        }
        // Add other params as needed
        foreach ($params as $i => $param) {
            if ($i > 0 || !$date_from) {
                if (is_int($param)) {
                    $bound_types .= 'i';
                } else {
                    $bound_types .= 's';
                }
                $bound_params[] = $param;
            }
        }
        $stmt->bind_param($bound_types, ...$bound_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $mechanics = [];
    $job_counts = [];
    
    while ($row = $result->fetch_assoc()) {
        $mechanics[] = $row['mechanic_name'];
        $job_counts[] = (int)$row['job_count'];
    }
    
    // If no mechanics, add placeholder
    if (empty($mechanics)) {
        $mechanics[] = 'No Mechanics';
        $job_counts[] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'mechanics' => $mechanics,
        'job_counts' => $job_counts
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
