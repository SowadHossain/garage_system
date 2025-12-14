<?php
session_start();
require_once '../../config/db.php';

// Admin only
if (!isset($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'admin') {
    header('Location: ../staff_login.php');
    exit;
}

$page_title = 'Service Performance';
require_once '../../includes/header.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// 1) Service stats (schema-correct)
$service_stats = $conn->query("
    SELECT
        s.service_id,
        s.name,
        s.base_price,
        s.is_active,
        COUNT(js.job_service_id) AS times_used,
        COALESCE(SUM(js.qty), 0) AS total_qty,
        COALESCE(SUM(js.line_total), 0) AS total_revenue,
        COALESCE(AVG(NULLIF(js.unit_price,0)), 0) AS avg_price
    FROM services s
    LEFT JOIN job_services js ON s.service_id = js.service_id
    GROUP BY s.service_id, s.name, s.base_price, s.is_active
    ORDER BY total_revenue DESC, times_used DESC
")->fetch_all(MYSQLI_ASSOC);

// 2) Top by usage
$top_by_usage = $conn->query("
    SELECT
        s.service_id,
        s.name,
        COUNT(js.job_service_id) AS usage_count,
        COALESCE(SUM(js.qty), 0) AS total_qty,
        COALESCE(SUM(js.line_total), 0) AS revenue
    FROM services s
    JOIN job_services js ON s.service_id = js.service_id
    GROUP BY s.service_id, s.name
    ORDER BY usage_count DESC, revenue DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// 3) Top by revenue
$top_by_revenue = $conn->query("
    SELECT
        s.service_id,
        s.name,
        COUNT(js.job_service_id) AS usage_count,
        COALESCE(SUM(js.qty), 0) AS total_qty,
        COALESCE(SUM(js.line_total), 0) AS revenue
    FROM services s
    JOIN job_services js ON s.service_id = js.service_id
    GROUP BY s.service_id, s.name
    ORDER BY revenue DESC, usage_count DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// 4) Unused services
$unused_services = $conn->query("
    SELECT
        s.service_id,
        s.name,
        s.base_price,
        s.is_active,
        s.created_at
    FROM services s
    LEFT JOIN job_services js ON s.service_id = js.service_id
    WHERE js.job_service_id IS NULL
    ORDER BY s.name
")->fetch_all(MYSQLI_ASSOC);

// KPIs
$total_services = count($service_stats);
$total_used = count(array_filter($service_stats, fn($s) => (int)$s['times_used'] > 0));
$total_unused = count($unused_services);
$total_revenue_all = array_sum(array_map('floatval', array_column($service_stats, 'total_revenue')));
?>

<style>
    :root{
        --admin-bg:#f0f4ff;
        --admin-primary:#0d6efd;
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
            <li class="breadcrumb-item active">Service Performance</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1"><i class="bi bi-tools me-2"></i>Service Performance</h2>
            <div class="soft-muted">Usage and revenue across services.</div>
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
                    <div class="kpi-title">Total Services</div>
                    <div class="kpi-value fs-3 text-primary"><?php echo (int)$total_services; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card" style="border-left-color:#198754;">
                <div class="card-body">
                    <div class="kpi-title">Services Used</div>
                    <div class="kpi-value fs-3 text-success"><?php echo (int)$total_used; ?></div>
                    <div class="soft-muted small">Used at least once</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card" style="border-left-color:#fd7e14;">
                <div class="card-body">
                    <div class="kpi-title">Unused Services</div>
                    <div class="kpi-value fs-3 text-warning"><?php echo (int)$total_unused; ?></div>
                    <div class="soft-muted small">Never used</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card" style="border-left-color:#0dcaf0;">
                <div class="card-body">
                    <div class="kpi-title">Total Service Revenue</div>
                    <div class="kpi-value fs-3 text-info">৳<?php echo number_format((float)$total_revenue_all, 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top tables -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Top Services by Usage</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_by_usage)): ?>
                        <p class="text-muted mb-0">No service usage data yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Service</th>
                                    <th class="text-end">Times</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($top_by_usage as $i => $svc): ?>
                                    <tr>
                                        <td class="fw-semibold">
                                            <?php if ($i < 3): ?><i class="bi bi-trophy-fill text-warning me-1"></i><?php endif; ?>
                                            <?php echo h($svc['name']); ?>
                                        </td>
                                        <td class="text-end"><span class="badge bg-primary"><?php echo (int)$svc['usage_count']; ?></span></td>
                                        <td class="text-end"><?php echo (int)$svc['total_qty']; ?></td>
                                        <td class="text-end fw-bold">৳<?php echo number_format((float)$svc['revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Top Services by Revenue</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_by_revenue)): ?>
                        <p class="text-muted mb-0">No revenue data yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Service</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-end">Times</th>
                                    <th class="text-end">Qty</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($top_by_revenue as $i => $svc): ?>
                                    <tr>
                                        <td class="fw-semibold">
                                            <?php if ($i < 3): ?><i class="bi bi-gem text-warning me-1"></i><?php endif; ?>
                                            <?php echo h($svc['name']); ?>
                                        </td>
                                        <td class="text-end fw-bold text-success">৳<?php echo number_format((float)$svc['revenue'], 2); ?></td>
                                        <td class="text-end"><?php echo (int)$svc['usage_count']; ?></td>
                                        <td class="text-end"><?php echo (int)$svc['total_qty']; ?></td>
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

    <!-- All services -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>All Services</h5>
        </div>
        <div class="card-body">
            <?php if (empty($service_stats)): ?>
                <p class="text-muted mb-0">No services found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Service</th>
                            <th class="text-end">Base Price</th>
                            <th class="text-end">Times Used</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Avg Unit Price</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($service_stats as $s): ?>
                            <?php
                                $times = (int)$s['times_used'];
                                $active = (int)$s['is_active'] === 1;
                                $useBadge = $times > 0 ? 'bg-success' : 'bg-secondary';
                                $tag = $times > 5 ? ['Popular','success'] : ($times > 0 ? ['Active','info'] : ['Unused','warning']);
                            ?>
                            <tr>
                                <td class="fw-semibold"><?php echo h($s['name']); ?></td>
                                <td class="text-end">৳<?php echo number_format((float)$s['base_price'], 2); ?></td>
                                <td class="text-end"><span class="badge <?php echo $useBadge; ?>"><?php echo $times; ?></span></td>
                                <td class="text-end"><?php echo (int)($s['total_qty'] ?? 0); ?></td>
                                <td class="text-end fw-semibold <?php echo ((float)$s['total_revenue'] > 0) ? 'text-success' : 'text-muted'; ?>">
                                    ৳<?php echo number_format((float)($s['total_revenue'] ?? 0), 2); ?>
                                </td>
                                <td class="text-end">
                                    <?php if ((float)$s['avg_price'] > 0): ?>
                                        ৳<?php echo number_format((float)$s['avg_price'], 2); ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $tag[1]; ?>"><?php echo $tag[0]; ?></span>
                                    <?php if (!$active): ?>
                                        <span class="badge bg-secondary ms-1">Disabled</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Unused services -->
    <?php if (!empty($unused_services)): ?>
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Unused Services</h5>
            </div>
            <div class="card-body">
                <div class="soft-muted mb-3">These services haven’t been used in any job yet.</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Service</th>
                            <th class="text-end">Base Price</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($unused_services as $u): ?>
                            <tr>
                                <td><?php echo (int)$u['service_id']; ?></td>
                                <td class="fw-semibold"><?php echo h($u['name']); ?></td>
                                <td class="text-end">৳<?php echo number_format((float)$u['base_price'], 2); ?></td>
                                <td>
                                    <?php if ((int)$u['is_active'] === 1): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?php echo !empty($u['created_at']) ? date('M d, Y', strtotime($u['created_at'])) : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../../includes/footer.php'; ?>
