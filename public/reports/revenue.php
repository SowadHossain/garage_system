<?php
session_start();
require_once '../../config/db.php';

// Admin only
if (!isset($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'admin') {
    header('Location: ../staff_login.php');
    exit;
}

$page_title = 'Revenue Reports';
require_once '../../includes/header.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ✅ Schema: bills.total + bills.created_at
$stats = $conn->query("
    SELECT
        COUNT(*) as total_bills,
        COALESCE(SUM(total),0) as total_revenue,
        COALESCE(AVG(total),0) as avg_bill_amount,
        COALESCE(MIN(total),0) as min_bill,
        COALESCE(MAX(total),0) as max_bill,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END),0) as paid_revenue,
        COALESCE(SUM(CASE WHEN payment_status = 'unpaid' THEN total ELSE 0 END),0) as unpaid_revenue
    FROM bills
")->fetch_assoc();

$monthly_data = $conn->query("
    SELECT
        YEAR(created_at) as year,
        MONTH(created_at) as month,
        DATE_FORMAT(created_at, '%Y-%m') as month_key,
        DATE_FORMAT(created_at, '%b %Y') as month_name,
        COUNT(*) as bill_count,
        COALESCE(SUM(total),0) as revenue,
        COALESCE(AVG(total),0) as avg_bill
    FROM bills
    GROUP BY YEAR(created_at), MONTH(created_at), DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b %Y')
    ORDER BY year DESC, month DESC
    LIMIT 12
")->fetch_all(MYSQLI_ASSOC);

$payment_methods = $conn->query("
    SELECT
        COALESCE(NULLIF(payment_method,''), 'Not Specified') as method,
        COUNT(*) as transaction_count,
        COALESCE(SUM(total),0) as total_revenue,
        COALESCE(AVG(total),0) as avg_amount
    FROM bills
    WHERE payment_status = 'paid'
    GROUP BY method
    ORDER BY total_revenue DESC
")->fetch_all(MYSQLI_ASSOC);

$top_customers = $conn->query("
    SELECT
        c.customer_id,
        c.name,
        COUNT(DISTINCT b.bill_id) as bill_count,
        COALESCE(SUM(b.total),0) as total_spent
    FROM customers c
    JOIN appointments a ON c.customer_id = a.customer_id
    JOIN jobs j ON a.appointment_id = j.appointment_id
    JOIN bills b ON j.job_id = b.job_id
    WHERE b.payment_status='paid'
    GROUP BY c.customer_id, c.name
    HAVING COALESCE(SUM(b.total),0) > 0
    ORDER BY total_spent DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>

<style>
    /* Make it sit nicer with the admin portal look */
    :root{
        --admin-primary:#0d6efd;
        --admin-bg:#f0f4ff;
    }
    body { background: var(--admin-bg); }
    .page-wrap { max-width: 1400px; margin: 0 auto; }
    .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); border: 0; }
    .card-header { border-top-left-radius: 12px; border-top-right-radius: 12px; }
    .kpi-card { border-left: 4px solid var(--admin-primary); }
    .kpi-title { color:#6b7280; font-size:.9rem; }
    .kpi-value { font-weight:800; letter-spacing:-.02em; }
    .soft-muted { color:#6b7280; }
</style>

<div class="container-fluid py-4 page-wrap">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../admin_portal/admin_dashboard.php">Admin Dashboard</a></li>
            <li class="breadcrumb-item active">Revenue Reports</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1"><i class="bi bi-graph-up me-2"></i>Revenue Reports</h2>
            <div class="soft-muted">Revenue overview, trends, and payment breakdown.</div>
        </div>
        <a class="btn btn-outline-primary" href="../admin_portal/admin_dashboard.php">
            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card kpi-card">
                <div class="card-body">
                    <div class="kpi-title">Total Revenue</div>
                    <div class="kpi-value fs-3 text-primary">৳<?php echo number_format((float)($stats['total_revenue'] ?? 0), 2); ?></div>
                    <div class="soft-muted small"><?php echo (int)($stats['total_bills'] ?? 0); ?> bills</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card" style="border-left-color:#198754;">
                <div class="card-body">
                    <div class="kpi-title">Paid Revenue</div>
                    <div class="kpi-value fs-3 text-success">৳<?php echo number_format((float)($stats['paid_revenue'] ?? 0), 2); ?></div>
                    <div class="soft-muted small">Collected</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card" style="border-left-color:#fd7e14;">
                <div class="card-body">
                    <div class="kpi-title">Unpaid Revenue</div>
                    <div class="kpi-value fs-3 text-warning">৳<?php echo number_format((float)($stats['unpaid_revenue'] ?? 0), 2); ?></div>
                    <div class="soft-muted small">Outstanding</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card" style="border-left-color:#0dcaf0;">
                <div class="card-body">
                    <div class="kpi-title">Average Bill</div>
                    <div class="kpi-value fs-3 text-info">৳<?php echo number_format((float)($stats['avg_bill_amount'] ?? 0), 2); ?></div>
                    <div class="soft-muted small">
                        Min: ৳<?php echo number_format((float)($stats['min_bill'] ?? 0), 2); ?> |
                        Max: ৳<?php echo number_format((float)($stats['max_bill'] ?? 0), 2); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Revenue -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Monthly Revenue</h5>
                <span class="small opacity-75">Last 12 months</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($monthly_data)): ?>
                <p class="text-muted mb-0">No revenue data available.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Month</th>
                            <th class="text-end">Bills</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Average Bill</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($monthly_data as $m): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo h($m['month_name']); ?></td>
                                <td class="text-end"><?php echo (int)$m['bill_count']; ?></td>
                                <td class="text-end text-success fw-semibold">৳<?php echo number_format((float)$m['revenue'], 2); ?></td>
                                <td class="text-end">৳<?php echo number_format((float)$m['avg_bill'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                        <tr>
                            <th>Total</th>
                            <th class="text-end"><?php echo array_sum(array_map('intval', array_column($monthly_data, 'bill_count'))); ?></th>
                            <th class="text-end fw-bold text-primary">
                                ৳<?php echo number_format(array_sum(array_map('floatval', array_column($monthly_data, 'revenue'))), 2); ?>
                            </th>
                            <th></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Split row -->
    <div class="row g-4 mb-2">
        <!-- Payment Methods -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Revenue by Payment Method</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payment_methods)): ?>
                        <p class="text-muted mb-0">No paid transactions available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Method</th>
                                    <th class="text-end">Transactions</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Avg</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($payment_methods as $p): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo h($p['method']); ?></td>
                                        <td class="text-end"><?php echo (int)$p['transaction_count']; ?></td>
                                        <td class="text-end">৳<?php echo number_format((float)$p['total_revenue'], 2); ?></td>
                                        <td class="text-end text-muted">৳<?php echo number_format((float)$p['avg_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Top Customers</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_customers)): ?>
                        <p class="text-muted mb-0">No paid revenue customer data available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-end">Bills</th>
                                    <th class="text-end">Total Spent</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($top_customers as $i => $c): ?>
                                    <tr>
                                        <td class="fw-semibold">
                                            <?php if ($i < 3): ?><i class="bi bi-trophy-fill text-warning me-1"></i><?php endif; ?>
                                            <?php echo h($c['name']); ?>
                                        </td>
                                        <td class="text-end"><?php echo (int)$c['bill_count']; ?></td>
                                        <td class="text-end fw-bold">৳<?php echo number_format((float)$c['total_spent'], 2); ?></td>
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

</div>

<?php require_once '../../includes/footer.php'; ?>
