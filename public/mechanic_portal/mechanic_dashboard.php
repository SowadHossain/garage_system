<?php
// public/mechanic_portal/mechanic_dashboard.php - Mechanic Dashboard
session_start();
require_once __DIR__ . "/../../config/db.php";

// Check if user is logged in as mechanic
if (empty($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'mechanic') {
    header("Location: ../staff_login.php");
    exit;
}

$staff_id = (int)($_SESSION['staff_id'] ?? 0);
$staff_name = $_SESSION['staff_name'] ?? 'Mechanic';

// ===== Stats (DB-compatible) =====
$my_active_jobs = $conn->prepare("SELECT COUNT(*) AS count FROM jobs WHERE mechanic_id = ? AND status IN ('open','in_progress')");
$my_active_jobs->bind_param("i", $staff_id);
$my_active_jobs->execute();
$active_jobs_count = (int)$my_active_jobs->get_result()->fetch_assoc()['count'];
$my_active_jobs->close();

$my_completed_jobs = $conn->prepare("SELECT COUNT(*) AS count FROM jobs WHERE mechanic_id = ? AND status = 'completed'");
$my_completed_jobs->bind_param("i", $staff_id);
$my_completed_jobs->execute();
$completed_jobs_count = (int)$my_completed_jobs->get_result()->fetch_assoc()['count'];
$my_completed_jobs->close();

$total_open_jobs = (int)$conn->query("SELECT COUNT(*) AS count FROM jobs WHERE status IN ('open','in_progress')")->fetch_assoc()['count'];

$pending_appointments = (int)$conn->query("
    SELECT COUNT(*) AS count 
    FROM appointments 
    WHERE status IN ('requested','booked','in_progress')
")->fetch_assoc()['count'];

// ===== My Assigned Jobs (DB-compatible) =====
$my_jobs = $conn->prepare("
    SELECT 
        j.job_id,
        j.status,
        j.created_at,
        a.appointment_id,
        a.requested_date,
        a.requested_slot,
        a.problem_text,
        c.name AS customer_name,
        c.phone,
        v.plate_no,
        v.make,
        v.model,
        v.year AS model_year
    FROM jobs j
    JOIN appointments a ON j.appointment_id = a.appointment_id
    JOIN customers c ON a.customer_id = c.customer_id
    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE j.mechanic_id = ?
    ORDER BY j.created_at DESC
    LIMIT 8
");
$my_jobs->bind_param("i", $staff_id);
$my_jobs->execute();
$assigned_jobs = $my_jobs->get_result()->fetch_all(MYSQLI_ASSOC);
$my_jobs->close();

// ===== Upcoming Appointments (DB-compatible) =====
$upcoming_appointments = $conn->prepare("
    SELECT 
        a.appointment_id,
        a.status,
        a.requested_date,
        a.requested_slot,
        c.name AS customer_name,
        c.phone,
        v.plate_no,
        v.make,
        v.model
    FROM appointments a
    JOIN customers c ON a.customer_id = c.customer_id
    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE a.status IN ('booked','in_progress')
      AND a.requested_date >= CURDATE()
    ORDER BY a.requested_date ASC, a.requested_slot ASC
    LIMIT 6
");
$upcoming_appointments->execute();
$upcoming = $upcoming_appointments->get_result()->fetch_all(MYSQLI_ASSOC);
$upcoming_appointments->close();

function badgeClassJob($status) {
    switch ($status) {
        case 'open':        return 'warning';
        case 'in_progress': return 'primary';
        case 'completed':   return 'success';
        case 'cancelled':   return 'danger';
        default:            return 'primary';
    }
}

function apptStatusLabel($status) {
    return ucfirst(str_replace('_', ' ', (string)$status));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mechanic Dashboard - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #f59e0b;
            --primary-dark: #d97706;
            --secondary-color: #fb923c;
            --accent-color: #ea580c;
            --light-bg: #fffbeb;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light-bg);
            margin: 0;
            padding: 0;
        }
        .top-nav {
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .top-nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .container-main {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .dashboard-header {
            margin-bottom: 2rem;
        }
        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 0.5rem;
        }
        .dashboard-subtitle {
            color: #6b7280;
            font-size: 1.1rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(245,158,11,0.15);
        }
        .stat-card.orange { border-left-color: #f59e0b; }
        .stat-card.amber { border-left-color: #fb923c; }
        .stat-card.red { border-left-color: #ea580c; }
        .stat-card.green { border-left-color: #10b981; }
        .stat-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stat-icon.orange { background: #fef3c7; color: #92400e; }
        .stat-icon.amber { background: #fed7aa; color: #9a3412; }
        .stat-icon.red { background: #fee2e2; color: #991b1b; }
        .stat-icon.green { background: #d1fae5; color: #065f46; }
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }
        .quick-actions {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .action-btn {
            background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
            color: white;
            padding: 1.25rem;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(234,88,12,0.3);
            color: white;
        }
        .action-btn i { font-size: 1.75rem; }
        .action-text { flex: 1; }
        .action-title { font-weight: 600; font-size: 1.05rem; margin-bottom: 0.25rem; }
        .action-subtitle { font-size: 0.85rem; opacity: 0.9; }
        .content-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 992px) {
            .content-row { grid-template-columns: 1fr; }
        }
        .data-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .list-item {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
        }
        .list-item:last-child { border-bottom: none; }
        .list-item:hover { background: #fffbeb; }
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }
        .item-title { font-weight: 600; color: #111827; }
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge.success { background: #d1fae5; color: #065f46; }
        .badge.warning { background: #fef3c7; color: #92400e; }
        .badge.info { background: #dbeafe; color: #1e40af; }
        .badge.danger { background: #fee2e2; color: #991b1b; }
        .badge.primary { background: #fed7aa; color: #9a3412; }
        .item-details { color: #6b7280; font-size: 0.9rem; }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.3); color: white; }
        .job-card {
            border: 2px solid #fed7aa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fffbeb;
        }
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.75rem;
        }
        .job-id { font-weight: 700; color: var(--accent-color); font-size: 1.1rem; }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="top-nav-content">
            <div class="logo">
                <i class="bi bi-tools"></i>
                Screw Dheela - Mechanic Portal
            </div>
            <div class="user-info">
                <span class="user-badge">
                    <i class="bi bi-person-circle me-2"></i>
                    <?php echo htmlspecialchars($staff_name); ?>
                </span>
                <a href="../logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-main">
        <div class="dashboard-header">
            <h1 class="dashboard-title">
                <i class="bi bi-wrench-adjustable-circle me-2"></i>Mechanic Dashboard
            </h1>
            <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($staff_name); ?>! Manage your jobs and services.</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card orange">
                <div class="stat-header">
                    <div class="stat-icon orange">
                        <i class="bi bi-wrench"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $active_jobs_count; ?></div>
                        <div class="stat-label">My Active Jobs</div>
                    </div>
                </div>
            </div>

            <div class="stat-card green">
                <div class="stat-header">
                    <div class="stat-icon green">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $completed_jobs_count; ?></div>
                        <div class="stat-label">Completed Jobs</div>
                    </div>
                </div>
            </div>

            <div class="stat-card amber">
                <div class="stat-header">
                    <div class="stat-icon amber">
                        <i class="bi bi-gear-fill"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $total_open_jobs; ?></div>
                        <div class="stat-label">Total Open Jobs</div>
                    </div>
                </div>
            </div>

            <div class="stat-card red">
                <div class="stat-header">
                    <div class="stat-icon red">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $pending_appointments; ?></div>
                        <div class="stat-label">Pending Appointments</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2 class="section-title">
                <i class="bi bi-lightning-charge-fill"></i>
                Quick Actions
            </h2>
            <div class="actions-grid">
                <a href="jobs_list.php" class="action-btn">
                    <i class="bi bi-list-task"></i>
                    <div class="action-text">
                        <div class="action-title">View All Jobs</div>
                        <div class="action-subtitle">See all work orders</div>
                    </div>
                </a>

                <a href="add_services.php" class="action-btn">
                    <i class="bi bi-plus-circle-fill"></i>
                    <div class="action-text">
                        <div class="action-title">Add Services</div>
                        <div class="action-subtitle">Add parts/services</div>
                    </div>
                </a>

                <a href="appointments_list.php" class="action-btn">
                    <i class="bi bi-calendar2-week"></i>
                    <div class="action-text">
                        <div class="action-title">View Appointments</div>
                        <div class="action-subtitle">Check schedule</div>
                    </div>
                </a>

                <a href="vehicles_list.php" class="action-btn">
                    <i class="bi bi-car-front"></i>
                    <div class="action-text">
                        <div class="action-title">Vehicle Info</div>
                        <div class="action-subtitle">Search vehicles</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Content Row -->
        <div class="content-row">
            <!-- My Assigned Jobs -->
            <div class="data-card">
                <h2 class="section-title">
                    <i class="bi bi-clipboard-check"></i>
                    My Assigned Jobs
                </h2>
                <?php if (empty($assigned_jobs)): ?>
                    <p class="text-muted">No jobs assigned to you yet.</p>
                <?php else: ?>
                    <?php foreach ($assigned_jobs as $job): ?>
                        <div class="job-card">
                            <div class="job-header">
                                <div>
                                    <div class="job-id">Job #<?php echo (int)$job['job_id']; ?></div>
                                    <div class="item-title"><?php echo htmlspecialchars($job['customer_name']); ?></div>
                                </div>
                                <span class="badge <?php echo badgeClassJob($job['status']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($job['status'])); ?>
                                </span>
                            </div>
                            <div class="item-details">
                                <?php if (!empty($job['plate_no'])): ?>
                                    <i class="bi bi-car-front me-1"></i>
                                    <?php echo htmlspecialchars(trim(($job['make'] ?? '') . ' ' . ($job['model'] ?? '') . (!empty($job['model_year']) ? ' (' . $job['model_year'] . ')' : ''))); ?><br>
                                    <i class="bi bi-hash me-1"></i><?php echo htmlspecialchars($job['plate_no']); ?><br>
                                <?php endif; ?>

                                <i class="bi bi-calendar3 me-1"></i>
                                Appointment: <?php echo date('M d, Y', strtotime($job['requested_date'])); ?> (Slot <?php echo (int)$job['requested_slot']; ?>)<br>

                                <?php if (!empty($job['problem_text'])): ?>
                                    <i class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars(mb_strimwidth($job['problem_text'], 0, 120, '...')); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="mt-3 text-center">
                    <a href="jobs_list.php" class="btn btn-outline-warning">View All Jobs</a>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="data-card">
                <h2 class="section-title">
                    <i class="bi bi-calendar-event"></i>
                    Upcoming Appointments
                </h2>
                <?php if (empty($upcoming)): ?>
                    <p class="text-muted">No upcoming appointments.</p>
                <?php else: ?>
                    <?php foreach ($upcoming as $appt): ?>
                        <div class="list-item">
                            <div class="item-header">
                                <div>
                                    <div class="item-title"><?php echo htmlspecialchars($appt['customer_name']); ?></div>
                                    <div class="item-details">
                                        <?php if (!empty($appt['plate_no'])): ?>
                                            <i class="bi bi-car-front me-1"></i>
                                            <?php echo htmlspecialchars(trim(($appt['make'] ?? '') . ' ' . ($appt['model'] ?? ''))); ?>
                                            - <?php echo htmlspecialchars($appt['plate_no']); ?><br>
                                        <?php endif; ?>
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?php echo date('M d, Y', strtotime($appt['requested_date'])); ?>
                                        (Slot <?php echo (int)$appt['requested_slot']; ?>)<br>
                                        <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($appt['phone']); ?>
                                    </div>
                                </div>
                                <span class="badge info">
                                    <?php echo htmlspecialchars(apptStatusLabel($appt['status'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="mt-3 text-center">
                    <a href="appointments_list.php" class="btn btn-outline-warning">View All Appointments</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
