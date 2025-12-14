<?php
require_once __DIR__ . "/_guard.php";

// Stats
$total_customers = (int)$conn->query("SELECT COUNT(*) c FROM customers")->fetch_assoc()['c'];
$total_staff     = (int)$conn->query("SELECT COUNT(*) c FROM staff WHERE is_active = 1")->fetch_assoc()['c'];
$total_vehicles  = (int)$conn->query("SELECT COUNT(*) c FROM vehicles")->fetch_assoc()['c'];
$total_appts     = (int)$conn->query("SELECT COUNT(*) c FROM appointments")->fetch_assoc()['c'];

$pending_appts = (int)$conn->query("
    SELECT COUNT(*) c
    FROM appointments
    WHERE status IN ('requested','booked')
")->fetch_assoc()['c'];

$active_jobs = (int)$conn->query("
    SELECT COUNT(*) c
    FROM jobs
    WHERE status = 'in_progress'
")->fetch_assoc()['c'];

$unpaid_bills = (int)$conn->query("
    SELECT COUNT(*) c
    FROM bills
    WHERE payment_status = 'unpaid'
")->fetch_assoc()['c'];

// Revenue
$revenue = $conn->query("
    SELECT
        COALESCE(SUM(total),0) total_revenue,
        COALESCE(SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END),0) paid_revenue,
        COALESCE(SUM(CASE WHEN payment_status='unpaid' THEN total ELSE 0 END),0) unpaid_revenue,
        COUNT(*) total_bills
    FROM bills
")->fetch_assoc();

// Recent appointments
$recent_appointments = $conn->query("
    SELECT
        a.appointment_id, a.status, a.requested_date, a.requested_slot,
        c.name AS customer_name, c.phone,
        v.plate_no, v.make, v.model
    FROM appointments a
    JOIN customers c ON c.customer_id = a.customer_id
    LEFT JOIN vehicles v ON v.vehicle_id = a.vehicle_id
    ORDER BY a.created_at DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// Top customers (this month) based on bills.total
$top_customers_month = $conn->query("
    SELECT
        c.customer_id,
        c.name,
        COUNT(DISTINCT a.appointment_id) AS appointment_count,
        COALESCE(SUM(b.total), 0) AS total_spent
    FROM customers c
    JOIN appointments a ON a.customer_id = c.customer_id
    LEFT JOIN jobs j ON j.appointment_id = a.appointment_id
    LEFT JOIN bills b ON b.job_id = j.job_id AND b.payment_status='paid'
    WHERE MONTH(a.requested_date) = MONTH(CURRENT_DATE())
      AND YEAR(a.requested_date) = YEAR(CURRENT_DATE())
    GROUP BY c.customer_id, c.name
    ORDER BY total_spent DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Reviews module safe-check (your init.sql doesn’t include reviews)
$has_reviews = false;
$review_stats = ['total_reviews'=>0,'avg_rating'=>0,'pending_response'=>0];
$recent_reviews = [];

$check = $conn->query("SHOW TABLES LIKE 'reviews'");
if ($check && $check->num_rows > 0) {
    $has_reviews = true;

    $review_stats = $conn->query("
        SELECT
            COUNT(*) AS total_reviews,
            AVG(rating) AS avg_rating,
            SUM(CASE WHEN staff_response IS NULL OR staff_response='' THEN 1 ELSE 0 END) AS pending_response
        FROM reviews
        WHERE is_approved = 1
    ")->fetch_assoc();

    $recent_reviews = $conn->query("
        SELECT r.review_id, r.rating, r.review_text, r.created_at, c.name AS customer_name
        FROM reviews r
        JOIN customers c ON c.customer_id = r.customer_id
        WHERE r.is_approved = 1
        ORDER BY r.created_at DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
}

ui_header_admin("Admin Dashboard", $staff_name);
?>

<div class="dashboard-header">
    <h1 class="dashboard-title">
        <i class="bi bi-speedometer2 me-2"></i>Administrator Dashboard
    </h1>
    <p class="dashboard-subtitle">Welcome back, <?php echo h($staff_name); ?>! Complete system overview and management.</p>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-header">
            <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value"><?php echo $total_customers; ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
        </div>
    </div>

    <div class="stat-card purple">
        <div class="stat-header">
            <div class="stat-icon purple"><i class="bi bi-person-badge"></i></div>
            <div>
                <div class="stat-value"><?php echo $total_staff; ?></div>
                <div class="stat-label">Active Staff</div>
            </div>
        </div>
    </div>

    <div class="stat-card cyan">
        <div class="stat-header">
            <div class="stat-icon cyan"><i class="bi bi-car-front-fill"></i></div>
            <div>
                <div class="stat-value"><?php echo $total_vehicles; ?></div>
                <div class="stat-label">Registered Vehicles</div>
            </div>
        </div>
    </div>

    <div class="stat-card green">
        <div class="stat-header">
            <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="stat-value">৳<?php echo number_format((float)$revenue['total_revenue'], 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
    </div>

    <div class="stat-card orange">
        <div class="stat-header">
            <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <div class="stat-value"><?php echo $pending_appts; ?></div>
                <div class="stat-label">Pending Appointments</div>
            </div>
        </div>
    </div>

    <div class="stat-card red">
        <div class="stat-header">
            <div class="stat-icon red"><i class="bi bi-receipt"></i></div>
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
            <div class="report-icon"><i class="bi bi-currency-dollar"></i></div>
            <div class="report-title">Revenue Reports</div>
            <div class="report-description">Revenue analysis, paid vs unpaid, and totals.</div>
            <a href="../reports/revenue.php" class="btn btn-success w-100">
                <i class="bi bi-arrow-right me-1"></i>View Report
            </a>
        </div>

        <div class="report-card services">
            <div class="report-icon"><i class="bi bi-tools"></i></div>
            <div class="report-title">Service Performance</div>
            <div class="report-description">Popular services and service trends (if enabled).</div>
            <a href="../reports/services.php" class="btn btn-info w-100">
                <i class="bi bi-arrow-right me-1"></i>View Report
            </a>
        </div>

        <div class="report-card customers">
            <div class="report-icon"><i class="bi bi-people"></i></div>
            <div class="report-title">Customer Analytics</div>
            <div class="report-description">Customer insights and activity.</div>
            <a href="../reports/customers.php" class="btn btn-warning w-100">
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
        <!-- IMPORTANT: correct path based on your tree: /public/admin/manage_staff.php -->
        <a href="manage_staff.php" class="action-btn">
            <i class="bi bi-person-gear"></i>
            <div class="action-text">
                <div class="action-title">Manage Staff</div>
                <div class="action-subtitle">Add/edit staff members</div>
            </div>
        </a>

        <a href="customers.php" class="action-btn">
            <i class="bi bi-search"></i>
            <div class="action-text">
                <div class="action-title">Search Customers</div>
                <div class="action-subtitle">Find customer records</div>
            </div>
        </a>

        <a href="vehicles.php" class="action-btn">
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

        <a href="appointments.php" class="action-btn">
            <i class="bi bi-calendar-check"></i>
            <div class="action-text">
                <div class="action-title">Appointments</div>
                <div class="action-subtitle">View all appointments</div>
            </div>
        </a>

        <a href="jobs.php" class="action-btn">
            <i class="bi bi-clipboard-check"></i>
            <div class="action-text">
                <div class="action-title">Job Management</div>
                <div class="action-subtitle">Monitor all jobs</div>
            </div>
        </a>

        <a href="bills.php" class="action-btn">
            <i class="bi bi-receipt"></i>
            <div class="action-text">
                <div class="action-title">Billing</div>
                <div class="action-subtitle">View bills & payment status</div>
            </div>
        </a>
    </div>
</div>

<!-- Reviews Section (safe) -->
<div class="reports-section">
    <h2 class="section-title">
        <i class="bi bi-star-half"></i>
        Customer Feedback
    </h2>

    <?php if (!$has_reviews): ?>
        <div class="alert alert-warning mb-0">
            Reviews module is not installed (no <code>reviews</code> table). UI kept, queries disabled.
        </div>
    <?php else: ?>
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-value"><?php echo (int)($review_stats['total_reviews'] ?? 0); ?></div>
                    <div class="stat-label">Total Reviews</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-value">
                        <?php echo number_format((float)($review_stats['avg_rating'] ?? 0), 1); ?>
                        <i class="bi bi-star-fill text-warning"></i>
                    </div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card orange">
                    <div class="stat-value"><?php echo (int)($review_stats['pending_response'] ?? 0); ?></div>
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
                            <div class="item-title"><?php echo h($review['customer_name']); ?></div>
                            <div class="item-details"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                        </div>
                        <span class="badge" style="background:#ffc107;color:#000;">
                            <?php echo h($review['rating']); ?> <i class="bi bi-star-fill"></i>
                        </span>
                    </div>
                    <p class="mb-0 text-muted">
                        <?php
                            $txt = (string)($review['review_text'] ?? '');
                            echo h(mb_strimwidth($txt, 0, 150, '...'));
                        ?>
                    </p>
                </div>
            <?php endforeach; ?>

            <div class="text-center mt-3">
                <a href="../reviews/moderate.php" class="btn btn-outline-primary">View All Reviews</a>
            </div>
        <?php endif; ?>
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
                            <div class="item-title"><?php echo h($appt['customer_name']); ?></div>
                            <div class="item-details">
                                <?php if (!empty($appt['plate_no'])): ?>
                                    <i class="bi bi-car-front me-1"></i>
                                    <?php echo h(trim(($appt['make'] ?? '') . ' ' . ($appt['model'] ?? ''))); ?>
                                    - <?php echo h($appt['plate_no']); ?><br>
                                <?php endif; ?>
                                <i class="bi bi-calendar3 me-1"></i>
                                <?php echo date('M d, Y', strtotime($appt['requested_date'])); ?>
                                (Slot <?php echo (int)$appt['requested_slot']; ?>)
                            </div>
                        </div>
                        <span class="badge <?php echo h(statusBadgeClass($appt['status'])); ?>">
                            <?php echo h(ucfirst(str_replace('_',' ', (string)$appt['status']))); ?>
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
            <i class="bi bi-trophy-fill"></i>
            Top Customers (This Month)
        </h2>

        <?php if (empty($top_customers_month)): ?>
            <p class="text-muted">No customer data for this month.</p>
        <?php else: ?>
            <?php foreach ($top_customers_month as $index => $c): ?>
                <div class="list-item">
                    <div class="item-header">
                        <div>
                            <div class="item-title">
                                <?php if ($index < 3): ?><i class="bi bi-trophy-fill text-warning me-1"></i><?php endif; ?>
                                <?php echo h($c['name']); ?>
                            </div>
                            <div class="item-details">
                                <i class="bi bi-calendar-check me-1"></i><?php echo (int)$c['appointment_count']; ?> visits
                            </div>
                        </div>
                        <span class="badge success">
                            ৳<?php echo number_format((float)$c['total_spent'], 2); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="mt-3 text-center">
            <a href="../reports/customers.php" class="btn btn-outline-primary">View Customer Report</a>
        </div>
    </div>
</div>

<?php ui_footer_admin(); ?>
