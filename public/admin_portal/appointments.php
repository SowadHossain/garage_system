<?php
require_once __DIR__ . "/_guard.php";

$status = trim($_GET['status'] ?? '');
$allowed = ['requested','booked','in_progress','completed','cancelled'];

$where = "1=1";
$params = [];
$types = "";

if ($status !== '' && in_array($status, $allowed, true)) {
    $where .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql = "
    SELECT
        a.appointment_id,
        a.status,
        a.requested_date,
        a.requested_slot,
        a.problem_text,
        c.name AS customer_name,
        c.phone,
        v.plate_no,
        v.make,
        v.model,
        s.name AS mechanic_name
    FROM appointments a
    JOIN customers c ON c.customer_id = a.customer_id
    LEFT JOIN vehicles v ON v.vehicle_id = a.vehicle_id
    LEFT JOIN staff s ON s.staff_id = a.mechanic_id
    WHERE $where
    ORDER BY a.created_at DESC
    LIMIT 200
";

if ($types !== "") {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $appointments = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

ui_header_admin("Appointments", $staff_name);
?>

<div class="dashboard-header">
    <h1 class="dashboard-title"><i class="bi bi-calendar-check me-2"></i>Appointments</h1>
    <p class="dashboard-subtitle">Monitor all appointments by status.</p>
</div>

<div class="data-card mb-3">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-9">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                <?php foreach (['requested','booked','in_progress','completed','cancelled'] as $st): ?>
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
    <h2 class="section-title"><i class="bi bi-clock-history"></i>Latest Appointments</h2>

    <?php if (empty($appointments)): ?>
        <p class="text-muted mb-0">No appointments found.</p>
    <?php else: ?>
        <?php foreach ($appointments as $a): ?>
            <div class="list-item">
                <div class="item-header">
                    <div>
                        <div class="item-title"><?php echo h($a['customer_name']); ?></div>
                        <div class="item-details">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?php echo date('M d, Y', strtotime($a['requested_date'])); ?>
                            (Slot <?php echo (int)$a['requested_slot']; ?>)
                            <br>
                            <?php if (!empty($a['plate_no'])): ?>
                                <i class="bi bi-car-front me-1"></i>
                                <?php echo h(trim(($a['make'] ?? '') . ' ' . ($a['model'] ?? ''))); ?> — <?php echo h($a['plate_no']); ?><br>
                            <?php endif; ?>
                            <i class="bi bi-person-wrench me-1"></i>
                            Mechanic: <?php echo h($a['mechanic_name'] ?? 'Unassigned'); ?>
                            <?php if (!empty($a['phone'])): ?>
                                • <i class="bi bi-telephone me-1"></i><?php echo h($a['phone']); ?>
                            <?php endif; ?>
                            <?php if (!empty($a['problem_text'])): ?>
                                <br><i class="bi bi-chat-square-text me-1"></i><?php echo h(mb_strimwidth($a['problem_text'], 0, 120, '...')); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <span class="badge <?php echo h(statusBadgeClass($a['status'])); ?>">
                        <?php echo h(ucfirst(str_replace('_',' ', (string)$a['status']))); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php ui_footer_admin(); ?>
