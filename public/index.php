<?php
// public/index.php - Redirect to staff dashboard

require_once __DIR__ . "/../includes/auth_check.php";

header("Location: staff_dashboard.php");
exit;
