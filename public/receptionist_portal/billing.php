<?php
require_once __DIR__ . "/_guard.php";

$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];
$params = [];
$types = "";

if ($status !== '') { $where[] = "b.payment_status = ?"; $types.="s"; $params[]=$status; }
if ($q !== '') {
    $where[] = "(b.bill_no LIKE ? OR c.name LIKE ? OR v.plate_no LIKE ?)";
    $types.="sss";
    $like="%".$q."%";
    $params[]=$like; $params[]=$like; $params[]=$like;
}

$sql = "
    SELECT
        b.bill_id, b.bill_no, b.total, b.payment_status, b.payment_method, b.paid_at,
        c.name AS customer_name, v.plate_no,
        a.appointment_id, a.requested_date,
        s1.name AS created_by_name,
        s2.name AS paid_by_name
    FROM bills b
    JOIN jobs j ON b.job_id=j.job_id
    JOIN appointments a ON j.appointment_id=a.appointment_id
    JOIN customers c ON a.customer_id=c.customer_id
    JOIN vehicles v ON a.vehicle_id=v.vehicle_id
    LEFT JOIN staff s1 ON b.created_by_staff_id=s1.staff_id
    LEFT JOIN staff s2 ON b.paid_by_staff_id=s2.staff_id
";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY b.created_at DESC LIMIT 200";

$stmt = $conn->prepare($sql);
if ($types !== "") $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

ui_header("Billing", $staff_name);
?>
<div class="card-soft">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0"><i class="bi bi-receipt"></i>Billing</h2>
        <a class="btn btn-outline-success" href="receptionist_dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
    </div>

    <form class="row g-2 mb-3" method="get">
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">All</option>
                <option value="unpaid" <?php echo ($status==='unpaid')?'selected':''; ?>>Unpaid</option>
                <option value="paid" <?php echo ($status==='paid')?'selected':''; ?>>Paid</option>
            </select>
        </div>
        <div class="col-md-7">
            <input class="form-control" name="q" placeholder="Search bill no / customer / plate" value="<?php echo h($q); ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-outline-success"><i class="bi bi-funnel me-1"></i>Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Bill</th>
                <th>Customer</th>
                <th>Plate</th>
                <th>Total</th>
                <th>Status</th>
                <th>Paid</th>
                <th style="width: 220px;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7" class="text-muted">No bills found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="fw-semibold"><?php echo h($r['bill_no']); ?></td>
                    <td><?php echo h($r['customer_name']); ?></td>
                    <td><?php echo h($r['plate_no']); ?></td>
                    <td>৳ <?php echo number_format((float)$r['total'], 2); ?></td>
                    <td>
                        <span class="badge <?php echo h($r['payment_status']==='paid'?'success':'warning'); ?>">
                            <?php echo h(strtoupper($r['payment_status'])); ?>
                        </span>
                    </td>
                    <td class="text-muted small">
                        <?php if ($r['payment_status']==='paid'): ?>
                            <?php echo h($r['payment_method'] ?? ''); ?><br>
                            <?php echo h($r['paid_at'] ?? ''); ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-outline-success" href="view_bill.php?bill_id=<?php echo (int)$r['bill_id']; ?>">
                            <i class="bi bi-eye me-1"></i>View
                        </a>
                        <?php if ($r['payment_status'] !== 'paid'): ?>
                            <a class="btn btn-sm btn-sd" href="mark_paid.php?bill_id=<?php echo (int)$r['bill_id']; ?>">
                                <i class="bi bi-cash-coin me-1"></i>Mark Paid
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php ui_footer(); ?>
