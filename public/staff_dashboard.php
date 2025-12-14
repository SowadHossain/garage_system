<?php
// public/staff_dashboard.php
// Redirect-only fallback dashboard

session_start();

if (empty($_SESSION['staff_id']) || empty($_SESSION['staff_role'])) {
    header("Location: staff_login.php");
    exit;
}

switch ($_SESSION['staff_role']) {
    case 'admin':
        header("Location: admin_portal/admin_dashboard.php");
        break;

    case 'receptionist':
        header("Location: receptionist_portal/receptionist_dashboard.php");
        break;

    case 'mechanic':
        header("Location: mechanic_portal/mechanic_dashboard.php");
        break;

    default:
        header("Location: access_denied.php");
        break;
}

exit;
