<?php
session_start();
require_once '../../config/db.php';

// Admin only
if (!isset($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'admin') {
    header('Location: ../staff_login.php');
    exit;
}

$page_title = 'Customer Analytics';
require_once '../../includes/header.php';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| CUSTOMER SUMMARY (VIEW if exists, fallback if not)
|--------------------------------------------------------------------------
*/
$customer_summary = [];
$view_ok = false;

// Check if view exists safely
$view_check = $conn->query("
    SELECT 1
    FROM information_schema.VIEWS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'view_customer_summary'
    LIMIT 1
");

if ($view_check && $view_check->num_rows > 0) {
    // Use VIEW
    $view_ok = true;
    $res = $conn->query("
        SELECT *
        FROM view_customer_summary
        ORDER BY total_spent DESC
        LIMIT 20
    ");
    if ($res instanceof mysqli_result) {
        $customer_summary = $res->fetch_all(MYSQLI_ASSOC);
    }
} else {
    // Fallback query (matches your actual schema)
    $fallback = "
        SELECT
            c.customer_id,
            c.name,
            c.email,
            c.phone,

            COUNT(DISTINCT v.vehicle_id) AS vehicle_count,
            COUNT(DISTINCT a.appointment_id) AS appointment_count,
            SUM(a.status = 'completed') AS completed_appointments,

            COALESCE(SUM(CASE WHEN b.payment_status='paid' THEN b.total ELSE 0 END),0) AS total_spent,
            AVG(CASE WHEN b.payment_status='paid' THEN b.total ELSE NULL END) AS avg_bill_amount,

            MAX(a.requested_date) AS last_appointment_date
        FROM customers c
        LEFT JOIN vehicles v ON v.customer_id = c.customer_id
        LEFT JOIN appointments a ON a.customer_id = c.customer_id
        LEFT JOIN jobs j ON j.appointment_id = a.appointment_id
        LEFT JOIN bills b ON b.job_id = j.job_id
        GROUP BY c.customer_id, c.name, c.email, c.phone
        ORDER BY total_spent DESC
        LIMIT 20
    ";
    $res = $conn->query($fallback);
    if ($res instanceof mysqli_result) {
        $customer_summary = $res->fetch_all(MYSQLI_ASSOC);
    }
}

/*
|--------------------------------------------------------------------------
| KPI STATS
|--------------------------------------------------------------------------
*/
$total_customers = (int)$conn->query("SELECT COUNT(*) c FROM customers")->fetch_assoc()['c'];
$customers_with_vehicles = (int)$conn->query("SELECT COUNT(DISTINCT customer_id) c FROM vehicles")->fetch_assoc()['c'];
$customers_with_appointments = (int)$conn->query("SELECT COUNT(DISTINCT customer_id) c FROM appointments")->fetch_assoc()['c'];

/*
|--------------------------------------------------------------------------
| CUSTOMERS WITH UNPAID BILLS
|--------------------------------------------------------------------------
*/
$unpaid_customers = [];
$res = $conn->query("
    SELECT
        c.customer_id,
        c.name,
        c.email,
        c.phone,
        COUNT(DISTINCT a.appointment_id) AS appointment_count
    FROM customers c
    JOIN appointments a ON a.customer_id = c.customer_id
    JOIN jobs j ON j.appointment_id = a.appointment_id
    JOIN bills b ON b.job_id = j.job_id
    WHERE b.payment_status = 'unpaid'
    GROUP BY c.customer_id, c.name, c.email, c.phone
    ORDER BY c.name
");
if ($res instanceof mysqli_result) {
    $unpaid_customers = $res->fetch_all(MYSQLI_ASSOC);
}

/*
|--------------------------------------------------------------------------
| CUSTOMERS WITHOUT VEHICLES
|--------------------------------------------------------------------------
*/
$no_vehicle_customers = [];
$res = $conn->query("
    SELECT
        c.customer_id,
        c.name,
        c.email,
        c.phone,
        c.created_at
    FROM customers c
    LEFT JOIN vehicles v ON v.customer_id = c.customer_id
    WHERE v.vehicle_id IS NULL
    ORDER BY c.created_at DESC
");
if ($res instanceof mysqli_result) {
    $no_vehicle_customers = $res->fetch_all(MYSQLI_ASSOC);
}

/*
|--------------------------------------------------------------------------
| DISTINCT VEHICLE MAKES
|--------------------------------------------------------------------------
*/
$vehicle_makes = [];
$res = $conn->query("
    SELECT DISTINCT make
    FROM vehicles
    WHERE make IS NOT NULL AND make <> ''
    ORDER BY make
");
if ($res instanceof mysqli_result) {
    while ($r = $res->fetch_assoc()) {
        $vehicle_makes[] = $r['make'];
    }
}

/*
|--------------------------------------------------------------------------
| LOYAL CUSTOMERS (COMPLETED APPOINTMENTS)
|--------------------------------------------------------------------------
*/
$loyal_customers = [];
$res = $conn->query("
    SELECT
        c.customer_id,
        c.name,
        c.email,
        COUNT(*) AS completed_count
    FROM customers c
    JOIN appointments a ON a.customer_id = c.customer_id
    WHERE a.status = 'completed'
    GROUP BY c.customer_id, c.name, c.email
    ORDER BY completed_count DESC
    LIMIT 10
");
if ($res instanceof mysqli_result) {
    $loyal_customers = $res->fetch_all(MYSQLI_ASSOC);
}
?>

<style>
    body { background:#f0f4ff; }
    .page-wrap { max-width:1400px; margin:auto; }
    .card { border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.06); border:0; }
    .kpi { border-left:4px solid #0d6efd; }
</style>

<div class="container-fluid py-4 page-wrap">

    <nav class="breadcrumb mb-3">
        <a class="breadcrumb-item" href="../admin_portal/admin_dashboard.php">Admin Dashboard</a>
        <span class="breadcrumb-item active">Customer Analytics</span>
    </nav>

    <h2 class="mb-1"><i class="bi bi-people me-2"></i>Customer Analytics</h2>
    <p class="text-muted mb-4">Customer activity, billing, and engagement overview.</p>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card kpi"><div class="card-body"><small>Total Customers</small><h3><?= $total_customers ?></h3></div></div></div>
        <div class="col-md-3"><div class="card kpi" style="border-left-color:#198754;"><div class="card-body"><small>With Vehicles</small><h3 class="text-success"><?= $customers_with_vehicles ?></h3></div></div></div>
        <div class="col-md-3"><div class="card kpi" style="border-left-color:#0dcaf0;"><div class="card-body"><small>With Appointments</small><h3 class="text-info"><?= $customers_with_appointments ?></h3></div></div></div>
        <div class="col-md-3"><div class="card kpi" style="border-left-color:#fd7e14;"><div class="card-body"><small>Vehicle Makes</small><h3 class="text-warning"><?= count($vehicle_makes) ?></h3></div></div></div>
    </div>

    <!-- Customer Summary -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between">
            <span>Customer Summary</span>
            <small><?= $view_ok ? 'View-backed' : 'Computed' ?></small>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th class="text-end">Vehicles</th>
                    <th class="text-end">Appointments</th>
                    <th class="text-end">Completed</th>
                    <th class="text-end">Total Spent</th>
                    <th>Last Visit</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($customer_summary as $c): ?>
                    <tr>
                        <td><?= h($c['name']) ?></td>
                        <td class="small"><?= h($c['email']) ?><br><?= h($c['phone']) ?></td>
                        <td class="text-end"><?= (int)$c['vehicle_count'] ?></td>
                        <td class="text-end"><?= (int)$c['appointment_count'] ?></td>
                        <td class="text-end"><?= (int)$c['completed_appointments'] ?></td>
                        <td class="text-end text-success fw-semibold">৳<?= number_format($c['total_spent'],2) ?></td>
                        <td><?= $c['last_appointment_date'] ? date('M d, Y', strtotime($c['last_appointment_date'])) : 'Never' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once '../../includes/footer.php'; ?>
