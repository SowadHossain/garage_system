<?php
/**
 * Search Results CSV Export
 * Exports search results to CSV file
 */

session_start();
require_once __DIR__ . '/../config/db.php';

try {
    // Check authentication
    if (!isset($_SESSION['staff_id'])) {
        throw new Exception('Unauthorized access');
    }
    
    // Get parameters (same as search)
    $entity = isset($_GET['entity']) ? trim($_GET['entity']) : '';
    $search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
    $date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : [];
    $staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;
    $price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : 0;
    $price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : PHP_INT_MAX;
    
    // Validate entity type
    if (!in_array($entity, ['appointments', 'bills', 'jobs'])) {
        throw new Exception('Invalid entity type');
    }
    
    // Ensure status is array
    if (!is_array($status)) {
        $status = !empty($status) ? explode(',', $status) : [];
    }
    
    // Prepare CSV output
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $entity . '_export_' . date('Y-m-d_His') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Build queries and export data
    $where_params = [];
    $param_types = '';
    
    // Build WHERE clause
    $where_conditions = [];
    
    if (!empty($status)) {
        $placeholders = implode(',', array_fill(0, count($status), '?'));
        $where_conditions[] = "status IN ($placeholders)";
        $where_params = array_merge($where_params, $status);
        $param_types .= str_repeat('s', count($status));
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(date_column) >= ?";
        $where_params[] = $date_from;
        $param_types .= 's';
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(date_column) <= ?";
        $where_params[] = $date_to;
        $param_types .= 's';
    }
    
    switch ($entity) {
        case 'appointments':
            // Headers
            fputcsv($output, ['Appointment ID', 'Date/Time', 'Status', 'Customer Name', 'Phone', 'Vehicle', 'Registration', 'Problem Description']);
            
            if (!empty($search_term)) {
                $where_conditions[] = "(c.name LIKE ? OR v.registration_no LIKE ? OR a.problem_description LIKE ?)";
                $search_param = '%' . $search_term . '%';
                $where_params = array_merge($where_params, [$search_param, $search_param, $search_param]);
                $param_types .= 'sss';
            }
            
            $where_conditions_final = [];
            foreach ($where_conditions as $cond) {
                $cond = str_replace('date_column', 'a.appointment_datetime', $cond);
                $where_conditions_final[] = $cond;
            }
            $where_clause = !empty($where_conditions_final) ? 'WHERE ' . implode(' AND ', $where_conditions_final) : '';
            
            $sql = "SELECT a.appointment_id, a.appointment_datetime, a.status, 
                           c.name, c.phone, CONCAT(v.brand, ' ', v.model) as vehicle,
                           v.registration_no, a.problem_description
                    FROM appointments a
                    JOIN customers c ON a.customer_id = c.customer_id
                    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                    $where_clause
                    ORDER BY a.appointment_datetime DESC";
            
            $stmt = $conn->prepare($sql);
            if (!empty($where_params)) {
                $stmt->bind_param($param_types, ...$where_params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['appointment_id'],
                    $row['appointment_datetime'],
                    $row['status'],
                    $row['name'],
                    $row['phone'],
                    $row['vehicle'],
                    $row['registration_no'],
                    $row['problem_description']
                ]);
            }
            $stmt->close();
            break;
            
        case 'bills':
            // Headers
            fputcsv($output, ['Bill ID', 'Date', 'Amount', 'Payment Status', 'Customer Name', 'Phone']);
            
            if (!empty($search_term)) {
                $where_conditions[] = "(c.name LIKE ? OR b.bill_id LIKE ?)";
                $search_param = '%' . $search_term . '%';
                $where_params = array_merge($where_params, [$search_param, $search_param]);
                $param_types .= 'ss';
            }
            
            if ($price_min > 0) {
                $where_conditions[] = "b.total_amount >= ?";
                $where_params[] = $price_min;
                $param_types .= 'd';
            }
            if ($price_max < PHP_INT_MAX) {
                $where_conditions[] = "b.total_amount <= ?";
                $where_params[] = $price_max;
                $param_types .= 'd';
            }
            
            $where_conditions_final = [];
            foreach ($where_conditions as $cond) {
                $cond = str_replace('date_column', 'b.bill_date', $cond);
                $where_conditions_final[] = $cond;
            }
            $where_clause = !empty($where_conditions_final) ? 'WHERE ' . implode(' AND ', $where_conditions_final) : '';
            
            $sql = "SELECT b.bill_id, b.bill_date, b.total_amount, b.payment_status,
                           c.name, c.phone
                    FROM bills b
                    JOIN jobs j ON b.job_id = j.job_id
                    JOIN appointments a ON j.appointment_id = a.appointment_id
                    JOIN customers c ON a.customer_id = c.customer_id
                    $where_clause
                    ORDER BY b.bill_date DESC";
            
            $stmt = $conn->prepare($sql);
            if (!empty($where_params)) {
                $stmt->bind_param($param_types, ...$where_params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['bill_id'],
                    $row['bill_date'],
                    $row['total_amount'],
                    $row['payment_status'],
                    $row['name'],
                    $row['phone']
                ]);
            }
            $stmt->close();
            break;
            
        case 'jobs':
            // Headers
            fputcsv($output, ['Job ID', 'Date', 'Status', 'Customer Name', 'Phone', 'Mechanic', 'Appointment ID']);
            
            if (!empty($search_term)) {
                $where_conditions[] = "(c.name LIKE ? OR j.job_id LIKE ?)";
                $search_param = '%' . $search_term . '%';
                $where_params = array_merge($where_params, [$search_param, $search_param]);
                $param_types .= 'ss';
            }
            
            if ($staff_id > 0) {
                $where_conditions[] = "j.mechanic_id = ?";
                $where_params[] = $staff_id;
                $param_types .= 'i';
            }
            
            $where_conditions_final = [];
            foreach ($where_conditions as $cond) {
                $cond = str_replace('date_column', 'j.job_date', $cond);
                $where_conditions_final[] = $cond;
            }
            $where_clause = !empty($where_conditions_final) ? 'WHERE ' . implode(' AND ', $where_conditions_final) : '';
            
            $sql = "SELECT j.job_id, j.job_date, j.status,
                           c.name, c.phone, s.name as mechanic_name,
                           a.appointment_id
                    FROM jobs j
                    JOIN appointments a ON j.appointment_id = a.appointment_id
                    JOIN customers c ON a.customer_id = c.customer_id
                    LEFT JOIN staff s ON j.mechanic_id = s.staff_id
                    $where_clause
                    ORDER BY j.job_date DESC";
            
            $stmt = $conn->prepare($sql);
            if (!empty($where_params)) {
                $stmt->bind_param($param_types, ...$where_params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['job_id'],
                    $row['job_date'],
                    $row['status'],
                    $row['name'],
                    $row['phone'],
                    $row['mechanic_name'],
                    $row['appointment_id']
                ]);
            }
            $stmt->close();
            break;
    }
    
    fclose($output);
    
} catch (Exception $e) {
    http_response_code(400);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
}
?>
