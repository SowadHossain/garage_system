<?php
require_once __DIR__ . "/_guard.php";

$customers = $conn->query("SELECT customer_id, name, phone FROM customers ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

$customer_id = (int)($_GET['customer_id'] ?? ($_POST['customer_id'] ?? 0));
$vehicles = [];
if ($customer_id > 0) {
    $stmt = $conn->prepare("SELECT vehicle_id, plate_no, make, model FROM vehicles WHERE customer_id=? ORDER BY plate_no ASC");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $vehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$err = "";
$ok = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
    $requested_date = trim($_POST['requested_date'] ?? '');
    $requested_slot = (int)($_POST['requested_slot'] ?? 0);
    $problem_text = trim($_POST['problem_text'] ?? '');

    if ($customer_id <= 0 || $vehicle_id <= 0 || $requested_date === '' || $requested_slot < 1 || $requested_slot > 4 || $problem_text === '') {
        $err = "Please fill all required fields (slot must be 1 to 4).";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO appointments (customer_id, vehicle_id, requested_date, requested_slot, problem_text, status)
            VALUES (?,?,?,?,?,'requested')
        ");
        $stmt->bind_param("iisis", $customer_id, $vehicle_id, $requested_date, $requested_slot, $problem_text);

        if ($stmt->execute()) {
            $ok = "Appointment created as REQUESTED. Now assign mechanic from Appointments page.";
        } else {
            $err = "Failed to create appointment. Try again.";
        }
        $stmt->close();
    }
}

ui_header("Book Appointment", $staff_name);
?>
<div class="card-soft">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0"><i class="bi bi-calendar-plus"></i>Book Appointment</h2>
        <a class="btn btn-outline-success" href="appointments.php"><i class="bi bi-calendar-event me-1"></i>Appointments</a>
    </div>

    <?php if ($err): ?><div class="alert alert-danger"><?php echo h($err); ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert alert-success"><?php echo h($ok); ?></div><?php endif; ?>

    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Customer *</label>
            <select class="form-select" name="customer_id" required onchange="location='book_appointment.php?customer_id='+this.value">
                <option value="">Select customer</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?php echo (int)$c['customer_id']; ?>" <?php echo ($customer_id === (int)$c['customer_id']) ? 'selected' : ''; ?>>
                        <?php echo h($c['name']); ?> (<?php echo h($c['phone'] ?? ''); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Pick a customer to load their vehicles.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Vehicle *</label>
            <select class="form-select" name="vehicle_id" required <?php echo $customer_id > 0 ? '' : 'disabled'; ?>>
                <option value="">Select vehicle</option>
                <?php foreach ($vehicles as $v): ?>
                    <option value="<?php echo (int)$v['vehicle_id']; ?>" <?php echo ((int)($_POST['vehicle_id'] ?? 0) === (int)$v['vehicle_id']) ? 'selected' : ''; ?>>
                        <?php echo h($v['plate_no']); ?> (<?php echo h(trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? ''))); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Requested Date *</label>
            <input class="form-control" type="date" name="requested_date" required value="<?php echo h($_POST['requested_date'] ?? date('Y-m-d')); ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Slot (1-4) *</label>
            <select class="form-select" name="requested_slot" required>
                <?php for ($i=1;$i<=4;$i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ((int)($_POST['requested_slot'] ?? 1) === $i) ? 'selected' : ''; ?>>Slot <?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Problem / Notes *</label>
            <textarea class="form-control" name="problem_text" rows="4" required><?php echo h($_POST['problem_text'] ?? ''); ?></textarea>
        </div>

        <div class="col-12">
            <button class="btn btn-sd"><i class="bi bi-check2-circle me-1"></i>Create Request</button>
            <a class="btn btn-light" href="receptionist_dashboard.php">Back</a>
        </div>
    </form>
</div>
<?php ui_footer(); ?>
