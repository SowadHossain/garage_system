<?php
// public/customer_logout.php - Customer Logout

session_start();

// Clear customer session
unset($_SESSION['customer_id']);
unset($_SESSION['customer_name']);
unset($_SESSION['customer_email']);
unset($_SESSION['customer_phone']);

session_destroy();

header("Location: customer_login.php");
exit;
