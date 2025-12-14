<?php
require_once __DIR__ . "/_guard.php";

$q = trim($_GET['q'] ?? '');
$like = "%".$q."%";

$customers = $vehicles = $appointments = $bills = [];

if ($q !== '') {
    $stmt = $conn->prepare("SELECT customer_id, name, phone, email FROM customers WHERE name LIKE ? OR phone LIKE ? OR email LIKE ? LIMIT 10");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT v.vehicle_id, v.plate_no, v.make, v.model, c.name AS customer_name
        FROM vehicles v JOIN customers c ON v.customer_id=c.customer_id
        WHERE v.plate_no LIKE ? OR v.make LIKE ? OR v.model LIKE ? OR c.name LIKE ?
        LIMIT 10
    ");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $vehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT a.appointment_id, a.status, a.requested_date, a.requested_slot, c.name AS customer_name, v.plate_no
        FROM appointments a
        JOIN customers c ON a.customer_id=c.customer_id
        JOIN vehicles v ON a.vehicle_id=v.vehicle_id
        WHERE c.name LIKE ? OR v.plate_no LIKE ? OR a.problem_text LIKE ?
        ORDER BY a.requested_date DESC
        LIMIT 10
    ");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT b.bill_id, b.bill_no, b.payment_status, b.total, c.name AS customer_name, v.plate_no
        FROM bills b
        JOIN jobs j ON b.job_id=j.job_id
        JOIN appointments a ON j.appointment_id=a.appointment_id
        JOIN customers c ON a.customer_id=c.customer_id
        JOIN vehicles v ON a.vehicle_id=v.vehicle_id
        WHERE b.bill_no LIKE ? OR c.name LIKE ? OR v.plate_no LIKE ?
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

ui_header("Global Search", $staff_name);
?>
<div class="card-soft">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0"><i class="bi bi-search-heart"></i>Global Search</h2>
        <a class="btn btn-outline-success" href="receptionist_dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
    </div>

    <form class="row g-2 mb-4" method="get">
        <div class="col-md-10">
            <input class="form-control" name="q" placeholder="Type anything: name, phone, plate, bill no..." value="<?php echo h($q); ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-sd"><i class="bi bi-search me-1"></i>Search</button>
        </div>
    </form>

    <?php if ($q === ''): ?>
        <div class="text-muted">Enter a keyword to search customers, vehicles, appointments, and bills.</div>
    <?php else: ?>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="p-3 rounded bg-light">
                    <div class="fw-semibold mb-2"><i class="bi bi-people me-1"></i>Customers</div>
                    <?php if (!$customers): ?><div class="text-muted small">No matches.</div><?php endif; ?>
                    <?php foreach ($customers as $c): ?>
                        <div class="small">
                            <span class="fw-semibold"><?php echo h($c['name']); ?></span>
                            <span class="text-muted">(#<?php echo (int)$c['customer_id']; ?>)</span>
                            <div class="text-muted"><?php echo h($c['phone'] ?? ''); ?> | <?php echo h($c['email'] ?? ''); ?></div>
                        </div>
                        <hr class="my-2">
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="p-3 rounded bg-light">
                    <div class="fw-semibold mb-2"><i class="bi bi-car-front me-1"></i>Vehicles</div>
                    <?php if (!$vehicles): ?><div class="text-muted small">No matches.</div><?php endif; ?>
                    <?php foreach ($vehicles as $v): ?>
                        <div class="small">
                            <span class="fw-semibold"><?php echo h($v['plate_no']); ?></span>
                            <span class="text-muted"><?php echo h(trim(($v['make'] ?? '').' '.($v['model'] ?? ''))); ?></span>
                            <div class="text-muted">Owner: <?php echo h($v['customer_name']); ?></div>
                        </div>
                        <hr class="my-2">
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="p-3 rounded bg-light">
                    <div class="fw-semibold mb-2"><i class="bi bi-calendar-event me-1"></i>Appointments</div>
                    <?php if (!$appointments): ?><div class="text-muted small">No matches.</div><?php endif; ?>
                    <?php foreach ($appointments as $a): ?>
                        <div class="small d-flex justify-content-between">
                            <div>
                                <span class="fw-semibold">#<?php echo (int)$a['appointment_id']; ?></span>
                                <?php echo h($a['customer_name']); ?> (<?php echo h($a['plate_no']); ?>)
                                <div class="text-muted"><?php echo h($a['requested_date']); ?> Slot <?php echo (int)$a['requested_slot']; ?></div>
                            </div>
                            <span class="badge <?php echo h(statusBadgeClass($a['status'])); ?>"><?php echo h(ucfirst(str_replace('_',' ',$a['status']))); ?></span>
                        </div>
                        <hr class="my-2">
                    <?php endforeach; ?>
                    <a class="btn btn-sm btn-outline-success" href="appointments.php?q=<?php echo urlencode($q); ?>"><i class="bi bi-box-arrow-up-right me-1"></i>Open in Appointments</a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="p-3 rounded bg-light">
                    <div class="fw-semibold mb-2"><i class="bi bi-receipt me-1"></i>Bills</div>
                    <?php if (!$bills): ?><div class="text-muted small">No matches.</div><?php endif; ?>
                    <?php foreach ($bills as $b): ?>
                        <div class="small d-flex justify-content-between">
                            <div>
                                <span class="fw-semibold"><?php echo h($b['bill_no']); ?></span>
                                <div class="text-muted"><?php echo h($b['customer_name']); ?> (<?php echo h($b['plate_no']); ?>)</div>
                                <div class="text-muted">৳ <?php echo number_format((float)$b['total'], 2); ?></div>
                            </div>
                            <span class="badge <?php echo h(($b['payment_status']==='paid')?'success':'warning'); ?>"><?php echo h(strtoupper($b['payment_status'])); ?></span>
                        </div>
                        <hr class="my-2">
                    <?php endforeach; ?>
                    <?php if ($bills): ?>
                        <a class="btn btn-sm btn-outline-success" href="view_bill.php?bill_id=<?php echo (int)$bills[0]['bill_id']; ?>"><i class="bi bi-eye me-1"></i>View First Result</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php ui_footer(); ?>
