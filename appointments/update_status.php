<?php
// appointments/update_status.php - Update appointment status (DB compatible)
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/role_check.php';

requireRole(['admin', 'receptionist', 'mechanic']);

$appointment_id = (int)($_GET['appointment_id'] ?? $_POST['appointment_id'] ?? 0);
if ($appointment_id <= 0) {
    header("Location: /garage_system/public/appointments/list.php");
    exit;
}

$role = $_SESSION['staff_role'] ?? '';
$staff_id = (int)($_SESSION['staff_id'] ?? 0);

$stmt = $conn->prepare("
    SELECT a.appointment_id, a.status, a.requested_date, a.requested_slot, a.problem_text,
           a.mechanic_id,
           c.name AS customer_name, c.phone,
           v.plate_no, v.make, v.model,
           s.name AS mechanic_name
    FROM appointments a
    JOIN customers c ON c.customer_id = a.customer_id
    LEFT JOIN vehicles v ON v.vehicle_id = a.vehicle_id
    LEFT JOIN staff s ON s.staff_id = a.mechanic_id
    WHERE a.appointment_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$appt = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appt) {
    header("Location: /garage_system/public/appointments/list.php");
    exit;
}

// Mechanics: only update if this appointment is assigned to them
if ($role === 'mechanic' && (int)($appt['mechanic_id'] ?? 0) !== $staff_id) {
    header("Location: /garage_system/public/access_denied.php");
    exit;
}

$allowed_statuses = ['requested','booked','in_progress','completed','cancelled'];

// Mechanics: only allowed to set in_progress/completed (and maybe cancelled if you want)
if ($role === 'mechanic') {
    $allowed_statuses = ['in_progress','completed'];
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = trim($_POST['status'] ?? '');

    if (!in_array($new_status, $allowed_statuses, true)) {
        $error = "Invalid status.";
    } else {
        // Receptionist/Admin should not set booked without mechanic assigned
        if (($role === 'admin' || $role === 'receptionist') && $new_status === 'booked' && empty($appt['mechanic_id'])) {
            $error = "This appointment has no mechanic. Use the booking/assign page first.";
        } else {
            $u = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
            $u->bind_param("si", $new_status, $appointment_id);

            if ($u->execute()) {
                header("Location: /garage_system/public/appointments/view_appointments.php?appointment_id=" . $appointment_id);
                exit;
            } else {
                $error = "Update failed: " . $u->error;
            }
            $u->close();
        }
    }
}

$page_title = "Update Appointment Status";
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Update Status</h3>
    <a class="btn btn-outline-secondary" href="/garage_system/public/appointments/view_appointments.php?appointment_id=<?= (int)$appointment_id ?>">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <div class="fw-semibold">Customer:</div>
            <div><?= htmlspecialchars($appt['customer_name']) ?> (<?= htmlspecialchars($appt['phone'] ?? '') ?>)</div>
        </div>

        <div class="mb-3">
            <div class="fw-semibold">Appointment:</div>
            <div><?= htmlspecialchars($appt['requested_date']) ?> (Slot <?= (int)$appt['requested_slot'] ?>)</div>
        </div>

        <div class="mb-3">
            <div class="fw-semibold">Vehicle:</div>
            <div>
                <?php if (!empty($appt['plate_no'])): ?>
                    <?= htmlspecialchars($appt['plate_no']) ?> - <?= htmlspecialchars(trim(($appt['make'] ?? '') . ' ' . ($appt['model'] ?? ''))) ?>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-3">
            <div class="fw-semibold">Mechanic:</div>
            <div><?= htmlspecialchars($appt['mechanic_name'] ?? '—') ?></div>
        </div>

        <form method="post" action="">
            <input type="hidden" name="appointment_id" value="<?= (int)$appointment_id ?>">

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <?php foreach ($allowed_statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($appt['status'] === $s) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst(str_replace('_',' ', $s))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (($role === 'admin' || $role === 'receptionist') && empty($appt['mechanic_id'])): ?>
                    <div class="form-text text-muted">
                        Note: to set <b>Booked</b>, assign a mechanic first.
                    </div>
                <?php endif; ?>
            </div>

            <button class="btn btn-primary">
                <i class="bi bi-check2-circle me-1"></i>Save
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
