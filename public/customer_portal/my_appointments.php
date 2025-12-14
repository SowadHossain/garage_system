<?php
// public/customer_portal/my_appointments.php

session_start();
require_once __DIR__ . "/../../config/db.php";

if (empty($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit;
}

$customer_id = (int)$_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'] ?? 'Customer';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function getTableColumns(mysqli $conn, string $table): array {
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($res) { while ($r = $res->fetch_assoc()) $cols[] = $r['Field']; $res->free(); }
    return $cols;
}
function detectColumn(array $cols, array $candidates): string {
    foreach ($candidates as $c) if (in_array($c, $cols, true)) return $c;
    return '';
}

/* Detect columns */
$apptCols = getTableColumns($conn, "appointments");
$vehCols  = getTableColumns($conn, "vehicles");

$apptStatusCol = detectColumn($apptCols, ['status','appointment_status']);
$apptDateCol = detectColumn($apptCols, ['requested_date','appointment_date','date','appt_date']);
$apptSlotCol = detectColumn($apptCols, ['requested_slot','slot','time_slot']);
$apptDatetimeCol = detectColumn($apptCols, ['appointment_datetime','scheduled_at','schedule_datetime','date_time','start_time','created_at']);
$apptProblemCol = detectColumn($apptCols, ['problem_text','problem_description','description','issue','notes','complaint']);

$regCol = detectColumn($vehCols, ['registration_no','registration_number','plate_no','license_plate','reg_no','plate']);
$brandCol = detectColumn($vehCols, ['brand','make']);
$modelCol = detectColumn($vehCols, ['model']);

/* Handle cancel request (only if status column exists) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id']) && $apptStatusCol) {
    $cancel_id = (int)$_POST['cancel_id'];

    // Only allow cancel if belongs to customer and status is requested/booked
    $sql = "UPDATE appointments
            SET `$apptStatusCol` = 'cancelled'
            WHERE appointment_id = ? AND customer_id = ?
              AND `$apptStatusCol` IN ('requested','booked','pending')";
    $st = $conn->prepare($sql);
    if ($st) {
        $st->bind_param("ii", $cancel_id, $customer_id);
        $st->execute();
        $st->close();
    }
    header("Location: my_appointments.php?cancelled=1");
    exit;
}

/* Build safe selects */
$selReg   = $regCol   ? "v.`$regCol` AS registration_no," : "NULL AS registration_no,";
$selBrand = $brandCol ? "v.`$brandCol` AS brand,"         : "NULL AS brand,";
$selModel = $modelCol ? "v.`$modelCol` AS model,"         : "NULL AS model,";

$selStatus = $apptStatusCol ? "a.`$apptStatusCol` AS status," : "NULL AS status,";
$selProblem = $apptProblemCol ? "a.`$apptProblemCol` AS problem_description," : "NULL AS problem_description,";

// Date display: prefer requested_date, else datetime
$selDate = $apptDateCol ? "a.`$apptDateCol` AS appt_date," : "NULL AS appt_date,";
$selDt   = $apptDatetimeCol ? "a.`$apptDatetimeCol` AS appointment_datetime," : "NULL AS appointment_datetime,";
$selSlot = $apptSlotCol ? "a.`$apptSlotCol` AS requested_slot," : "NULL AS requested_slot,";

$orderBy = $apptDatetimeCol ? "a.`$apptDatetimeCol` DESC" : ($apptDateCol ? "a.`$apptDateCol` DESC" : "a.appointment_id DESC");

$sql = "
    SELECT
        a.appointment_id,
        $selStatus
        $selProblem
        $selDate
        $selDt
        $selSlot
        $selReg
        $selBrand
        $selModel
        1 AS _dummy
    FROM appointments a
    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE a.customer_id = ?
    ORDER BY $orderBy
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function badgeClass($status): string {
    $s = strtolower((string)$status);
    return match($s) {
        'completed' => 'success',
        'booked' => 'primary',
        'requested','pending' => 'warning',
        'cancelled' => 'secondary',
        'in_progress' => 'info',
        default => 'warning'
    };
}

function displayDate(array $a): string {
    $d = $a['appt_date'] ?? '';
    if ($d) return date('M d, Y', strtotime($d));
    $dt = $a['appointment_datetime'] ?? '';
    if ($dt) return date('M d, Y', strtotime($dt));
    return '-';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Appointments - Screw Dheela</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white border-bottom sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold text-success" href="customer_dashboard.php">
      <i class="bi bi-gear-wide-connected me-2"></i>Screw Dheela
    </a>
    <div class="d-flex align-items-center gap-2">
      <span class="text-muted small d-none d-md-inline"><?php echo h($customer_name); ?></span>
      <a class="btn btn-sm btn-outline-danger" href="customer_logout.php"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h3 class="mb-1"><i class="bi bi-calendar3 me-2"></i>My Appointments</h3>
      <div class="text-muted">All your appointment requests and history.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-success" href="book_appointment.php"><i class="bi bi-plus-circle me-1"></i>New</a>
      <a class="btn btn-outline-secondary" href="customer_dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
  </div>

  <?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Appointment request submitted.</div>
  <?php endif; ?>
  <?php if (isset($_GET['cancelled'])): ?>
    <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Appointment cancelled (if it was eligible).</div>
  <?php endif; ?>

  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <?php if (empty($appointments)): ?>
        <div class="p-4 text-center text-muted">
          <div class="display-6 mb-2"><i class="bi bi-calendar-x"></i></div>
          No appointments yet.
          <div class="mt-3">
            <a class="btn btn-success" href="book_appointment.php">Book your first appointment</a>
          </div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Date</th>
                <th>Vehicle</th>
                <th>Problem</th>
                <th>Status</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $a): ?>
              <?php
                $veh = trim(($a['brand'] ?? '') . ' ' . ($a['model'] ?? ''));
                $plate = $a['registration_no'] ?? '';
                $vehTxt = trim($veh . ($plate ? " • ".$plate : ""));
                $status = $a['status'] ?? '';
                $canCancel = $apptStatusCol && in_array(strtolower((string)$status), ['requested','booked','pending'], true);
              ?>
              <tr>
                <td><?php echo h(displayDate($a)); ?><?php if (!empty($a['requested_slot'])): ?><div class="small text-muted">Slot <?php echo (int)$a['requested_slot']; ?></div><?php endif; ?></td>
                <td><?php echo $vehTxt ? h($vehTxt) : '<span class="text-muted">-</span>'; ?></td>
                <td><?php echo !empty($a['problem_description']) ? h($a['problem_description']) : '<span class="text-muted">-</span>'; ?></td>
                <td>
                  <span class="badge bg-<?php echo h(badgeClass($status)); ?>">
                    <?php echo h(ucfirst(str_replace('_',' ', (string)$status))); ?>
                  </span>
                </td>
                <td class="text-end">
                  <?php if ($canCancel): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="cancel_id" value="<?php echo (int)$a['appointment_id']; ?>">
                      <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this appointment?');">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
