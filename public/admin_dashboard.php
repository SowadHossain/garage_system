<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is staff admin
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'admin') {
    header('Location: staff_login.php');
    exit;
}

$staff_name = $_SESSION['staff_name'];

// Get comprehensive statistics
$total_customers = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
$total_staff = $conn->query("SELECT COUNT(*) as count FROM staff WHERE active = 1")->fetch_assoc()['count'];
$total_vehicles = $conn->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'];
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
$pending_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status IN ('booked', 'pending')")->fetch_assoc()['count'];
$active_jobs = $conn->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'open'")->fetch_assoc()['count'];
$unpaid_bills = $conn->query("SELECT COUNT(*) as count FROM bills WHERE payment_status = 'unpaid'")->fetch_assoc()['count'];

// Revenue statistics
$revenue_stats = $conn->query("SELECT 
                                    COUNT(*) as total_bills,
                                    SUM(total_amount) as total_revenue,
                                    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_revenue,
                                    SUM(CASE WHEN payment_status = 'unpaid' THEN total_amount ELSE 0 END) as unpaid_revenue
                                FROM bills")->fetch_assoc();

// Get recent appointments
$recent_appointments = $conn->query("SELECT a.*, c.name as customer_name, c.phone, v.registration_no, v.brand, v.model
                                     FROM appointments a
                                     JOIN customers c ON a.customer_id = c.customer_id
                                     LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                                     ORDER BY a.created_at DESC
                                     LIMIT 6")->fetch_all(MYSQLI_ASSOC);

// Get top customers this month
$top_customers_month = $conn->query("SELECT 
                                        c.customer_id, c.name,
                                        COUNT(DISTINCT a.appointment_id) as appointment_count,
                                        COALESCE(SUM(b.total_amount), 0) as total_spent
                                    FROM customers c
                                    JOIN appointments a ON c.customer_id = a.customer_id
                                    LEFT JOIN jobs j ON a.appointment_id = j.appointment_id
                                    LEFT JOIN bills b ON j.job_id = b.job_id
                                    WHERE MONTH(a.appointment_datetime) = MONTH(CURRENT_DATE())
                                    AND YEAR(a.appointment_datetime) = YEAR(CURRENT_DATE())
                                    GROUP BY c.customer_id, c.name
                                    ORDER BY total_spent DESC
                                    LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Get review statistics
$review_stats = $conn->query("SELECT 
                                COUNT(*) as total_reviews,
                                AVG(rating) as avg_rating,
                                SUM(CASE WHEN staff_response IS NULL THEN 1 ELSE 0 END) as pending_response
                              FROM reviews 
                              WHERE is_approved = TRUE")->fetch_assoc();

// Get recent reviews
$recent_reviews = $conn->query("SELECT r.*, c.name as customer_name, r.rating
                               FROM reviews r
                               JOIN customers c ON r.customer_id = c.customer_id
                               WHERE r.is_approved = TRUE
                               ORDER BY r.created_at DESC
                               LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0a58ca;
            --secondary-color: #0dcaf0;
            --accent-color: #6610f2;
            --light-bg: #f0f4ff;
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
            box-shadow: 0 4px 16px rgba(13,110,253,0.15);
        }
        
        .stat-card.blue { border-left-color: #0d6efd; }
        .stat-card.purple { border-left-color: #6610f2; }
        .stat-card.cyan { border-left-color: #0dcaf0; }
        .stat-card.green { border-left-color: #198754; }
        .stat-card.orange { border-left-color: #fd7e14; }
        .stat-card.red { border-left-color: #dc3545; }
        
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
        
        .stat-icon.blue { background: #cfe2ff; color: #084298; }
        .stat-icon.purple { background: #e0cffc; color: #3d0a91; }
        .stat-icon.cyan { background: #cff4fc; color: #055160; }
        .stat-icon.green { background: #d1e7dd; color: #0a3622; }
        .stat-icon.orange { background: #ffe5d0; color: #984c0c; }
        .stat-icon.red { background: #f8d7da; color: #58151c; }
        
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
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
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
            box-shadow: 0 6px 20px rgba(13,110,253,0.3);
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
        
        .reports-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .report-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .report-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 1.5rem;
            border: 2px solid #dee2e6;
            transition: all 0.2s;
        }
        
        .report-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(13,110,253,0.15);
        }
        
        .report-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }
        
        .report-card.revenue .report-icon { background: #d1e7dd; color: #0a3622; }
        .report-card.services .report-icon { background: #cff4fc; color: #055160; }
        .report-card.customers .report-icon { background: #ffe5d0; color: #984c0c; }
        
        .report-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        
        .report-description {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 1rem;
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
            background: #f0f4ff;
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
        
        .badge.success { background: #d1e7dd; color: #0a3622; }
        .badge.warning { background: #fff3cd; color: #664d03; }
        .badge.info { background: #cfe2ff; color: #084298; }
        .badge.danger { background: #f8d7da; color: #58151c; }
        .badge.primary { background: #e0cffc; color: #3d0a91; }
        
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
                <i class="bi bi-shield-check"></i>
                Screw Dheela - Admin Portal
            </div>
            <div class="user-info">
                <span class="user-badge">
                    <i class="bi bi-person-circle me-2"></i>
                    <?php echo htmlspecialchars($staff_name); ?>
                </span>
                <a href="logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-main">
        <div class="dashboard-header">
            <h1 class="dashboard-title">
                <i class="bi bi-speedometer2 me-2"></i>Administrator Dashboard
            </h1>
            <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($staff_name); ?>! Complete system overview and management.</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-header">
                    <div class="stat-icon blue">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $total_customers; ?></div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card purple">
                <div class="stat-header">
                    <div class="stat-icon purple">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $total_staff; ?></div>
                        <div class="stat-label">Active Staff</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card cyan">
                <div class="stat-header">
                    <div class="stat-icon cyan">
                        <i class="bi bi-car-front-fill"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $total_vehicles; ?></div>
                        <div class="stat-label">Registered Vehicles</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card green">
                <div class="stat-header">
                    <div class="stat-icon green">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="stat-value">$<?php echo number_format($revenue_stats['total_revenue'] ?? 0, 0); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card orange">
                <div class="stat-header">
                    <div class="stat-icon orange">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $pending_appointments; ?></div>
                        <div class="stat-label">Pending Appointments</div>
                    </div>
                </div>
            </div>
            
            <div class="stat-card red">
                <div class="stat-header">
                    <div class="stat-icon red">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="stat-value"><?php echo $unpaid_bills; ?></div>
                        <div class="stat-label">Unpaid Bills</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports & Analytics -->
        <div class="reports-section">
            <h2 class="section-title">
                <i class="bi bi-graph-up-arrow"></i>
                Reports & Analytics
            </h2>
            <div class="report-cards">
                <div class="report-card revenue">
                    <div class="report-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="report-title">Revenue Reports</div>
                    <div class="report-description">
                        Comprehensive revenue analysis with monthly trends, payment methods, and profitability metrics.
                    </div>
                    <a href="reports/revenue.php" class="btn btn-success w-100">
                        <i class="bi bi-arrow-right me-1"></i>View Report
                    </a>
                </div>
                
                <div class="report-card services">
                    <div class="report-icon">
                        <i class="bi bi-tools"></i>
                    </div>
                    <div class="report-title">Service Performance</div>
                    <div class="report-description">
                        Service usage statistics, popular services, and performance by category.
                    </div>
                    <a href="reports/services.php" class="btn btn-info w-100">
                        <i class="bi bi-arrow-right me-1"></i>View Report
                    </a>
                </div>
                
                <div class="report-card customers">
                    <div class="report-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="report-title">Customer Analytics</div>
                    <div class="report-description">
                        Customer insights, spending patterns, loyalty metrics, and engagement data.
                    </div>
                    <a href="reports/customers.php" class="btn btn-warning w-100">
                        <i class="bi bi-arrow-right me-1"></i>View Report
                    </a>
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
                <a href="admin/manage_staff.php" class="action-btn">
                    <i class="bi bi-person-gear"></i>
                    <div class="action-text">
                        <div class="action-title">Manage Staff</div>
                        <div class="action-subtitle">Add/edit staff members</div>
                    </div>
                </a>
                
                <a href="../customers/list.php" class="action-btn">
                    <i class="bi bi-search"></i>
                    <div class="action-text">
                        <div class="action-title">Search Customers</div>
                        <div class="action-subtitle">Find customer records</div>
                    </div>
                </a>
                
                <a href="../vehicles/list.php" class="action-btn">
                    <i class="bi bi-car-front"></i>
                    <div class="action-text">
                        <div class="action-title">Vehicle Registry</div>
                        <div class="action-subtitle">Search vehicles</div>
                    </div>
                </a>
                
                <a href="search.php" class="action-btn">
                    <i class="bi bi-search-heart"></i>
                    <div class="action-text">
                        <div class="action-title">Global Search</div>
                        <div class="action-subtitle">Search everything</div>
                    </div>
                </a>
                
                <a href="../appointments/list.php" class="action-btn">
                    <i class="bi bi-calendar-check"></i>
                    <div class="action-text">
                        <div class="action-title">Appointments</div>
                        <div class="action-subtitle">View all appointments</div>
                    </div>
                </a>
                
                <a href="../jobs/list.php" class="action-btn">
                    <i class="bi bi-clipboard-check"></i>
                    <div class="action-text">
                        <div class="action-title">Job Management</div>
                        <div class="action-subtitle">Monitor all jobs</div>
                    </div>
                </a>
                
                <a href="../reviews/moderate.php" class="action-btn">
                    <i class="bi bi-star-fill"></i>
                    <div class="action-text">
                        <div class="action-title">Review Moderation</div>
                        <div class="action-subtitle">Manage customer reviews</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="reports-section">
            <h2 class="section-title">
                <i class="bi bi-star-half"></i>
                Customer Feedback
            </h2>
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $review_stats['total_reviews'] ?? 0; ?></div>
                        <div class="stat-label">Total Reviews</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format($review_stats['avg_rating'] ?? 0, 1); ?> <i class="bi bi-star-fill text-warning"></i></div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card orange">
                        <div class="stat-value"><?php echo $review_stats['pending_response'] ?? 0; ?></div>
                        <div class="stat-label">Pending Responses</div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($recent_reviews)): ?>
                <h5 class="mb-3">Recent Reviews</h5>
                <?php foreach ($recent_reviews as $review): ?>
                    <div class="list-item">
                        <div class="item-header">
                            <div>
                                <div class="item-title"><?php echo htmlspecialchars($review['customer_name']); ?></div>
                                <div class="item-details">
                                    <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                            <span class="badge" style="background: #ffc107; color: #000;">
                                <?php echo $review['rating']; ?> <i class="bi bi-star-fill"></i>
                            </span>
                        </div>
                        <p class="mb-0 text-muted"><?php echo htmlspecialchars(substr($review['review_text'], 0, 150)); ?><?php echo strlen($review['review_text']) > 150 ? '...' : ''; ?></p>
                    </div>
                <?php endforeach; ?>
                <div class="text-center mt-3">
                    <a href="../reviews/moderate.php" class="btn btn-outline-primary">View All Reviews</a>
                </div>
            <?php endif; ?>
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
                    <p class="text-muted">No recent appointments.</p>
                <?php else: ?>
                    <?php foreach ($recent_appointments as $appt): ?>
                        <div class="list-item">
                            <div class="item-header">
                                <div>
                                    <div class="item-title"><?php echo htmlspecialchars($appt['customer_name']); ?></div>
                                    <div class="item-details">
                                        <?php if ($appt['registration_no']): ?>
                                            <i class="bi bi-car-front me-1"></i><?php echo htmlspecialchars($appt['brand'] . ' ' . $appt['model']); ?> 
                                            - <?php echo htmlspecialchars($appt['registration_no']); ?><br>
                                        <?php endif; ?>
                                        <i class="bi bi-calendar3 me-1"></i><?php echo date('M d, Y h:i A', strtotime($appt['appointment_datetime'])); ?>
                                    </div>
                                </div>
                                <span class="badge <?php 
                                    echo match($appt['status']) {
                                        'booked' => 'info',
                                        'pending' => 'warning',
                                        'confirmed' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'info'
                                    };
                                ?>">
                                    <?php echo ucfirst(htmlspecialchars($appt['status'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="mt-3 text-center">
                    <a href="../appointments/list.php" class="btn btn-outline-primary">View All Appointments</a>
                </div>
            </div>

            <!-- Top Customers -->
            <div class="data-card">
                <h2 class="section-title">
                    <i class="bi bi-star-fill"></i>
                    Top Customers (This Month)
                </h2>
                <?php if (empty($top_customers_month)): ?>
                    <p class="text-muted">No customer data for this month.</p>
                <?php else: ?>
                    <?php foreach ($top_customers_month as $index => $customer): ?>
                        <div class="list-item">
                            <div class="item-header">
                                <div>
                                    <div class="item-title">
                                        <?php if ($index < 3): ?>
                                            <i class="bi bi-trophy-fill text-warning me-1"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($customer['name']); ?>
                                    </div>
                                    <div class="item-details">
                                        <i class="bi bi-calendar-check me-1"></i><?php echo $customer['appointment_count']; ?> visits
                                    </div>
                                </div>
                                <span class="badge success">
                                    $<?php echo number_format($customer['total_spent'], 2); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="mt-3 text-center">
                    <a href="reports/customers.php" class="btn btn-outline-primary">View Customer Report</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
