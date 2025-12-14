<?php
// public/receptionist_portal/receptionist_dashboard.php - Receptionist Dashboard
session_start();
require_once __DIR__ . "/../../config/db.php";

// Check if user is logged in as receptionist
if (empty($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'receptionist') {
    header("Location: ../staff_login.php");
    exit;
}

$staff_name = $_SESSION['staff_name'] ?? 'Receptionist';

// Get receptionist-specific statistics (DB-compatible)
$total_customers = (int)$conn->query("SELECT COUNT(*) AS count FROM customers")->fetch_assoc()['count'];

$pending_appointments = (int)$conn->query("
    SELECT COUNT(*) AS count 
    FROM appointments 
    WHERE status IN ('requested','booked')
")->fetch_assoc()['count'];

$today_appointments = (int)$conn->query("
    SELECT COUNT(*) AS count 
    FROM appointments 
    WHERE requested_date = CURDATE()
")->fetch_assoc()['count'];

$unpaid_bills = (int)$conn->query("
    SELECT COUNT(*) AS count 
    FROM bills 
    WHERE payment_status = 'unpaid'
")->fetch_assoc()['count'];

// Get recent appointments (DB-compatible)
$recent_appointments = $conn->query("
    SELECT 
        a.appointment_id,
        a.status,
        a.requested_date,
        a.requested_slot,
        a.problem_text,
        c.name AS customer_name,
        c.phone,
        v.plate_no,
        v.make,
        v.model
    FROM appointments a
    JOIN customers c ON a.customer_id = c.customer_id
    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    ORDER BY a.requested_date DESC, a.requested_slot DESC, a.created_at DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Get recent customers
$recent_customers = $conn->query("
    SELECT customer_id, name, email, phone, created_at
    FROM customers
    ORDER BY created_at DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

function statusBadgeClass($status) {
    switch ($status) {
        case 'requested':   return 'warning';
        case 'booked':      return 'info';
        case 'in_progress': return 'info';
        case 'completed':   return 'success';
        case 'cancelled':   return 'danger';
        default:            return 'info';
    }
}

function statusLabel($status) {
    return ucfirst(str_replace('_', ' ', (string)$status));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Dashboard - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #059669;
            --primary-dark: #047857;
            --secondary-color: #10b981;
            --accent-color: #14b8a6;
            --light-bg: #f0fdf4;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light-bg);
            margin: 0;
            padding: 0;
        }
        
        .top-nav {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
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
            color: var(--primary-color);
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
            box-shadow: 0 4px 16px rgba(5,150,105,0.15);
        }
        
        .stat-card.green { border-left-color: #059669; }
        .stat-card.teal { border-left-color: #14b8a6; }
        .stat-card.blue { border-left-color: #3b82f6; }
        .stat-card.amber { border-left-color: #f59e0b; }
        
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
        
        .stat-icon.green { background: #d1fae5; color: #065f46; }
        .stat-icon.teal { background: #ccfbf1; color: #115e59; }
        .stat-icon.blue { background: #dbeafe; color: #1e40af; }
        .stat-icon.amber { background: #fef3c7; color: #92400e; }
        
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
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
            box-shadow: 0 6px 20px rgba(5,150,105,0.3);
            color: white;
        }
        
        .action-btn i {
            font-size: 1.75rem;
        }
        
        .action-text {
            flex: 1;
        }
        
        .action-title {
            font-weight: 600;
            font-size: 1.05rem;
            margin-bottom: 0.25rem;
        }
        
        .action-subtitle {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .content-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 992px) {
            .content-row {
                grid-template-columns: 1fr;
            }
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
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        .list-item:hover {
            background: #f9fafb;
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }
        
        .item-title {
            font-weight: 600;
            color: #111827;
        }
        
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
        
        .item-details {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="top-nav-content">
            <div class="logo">
                <i class="bi bi-person-badge"></i>
                Screw Dheela - Receptionist Portal
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
                <i class="bi bi-speedometer2 me-2"></i>Receptionist Dashboard
            </h1>
            <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($staff_name); ?>! Manage customers, appointments, and billing.</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-header">
                    <div class="stat-icon green">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $total_customers; ?></div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card teal">
                <div class="stat-header">
                    <div class="stat-icon teal">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $today_appointments; ?></div>
                        <div class="stat-label">Today's Appointments</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card blue">
                <div class="stat-header">
                    <div class="stat-icon blue">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $pending_appointments; ?></div>
                        <div class="stat-label">Pending Appointments</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card amber">
                <div class="stat-header">
                    <div class="stat-icon amber">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $unpaid_bills; ?></div>
                        <div class="stat-label">Unpaid Bills</div>
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
                <a href="add_customer.php" class="action-btn">
                    <i class="bi bi-person-plus-fill"></i>
                    <div class="action-text">
                        <div class="action-title">Add Customer</div>
                        <div class="action-subtitle">Register new customer</div>
                    </div>
                </a>
                
                <a href="book_appointment.php" class="action-btn">
                    <i class="bi bi-calendar-plus"></i>
                    <div class="action-text">
                        <div class="action-title">Book Appointment</div>
                        <div class="action-subtitle">Schedule service</div>
                    </div>
                </a>
                
                <a href="add_vehicle.php" class="action-btn">
                    <i class="bi bi-car-front-fill"></i>
                    <div class="action-text">
                        <div class="action-title">Add Vehicle</div>
                        <div class="action-subtitle">Register vehicle</div>
                    </div>
                </a>
                
                <a href="billing.php" class="action-btn">
                    <i class="bi bi-receipt-cutoff"></i>
                    <div class="action-text">
                        <div class="action-title">Generate Bill</div>
                        <div class="action-subtitle">Create invoice</div>
                    </div>
                </a>
                
                <a href="customers_list.php" class="action-btn">
                    <i class="bi bi-search"></i>
                    <div class="action-text">
                        <div class="action-title">Search Customers</div>
                        <div class="action-subtitle">Find customer records</div>
                    </div>
                </a>
                
                <a href="global_search.php" class="action-btn">
                    <i class="bi bi-search-heart"></i>
                    <div class="action-text">
                        <div class="action-title">Global Search</div>
                        <div class="action-subtitle">Search everything</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Content Row -->
        <div class="content-row">
            <!-- Recent Appointments -->
            <div class="data-card">
                <h2 class="section-title">
                    <i class="bi bi-calendar-event"></i>
                    Recent Appointments
                </h2>
                <?php if (empty($recent_appointments)): ?>
                    <p class="text-muted">No appointments found.</p>
                <?php else: ?>
                    <?php foreach ($recent_appointments as $appt): ?>
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
                                        (Slot <?php echo (int)$appt['requested_slot']; ?>)
                                        <?php if (!empty($appt['problem_text'])): ?>
                                            <br><i class="bi bi-wrench me-1"></i><?php echo htmlspecialchars(mb_strimwidth($appt['problem_text'], 0, 90, '...')); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="badge <?php echo statusBadgeClass($appt['status']); ?>">
                                    <?php echo htmlspecialchars(statusLabel($appt['status'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="mt-3 text-center">
                    <a href="appointments.php" class="btn btn-outline-success">View All Appointments</a>
                </div>
            </div>

            <!-- Recent Customers -->
            <div class="data-card">
                <h2 class="section-title">
                    <i class="bi bi-people"></i>
                    Recent Customers
                </h2>
                <?php if (empty($recent_customers)): ?>
                    <p class="text-muted">No customers found.</p>
                <?php else: ?>
                    <?php foreach ($recent_customers as $customer): ?>
                        <div class="list-item">
                            <div class="item-title"><?php echo htmlspecialchars($customer['name']); ?></div>
                            <div class="item-details">
                                <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($customer['email']); ?><br>
                                <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($customer['phone']); ?><br>
                                <i class="bi bi-clock me-1"></i>Registered: <?php echo date('M d, Y', strtotime($customer['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="mt-3 text-center">
                    <a href="customers_list.php" class="btn btn-outline-success">View All Customers</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
