<?php
/**
 * Activity Logging Helper Functions
 * Use these functions throughout the application to log user actions
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Log an activity
 * 
 * @param string $action Action description (e.g., "Created Customer", "Updated Job")
 * @param string $action_type Type: create, read, update, delete, login, logout, etc.
 * @param string|null $entity_type Entity type (e.g., 'customer', 'job', 'appointment')
 * @param int|null $entity_id Entity ID
 * @param array|null $old_values Old values before change (will be JSON encoded)
 * @param array|null $new_values New values after change (will be JSON encoded)
 * @param string|null $description Detailed description
 * @param string $severity info, warning, error, critical
 * @param string $status success, failed, pending
 * @param array|null $additional_data Any additional metadata
 * @return bool Success status
 */
function logActivity(
    $action,
    $action_type,
    $entity_type = null,
    $entity_id = null,
    $old_values = null,
    $new_values = null,
    $description = null,
    $severity = 'info',
    $status = 'success',
    $additional_data = null
) {
    global $conn;
    
    try {
        // Get user information from session
        session_start();
        
        if (isset($_SESSION['staff_id'])) {
            $user_type = 'staff';
            $user_id = $_SESSION['staff_id'];
            $username = $_SESSION['staff_username'] ?? $_SESSION['staff_name'] ?? 'Unknown';
        } elseif (isset($_SESSION['customer_id'])) {
            $user_type = 'customer';
            $user_id = $_SESSION['customer_id'];
            $username = $_SESSION['customer_email'] ?? $_SESSION['customer_name'] ?? 'Unknown';
        } else {
            $user_type = 'system';
            $user_id = null;
            $username = 'System';
        }
        
        // Get request information
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $request_method = $_SERVER['REQUEST_METHOD'] ?? null;
        $request_url = $_SERVER['REQUEST_URI'] ?? null;
        
        // Encode values to JSON
        $old_values_json = $old_values ? json_encode($old_values) : null;
        $new_values_json = $new_values ? json_encode($new_values) : null;
        $additional_data_json = $additional_data ? json_encode($additional_data) : null;
        
        // Insert log
        $query = "INSERT INTO activity_logs (
                    user_type, user_id, username, action, action_type,
                    entity_type, entity_id, old_values, new_values,
                    description, ip_address, user_agent, request_method,
                    request_url, severity, status, additional_data
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "sisssisssssssssss",
            $user_type,
            $user_id,
            $username,
            $action,
            $action_type,
            $entity_type,
            $entity_id,
            $old_values_json,
            $new_values_json,
            $description,
            $ip_address,
            $user_agent,
            $request_method,
            $request_url,
            $severity,
            $status,
            $additional_data_json
        );
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Activity Logging Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log a login attempt
 * 
 * @param string $username Username or email
 * @param string $user_type 'staff' or 'customer'
 * @param bool $success Whether login was successful
 * @param string|null $failure_reason Reason for failure
 * @return bool Success status
 */
function logLoginAttempt($username, $user_type, $success, $failure_reason = null) {
    global $conn;
    
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $status = $success ? 'success' : 'failed';
        
        // Insert login attempt
        $query = "INSERT INTO login_attempts (
                    username, user_type, ip_address, user_agent, status, failure_reason
                  ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssss", $username, $user_type, $ip_address, $user_agent, $status, $failure_reason);
        $stmt->execute();
        
        // Also log to activity_logs
        $action = $success ? 'Login Successful' : 'Login Failed';
        $action_type = $success ? 'login' : 'login_failed';
        $description = $success ? "User logged in successfully" : "Login failed: $failure_reason";
        $severity = $success ? 'info' : 'warning';
        $log_status = $success ? 'success' : 'failed';
        
        // For failed logins, we don't have session data yet
        if (!$success) {
            $insert_query = "INSERT INTO activity_logs (
                                user_type, user_id, username, action, action_type,
                                description, ip_address, user_agent, severity, status
                             ) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)";
            $log_stmt = $conn->prepare($insert_query);
            $log_stmt->bind_param("sssssssss", $user_type, $username, $action, $action_type, $description, $ip_address, $user_agent, $severity, $log_status);
            $log_stmt->execute();
        } else {
            logActivity($action, $action_type, null, null, null, null, $description, $severity, $log_status);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Login Attempt Logging Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check for suspicious login activity (brute force detection)
 * 
 * @param string $username Username to check
 * @param int $time_window Time window in minutes (default 15)
 * @param int $max_attempts Max failed attempts (default 5)
 * @return bool True if suspicious activity detected
 */
function detectSuspiciousLogin($username, $time_window = 15, $max_attempts = 5) {
    global $conn;
    
    $query = "SELECT COUNT(*) as failed_count
              FROM login_attempts
              WHERE username = ?
              AND status = 'failed'
              AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $username, $time_window);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return ($row['failed_count'] >= $max_attempts);
}

/**
 * Get recent login attempts for a user
 * 
 * @param string $username Username to check
 * @param int $limit Number of attempts to return
 * @return array Login attempts
 */
function getRecentLoginAttempts($username, $limit = 10) {
    global $conn;
    
    $query = "SELECT * FROM login_attempts
              WHERE username = ?
              ORDER BY attempted_at DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $username, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attempts = [];
    while ($row = $result->fetch_assoc()) {
        $attempts[] = $row;
    }
    
    return $attempts;
}

/**
 * Log CRUD operations with automatic before/after tracking
 * 
 * @param string $operation 'create', 'update', 'delete'
 * @param string $entity_type Entity type (e.g., 'customer', 'job')
 * @param int $entity_id Entity ID
 * @param array|null $old_data Old data (for update/delete)
 * @param array|null $new_data New data (for create/update)
 * @return bool Success status
 */
function logCRUD($operation, $entity_type, $entity_id, $old_data = null, $new_data = null) {
    $action_map = [
        'create' => "Created " . ucfirst($entity_type),
        'update' => "Updated " . ucfirst($entity_type),
        'delete' => "Deleted " . ucfirst($entity_type)
    ];
    
    $action = $action_map[$operation] ?? "Modified " . ucfirst($entity_type);
    
    // Build description
    $description = $action . " (ID: $entity_id)";
    if ($operation === 'update' && $old_data && $new_data) {
        $changes = [];
        foreach ($new_data as $key => $value) {
            if (isset($old_data[$key]) && $old_data[$key] != $value) {
                $changes[] = "$key: '{$old_data[$key]}' → '$value'";
            }
        }
        if (!empty($changes)) {
            $description .= " - Changes: " . implode(", ", $changes);
        }
    }
    
    return logActivity(
        $action,
        $operation,
        $entity_type,
        $entity_id,
        $old_data,
        $new_data,
        $description
    );
}

/**
 * Action type constants
 */
define('LOG_ACTION_CREATE', 'create');
define('LOG_ACTION_READ', 'read');
define('LOG_ACTION_UPDATE', 'update');
define('LOG_ACTION_DELETE', 'delete');
define('LOG_ACTION_LOGIN', 'login');
define('LOG_ACTION_LOGOUT', 'logout');
define('LOG_ACTION_LOGIN_FAILED', 'login_failed');
define('LOG_ACTION_STATUS_CHANGE', 'status_change');
define('LOG_ACTION_PERMISSION_CHANGE', 'permission_change');
define('LOG_ACTION_EXPORT', 'export');
define('LOG_ACTION_IMPORT', 'import');

/**
 * Severity constants
 */
define('LOG_SEVERITY_INFO', 'info');
define('LOG_SEVERITY_WARNING', 'warning');
define('LOG_SEVERITY_ERROR', 'error');
define('LOG_SEVERITY_CRITICAL', 'critical');

/**
 * Example usage:
 * 
 * // Log customer creation
 * logCRUD('create', 'customer', $customer_id, null, [
 *     'name' => $name,
 *     'email' => $email,
 *     'phone' => $phone
 * ]);
 * 
 * // Log status change
 * logActivity(
 *     'Changed Appointment Status',
 *     LOG_ACTION_STATUS_CHANGE,
 *     'appointment',
 *     $appointment_id,
 *     ['status' => 'booked'],
 *     ['status' => 'confirmed'],
 *     'Appointment confirmed by receptionist'
 * );
 * 
 * // Log login
 * logLoginAttempt($username, 'staff', true);
 * 
 * // Log failed login
 * logLoginAttempt($username, 'staff', false, 'Invalid password');
 */
?>
