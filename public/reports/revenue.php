<?php
session_start();
require_once '../../config/db.php';

// Check if user is staff (admin)
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'admin') {
    header('Location: ../staff_login.php');
    exit;
}

$page_title = 'Revenue Reports';
require_once '../../includes/header.php';

// Demonstrates: SUM, AVG, MIN, MAX, GROUP BY, HAVING

// 1. Overall Revenue Statistics (demonstrates SUM, AVG, MIN, MAX)
$stats_query = "SELECT 
                    COUNT(*) as total_bills,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_bill_amount,
                    MIN(total_amount) as min_bill,
                    MAX(total_amount) as max_bill,
                    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_revenue,
                    SUM(CASE WHEN payment_status = 'unpaid' THEN total_amount ELSE 0 END) as unpaid_revenue
                FROM bills";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// 2. Revenue by Month (demonstrates GROUP BY)
$monthly_query = "SELECT 
                    YEAR(bill_date) as year,
                    MONTH(bill_date) as month,
                    DATE_FORMAT(bill_date, '%Y-%m') as month_key,
                    DATE_FORMAT(bill_date, '%b %Y') as month_name,
                    COUNT(*) as bill_count,
                    SUM(total_amount) as revenue,
                    AVG(total_amount) as avg_bill
                FROM bills
                GROUP BY YEAR(bill_date), MONTH(bill_date), DATE_FORMAT(bill_date, '%Y-%m'), DATE_FORMAT(bill_date, '%b %Y')
                ORDER BY year DESC, month DESC
                LIMIT 12";
$monthly_result = $conn->query($monthly_query);
$monthly_data = [];
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_data[] = $row;
}

// 3. Revenue by Payment Method (demonstrates GROUP BY)
$payment_method_query = "SELECT 
                            COALESCE(payment_method, 'Not Specified') as method,
                            COUNT(*) as transaction_count,
                            SUM(total_amount) as total_revenue,
                            AVG(total_amount) as avg_amount
                        FROM bills
                        WHERE payment_status = 'paid'
                        GROUP BY payment_method
                        ORDER BY total_revenue DESC";
$payment_method_result = $conn->query($payment_method_query);
$payment_methods = [];
while ($row = $payment_method_result->fetch_assoc()) {
    $payment_methods[] = $row;
}

// 4. Top Customers by Revenue (demonstrates GROUP BY with HAVING)
$top_customers_query = "SELECT 
                            c.customer_id,
                            c.name,
                            c.email,
                            c.phone,
                            COUNT(DISTINCT b.bill_id) as bill_count,
                            SUM(b.total_amount) as total_spent,
                            AVG(b.total_amount) as avg_per_bill
                        FROM customers c
                        JOIN appointments a ON c.customer_id = a.customer_id
                        JOIN jobs j ON a.appointment_id = j.appointment_id
                        JOIN bills b ON j.job_id = b.job_id
                        GROUP BY c.customer_id, c.name, c.email, c.phone
                        HAVING SUM(b.total_amount) > 0
                        ORDER BY total_spent DESC
                        LIMIT 10";
$top_customers_result = $conn->query($top_customers_query);
$top_customers = [];
while ($row = $top_customers_result->fetch_assoc()) {
    $top_customers[] = $row;
}

// 5. Unpaid Bills Summary (demonstrates GROUP BY with HAVING)
$unpaid_query = "SELECT 
                    c.customer_id,
                    c.name,
                    c.email,
                    c.phone,
                    COUNT(b.bill_id) as unpaid_count,
                    SUM(b.total_amount) as total_unpaid
                FROM customers c
                JOIN appointments a ON c.customer_id = a.customer_id
                JOIN jobs j ON a.appointment_id = j.appointment_id
                JOIN bills b ON j.job_id = b.job_id
                WHERE b.payment_status = 'unpaid'
                GROUP BY c.customer_id, c.name, c.email, c.phone
                HAVING SUM(b.total_amount) >= 10
                ORDER BY total_unpaid DESC";
$unpaid_result = $conn->query($unpaid_query);
$unpaid_customers = [];
while ($row = $unpaid_result->fetch_assoc()) {
    $unpaid_customers[] = $row;
}
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../admin_dashboard.php">Admin Dashboard</a></li>
            <li class="breadcrumb-item active">Revenue Reports</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-graph-up me-2"></i>Revenue Reports
            </h2>
            <p class="text-muted">Comprehensive revenue analysis using SQL aggregates</p>
        </div>
    </div>

    <!-- Overall Statistics (SUM, AVG, MIN, MAX) -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Revenue</h6>
                    <h3 class="mb-0 text-primary">
                        $<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?>
                    </h3>
                    <small class="text-muted"><?php echo $stats['total_bills']; ?> bills</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Paid Revenue</h6>
                    <h3 class="mb-0 text-success">
                        $<?php echo number_format($stats['paid_revenue'] ?? 0, 2); ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Unpaid Revenue</h6>
                    <h3 class="mb-0 text-warning">
                        $<?php echo number_format($stats['unpaid_revenue'] ?? 0, 2); ?>
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Average Bill</h6>
                    <h3 class="mb-0 text-info">
                        $<?php echo number_format($stats['avg_bill_amount'] ?? 0, 2); ?>
                    </h3>
                    <small class="text-muted">
                        Min: $<?php echo number_format($stats['min_bill'] ?? 0, 2); ?> |
                        Max: $<?php echo number_format($stats['max_bill'] ?? 0, 2); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Revenue (GROUP BY) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar3 me-2"></i>Monthly Revenue (GROUP BY Month)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($monthly_data)): ?>
                        <p class="text-muted">No revenue data available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Month</th>
                                        <th class="text-end">Bills</th>
                                        <th class="text-end">Revenue</th>
                                        <th class="text-end">Average Bill</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthly_data as $month): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($month['month_name']); ?></strong></td>
                                            <td class="text-end"><?php echo $month['bill_count']; ?></td>
                                            <td class="text-end">
                                                <strong class="text-success">
                                                    $<?php echo number_format($month['revenue'], 2); ?>
                                                </strong>
                                            </td>
                                            <td class="text-end">
                                                $<?php echo number_format($month['avg_bill'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-end">
                                            <?php echo array_sum(array_column($monthly_data, 'bill_count')); ?>
                                        </th>
                                        <th class="text-end">
                                            <strong class="text-primary">
                                                $<?php echo number_format(array_sum(array_column($monthly_data, 'revenue')), 2); ?>
                                            </strong>
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Payment Methods (GROUP BY) -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-credit-card me-2"></i>Revenue by Payment Method
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payment_methods)): ?>
                        <p class="text-muted">No payment data available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th class="text-end">Transactions</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-end">Avg</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payment_methods as $method): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($method['method']); ?></td>
                                            <td class="text-end"><?php echo $method['transaction_count']; ?></td>
                                            <td class="text-end">
                                                $<?php echo number_format($method['total_revenue'], 2); ?>
                                            </td>
                                            <td class="text-end">
                                                $<?php echo number_format($method['avg_amount'], 2); ?>
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

        <!-- Top Customers (GROUP BY with HAVING) -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-star me-2"></i>Top Customers (HAVING clause)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_customers)): ?>
                        <p class="text-muted">No customer data available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th class="text-end">Bills</th>
                                        <th class="text-end">Total Spent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_customers as $index => $customer): ?>
                                        <tr>
                                            <td>
                                                <?php if ($index < 3): ?>
                                                    <i class="bi bi-trophy-fill text-warning me-1"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($customer['name']); ?>
                                            </td>
                                            <td class="text-end"><?php echo $customer['bill_count']; ?></td>
                                            <td class="text-end">
                                                <strong>$<?php echo number_format($customer['total_spent'], 2); ?></strong>
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

    <!-- Unpaid Bills by Customer (GROUP BY with HAVING) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>Customers with Unpaid Bills (HAVING total >= $10)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($unpaid_customers)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            No significant unpaid bills found! All customers are up to date.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th class="text-end">Unpaid Bills</th>
                                        <th class="text-end">Total Unpaid</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unpaid_customers as $customer): ?>
                                        <tr>
                                            <td><?php echo $customer['customer_id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($customer['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                            <td class="text-end">
                                                <span class="badge bg-warning"><?php echo $customer['unpaid_count']; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-danger">
                                                    $<?php echo number_format($customer['total_unpaid'], 2); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-envelope"></i> Email
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4">Total</th>
                                        <th class="text-end">
                                            <?php echo array_sum(array_column($unpaid_customers, 'unpaid_count')); ?>
                                        </th>
                                        <th class="text-end">
                                            <strong class="text-danger">
                                                $<?php echo number_format(array_sum(array_column($unpaid_customers, 'total_unpaid')), 2); ?>
                                            </strong>
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

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
                        <div class="col-md-4">
                            <h6 class="text-primary">Aggregate Functions</h6>
                            <ul class="small">
                                <li><code>SUM()</code> - Total revenue</li>
                                <li><code>AVG()</code> - Average bill amount</li>
                                <li><code>MIN()</code> - Minimum bill</li>
                                <li><code>MAX()</code> - Maximum bill</li>
                                <li><code>COUNT()</code> - Number of bills</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-success">GROUP BY</h6>
                            <ul class="small">
                                <li>Revenue by month</li>
                                <li>Revenue by payment method</li>
                                <li>Top customers by spending</li>
                                <li>Unpaid bills by customer</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-warning">HAVING Clause</h6>
                            <ul class="small">
                                <li>Filter customers with total_spent > 0</li>
                                <li>Filter customers with unpaid >= $10</li>
                                <li>Post-aggregation filtering</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
