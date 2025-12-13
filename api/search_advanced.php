<?php
/**
 * Advanced Search API
 * Handles dynamic filtering and searching across appointments, bills, and jobs
 * Returns JSON data for AJAX requests
 */

header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/role_check.php';

try {
    // Check authentication
    if (!isset($_SESSION['staff_id'])) {
        throw new Exception('Unauthorized access');
    }
    
    // Get parameters
    $entity = isset($_GET['entity']) ? trim($_GET['entity']) : '';
    $search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
    $date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : [];
    $staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;
    $price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : 0;
    $price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : PHP_INT_MAX;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    // Validate entity type
    if (!in_array($entity, ['appointments', 'bills', 'jobs'])) {
        throw new Exception('Invalid entity type');
    }
    
    // Ensure status is array
    if (!is_array($status)) {
        $status = !empty($status) ? explode(',', $status) : [];
    }
    
    $response = [
        'success' => true,
        'entity' => $entity,
        'data' => [],
        'total' => 0,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => 0,
        'filters_applied' => []
    ];
    
    // Build WHERE clause based on filters
    $where_conditions = [];
    $where_params = [];
    $param_types = '';
    
    // Add status filter
    if (!empty($status)) {
        $placeholders = implode(',', array_fill(0, count($status), '?'));
        $where_conditions[] = "status IN ($placeholders)";
        $where_params = array_merge($where_params, $status);
        $param_types .= str_repeat('s', count($status));
        $response['filters_applied'][] = 'status: ' . implode(', ', $status);
    }
    
    // Add date filters
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(date_column) >= ?";
        $where_params[] = $date_from;
        $param_types .= 's';
        $response['filters_applied'][] = 'from: ' . $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(date_column) <= ?";
        $where_params[] = $date_to;
        $param_types .= 's';
        $response['filters_applied'][] = 'to: ' . $date_to;
    }
    
    // Add search term filter
    if (!empty($search_term)) {
        $response['filters_applied'][] = 'search: ' . $search_term;
    }
    
    // Build query based on entity type
    switch ($entity) {
        case 'appointments':
            // Search: Customer name, vehicle registration, problem description
            if (!empty($search_term)) {
                $where_conditions[] = "(c.name LIKE ? OR v.registration_no LIKE ? OR a.problem_description LIKE ?)";
                $search_param = '%' . $search_term . '%';
                $where_params = array_merge($where_params, [$search_param, $search_param, $search_param]);
                $param_types .= 'sss';
            }
            
            // Date filter uses appointment_datetime
            $where_conditions_final = [];
            foreach ($where_conditions as $cond) {
                $cond = str_replace('date_column', 'a.appointment_datetime', $cond);
                $where_conditions_final[] = $cond;
            }
            $where_clause = !empty($where_conditions_final) ? 'WHERE ' . implode(' AND ', $where_conditions_final) : '';
            
            // Count total
            $count_sql = "SELECT COUNT(*) as total FROM appointments a
                         JOIN customers c ON a.customer_id = c.customer_id
                         LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                         $where_clause";
            
            $count_stmt = $conn->prepare($count_sql);
            if (!empty($where_params) && !empty($param_types)) {
                $count_stmt->bind_param($param_types, ...$where_params);
            }
            $count_stmt->execute();
            $response['total'] = $count_stmt->get_result()->fetch_assoc()['total'];
            $count_stmt->close();
            
            // Get data
            $sql = "SELECT a.appointment_id, a.appointment_datetime, a.status, 
                           c.customer_id, c.name as customer_name, c.phone,
                           v.vehicle_id, v.registration_no, v.brand, v.model,
                           a.problem_description
                    FROM appointments a
                    JOIN customers c ON a.customer_id = c.customer_id
                    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                    $where_clause
                    ORDER BY a.appointment_datetime DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($sql);
            $limit_params = [$per_page, $offset];
            $combined_params = array_merge($where_params, $limit_params);
            $combined_types = $param_types . 'ii';
            
            if (!empty($combined_params)) {
                $stmt->bind_param($combined_types, ...$combined_params);
            }
            $stmt->execute();
            $response['data'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            break;
            
        case 'bills':
            // Search: Customer name, bill ID
            if (!empty($search_term)) {
                $where_conditions[] = "(c.name LIKE ? OR b.bill_id LIKE ?)";
                $search_param = '%' . $search_term . '%';
                $where_params = array_merge($where_params, [$search_param, $search_param]);
                $param_types .= 'ss';
            }
            
            // Price filter
            if ($price_min > 0) {
                $where_conditions[] = "b.total_amount >= ?";
                $where_params[] = $price_min;
                $param_types .= 'd';
                $response['filters_applied'][] = 'min price: ' . $price_min;
            }
            if ($price_max < PHP_INT_MAX) {
                $where_conditions[] = "b.total_amount <= ?";
                $where_params[] = $price_max;
                $param_types .= 'd';
                $response['filters_applied'][] = 'max price: ' . $price_max;
            }
            
            // Date filter uses bill_date
            $where_conditions_final = [];
            foreach ($where_conditions as $cond) {
                $cond = str_replace('date_column', 'b.bill_date', $cond);
                $where_conditions_final[] = $cond;
            }
            $where_clause = !empty($where_conditions_final) ? 'WHERE ' . implode(' AND ', $where_conditions_final) : '';
            
            // Count total
            $count_sql = "SELECT COUNT(*) as total FROM bills b
                         JOIN jobs j ON b.job_id = j.job_id
                         JOIN appointments a ON j.appointment_id = a.appointment_id
                         JOIN customers c ON a.customer_id = c.customer_id
                         $where_clause";
            
            $count_stmt = $conn->prepare($count_sql);
            if (!empty($where_params) && !empty($param_types)) {
                $count_stmt->bind_param($param_types, ...$where_params);
            }
            $count_stmt->execute();
            $response['total'] = $count_stmt->get_result()->fetch_assoc()['total'];
            $count_stmt->close();
            
            // Get data
            $sql = "SELECT b.bill_id, b.bill_date, b.total_amount, b.payment_status,
                           c.customer_id, c.name as customer_name, c.phone,
                           j.job_id, a.appointment_id
                    FROM bills b
                    JOIN jobs j ON b.job_id = j.job_id
                    JOIN appointments a ON j.appointment_id = a.appointment_id
                    JOIN customers c ON a.customer_id = c.customer_id
                    $where_clause
                    ORDER BY b.bill_date DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($sql);
            $limit_params = [$per_page, $offset];
            $combined_params = array_merge($where_params, $limit_params);
            $combined_types = $param_types . 'ii';
            
            if (!empty($combined_params)) {
                $stmt->bind_param($combined_types, ...$combined_params);
            }
            $stmt->execute();
            $response['data'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            break;
            
        case 'jobs':
            // Search: Customer name, job ID
            if (!empty($search_term)) {
                $where_conditions[] = "(c.name LIKE ? OR j.job_id LIKE ?)";
                $search_param = '%' . $search_term . '%';
                $where_params = array_merge($where_params, [$search_param, $search_param]);
                $param_types .= 'ss';
            }
            
            // Staff filter for mechanic
            if ($staff_id > 0) {
                $where_conditions[] = "j.mechanic_id = ?";
                $where_params[] = $staff_id;
                $param_types .= 'i';
                $response['filters_applied'][] = 'mechanic: ' . $staff_id;
            }
            
            // Date filter uses job_date
            $where_conditions_final = [];
            foreach ($where_conditions as $cond) {
                $cond = str_replace('date_column', 'j.job_date', $cond);
                $where_conditions_final[] = $cond;
            }
            $where_clause = !empty($where_conditions_final) ? 'WHERE ' . implode(' AND ', $where_conditions_final) : '';
            
            // Count total
            $count_sql = "SELECT COUNT(*) as total FROM jobs j
                         JOIN appointments a ON j.appointment_id = a.appointment_id
                         JOIN customers c ON a.customer_id = c.customer_id
                         LEFT JOIN staff s ON j.mechanic_id = s.staff_id
                         $where_clause";
            
            $count_stmt = $conn->prepare($count_sql);
            if (!empty($where_params) && !empty($param_types)) {
                $count_stmt->bind_param($param_types, ...$where_params);
            }
            $count_stmt->execute();
            $response['total'] = $count_stmt->get_result()->fetch_assoc()['total'];
            $count_stmt->close();
            
            // Get data
            $sql = "SELECT j.job_id, j.job_date, j.status,
                           c.customer_id, c.name as customer_name, c.phone,
                           s.name as mechanic_name, s.staff_id,
                           a.appointment_id
                    FROM jobs j
                    JOIN appointments a ON j.appointment_id = a.appointment_id
                    JOIN customers c ON a.customer_id = c.customer_id
                    LEFT JOIN staff s ON j.mechanic_id = s.staff_id
                    $where_clause
                    ORDER BY j.job_date DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($sql);
            $limit_params = [$per_page, $offset];
            $combined_params = array_merge($where_params, $limit_params);
            $combined_types = $param_types . 'ii';
            
            if (!empty($combined_params)) {
                $stmt->bind_param($combined_types, ...$combined_params);
            }
            $stmt->execute();
            $response['data'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            break;
    }
    
    // Calculate total pages
    $response['total_pages'] = ceil($response['total'] / $per_page);
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
