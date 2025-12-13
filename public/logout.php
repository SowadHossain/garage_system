<?php
// public/logout.php
session_start();

// Log logout before destroying session
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

if (isset($_SESSION['staff_id']) || isset($_SESSION['customer_id'])) {
    logActivity(
        'User Logged Out',
        LOG_ACTION_LOGOUT,
        null,
        null,
        null,
        null,
        'User logged out successfully',
        LOG_SEVERITY_INFO,
        'success'
    );
}

session_unset();
session_destroy();

header("Location: welcome.php");
exit;
