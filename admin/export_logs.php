<?php
// Export Activity Logs to CSV (Admin Only)
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../config/db.php';

// Admin only
requireRole(['admin']);

// Get filters from query string (same as activity_logs.php)
$filter_user_type = $_GET['user_type'] ?? '';
$filter_action_type = $_GET['action_type'] ?? '';
$filter_severity = $_GET['severity'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_entity_type = $_GET['entity_type'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_search = $_GET['search'] ?? '';

// Build query (same logic as activity_logs.php)
$where_clauses = [];
$params = [];
$types = '';

if ($filter_user_type) {
    $where_clauses[] = "user_type = ?";
    $params[] = $filter_user_type;
    $types .= 's';
}

if ($filter_action_type) {
    $where_clauses[] = "action_type = ?";
    $params[] = $filter_action_type;
    $types .= 's';
}

if ($filter_severity) {
    $where_clauses[] = "severity = ?";
    $params[] = $filter_severity;
    $types .= 's';
}

if ($filter_status) {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($filter_entity_type) {
    $where_clauses[] = "entity_type = ?";
    $params[] = $filter_entity_type;
    $types .= 's';
}

if ($filter_date_from) {
    $where_clauses[] = "DATE(created_at) >= ?";
    $params[] = $filter_date_from;
    $types .= 's';
}

if ($filter_date_to) {
    $where_clauses[] = "DATE(created_at) <= ?";
    $params[] = $filter_date_to;
    $types .= 's';
}

if ($filter_search) {
    $where_clauses[] = "(action LIKE ? OR description LIKE ? OR username LIKE ? OR ip_address LIKE ?)";
    $search_term = "%$filter_search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ssss';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch all matching logs (no limit for export)
$query = "SELECT 
            log_id,
            user_type,
            user_id,
            username,
            action,
            action_type,
            entity_type,
            entity_id,
            description,
            ip_address,
            request_method,
            request_url,
            severity,
            status,
            created_at
          FROM activity_logs 
          $where_sql 
          ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Set headers for CSV download
$filename = 'activity_logs_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write CSV header
fputcsv($output, [
    'Log ID',
    'User Type',
    'User ID',
    'Username',
    'Action',
    'Action Type',
    'Entity Type',
    'Entity ID',
    'Description',
    'IP Address',
    'Request Method',
    'Request URL',
    'Severity',
    'Status',
    'Created At'
]);

// Write data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['log_id'],
        $row['user_type'],
        $row['user_id'],
        $row['username'],
        $row['action'],
        $row['action_type'],
        $row['entity_type'],
        $row['entity_id'],
        $row['description'],
        $row['ip_address'],
        $row['request_method'],
        $row['request_url'],
        $row['severity'],
        $row['status'],
        $row['created_at']
    ]);
}

fclose($output);
$conn->close();

// Log the export action
require_once __DIR__ . '/../includes/activity_logger.php';
logActivity(
    'Exported Activity Logs',
    LOG_ACTION_EXPORT,
    'activity_logs',
    null,
    null,
    null,
    "Exported activity logs to CSV ($filename) with applied filters",
    LOG_SEVERITY_INFO,
    'success',
    ['filename' => $filename, 'filters' => $_GET]
);

exit;
?>
