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
    // Build WHERE clause for bills
    $bill_where_conditions = [];
    $bill_types = '';
    $bill_params = [];
    
    if ($date_from) {
        $bill_where_conditions[] = "b.bill_date >= ?";
        $bill_types .= 's';
        $bill_params[] = $date_from;
    }
    
    if ($date_to) {
        $bill_where_conditions[] = "b.bill_date <= ?";
        $bill_types .= 's';
        $bill_params[] = $date_to;
    }
    
    if ($mechanic_id > 0) {
        $bill_where_conditions[] = "j.mechanic_id = ?";
        $bill_types .= 'i';
        $bill_params[] = $mechanic_id;
    }
    
    if ($service_id > 0) {
        $bill_where_conditions[] = "EXISTS (SELECT 1 FROM job_services WHERE job_id = j.job_id AND service_id = ?)";
        $bill_types .= 'i';
        $bill_params[] = $service_id;
    }
    
    $bill_where_clause = !empty($bill_where_conditions) ? "WHERE " . implode(" AND ", $bill_where_conditions) : "";
    
    // Build WHERE clause for appointments
    $appt_where_conditions = [];
    $appt_types = '';
    $appt_params = [];
    
    if ($date_from) {
        $appt_where_conditions[] = "a.appointment_datetime >= ?";
        $appt_types .= 's';
        $appt_params[] = $date_from;
    }
    
    if ($date_to) {
        $appt_where_conditions[] = "a.appointment_datetime <= ?";
        $appt_types .= 's';
        $appt_params[] = $date_to;
    }
    
    if ($mechanic_id > 0) {
        $appt_where_conditions[] = "EXISTS (SELECT 1 FROM jobs WHERE appointment_id = a.appointment_id AND mechanic_id = ?)";
        $appt_types .= 'i';
        $appt_params[] = $mechanic_id;
    }
    
    if ($service_id > 0) {
        $appt_where_conditions[] = "EXISTS (SELECT 1 FROM jobs j JOIN job_services js ON j.job_id = js.job_id WHERE j.appointment_id = a.appointment_id AND js.service_id = ?)";
        $appt_types .= 'i';
        $appt_params[] = $service_id;
    }
    
    $appt_where_clause = !empty($appt_where_conditions) ? "WHERE " . implode(" AND ", $appt_where_conditions) : "";
    
    // Get all summary metrics
    $metrics = [];
    
    // Total Bills
    $query = "SELECT COUNT(*) as count FROM bills b JOIN jobs j ON b.job_id = j.job_id $bill_where_clause";
    $stmt = $conn->prepare($query);
    if (!empty($bill_params)) $stmt->bind_param($bill_types, ...$bill_params);
    $stmt->execute();
    $metrics['total_bills'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Total Appointments
    $query = "SELECT COUNT(*) as count FROM appointments a $appt_where_clause";
    $stmt = $conn->prepare($query);
    if (!empty($appt_params)) $stmt->bind_param($appt_types, ...$appt_params);
    $stmt->execute();
    $metrics['total_appointments'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Completed Appointments
    $query = "SELECT COUNT(*) as count FROM appointments a WHERE a.status = 'completed' $appt_where_clause";
    $stmt = $conn->prepare($query);
    if (!empty($appt_params)) $stmt->bind_param($appt_types, ...$appt_params);
    $stmt->execute();
    $metrics['completed_appointments'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Average Bill Amount
    $query = "SELECT AVG(total_amount) as avg FROM bills b JOIN jobs j ON b.job_id = j.job_id $bill_where_clause";
    $stmt = $conn->prepare($query);
    if (!empty($bill_params)) $stmt->bind_param($bill_types, ...$bill_params);
    $stmt->execute();
    $avg = $stmt->get_result()->fetch_assoc()['avg'];
    $metrics['average_bill_amount'] = round($avg ?? 0, 2);
    
    // Total Jobs
    $query = "SELECT COUNT(*) as count FROM jobs j JOIN appointments a ON j.appointment_id = a.appointment_id $appt_where_clause";
    $stmt = $conn->prepare($query);
    if (!empty($appt_params)) $stmt->bind_param($appt_types, ...$appt_params);
    $stmt->execute();
    $metrics['total_jobs'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Unique Customers
    $query = "SELECT COUNT(DISTINCT c.customer_id) as count FROM customers c JOIN appointments a ON c.customer_id = a.customer_id $appt_where_clause";
    $stmt = $conn->prepare($query);
    if (!empty($appt_params)) $stmt->bind_param($appt_types, ...$appt_params);
    $stmt->execute();
    $metrics['unique_customers'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Unique Mechanics
    $query = "SELECT COUNT(DISTINCT j.mechanic_id) as count FROM jobs j JOIN appointments a ON j.appointment_id = a.appointment_id WHERE j.mechanic_id IS NOT NULL $appt_where_clause";
    $stmt = $conn->prepare($query);
    if (!empty($appt_params)) $stmt->bind_param($appt_types, ...$appt_params);
    $stmt->execute();
    $metrics['active_mechanics'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Collection Rate
    $query = "SELECT COUNT(CASE WHEN b.payment_status = 'paid' THEN 1 END) as paid, COUNT(*) as total FROM bills b JOIN jobs j ON b.job_id = j.job_id $bill_where_clause";
    $stmt = $conn->prepare($query);
    if (!empty($bill_params)) $stmt->bind_param($bill_types, ...$bill_params);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $collection_rate = $result['total'] > 0 ? round(($result['paid'] / $result['total']) * 100, 1) : 0;
    $metrics['collection_rate_percent'] = $collection_rate;
    
    // Completion Rate
    $query = "SELECT COUNT(CASE WHEN j.status = 'completed' THEN 1 END) as completed, COUNT(*) as total FROM jobs j JOIN appointments a ON j.appointment_id = a.appointment_id $appt_where_clause";
    $stmt = $conn->prepare($query);
    if (!empty($appt_params)) $stmt->bind_param($appt_types, ...$appt_params);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $completion_rate = $result['total'] > 0 ? round(($result['completed'] / $result['total']) * 100, 1) : 0;
    $metrics['completion_rate_percent'] = $completion_rate;
    
    echo json_encode([
        'success' => true,
        'metrics' => $metrics
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
