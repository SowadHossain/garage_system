<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Admin only
if (empty($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'admin') {
    header('Location: ../staff_login.php');
    exit;
}

$staff_name = $_SESSION['staff_name'];

// Core statistics
$total_customers = $conn->query("SELECT COUNT(*) c FROM customers")->fetch_assoc()['c'];
$total_staff     = $conn->query("SELECT COUNT(*) c FROM staff WHERE is_active = 1")->fetch_assoc()['c'];
$total_vehicles  = $conn->query("SELECT COUNT(*) c FROM vehicles")->fetch_assoc()['c'];
$total_appts     = $conn->query("SELECT COUNT(*) c FROM appointments")->fetch_assoc()['c'];

$pending_appts = $conn->query("
    SELECT COUNT(*) c 
    FROM appointments 
    WHERE status IN ('requested','booked')
")->fetch_assoc()['c'];

$active_jobs = $conn->query("
    SELECT COUNT(*) c 
    FROM jobs 
    WHERE status = 'in_progress'
")->fetch_assoc()['c'];

$unpaid_bills = $conn->query("
    SELECT COUNT(*) c 
    FROM bills 
    WHERE payment_status = 'unpaid'
")->fetch_assoc()['c'];

// Revenue
$revenue = $conn->query("
    SELECT 
        COALESCE(SUM(total),0) total_revenue,
        COALESCE(SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END),0) paid_revenue
    FROM bills
")->fetch_assoc();

// Recent appointments
$recent_appointments = $conn->query("
    SELECT 
        a.appointment_id,
        a.status,
        a.requested_date,
        a.requested_slot,
        c.name AS customer_name,
        v.plate_no,
        v.make,
        v.model
    FROM appointments a
    JOIN customers c ON c.customer_id = a.customer_id
    LEFT JOIN vehicles v ON v.vehicle_id = a.vehicle_id
    ORDER BY a.created_at DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>

<nav class="top-nav">
    <div class="top-nav-content">
        <div class="logo">
            <i class="bi bi-shield-check"></i> Screw Dheela – Admin
        </div>
        <div class="user-info">
            <span class="user-badge">
                <i class="bi bi-person-circle me-1"></i>
                <?= htmlspecialchars($staff_name) ?>
            </span>
            <a href="../logout.php" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container-main">

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card blue"><div class="stat-value"><?= $total_customers ?></div><div class="stat-label">Customers</div></div>
        <div class="stat-card purple"><div class="stat-value"><?= $total_staff ?></div><div class="stat-label">Staff</div></div>
        <div class="stat-card cyan"><div class="stat-value"><?= $total_vehicles ?></div><div class="stat-label">Vehicles</div></div>
        <div class="stat-card green"><div class="stat-value">৳<?= number_format($revenue['total_revenue']) ?></div><div class="stat-label">Revenue</div></div>
        <div class="stat-card orange"><div class="stat-value"><?= $pending_appts ?></div><div class="stat-label">Pending Appointments</div></div>
        <div class="stat-card red"><div class="stat-value"><?= $unpaid_bills ?></div><div class="stat-label">Unpaid Bills</div></div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="admin/manage_staff.php" class="action-btn"><i class="bi bi-person-gear"></i>Manage Staff</a>
        <a href="../customers/list.php" class="action-btn"><i class="bi bi-people"></i>Customers</a>
        <a href="../vehicles/list.php" class="action-btn"><i class="bi bi-car-front"></i>Vehicles</a>
        <a href="../appointments/list.php" class="action-btn"><i class="bi bi-calendar-check"></i>Appointments</a>
        <a href="../jobs/list.php" class="action-btn"><i class="bi bi-tools"></i>Jobs</a>
        <a href="../bills/list.php" class="action-btn"><i class="bi bi-receipt"></i>Bills</a>
    </div>

    <!-- Recent Appointments -->
    <div class="data-card">
        <h3>Recent Appointments</h3>
        <?php foreach ($recent_appointments as $a): ?>
            <div class="list-item">
                <strong><?= htmlspecialchars($a['customer_name']) ?></strong><br>
                <?= htmlspecialchars($a['requested_date']) ?> (Slot <?= $a['requested_slot'] ?>)<br>
                <?php if ($a['plate_no']): ?>
                    <?= htmlspecialchars($a['plate_no'].' '.$a['make'].' '.$a['model']) ?><br>
                <?php endif; ?>
                <span class="badge bg-secondary"><?= ucfirst($a['status']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

</div>

</body>
</html>
