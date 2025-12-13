<?php
session_start();
require_once '../../config/db.php';

// Check if user is staff (admin)
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'admin') {
    header('Location: ../staff_login.php');
    exit;
}

$page_title = 'Service Performance Reports';
require_once '../../includes/header.php';

// Demonstrates: GROUP BY, COUNT, aggregates

// 1. Service Popularity and Revenue
$service_stats_query = "SELECT 
                            s.service_id,
                            s.name,
                            s.category,
                            s.base_price,
                            COUNT(js.job_service_id) as times_used,
                            SUM(js.quantity) as total_quantity,
                            SUM(js.quantity * js.unit_price) as total_revenue,
                            AVG(js.unit_price) as avg_price
                        FROM services s
                        LEFT JOIN job_services js ON s.service_id = js.service_id
                        GROUP BY s.service_id, s.name, s.category, s.base_price
                        ORDER BY times_used DESC, total_revenue DESC";
$service_stats_result = $conn->query($service_stats_query);
$service_stats = [];
while ($row = $service_stats_result->fetch_assoc()) {
    $service_stats[] = $row;
}

// 2. Revenue by Service Category (GROUP BY category)
$category_revenue_query = "SELECT 
                                s.category,
                                COUNT(DISTINCT s.service_id) as service_count,
                                COUNT(js.job_service_id) as usage_count,
                                SUM(js.quantity * js.unit_price) as total_revenue,
                                AVG(js.unit_price) as avg_price
                            FROM services s
                            LEFT JOIN job_services js ON s.service_id = js.service_id
                            GROUP BY s.category
                            ORDER BY total_revenue DESC";
$category_revenue_result = $conn->query($category_revenue_query);
$category_data = [];
while ($row = $category_revenue_result->fetch_assoc()) {
    $category_data[] = $row;
}

// 3. Most Popular Services (used more than once - demonstrates HAVING)
$popular_services_query = "SELECT 
                                s.service_id,
                                s.name,
                                s.category,
                                COUNT(js.job_service_id) as usage_count,
                                SUM(js.quantity * js.unit_price) as revenue
                            FROM services s
                            JOIN job_services js ON s.service_id = js.service_id
                            GROUP BY s.service_id, s.name, s.category
                            HAVING COUNT(js.job_service_id) >= 1
                            ORDER BY usage_count DESC
                            LIMIT 10";
$popular_services_result = $conn->query($popular_services_query);
$popular_services = [];
while ($row = $popular_services_result->fetch_assoc()) {
    $popular_services[] = $row;
}

// 4. Unused Services (demonstrates LEFT JOIN with IS NULL check)
$unused_services_query = "SELECT 
                            s.service_id,
                            s.name,
                            s.category,
                            s.base_price,
                            s.description
                        FROM services s
                        LEFT JOIN job_services js ON s.service_id = js.service_id
                        WHERE js.job_service_id IS NULL
                        ORDER BY s.category, s.name";
$unused_services_result = $conn->query($unused_services_query);
$unused_services = [];
while ($row = $unused_services_result->fetch_assoc()) {
    $unused_services[] = $row;
}

// 5. Overall stats
$total_services = count($service_stats);
$total_used = count(array_filter($service_stats, function($s) { return $s['times_used'] > 0; }));
$total_unused = count($unused_services);
$total_revenue_all = array_sum(array_column($service_stats, 'total_revenue'));
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../admin_dashboard.php">Admin Dashboard</a></li>
            <li class="breadcrumb-item active">Service Performance</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-tools me-2"></i>Service Performance Reports
            </h2>
            <p class="text-muted">Analysis of service usage and revenue</p>
        </div>
    </div>

    <!-- Overall Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Services</h6>
                    <h3 class="mb-0 text-primary"><?php echo $total_services; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Services Used</h6>
                    <h3 class="mb-0 text-success"><?php echo $total_used; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Unused Services</h6>
                    <h3 class="mb-0 text-warning"><?php echo $total_unused; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Total Revenue</h6>
                    <h3 class="mb-0 text-info">$<?php echo number_format($total_revenue_all, 2); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue by Category (GROUP BY) -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-grid me-2"></i>Revenue by Category (GROUP BY)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($category_data)): ?>
                        <p class="text-muted">No category data available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Services</th>
                                        <th class="text-end">Usage</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category_data as $cat): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($cat['category'] ?? 'Uncategorized'); ?></strong></td>
                                            <td class="text-end"><?php echo $cat['service_count']; ?></td>
                                            <td class="text-end"><?php echo $cat['usage_count'] ?? 0; ?></td>
                                            <td class="text-end">
                                                <strong class="text-success">
                                                    $<?php echo number_format($cat['total_revenue'] ?? 0, 2); ?>
                                                </strong>
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

        <!-- Most Popular Services (HAVING) -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-star me-2"></i>Top Services (HAVING usage >= 1)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($popular_services)): ?>
                        <p class="text-muted">No service usage data available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th class="text-end">Times Used</th>
                                        <th class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($popular_services as $index => $service): ?>
                                        <tr>
                                            <td>
                                                <?php if ($index < 3): ?>
                                                    <i class="bi bi-trophy-fill text-warning me-1"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($service['name']); ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($service['category']); ?></small>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-primary"><?php echo $service['usage_count']; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <strong>$<?php echo number_format($service['revenue'], 2); ?></strong>
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

    <!-- All Services Detail -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-list-check me-2"></i>All Services Performance
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Service Name</th>
                                    <th>Category</th>
                                    <th class="text-end">Base Price</th>
                                    <th class="text-end">Times Used</th>
                                    <th class="text-end">Total Quantity</th>
                                    <th class="text-end">Total Revenue</th>
                                    <th class="text-end">Avg Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($service_stats as $service): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($service['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($service['category'] ?? 'N/A'); ?></td>
                                        <td class="text-end">
                                            $<?php echo number_format($service['base_price'], 2); ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($service['times_used'] > 0): ?>
                                                <span class="badge bg-success"><?php echo $service['times_used']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?php echo $service['total_quantity'] ?? 0; ?></td>
                                        <td class="text-end">
                                            <strong class="<?php echo $service['total_revenue'] > 0 ? 'text-success' : 'text-muted'; ?>">
                                                $<?php echo number_format($service['total_revenue'] ?? 0, 2); ?>
                                            </strong>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($service['avg_price']): ?>
                                                $<?php echo number_format($service['avg_price'], 2); ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($service['times_used'] > 5): ?>
                                                <span class="badge bg-success">Popular</span>
                                            <?php elseif ($service['times_used'] > 0): ?>
                                                <span class="badge bg-info">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Unused</span>
                                            <?php endif; ?>
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

    <!-- Unused Services (IS NULL check) -->
    <?php if (!empty($unused_services)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>Unused Services (IS NULL Check)
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            These services have never been used in any job. Consider promotional pricing or removing them.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Service ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th class="text-end">Base Price</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unused_services as $service): ?>
                                        <tr>
                                            <td><?php echo $service['service_id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($service['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($service['category'] ?? 'N/A'); ?></td>
                                            <td class="text-end">
                                                $<?php echo number_format($service['base_price'], 2); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($service['description'] ?? 'No description'); ?></td>
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
                        <div class="col-md-4">
                            <h6 class="text-primary">Aggregate Functions</h6>
                            <ul class="small">
                                <li><code>COUNT()</code> - Service usage count</li>
                                <li><code>SUM()</code> - Total revenue per service</li>
                                <li><code>AVG()</code> - Average service price</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-success">GROUP BY</h6>
                            <ul class="small">
                                <li>Service statistics by service_id</li>
                                <li>Revenue by service category</li>
                                <li>Popular services with usage counts</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-warning">Special Queries</h6>
                            <ul class="small">
                                <li><code>IS NULL</code> - Find unused services</li>
                                <li><code>LEFT JOIN</code> - Include all services</li>
                                <li><code>HAVING</code> - Filter by usage count</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
