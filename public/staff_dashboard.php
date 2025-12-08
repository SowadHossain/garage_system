<?php
// public/staff_dashboard.php - Modern Staff Dashboard

session_start();

require_once __DIR__ . "/../config/db.php";

// Check if staff is logged in
if (empty($_SESSION['staff_id'])) {
    header("Location: staff_login.php");
    exit;
}

$staff_id = $_SESSION['staff_id'];
$staff_name = $_SESSION['staff_name'];
$staff_role = $_SESSION['staff_role'];

// Get statistics
$stats = [
    'total_customers' => 0,
    'pending_appointments' => 0,
    'in_progress_jobs' => 0,
    'unpaid_bills' => 0
];

// Total customers
$result = $conn->query("SELECT COUNT(*) as count FROM customers");
$stats['total_customers'] = $result->fetch_assoc()['count'];

// Pending appointments
$result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'");
$stats['pending_appointments'] = $result->fetch_assoc()['count'];

// In-progress jobs
$result = $conn->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'in_progress'");
$stats['in_progress_jobs'] = $result->fetch_assoc()['count'];

// Unpaid bills
$result = $conn->query("SELECT COUNT(*) as count FROM bills WHERE payment_status = 'unpaid'");
$stats['unpaid_bills'] = $result->fetch_assoc()['count'];

// Get recent appointments
$appointments_stmt = $conn->prepare("SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.problem_description,
                                            c.name as customer_name, c.phone as customer_phone,
                                            v.registration_no, v.brand, v.model
                                     FROM appointments a
                                     JOIN customers c ON a.customer_id = c.customer_id
                                     LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                                     ORDER BY a.appointment_date DESC, a.appointment_time DESC
                                     LIMIT 5");
$appointments_stmt->execute();
$recent_appointments = $appointments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$appointments_stmt->close();

// Get recent customers
$customers_stmt = $conn->prepare("SELECT customer_id, name, email, phone, created_at
                                  FROM customers
                                  ORDER BY created_at DESC
                                  LIMIT 5");
$customers_stmt->execute();
$recent_customers = $customers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$customers_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0b5ed7;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8f9fa;
        }
        
        .top-nav {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1rem 1.5rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .nav-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }
        
        .nav-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            margin: 0;
        }
        
        .user-role {
            font-size: 0.85rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .main-content {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #6c757d;
            font-size: 1rem;
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            color: inherit;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.blue { background: #cfe2ff; color: #084298; }
        .stat-icon.green { background: #d1e7dd; color: #0f5132; }
        .stat-icon.yellow { background: #fff3cd; color: #664d03; }
        .stat-icon.red { background: #f8d7da; color: #842029; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin: 0;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .content-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
            margin-bottom: 1.5rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #212529;
            margin: 0;
        }
        
        .section-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        
        .section-link:hover {
            color: var(--primary-dark);
        }
        
        .appointment-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s;
        }
        
        .appointment-item:last-child {
            border-bottom: none;
        }
        
        .appointment-item:hover {
            background: #f8f9fa;
        }
        
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .customer-name {
            font-weight: 600;
            color: #212529;
            margin: 0;
        }
        
        .appointment-date {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .vehicle-info {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0.25rem 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .status-pending { background: #fff3cd; color: #664d03; }
        .status-confirmed { background: #cfe2ff; color: #084298; }
        .status-in_progress { background: #d1e7dd; color: #0f5132; }
        .status-completed { background: #d1e7dd; color: #0f5132; }
        .status-cancelled { background: #f8d7da; color: #842029; }
        
        .customer-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s;
        }
        
        .customer-item:last-child {
            border-bottom: none;
        }
        
        .customer-item:hover {
            background: #f8f9fa;
        }
        
        .customer-email {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0.25rem 0;
        }
        
        .customer-phone {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-btn {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            text-decoration: none;
            color: #212529;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }
        
        .action-btn:hover {
            border-color: var(--primary-color);
            background: #f8f9fa;
            color: var(--primary-color);
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        .action-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: #e7f1ff;
            color: var(--primary-color);
        }
        
        .action-btn:hover .action-icon {
            background: var(--primary-color);
            color: white;
        }
        
        .action-label {
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .user-info {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-content">
            <a href="staff_dashboard.php" class="nav-brand">
                <i class="bi bi-tools me-2"></i>Screw Dheela
            </a>
            <div class="nav-user">
                <div class="user-info">
                    <p class="user-name"><?php echo htmlspecialchars($staff_name); ?></p>
                    <p class="user-role"><?php echo htmlspecialchars(ucfirst($staff_role)); ?></p>
                </div>
                <a href="logout.php" class="btn btn-sm btn-light">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </nav>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </h1>
            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($staff_name); ?>!</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <a href="../customers/list.php" class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon blue">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['total_customers']; ?></h2>
                <p class="stat-label">Total Customers</p>
            </a>
            
            <a href="../appointments/list.php" class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon yellow">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['pending_appointments']; ?></h2>
                <p class="stat-label">Pending Appointments</p>
            </a>
            
            <a href="../jobs/list.php" class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon green">
                        <i class="bi bi-tools"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['in_progress_jobs']; ?></h2>
                <p class="stat-label">Active Jobs</p>
            </a>
            
            <a href="../bills/list.php" class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon red">
                        <i class="bi bi-receipt"></i>
                    </div>
                </div>
                <h2 class="stat-value"><?php echo $stats['unpaid_bills']; ?></h2>
                <p class="stat-label">Unpaid Bills</p>
            </a>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="../customers/add.php" class="action-btn">
                <div class="action-icon">
                    <i class="bi bi-person-plus"></i>
                </div>
                <span class="action-label">Add Customer</span>
            </a>
            
            <a href="../appointments/list.php" class="action-btn">
                <div class="action-icon">
                    <i class="bi bi-calendar-plus"></i>
                </div>
                <span class="action-label">New Appointment</span>
            </a>
            
            <a href="../jobs/list.php" class="action-btn">
                <div class="action-icon">
                    <i class="bi bi-clipboard-plus"></i>
                </div>
                <span class="action-label">Create Job</span>
            </a>
            
            <a href="../bills/generate.php" class="action-btn">
                <div class="action-icon">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
                <span class="action-label">Generate Bill</span>
            </a>
            
            <a href="../chat/staff_chat.php" class="action-btn">
                <div class="action-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <span class="action-label">Customer Messages</span>
            </a>
        </div>
        
        <!-- Recent Appointments -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="bi bi-calendar-check me-2"></i>Recent Appointments
                </h2>
                <a href="../appointments/list.php" class="section-link">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            
            <?php if (empty($recent_appointments)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <p>No appointments yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_appointments as $apt): ?>
                    <div class="appointment-item">
                        <div class="appointment-header">
                            <div>
                                <h3 class="customer-name"><?php echo htmlspecialchars($apt['customer_name']); ?></h3>
                                <p class="appointment-date">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?> at 
                                    <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?>
                                </p>
                            </div>
                        </div>
                        <?php if ($apt['registration_no']): ?>
                            <p class="vehicle-info">
                                <i class="bi bi-car-front me-1"></i>
                                <?php echo htmlspecialchars($apt['registration_no'] . ' - ' . $apt['brand'] . ' ' . $apt['model']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($apt['problem_description']): ?>
                            <p class="vehicle-info">
                                <i class="bi bi-wrench me-1"></i>
                                <?php echo htmlspecialchars(substr($apt['problem_description'], 0, 80)); ?>
                                <?php if (strlen($apt['problem_description']) > 80) echo '...'; ?>
                            </p>
                        <?php endif; ?>
                        <span class="status-badge status-<?php echo $apt['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $apt['status'])); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Recent Customers -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="bi bi-people me-2"></i>Recent Customers
                </h2>
                <a href="../customers/list.php" class="section-link">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            
            <?php if (empty($recent_customers)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-person-x"></i>
                    </div>
                    <p>No customers yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_customers as $customer): ?>
                    <div class="customer-item">
                        <h3 class="customer-name">
                            <i class="bi bi-person-circle me-2"></i>
                            <?php echo htmlspecialchars($customer['name']); ?>
                        </h3>
                        <p class="customer-email">
                            <i class="bi bi-envelope me-1"></i>
                            <?php echo htmlspecialchars($customer['email']); ?>
                        </p>
                        <p class="customer-phone">
                            <i class="bi bi-telephone me-1"></i>
                            <?php echo htmlspecialchars($customer['phone']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
