<?php
// public/appointments/list.php - Appointments List (DB compatible)
session_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/role_check.php';

// Staff only (admin/receptionist/mechanic can view)
requireRole(['admin', 'receptionist', 'mechanic']);

$page_title = "Appointments";

// --- Filters ---
$status = trim($_GET['status'] ?? '');
$date   = trim($_GET['date'] ?? '');
$slot   = trim($_GET['slot'] ?? '');
$q      = trim($_GET['q'] ?? '');

// Build WHERE safely
$where = [];
$params = [];
$types  = '';

if ($status !== '') {
    $where[] = "a.status = ?";
    $types  .= 's';
    $params[] = $status;
}
if ($date !== '') {
    $where[] = "a.requested_date = ?";
    $types  .= 's';
    $params[] = $date;
}
if ($slot !== '' && ctype_digit($slot)) {
    $where[] = "a.requested_slot = ?";
    $types  .= 'i';
    $params[] = (int)$slot;
}
if ($q !== '') {
    $where[] = "(c.name LIKE ? OR c.phone LIKE ? OR v.plate_no LIKE ?)";
    $types  .= 'sss';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
    SELECT
        a.appointment_id,
        a.requested_date,
        a.requested_slot,
        a.problem_text,
        a.status,
        a.created_at,
        c.customer_id,
        c.name AS customer_name,
        c.phone AS customer_phone,
        v.vehicle_id,
        v.plate_no,
        v.make,
        v.model,
        s.name AS mechanic_name
    FROM appointments a
    JOIN customers c ON c.customer_id = a.customer_id
    LEFT JOIN vehicles v ON v.vehicle_id = a.vehicle_id
    LEFT JOIN staff s ON s.staff_id = a.mechanic_id
    $whereSql
    ORDER BY a.requested_date DESC, a.requested_slot DESC, a.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL error: " . htmlspecialchars($conn->error));
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function badgeClass($status) {
    switch ($status) {
        case 'requested':   return 'warning';
        case 'booked':      return 'info';
        case 'in_progress': return 'primary';
        case 'completed':   return 'success';
        case 'cancelled':   return 'danger';
        default:            return 'secondary';
    }
}

function prettyStatus($status) {
    return ucfirst(str_replace('_', ' ', (string)$status));
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">
        <i class="bi bi-calendar-check me-2"></i>Appointments
    </h3>
    <?php if (hasPermission('manage_appointments')): ?>
        <a class="btn btn-primary" href="/garage_system/public/appointments/add.php">
            <i class="bi bi-calendar-plus me-1"></i>New Appointment
        </a>
    <?php endif; ?>
</div>

<form class="card card-body mb-3" method="get" action="">
    <div class="row g-2">
        <div class="col-md-3">
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
        </div>
        <div class="col-md-2">
            <select name="slot" class="form-select">
                <option value="">All Slots</option>
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <option value="<?= $i ?>" <?= ($slot === (string)$i ? 'selected' : '') ?>>Slot <?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <?php
                $statuses = ['requested','booked','in_progress','completed','cancelled'];
                foreach ($statuses as $s):
                ?>
                    <option value="<?= $s ?>" <?= ($status === $s ? 'selected' : '') ?>><?= prettyStatus($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" name="q" class="form-control" placeholder="Search name/phone/plate" value="<?= htmlspecialchars($q) ?>">
        </div>
        <div class="col-md-1 d-grid">
            <button class="btn btn-outline-secondary" type="submit">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Slot</th>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Mechanic</th>
                    <th>Status</th>
                    <th style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">No appointments found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r['appointment_id'] ?></td>
                        <td><?= htmlspecialchars($r['requested_date']) ?></td>
                        <td>Slot <?= (int)$r['requested_slot'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($r['customer_name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($r['customer_phone'] ?? '') ?></div>
                        </td>
                        <td>
                            <?php if (!empty($r['plate_no'])): ?>
                                <div class="fw-semibold"><?= htmlspecialchars($r['plate_no']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars(trim(($r['make'] ?? '') . ' ' . ($r['model'] ?? ''))) ?></div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($r['mechanic_name'] ?? '—') ?></td>
                        <td>
                            <span class="badge bg-<?= badgeClass($r['status']) ?>">
                                <?= htmlspecialchars(prettyStatus($r['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary"
                               href="/garage_system/public/appointments/view_appointments.php?appointment_id=<?= (int)$r['appointment_id'] ?>">
                                <i class="bi bi-eye"></i>
                            </a>

                            <?php if (in_array($_SESSION['staff_role'] ?? '', ['admin','receptionist'], true)): ?>
                                <a class="btn btn-sm btn-outline-success"
                                   href="/garage_system/public/appointments/update_status.php?appointment_id=<?= (int)$r['appointment_id'] ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            <?php endif; ?>

                            <?php if (in_array($_SESSION['staff_role'] ?? '', ['admin','receptionist'], true)): ?>
                                <a class="btn btn-sm btn-outline-secondary"
                                   href="/garage_system/public/appointments/book.php?appointment_id=<?= (int)$r['appointment_id'] ?>">
                                    <i class="bi bi-person-check"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
