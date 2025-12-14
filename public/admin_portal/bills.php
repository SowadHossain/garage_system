<?php
require_once __DIR__ . "/_guard.php";

$pay = trim($_GET['payment'] ?? '');
$allowed = ['paid','unpaid'];

$where = "1=1";
$params = [];
$types = "";

if ($pay !== '' && in_array($pay, $allowed, true)) {
    $where .= " AND b.payment_status = ?";
    $params[] = $pay;
    $types .= "s";
}

$sql = "
    SELECT
        b.bill_id,
        b.bill_no,
        b.total,
        b.payment_status,
        b.created_at,
        c.name AS customer_name
    FROM bills b
    JOIN jobs j ON j.job_id = b.job_id
    JOIN appointments a ON a.appointment_id = j.appointment_id
    JOIN customers c ON c.customer_id = a.customer_id
    WHERE $where
    ORDER BY b.created_at DESC
    LIMIT 200
";

if ($types !== "") {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $bills = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

ui_header_admin("Bills", $staff_name);
?>

<div class="dashboard-header">
    <h1 class="dashboard-title"><i class="bi bi-receipt me-2"></i>Bills</h1>
    <p class="dashboard-subtitle">Track billing totals and payment status.</p>
</div>

<div class="data-card mb-3">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-9">
            <select name="payment" class="form-select">
                <option value="">All payments</option>
                <option value="paid" <?php echo ($pay==='paid'?'selected':''); ?>>Paid</option>
                <option value="unpaid" <?php echo ($pay==='unpaid'?'selected':''); ?>>Unpaid</option>
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
        </div>
    </form>
</div>

<div class="data-card">
    <h2 class="section-title"><i class="bi bi-cash-stack"></i>Latest Bills</h2>

    <?php if (empty($bills)): ?>
        <p class="text-muted mb-0">No bills found.</p>
    <?php else: ?>
        <?php foreach ($bills as $b): ?>
            <div class="list-item">
                <div class="item-header">
                    <div>
                        <div class="item-title"><?php echo h($b['bill_no']); ?></div>
                        <div class="item-details">
                            <i class="bi bi-person me-1"></i><?php echo h($b['customer_name']); ?><br>
                            <i class="bi bi-currency-exchange me-1"></i>
                            ৳<?php echo number_format((float)$b['total'], 2); ?>
                        </div>
                    </div>

                    <span class="badge <?php echo ($b['payment_status']==='paid' ? 'success' : 'danger'); ?>">
                        <?php echo h(ucfirst($b['payment_status'])); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php ui_footer_admin(); ?>
