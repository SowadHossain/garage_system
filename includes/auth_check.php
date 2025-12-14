<?php
// includes/auth_check.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 * Base URL for redirects
 * Adjust once if project folder name changes
 */
$BASE_URL = '/garage_system/public';

// Not logged in → redirect to staff login
if (empty($_SESSION['staff_id'])) {
    header("Location: {$BASE_URL}/staff_login.php");
    exit;
}
