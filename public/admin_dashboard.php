<?php
session_start();
require_once '../config/db.php';

// Check if user is staff admin
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'admin') {
    header('Location: staff_login.php');
    exit;
}

$page_title = 'Super Admin Dashboard';
require_once '../includes/header.php';

// Get comprehensive statistics
$total_customers = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
$total_staff = $conn->query("SELECT COUNT(*) as count FROM staff WHERE active = 1")->fetch_assoc()['count'];
$total_vehicles = $conn->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'];
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];

// Revenue statistics
$revenue_stats = $conn->query("SELECT 
                                    COUNT(*) as total_bills,
                                    SUM(total_amount) as total_revenue,
                                    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_revenue,
                                    SUM(CASE WHEN payment_status = 'unpaid' THEN total_amount ELSE 0 END) as unpaid_revenue
                                FROM bills")->fetch_assoc();

// Pending work
$pending_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status IN ('booked', 'pending')")->fetch_assoc()['count'];
$active_jobs = $conn->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'open'")->fetch_assoc()['count'];

// Recent activity
$recent_appointments_stmt = $conn->prepare("SELECT a.*, c.name as customer_name, v.registration_no
                                            FROM appointments a
                                            JOIN customers c ON a.customer_id = c.customer_id
                                            LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                                            ORDER BY a.created_at DESC
                                            LIMIT 5");
$recent_appointments_stmt->execute();
$recent_appointments = $recent_appointments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Top customers this month
$top_customers_month = $conn->query("SELECT 
                                        c.customer_id,
                                        c.name,
                                        COUNT(DISTINCT a.appointment_id) as appointment_count
                                    FROM customers c
                                    JOIN appointments a ON c.customer_id = a.customer_id
                                    WHERE MONTH(a.appointment_datetime) = MONTH(CURRENT_DATE())
                                    AND YEAR(a.appointment_datetime) = YEAR(CURRENT_DATE())
                                    GROUP BY c.customer_id, c.name
                                    ORDER BY appointment_count DESC
                                    LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-speedometer2 me-2"></i>Super Admin Dashboard
            </h2>
            <p class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['staff_name']); ?>! Comprehensive system overview</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-people-fill me-1"></i>Customers
                    </h6>
                    <h2 class="mb-0 text-primary"><?php echo $total_customers; ?></h2>
                    <a href="../customers/list.php" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="bi bi-arrow-right me-1"></i>View All
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-car-front-fill me-1"></i>Vehicles
                    </h6>
                    <h2 class="mb-0 text-success"><?php echo $total_vehicles; ?></h2>
                    <small class="text-muted"><?php echo $total_appointments; ?> appointments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-cash-stack me-1"></i>Total Revenue
                    </h6>
                    <h2 class="mb-0 text-info">$<?php echo number_format($revenue_stats['total_revenue'] ?? 0, 2); ?></h2>
                    <small class="text-success">Paid: $<?php echo number_format($revenue_stats['paid_revenue'] ?? 0, 2); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">
                        <i class="bi bi-hourglass-split me-1"></i>Pending Work
                    </h6>
                    <h2 class="mb-0 text-warning"><?php echo $pending_appointments; ?></h2>
                    <small class="text-muted"><?php echo $active_jobs; ?> active jobs</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports & Analytics Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart-line me-2"></i>Reports & Analytics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card border-success h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-graph-up text-success me-2"></i>Revenue Reports
                                    </h5>
                                    <p class="card-text text-muted small">
                                        SUM, AVG, MIN, MAX aggregates<br>
                                        GROUP BY month & payment method<br>
                                        HAVING clauses for filtering
                                    </p>
                                    <a href="reports/revenue.php" class="btn btn-success">
                                        <i class="bi bi-arrow-right me-1"></i>View Report
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-info h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-tools text-info me-2"></i>Service Performance
                                    </h5>
                                    <p class="card-text text-muted small">
                                        SERVICE usage statistics<br>
                                        GROUP BY category<br>
                                        IS NULL checks for unused services
                                    </p>
                                    <a href="reports/services.php" class="btn btn-info">
                                        <i class="bi bi-arrow-right me-1"></i>View Report
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-warning h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-people text-warning me-2"></i>Customer Analytics
                                    </h5>
                                    <p class="card-text text-muted small">
                                        SQL VIEWs & subqueries<br>
                                        IN / NOT IN filtering<br>
                                        DISTINCT brands
                                    </p>
                                    <a href="reports/customers.php" class="btn btn-warning">
                                        <i class="bi bi-arrow-right me-1"></i>View Report
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Recent Appointments -->
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-check me-2"></i>Recent Appointments
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_appointments)): ?>
                        <p class="text-muted">No recent appointments.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_appointments as $appt): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($appt['customer_name']); ?></strong>
                                            <?php if ($appt['registration_no']): ?>
                                                <span class="badge bg-secondary ms-1"><?php echo htmlspecialchars($appt['registration_no']); ?></span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php 
                                                $date = new DateTime($appt['appointment_datetime']);
                                                echo $date->format('M d, Y H:i');
                                                ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php 
                                            echo match($appt['status']) {
                                                'booked' => 'primary',
                                                'confirmed' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo htmlspecialchars($appt['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Customers This Month -->
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-star me-2"></i>Top Customers (This Month)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_customers_month)): ?>
                        <p class="text-muted">No appointments this month yet.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($top_customers_month as $index => $customer): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($index < 3): ?>
                                                <i class="bi bi-trophy-fill text-warning me-1"></i>
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                        </div>
                                        <span class="badge bg-success">
                                            <?php echo $customer['appointment_count']; ?> visits
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="../customers/list.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-search me-1"></i>Search Customers
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="../vehicles/list.php" class="btn btn-outline-success w-100">
                                <i class="bi bi-search me-1"></i>Search Vehicles
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="search.php" class="btn btn-outline-info w-100">
                                <i class="bi bi-search me-1"></i>Global Search
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="admin/manage_staff.php" class="btn btn-outline-warning w-100">
                                <i class="bi bi-people me-1"></i>Manage Staff
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SQL Techniques Summary -->
    <div class="row">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-check2-circle me-2"></i>Implemented SQL Core Requirements
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row small">
                        <div class="col-md-3">
                            <h6 class="text-success">✓ Database & Tables</h6>
                            <ul>
                                <li>15+ interrelated tables</li>
                                <li>PRIMARY & FOREIGN KEYS</li>
                                <li>Constraints (NOT NULL, UNIQUE, DEFAULT)</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-success">✓ Query Features</h6>
                            <ul>
                                <li>WHERE, ORDER BY, LIMIT</li>
                                <li>LIKE pattern matching</li>
                                <li>IS NULL checks</li>
                                <li>DISTINCT queries</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-success">✓ Aggregates & Joins</h6>
                            <ul>
                                <li>SUM, AVG, MIN, MAX, COUNT</li>
                                <li>GROUP BY & HAVING</li>
                                <li>INNER & LEFT JOIN</li>
                                <li>Multi-table joins (3+)</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-success">✓ Advanced</h6>
                            <ul>
                                <li>3 SQL VIEWs created</li>
                                <li>Subqueries (IN, NOT IN)</li>
                                <li>4 DB users with GRANT</li>
                                <li>WITH GRANT OPTION</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
