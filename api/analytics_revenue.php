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
    
    // Get overall revenue metrics
    $query = "
        SELECT 
            COALESCE(SUM(b.total_amount), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_amount ELSE 0 END), 0) as paid_revenue,
            COALESCE(SUM(CASE WHEN b.payment_status != 'paid' THEN b.total_amount ELSE 0 END), 0) as unpaid_revenue,
            COUNT(DISTINCT CASE WHEN j.status = 'completed' THEN j.job_id END) as completed_jobs
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
    $metrics = $stmt->get_result()->fetch_assoc();
    
    // Get monthly revenue trend
    $trend_query = "
        SELECT 
            DATE_FORMAT(b.bill_date, '%Y-%m') as month,
            COALESCE(SUM(b.total_amount), 0) as revenue,
            COALESCE(SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_amount ELSE 0 END), 0) as paid
        FROM bills b
        JOIN jobs j ON b.job_id = j.job_id
        JOIN appointments a ON j.appointment_id = a.appointment_id
        $where_clause
        GROUP BY DATE_FORMAT(b.bill_date, '%Y-%m')
        ORDER BY month ASC
    ";
    
    $trend_stmt = $conn->prepare($trend_query);
    if (!empty($params)) {
        $trend_stmt->bind_param($types, ...$params);
    }
    $trend_stmt->execute();
    $trend_result = $trend_stmt->get_result();
    
    $months = [];
    $revenue_data = [];
    $paid_data = [];
    
    while ($row = $trend_result->fetch_assoc()) {
        $months[] = date('M Y', strtotime($row['month'] . '-01'));
        $revenue_data[] = (float)$row['revenue'];
        $paid_data[] = (float)$row['paid'];
    }
    
    // If no data, add current month as placeholder
    if (empty($months)) {
        $months[] = date('M Y');
        $revenue_data[] = 0;
        $paid_data[] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'total_revenue' => (float)$metrics['total_revenue'],
        'paid_revenue' => (float)$metrics['paid_revenue'],
        'unpaid_revenue' => (float)$metrics['unpaid_revenue'],
        'completed_jobs' => (int)$metrics['completed_jobs'],
        'months' => $months,
        'revenue_data' => $revenue_data,
        'paid_data' => $paid_data
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
