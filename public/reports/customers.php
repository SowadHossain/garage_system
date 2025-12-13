<?php
session_start();
require_once '../../config/db.php';

// Check if user is staff (admin)
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'admin') {
    header('Location: ../staff_login.php');
    exit;
}

$page_title = 'Customer Analytics';
require_once '../../includes/header.php';

// Demonstrates: VIEWs, Subqueries with IN, IS NULL, DISTINCT

// 1. Customer Summary using VIEW (demonstrates CREATE VIEW usage)
$view_query = "SELECT * FROM view_customer_summary ORDER BY total_spent DESC LIMIT 20";
$view_result = $conn->query($view_query);
$customer_summary = [];
while ($row = $view_result->fetch_assoc()) {
    $customer_summary[] = $row;
}

// 2. Customers with unpaid bills (demonstrates subquery with IN)
$unpaid_customers_query = "SELECT 
                                c.customer_id,
                                c.name,
                                c.email,
                                c.phone,
                                COUNT(DISTINCT a.appointment_id) as total_appointments
                            FROM customers c
                            WHERE c.customer_id IN (
                                SELECT DISTINCT a.customer_id 
                                FROM appointments a
                                JOIN jobs j ON a.appointment_id = j.appointment_id
                                JOIN bills b ON j.job_id = b.job_id
                                WHERE b.payment_status = 'unpaid'
                            )
                            GROUP BY c.customer_id, c.name, c.email, c.phone
                            ORDER BY c.name";
$unpaid_customers_result = $conn->query($unpaid_customers_query);
$unpaid_customers = [];
while ($row = $unpaid_customers_result->fetch_assoc()) {
    $unpaid_customers[] = $row;
}

// 3. Customers without vehicles (demonstrates IS NULL check)
$no_vehicles_query = "SELECT 
                        c.customer_id,
                        c.name,
                        c.email,
                        c.phone,
                        c.created_at
                    FROM customers c
                    LEFT JOIN vehicles v ON c.customer_id = v.customer_id
                    WHERE v.vehicle_id IS NULL
                    ORDER BY c.created_at DESC";
$no_vehicles_result = $conn->query($no_vehicles_query);
$no_vehicles_customers = [];
while ($row = $no_vehicles_result->fetch_assoc()) {
    $no_vehicles_customers[] = $row;
}

// 4. Distinct vehicle brands in our system (demonstrates DISTINCT)
$distinct_brands_query = "SELECT DISTINCT brand 
                          FROM vehicles 
                          WHERE brand IS NOT NULL 
                          ORDER BY brand";
$distinct_brands_result = $conn->query($distinct_brands_query);
$distinct_brands = [];
while ($row = $distinct_brands_result->fetch_assoc()) {
    $distinct_brands[] = $row['brand'];
}

// 5. Customers who have completed appointments (demonstrates subquery with IN and ANY)
$completed_customers_query = "SELECT 
                                    c.customer_id,
                                    c.name,
                                    c.email,
                                    COUNT(DISTINCT a.appointment_id) as completed_count
                                FROM customers c
                                WHERE c.customer_id IN (
                                    SELECT customer_id 
                                    FROM appointments 
                                    WHERE status = 'completed'
                                )
                                GROUP BY c.customer_id, c.name, c.email
                                ORDER BY completed_count DESC
                                LIMIT 10";
$completed_customers_result = $conn->query($completed_customers_query);
$completed_customers = [];
while ($row = $completed_customers_result->fetch_assoc()) {
    $completed_customers[] = $row;
}

// 6. Active customers (have appointments in last 6 months) vs Inactive
$active_customers_query = "SELECT 
                                'Active' as status,
                                COUNT(DISTINCT c.customer_id) as customer_count
                            FROM customers c
                            WHERE c.customer_id IN (
                                SELECT customer_id 
                                FROM appointments 
                                WHERE appointment_datetime >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                            )
                            UNION ALL
                            SELECT 
                                'Inactive' as status,
                                COUNT(DISTINCT c.customer_id) as customer_count
                            FROM customers c
                            WHERE c.customer_id NOT IN (
                                SELECT customer_id 
                                FROM appointments 
                                WHERE appointment_datetime >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                            )";
$active_customers_result = $conn->query($active_customers_query);
$activity_stats = [];
while ($row = $active_customers_result->fetch_assoc()) {
    $activity_stats[$row['status']] = $row['customer_count'];
}

// 7. Customers with appointments but no bills (demonstrates complex subquery)
$no_bills_query = "SELECT 
                        c.customer_id,
                        c.name,
                        c.email,
                        COUNT(DISTINCT a.appointment_id) as appointment_count
                    FROM customers c
                    JOIN appointments a ON c.customer_id = a.customer_id
                    WHERE a.appointment_id NOT IN (
                        SELECT DISTINCT j.appointment_id 
                        FROM jobs j
                        JOIN bills b ON j.job_id = b.job_id
                    )
                    GROUP BY c.customer_id, c.name, c.email
                    ORDER BY appointment_count DESC";
$no_bills_result = $conn->query($no_bills_query);
$no_bills_customers = [];
while ($row = $no_bills_result->fetch_assoc()) {
    $no_bills_customers[] = $row;
}

// Overall stats
$total_customers = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
$customers_with_vehicles = $conn->query("SELECT COUNT(DISTINCT customer_id) as count FROM vehicles")->fetch_assoc()['count'];
$customers_with_appointments = $conn->query("SELECT COUNT(DISTINCT customer_id) as count FROM appointments")->fetch_assoc()['count'];
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../admin_dashboard.php">Admin Dashboard</a></li>
            <li class="breadcrumb-item active">Customer Analytics</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-people me-2"></i>Customer Analytics
            </h2>
            <p class="text-muted">Advanced customer insights using views and subqueries</p>
        </div>
    </div>

    <!-- Overall Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Customers</h6>
                    <h3 class="mb-0 text-primary"><?php echo $total_customers; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">With Vehicles</h6>
                    <h3 class="mb-0 text-success"><?php echo $customers_with_vehicles; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">With Appointments</h6>
                    <h3 class="mb-0 text-info"><?php echo $customers_with_appointments; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Vehicle Brands</h6>
                    <h3 class="mb-0 text-warning"><?php echo count($distinct_brands); ?></h3>
                    <small class="text-muted">Distinct brands</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Summary VIEW -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-eye me-2"></i>Customer Summary (Using VIEW: view_customer_summary)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        This data comes from a SQL VIEW that aggregates customer, vehicle, appointment, and billing data.
                    </p>
                    <?php if (empty($customer_summary)): ?>
                        <p class="text-muted">No customer summary data available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th class="text-end">Vehicles</th>
                                        <th class="text-end">Appointments</th>
                                        <th class="text-end">Completed</th>
                                        <th class="text-end">Total Spent</th>
                                        <th class="text-end">Avg Bill</th>
                                        <th>Last Visit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customer_summary as $customer): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($customer['name']); ?></strong></td>
                                            <td>
                                                <small>
                                                    <?php echo htmlspecialchars($customer['email']); ?><br>
                                                    <?php echo htmlspecialchars($customer['phone']); ?>
                                                </small>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-secondary"><?php echo $customer['vehicle_count']; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-info"><?php echo $customer['appointment_count']; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-success"><?php echo $customer['completed_appointments']; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-success">
                                                    $<?php echo number_format($customer['total_spent'], 2); ?>
                                                </strong>
                                            </td>
                                            <td class="text-end">
                                                $<?php echo number_format($customer['avg_bill_amount'], 2); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($customer['last_appointment_date']) {
                                                    $date = new DateTime($customer['last_appointment_date']);
                                                    echo $date->format('M d, Y');
                                                } else {
                                                    echo '<span class="text-muted">Never</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Customers with Unpaid Bills (IN subquery) -->
        <div class="col-md-6">
            <div class="card h-100 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>Unpaid Bills (Subquery IN)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Uses <code>WHERE customer_id IN (SELECT...)</code> to find customers with unpaid bills.
                    </p>
                    <?php if (empty($unpaid_customers)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            All bills are paid!
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($unpaid_customers as $customer): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($customer['name']); ?></strong><br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($customer['email']); ?> | 
                                                <?php echo htmlspecialchars($customer['phone']); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-warning"><?php echo $customer['total_appointments']; ?> appointments</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Customers without Vehicles (IS NULL) -->
        <div class="col-md-6">
            <div class="card h-100 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-x-circle me-2"></i>No Vehicles (IS NULL Check)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Uses <code>LEFT JOIN ... WHERE vehicle_id IS NULL</code> to find customers without registered vehicles.
                    </p>
                    <?php if (empty($no_vehicles_customers)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            All customers have vehicles registered!
                        </div>
                    <?php else: ?>
                        <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($no_vehicles_customers as $customer): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($customer['name']); ?></strong><br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($customer['email']); ?> | 
                                                <?php echo htmlspecialchars($customer['phone']); ?>
                                            </small>
                                        </div>
                                        <small class="text-muted">
                                            <?php 
                                            $date = new DateTime($customer['created_at']);
                                            echo $date->format('M d, Y');
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                Total: <?php echo count($no_vehicles_customers); ?> customer(s) without vehicles
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Distinct Vehicle Brands -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-car-front me-2"></i>Vehicle Brands (DISTINCT)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Uses <code>SELECT DISTINCT brand FROM vehicles</code> to list unique vehicle brands.
                    </p>
                    <?php if (empty($distinct_brands)): ?>
                        <p class="text-muted">No vehicles registered yet.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($distinct_brands as $brand): ?>
                                <span class="badge bg-info fs-6 px-3 py-2">
                                    <?php echo htmlspecialchars($brand); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Total: <?php echo count($distinct_brands); ?> distinct brand(s)
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Loyal Customers (Completed Appointments) -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-star me-2"></i>Loyal Customers (Subquery IN)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Uses <code>WHERE customer_id IN (SELECT ... WHERE status = 'completed')</code>
                    </p>
                    <?php if (empty($completed_customers)): ?>
                        <p class="text-muted">No completed appointments yet.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($completed_customers as $index => $customer): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($index < 3): ?>
                                                <i class="bi bi-trophy-fill text-warning me-1"></i>
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($customer['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                        </div>
                                        <span class="badge bg-success fs-6">
                                            <?php echo $customer['completed_count']; ?> completed
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

    <!-- Activity Status (Active vs Inactive) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-activity me-2"></i>Customer Activity Status (IN vs NOT IN)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Uses <code>IN (SELECT...)</code> and <code>NOT IN (SELECT...)</code> with UNION to categorize customers.
                    </p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Active Customers</h6>
                                    <h2 class="text-success mb-0">
                                        <?php echo $activity_stats['Active'] ?? 0; ?>
                                    </h2>
                                    <small class="text-muted">Appointments in last 6 months</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Inactive Customers</h6>
                                    <h2 class="text-warning mb-0">
                                        <?php echo $activity_stats['Inactive'] ?? 0; ?>
                                    </h2>
                                    <small class="text-muted">No recent appointments</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers with Appointments but No Bills -->
    <?php if (!empty($no_bills_customers)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="bi bi-receipt me-2"></i>Appointments Without Bills (NOT IN subquery)
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Uses <code>WHERE appointment_id NOT IN (SELECT...)</code> to find appointments without bills.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer</th>
                                        <th>Email</th>
                                        <th class="text-end">Unbilled Appointments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($no_bills_customers as $customer): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($customer['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                            <td class="text-end">
                                                <span class="badge bg-warning"><?php echo $customer['appointment_count']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- SQL Techniques Used -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-code-square me-2"></i>SQL Techniques Demonstrated
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h6 class="text-primary">VIEWS</h6>
                            <ul class="small">
                                <li><code>view_customer_summary</code> - Pre-aggregated customer data</li>
                                <li>Simplifies complex queries</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-success">Subqueries</h6>
                            <ul class="small">
                                <li><code>IN (SELECT...)</code> - Customers with unpaid bills</li>
                                <li><code>NOT IN (SELECT...)</code> - Unbilled appointments</li>
                                <li>Active vs inactive customers</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-warning">Special Checks</h6>
                            <ul class="small">
                                <li><code>IS NULL</code> - Customers without vehicles</li>
                                <li><code>LEFT JOIN</code> - Include all customers</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-info">Other</h6>
                            <ul class="small">
                                <li><code>DISTINCT</code> - Unique vehicle brands</li>
                                <li><code>UNION ALL</code> - Combine activity stats</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
