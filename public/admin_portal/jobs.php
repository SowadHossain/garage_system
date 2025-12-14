<?php
require_once __DIR__ . "/_guard.php";

$status = trim($_GET['status'] ?? '');
$allowed = ['open','in_progress','completed','cancelled'];

$where = "1=1";
$params = [];
$types = "";

if ($status !== '' && in_array($status, $allowed, true)) {
    $where .= " AND j.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql = "
    SELECT
        j.job_id,
        j.status,
        j.created_at,
        c.name AS customer_name,
        a.appointment_id,
        a.requested_date,
        a.requested_slot,
        s.name AS mechanic_name
    FROM jobs j
    JOIN appointments a ON a.appointment_id = j.appointment_id
    JOIN customers c ON c.customer_id = a.customer_id
    LEFT JOIN staff s ON s.staff_id = j.mechanic_id
    WHERE $where
    ORDER BY j.created_at DESC
    LIMIT 200
";

if ($types !== "") {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $jobs = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

ui_header_admin("Jobs", $staff_name);
?>

<div class="dashboard-header">
    <h1 class="dashboard-title"><i class="bi bi-clipboard-check me-2"></i>Jobs</h1>
    <p class="dashboard-subtitle">Track all jobs across mechanics and appointments.</p>
</div>

<div class="data-card mb-3">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-9">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <?php foreach (['open','in_progress','completed','cancelled'] as $st): ?>
                    <option value="<?php echo h($st); ?>" <?php echo ($status===$st?'selected':''); ?>>
                        <?php echo h(ucfirst(str_replace('_',' ',$st))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
        </div>
    </form>
</div>

<div class="data-card">
    <h2 class="section-title"><i class="bi bi-list-task"></i>Latest Jobs</h2>

    <?php if (empty($jobs)): ?>
        <p class="text-muted mb-0">No jobs found.</p>
    <?php else: ?>
        <?php foreach ($jobs as $j): ?>
            <div class="list-item">
                <div class="item-header">
                    <div>
                        <div class="item-title">Job #<?php echo (int)$j['job_id']; ?> • <?php echo h($j['customer_name']); ?></div>
                        <div class="item-details">
                            <i class="bi bi-calendar3 me-1"></i>
                            Appt #<?php echo (int)$j['appointment_id']; ?> —
                            <?php echo date('M d, Y', strtotime($j['requested_date'])); ?>
                            (Slot <?php echo (int)$j['requested_slot']; ?>)
                            <br>
                            <i class="bi bi-person-wrench me-1"></i>
                            Mechanic: <?php echo h($j['mechanic_name'] ?? 'Unassigned'); ?>
                        </div>
                    </div>

                    <span class="badge <?php echo h(statusBadgeClass($j['status'])); ?>">
                        <?php echo h(ucfirst(str_replace('_',' ', (string)$j['status']))); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php ui_footer_admin(); ?>
