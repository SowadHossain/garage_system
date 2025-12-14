<?php
/**
 * Role-Based Access Control (RBAC) Helper
 * Provides functions to check staff roles and enforce access control
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * Base URL for redirects
 * Adjust once if project folder name changes
 */
$BASE_URL = '/garage_system/public';

/**
 * Check if user is logged in as staff
 * Redirects to staff login if not authenticated
 */
function requireStaffLogin() {
    global $BASE_URL;

    if (empty($_SESSION['staff_id'])) {
        header("Location: {$BASE_URL}/staff_login.php");
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
    global $BASE_URL;

    // Ensure logged in
    if (empty($_SESSION['staff_id'])) {
        header("Location: {$BASE_URL}/staff_login.php");
        exit;
    }

    $user_role = $_SESSION['staff_role'] ?? '';

    if (!in_array($user_role, (array)$allowed_roles, true)) {
        if ($redirect) {
            header("Location: {$BASE_URL}/access_denied.php");
            exit;
        }
        return false;
    }

    return true;
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    return ($_SESSION['staff_role'] ?? '') === 'admin';
}

/**
 * Check if current user is receptionist
 */
function isReceptionist() {
    return ($_SESSION['staff_role'] ?? '') === 'receptionist';
}

/**
 * Check if current user is mechanic
 */
function isMechanic() {
    return ($_SESSION['staff_role'] ?? '') === 'mechanic';
}

/**
 * Get current user's role
 */
function getCurrentRole() {
    return $_SESSION['staff_role'] ?? null;
}

/**
 * Check if user has permission to access a specific feature
 * @param string $feature - Feature name (e.g., 'manage_customers', 'view_reports')
 */
function hasPermission($feature) {
    $role = getCurrentRole();

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

    return in_array($feature, $permissions[$role], true);
}

/**
 * Display role badge HTML
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
