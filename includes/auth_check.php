<?php
// includes/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['staff_id'])) {
    header("Location: /garage_system/public/login.php");
    exit;
}
