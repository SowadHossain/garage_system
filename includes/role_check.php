<?php
/**
 * Role-Based Access Control (RBAC) Helper
 * Provides functions to check staff roles and enforce access control
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in as staff
 * Redirects to login if not authenticated
 */
function requireStaffLogin() {
    if (!isset($_SESSION['staff_id'])) {
        header("Location: /garage_system/public/staff_login.php");
        exit;
    }
}

/**
 * Check if user has one of the required roles
 * @param array $allowed_roles - Array of allowed roles (e.g., ['admin', 'receptionist'])
 * @param bool $redirect - If true, redirect to access denied page; if false, return boolean
 * @return bool - True if user has required role, false otherwise
 */
function requireRole($allowed_roles, $redirect = true) {
    // First ensure user is logged in as staff
    if (!isset($_SESSION['staff_id'])) {
        header("Location: /garage_system/public/staff_login.php");
        exit;
    }
    
    // Check if user's role is in allowed roles
    $user_role = $_SESSION['staff_role'] ?? '';
    
    if (!in_array($user_role, $allowed_roles)) {
        if ($redirect) {
            // Redirect to access denied page
            header("Location: /garage_system/public/access_denied.php");
            exit;
        } else {
            return false;
        }
    }
    
    return true;
}

/**
 * Check if current user is admin
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'admin';
}

/**
 * Check if current user is receptionist
 * @return bool
 */
function isReceptionist() {
    return isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'receptionist';
}

/**
 * Check if current user is mechanic
 * @return bool
 */
function isMechanic() {
    return isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'mechanic';
}

/**
 * Get current user's role
 * @return string|null
 */
function getCurrentRole() {
    return $_SESSION['staff_role'] ?? null;
}

/**
 * Check if user has permission to access a specific feature
 * @param string $feature - Feature name (e.g., 'manage_customers', 'view_reports')
 * @return bool
 */
function hasPermission($feature) {
    $role = getCurrentRole();
    
    // Define permission matrix
    $permissions = [
        'admin' => [
            'view_dashboard',
            'manage_customers',
            'manage_vehicles', 
            'manage_appointments',
            'manage_jobs',
            'manage_bills',
            'view_reports',
            'manage_staff',
            'manage_broadcasts',
            'global_search',
            'manage_services',
            'view_all_data',
        ],
        'receptionist' => [
            'view_dashboard',
            'manage_customers',
            'manage_vehicles',
            'manage_appointments',
            'view_jobs',
            'manage_bills',
            'view_customer_data',
        ],
        'mechanic' => [
            'view_dashboard',
            'view_appointments',
            'manage_jobs',
            'add_services_to_jobs',
            'view_vehicle_data',
            'view_customer_data',
        ],
    ];
    
    if (!$role || !isset($permissions[$role])) {
        return false;
    }
    
    return in_array($feature, $permissions[$role]);
}

/**
 * Display role badge HTML
 * @return string
 */
function getRoleBadge() {
    $role = getCurrentRole();
    
    $badges = [
        'admin' => '<span class="badge bg-danger"><i class="bi bi-shield-fill-check me-1"></i>Admin</span>',
        'receptionist' => '<span class="badge bg-primary"><i class="bi bi-person-badge me-1"></i>Receptionist</span>',
        'mechanic' => '<span class="badge bg-success"><i class="bi bi-wrench me-1"></i>Mechanic</span>',
    ];
    
    return $badges[$role] ?? '<span class="badge bg-secondary">Unknown</span>';
}
