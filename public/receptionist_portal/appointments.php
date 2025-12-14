<?php
require_once __DIR__ . "/_guard.php";

$filter_status = trim($_GET['status'] ?? '');
$filter_date = trim($_GET['date'] ?? '');
$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];
$types = "";

if ($filter_status !== '') { $where[] = "a.status = ?"; $types .= "s"; $params[] = $filter_status; }
if ($filter_date !== '')   { $where[] = "a.requested_date = ?"; $types .= "s"; $params[] = $filter_date; }
if ($search !== '') {
    $where[] = "(c.name LIKE ? OR c.phone LIKE ? OR v.plate_no LIKE ?)";
    $types .= "sss";
    $like = "%".$search."%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

$sql = "
    SELECT
        a.appointment_id, a.status, a.requested_date, a.requested_slot, a.problem_text,
        a.mechanic_id, a.receptionist_note,
        c.customer_id, c.name AS customer_name, c.phone,
        v.vehicle_id, v.plate_no, v.make, v.model,
        m.name AS mechanic_name
    FROM appointments a
    JOIN customers c ON a.customer_id=c.customer_id
    JOIN vehicles v ON a.vehicle_id=v.vehicle_id
    LEFT JOIN staff m ON a.mechanic_id=m.staff_id
";

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY a.requested_date DESC, a.requested_slot DESC, a.created_at DESC LIMIT 200";

$stmt = $conn->prepare($sql);
if ($types !== "") $stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$mechanics = $conn->query("
    SELECT s.staff_id, s.name
    FROM staff s
    JOIN roles r ON s.role_id=r.role_id
    WHERE r.role_name='mechanic' AND s.is_active=1
    ORDER BY s.name ASC
")->fetch_all(MYSQLI_ASSOC);

$err = "";
$ok = "";

// Actions: assign / cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);

    if ($appointment_id <= 0) {
        $err = "Invalid appointment.";
    } else if ($action === 'cancel') {
        $stmt = $conn->prepare("UPDATE appointments SET status='cancelled' WHERE appointment_id=?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $stmt->close();
        $ok = "Appointment cancelled.";
    } else if ($action === 'assign') {
        $mechanic_id = (int)($_POST['mechanic_id'] ?? 0);
        $note = trim($_POST['receptionist_note'] ?? '');

        // Fetch appointment day
        $stmt = $conn->prepare("SELECT requested_date, requested_slot, status FROM appointments WHERE appointment_id=?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $appt = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$appt) {
            $err = "Appointment not found.";
        } else if (!in_array($appt['status'], ['requested','booked'], true)) {
            $err = "Only requested/booked appointments can be assigned.";
        } else if ($mechanic_id <= 0) {
            $err = "Select a mechanic.";
        } else {
            $work_date = $appt['requested_date'];

            $conn->begin_transaction();
            try {
                // Ensure schedule exists
                $stmt = $conn->prepare("
                    INSERT INTO mechanic_schedule (mechanic_id, work_date, capacity, reserved_count)
                    VALUES (?, ?, 4, 0)
                    ON DUPLICATE KEY UPDATE capacity=capacity
                ");
                $stmt->bind_param("is", $mechanic_id, $work_date);
                $stmt->execute();
                $stmt->close();

                // Lock schedule row + check capacity
                $stmt = $conn->prepare("
                    SELECT capacity, reserved_count
                    FROM mechanic_schedule
                    WHERE mechanic_id=? AND work_date=?
                    FOR UPDATE
                ");
                $stmt->bind_param("is", $mechanic_id, $work_date);
                $stmt->execute();
                $sched = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$sched) {
                    throw new Exception("Schedule missing.");
                }

                if ((int)$sched['reserved_count'] >= (int)$sched['capacity']) {
                    throw new Exception("Mechanic is full for that day.");
                }

                // Reserve 1 slot
                $stmt = $conn->prepare("
                    UPDATE mechanic_schedule
                    SET reserved_count = reserved_count + 1
                    WHERE mechanic_id=? AND work_date=?
                ");
                $stmt->bind_param("is", $mechanic_id, $work_date);
                $stmt->execute();
                $stmt->close();

                // Assign appointment
                $stmt = $conn->prepare("
                    UPDATE appointments
                    SET status='booked',
                        mechanic_id=?,
                        assigned_by_staff_id=?,
                        assigned_at=NOW(),
                        receptionist_note=?
                    WHERE appointment_id=?
                ");
                $stmt->bind_param("iisi", $mechanic_id, $staff_id, $note, $appointment_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $ok = "Appointment assigned and booked.";
            } catch (Throwable $e) {
                $conn->rollback();
                $err = $e->getMessage() ?: "Failed to assign.";
            }
        }
    }

    // reload list after action
    header("Location: appointments.php?status=" . urlencode($filter_status) . "&date=" . urlencode($filter_date) . "&q=" . urlencode($search) . "&msg=" . urlencode($ok ?: $err));
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] !== '') {
    // show either success or error-ish message without guessing
    $ok = $_GET['msg'];
}

ui_header("Appointments", $staff_name);
?>
<div class="card-soft">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0"><i class="bi bi-calendar-event"></i>Appointments</h2>
        <a class="btn btn-sd" href="book_appointment.php"><i class="bi bi-calendar-plus me-1"></i>New</a>
    </div>

    <?php if ($err): ?><div class="alert alert-danger"><?php echo h($err); ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert alert-success"><?php echo h($ok); ?></div><?php endif; ?>

    <form class="row g-2 mb-3" method="get">
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                <?php foreach (['requested','booked','in_progress','completed','cancelled'] as $st): ?>
                    <option value="<?php echo h($st); ?>" <?php echo ($filter_status===$st)?'selected':''; ?>><?php echo h(ucfirst(str_replace('_',' ',$st))); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input class="form-control" type="date" name="date" value="<?php echo h($filter_date); ?>">
        </div>
        <div class="col-md-4">
            <input class="form-control" name="q" placeholder="Search name/phone/plate" value="<?php echo h($search); ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-outline-success"><i class="bi bi-funnel me-1"></i>Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Vehicle</th>
                <th>Date/Slot</th>
                <th>Status</th>
                <th>Mechanic</th>
                <th style="width: 360px;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7" class="text-muted">No appointments found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>#<?php echo (int)$r['appointment_id']; ?></td>
                    <td>
                        <div class="fw-semibold"><?php echo h($r['customer_name']); ?></div>
                        <div class="text-muted small"><i class="bi bi-telephone me-1"></i><?php echo h($r['phone'] ?? ''); ?></div>
                    </td>
                    <td>
                        <div class="fw-semibold"><?php echo h($r['plate_no']); ?></div>
                        <div class="text-muted small"><?php echo h(trim(($r['make'] ?? '').' '.($r['model'] ?? ''))); ?></div>
                    </td>
                    <td>
                        <div class="fw-semibold"><?php echo h($r['requested_date']); ?></div>
                        <div class="text-muted small">Slot <?php echo (int)$r['requested_slot']; ?></div>
                    </td>
                    <td>
                        <span class="badge <?php echo h(statusBadgeClass($r['status'])); ?>">
                            <?php echo h(ucfirst(str_replace('_',' ',$r['status']))); ?>
                        </span>
                    </td>
                    <td><?php echo h($r['mechanic_name'] ?? '—'); ?></td>
                    <td>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if (in_array($r['status'], ['requested','booked'], true)): ?>
                                <form method="post" class="d-flex gap-2 flex-wrap">
                                    <input type="hidden" name="action" value="assign">
                                    <input type="hidden" name="appointment_id" value="<?php echo (int)$r['appointment_id']; ?>">
                                    <select class="form-select form-select-sm" name="mechanic_id" required style="min-width:160px;">
                                        <option value="">Assign mechanic</option>
                                        <?php foreach ($mechanics as $m): ?>
                                            <option value="<?php echo (int)$m['staff_id']; ?>" <?php echo ((int)$r['mechanic_id'] === (int)$m['staff_id'])?'selected':''; ?>>
                                                <?php echo h($m['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input class="form-control form-control-sm" name="receptionist_note" placeholder="Note (optional)" style="min-width:180px;" value="<?php echo h($r['receptionist_note'] ?? ''); ?>">
                                    <button class="btn btn-sm btn-sd"><i class="bi bi-check2 me-1"></i>Book</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($r['status'] !== 'cancelled' && $r['status'] !== 'completed'): ?>
                                <form method="post" onsubmit="return confirm('Cancel this appointment?');">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="appointment_id" value="<?php echo (int)$r['appointment_id']; ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Cancel</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($r['problem_text'])): ?>
                            <div class="text-muted small mt-1"><i class="bi bi-wrench me-1"></i><?php echo h(mb_strimwidth($r['problem_text'], 0, 90, '...')); ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <a class="btn btn-light" href="receptionist_dashboard.php">Back</a>
    </div>
</div>
<?php ui_footer(); ?>
