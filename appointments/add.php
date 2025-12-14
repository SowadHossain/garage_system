<?php
// appointments/add.php - Staff creates an appointment request (DB compatible)
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/role_check.php';

requireRole(['admin', 'receptionist']); // staff-only

$page_title = "Add Appointment";

$error = '';
$success = '';

$customers = $conn->query("SELECT customer_id, name, phone FROM customers ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$vehicles  = $conn->query("
    SELECT v.vehicle_id, v.customer_id, v.plate_no, v.make, v.model
    FROM vehicles v
    ORDER BY v.vehicle_id DESC
")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id   = (int)($_POST['customer_id'] ?? 0);
    $vehicle_id_in = trim($_POST['vehicle_id'] ?? '');
    $vehicle_id    = ($vehicle_id_in === '' ? null : (int)$vehicle_id_in);

    $requested_date = trim($_POST['requested_date'] ?? '');
    $requested_slot = (int)($_POST['requested_slot'] ?? 0);
    $problem_text   = trim($_POST['problem_text'] ?? '');

    if ($customer_id <= 0) {
        $error = "Select a customer.";
    } elseif ($requested_date === '') {
        $error = "Select a date.";
    } elseif ($requested_slot < 1 || $requested_slot > 4) {
        $error = "Select a valid slot (1-4).";
    } elseif ($problem_text === '') {
        $error = "Write the problem description.";
    } else {
        // Optional: basic "same customer same date+slot" duplicate block
        $chk = $conn->prepare("
            SELECT COUNT(*) AS c
            FROM appointments
            WHERE customer_id = ?
              AND requested_date = ?
              AND requested_slot = ?
              AND status IN ('requested','booked','in_progress')
        ");
        $chk->bind_param("isi", $customer_id, $requested_date, $requested_slot);
        $chk->execute();
        $exists = (int)$chk->get_result()->fetch_assoc()['c'];
        $chk->close();

        if ($exists > 0) {
            $error = "Customer already has an active appointment for this date/slot.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO appointments (
                    customer_id, vehicle_id,
                    requested_date, requested_slot,
                    problem_text,
                    status,
                    mechanic_id,
                    assigned_by_staff_id, assigned_at,
                    created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, 'requested',
                    NULL,
                    NULL, NULL,
                    NOW()
                )
            ");
            // vehicle_id can be NULL
            $stmt->bind_param("iisiss",
                $customer_id,
                $vehicle_id,
                $requested_date,
                $requested_slot,
                $problem_text,
                $dummy = '' // placeholder (see below)
            );
            // Fix bind types (mysqli doesn't like NULL with "i" sometimes): rebind manually
            $stmt->close();

            $sql = "
                INSERT INTO appointments (
                    customer_id, vehicle_id,
                    requested_date, requested_slot,
                    problem_text,
                    status,
                    mechanic_id,
                    assigned_by_staff_id, assigned_at,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, 'requested', NULL, NULL, NULL, NOW())
            ";
            $stmt2 = $conn->prepare($sql);
            if (!$stmt2) {
                $error = "SQL error: " . $conn->error;
            } else {
                // Use "i" for customer, "i" for vehicle (but allow null by binding as int and setting to null via param)
                // Workaround: bind as string for vehicle_id when null
                if ($vehicle_id === null) {
                    $vehicle_id_str = null;
                    $stmt2->bind_param("issis", $customer_id, $vehicle_id_str, $requested_date, $requested_slot, $problem_text);
                } else {
                    $stmt2->bind_param("iisis", $customer_id, $vehicle_id, $requested_date, $requested_slot, $problem_text);
                }

                if ($stmt2->execute()) {
                    $new_id = $stmt2->insert_id;
                    header("Location: /garage_system/public/appointments/view_appointments.php?appointment_id=" . (int)$new_id);
                    exit;
                } else {
                    $error = "Failed to create appointment: " . $stmt2->error;
                }
                $stmt2->close();
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Add Appointment</h3>
    <a class="btn btn-outline-secondary" href="/garage_system/public/appointments/list.php">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">Select customer</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= (int)$c['customer_id'] ?>"
                                <?= ((int)($_POST['customer_id'] ?? 0) === (int)$c['customer_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['phone'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Vehicle (optional)</label>
                    <select name="vehicle_id" class="form-select">
                        <option value="">No vehicle</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= (int)$v['vehicle_id'] ?>"
                                <?= (($_POST['vehicle_id'] ?? '') !== '' && (int)$_POST['vehicle_id'] === (int)$v['vehicle_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['plate_no'] ?? '') ?> - <?= htmlspecialchars(trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? ''))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="date" name="requested_date" class="form-control"
                           value="<?= htmlspecialchars($_POST['requested_date'] ?? '') ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Slot</label>
                    <select name="requested_slot" class="form-select" required>
                        <option value="">Select slot</option>
                        <?php for ($i=1; $i<=4; $i++): ?>
                            <option value="<?= $i ?>" <?= ((int)($_POST['requested_slot'] ?? 0) === $i) ? 'selected' : '' ?>>
                                Slot <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Problem</label>
                    <textarea name="problem_text" class="form-control" rows="3" required><?= htmlspecialchars($_POST['problem_text'] ?? '') ?></textarea>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Create Request
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
