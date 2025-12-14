<?php
// public/customer_portal/book_appointment.php

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
    if ($res) {
        while ($row = $res->fetch_assoc()) $cols[] = $row['Field'];
        $res->free();
    }
    return $cols;
}

function detectColumn(array $cols, array $candidates): string {
    foreach ($candidates as $c) if (in_array($c, $cols, true)) return $c;
    return '';
}

$errors = [];

/* --- Detect schema --- */
$apptCols = getTableColumns($conn, "appointments");
$vehCols  = getTableColumns($conn, "vehicles");

$apptStatusCol    = detectColumn($apptCols, ['status','appointment_status']);
$apptVehicleCol   = in_array('vehicle_id', $apptCols, true) ? 'vehicle_id' : '';
$apptDateCol      = detectColumn($apptCols, ['requested_date','appointment_date','date','appt_date']);
$apptSlotCol      = detectColumn($apptCols, ['requested_slot','slot','time_slot']);
$apptDatetimeCol  = detectColumn($apptCols, ['appointment_datetime','scheduled_at','schedule_datetime','date_time','start_time']);
$apptProblemCol   = detectColumn($apptCols, ['problem_text','problem_description','description','issue','notes','complaint']);
$apptCreatedAtCol = detectColumn($apptCols, ['created_at','requested_at']); // we will NOT insert it (DB default)

$regCol   = detectColumn($vehCols, ['registration_no','registration_number','plate_no','license_plate','reg_no','plate']);
$brandCol = detectColumn($vehCols, ['brand','make']);
$modelCol = detectColumn($vehCols, ['model']);
$yearCol  = detectColumn($vehCols, ['year','model_year','vehicle_year']);

/* --- Load vehicles for dropdown --- */
$vehicles = [];
$vehicles_stmt = $conn->prepare("SELECT * FROM vehicles WHERE customer_id = ? ORDER BY vehicle_id DESC");
if ($vehicles_stmt) {
    $vehicles_stmt->bind_param("i", $customer_id);
    $vehicles_stmt->execute();
    $vehicles = $vehicles_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $vehicles_stmt->close();
}

/* --- Form defaults --- */
$form = [
    'vehicle_id' => '',
    'date'       => '',
    'slot'       => '1',
    'problem'    => ''
];

// Slot -> time mapping (only used if your schema stores datetime)
$slotTimes = [
    1 => "09:00:00",
    2 => "10:00:00",
    3 => "11:00:00",
    4 => "12:00:00",
    5 => "14:00:00",
    6 => "15:00:00",
    7 => "16:00:00",
    8 => "17:00:00",
];

/**
 * Decide allowed slot range.
 * Your init.sql appointments.requested_slot has CHECK (requested_slot BETWEEN 1 AND 4).
 * So if the detected slot column is exactly "requested_slot", we only offer 1..4.
 * Otherwise (other schemas), keep 1..8.
 */
$maxSlots = 8;
if ($apptSlotCol === 'requested_slot') $maxSlots = 4;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['vehicle_id'] = trim($_POST['vehicle_id'] ?? '');
    $form['date']       = trim($_POST['date'] ?? '');
    $form['slot']       = trim($_POST['slot'] ?? '1');
    $form['problem']    = trim($_POST['problem'] ?? '');

    // Validate vehicle (only if appointments table has vehicle_id)
    if ($apptVehicleCol) {
        if ($form['vehicle_id'] === '') {
            $errors[] = "Please select a vehicle.";
        } else {
            $vid = (int)$form['vehicle_id'];
            $chk = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE vehicle_id = ? AND customer_id = ? LIMIT 1");
            if (!$chk) {
                $errors[] = "DB error: " . $conn->error;
            } else {
                $chk->bind_param("ii", $vid, $customer_id);
                $chk->execute();
                $ok = $chk->get_result()->num_rows > 0;
                $chk->close();
                if (!$ok) $errors[] = "Invalid vehicle selection.";
            }
        }
    }

    // Validate date
    if ($form['date'] === '') {
        $errors[] = "Please pick a date.";
    } else {
        $ts = strtotime($form['date']);
        if ($ts === false) {
            $errors[] = "Invalid date.";
        }
    }

    // Validate slot if used
    $slotInt = (int)$form['slot'];
    if ($apptSlotCol) {
        if ($slotInt < 1 || $slotInt > $maxSlots) {
            $errors[] = "Invalid time slot.";
        }
    }

    // Validate problem if column exists
    if ($apptProblemCol && $form['problem'] === '') {
        $errors[] = "Please describe the problem briefly.";
    }

    if (empty($errors)) {
        // Build dynamic INSERT based on available columns
        $cols  = [];
        $types = "";
        $vals  = [];

        // Always: customer_id
        $cols[]  = "customer_id";
        $types  .= "i";
        $vals[]  = $customer_id;

        if ($apptVehicleCol) {
            $cols[]  = "vehicle_id";
            $types  .= "i";
            $vals[]  = (int)$form['vehicle_id'];
        }

        // Prefer date column if present, else datetime column
        if ($apptDateCol) {
            $cols[]  = "`$apptDateCol`";
            $types  .= "s";
            $vals[]  = $form['date'];
        } elseif ($apptDatetimeCol) {
            $time = $slotTimes[$slotInt] ?? "09:00:00";
            $cols[]  = "`$apptDatetimeCol`";
            $types  .= "s";
            $vals[]  = $form['date'] . " " . $time;
        }

        if ($apptSlotCol) {
            $cols[]  = "`$apptSlotCol`";
            $types  .= "i";
            $vals[]  = $slotInt;
        }

        if ($apptProblemCol) {
            $cols[]  = "`$apptProblemCol`";
            $types  .= "s";
            $vals[]  = $form['problem'];
        }

        if ($apptStatusCol) {
            $cols[]  = "`$apptStatusCol`";
            $types  .= "s";
            $vals[]  = "requested";
        }

        /**
         * IMPORTANT FIX:
         * Do NOT include created_at / requested_at in the INSERT.
         * Your schema already defaults created_at (CURRENT_TIMESTAMP).
         * This avoids placeholder/type mismatch and your original array_pop($types) bug.
         */

        $placeholders = array_fill(0, count($cols), "?");

        $sql = "INSERT INTO appointments (" . implode(",", $cols) . ")
                VALUES (" . implode(",", $placeholders) . ")";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = "DB error: " . $conn->error;
        } else {
            if ($types !== "") {
                $stmt->bind_param($types, ...$vals);
            }
            if ($stmt->execute()) {
                header("Location: my_appointments.php?created=1");
                exit;
            } else {
                $errors[] = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// build vehicle label helper
function vehicleLabel(array $v, string $brandCol, string $modelCol, string $yearCol, string $regCol): string {
    $b = $brandCol && isset($v[$brandCol]) ? $v[$brandCol] : ($v['brand'] ?? '');
    $m = $modelCol && isset($v[$modelCol]) ? $v[$modelCol] : ($v['model'] ?? '');
    $y = $yearCol  && isset($v[$yearCol])  ? $v[$yearCol]  : ($v['year'] ?? '');
    $r = $regCol   && isset($v[$regCol])   ? $v[$regCol]   : ($v['registration_no'] ?? '');
    $title = trim($b . " " . $m);
    $bits = [];
    if ($y !== '') $bits[] = $y;
    if ($r !== '') $bits[] = $r;
    return $title . (empty($bits) ? "" : " (" . implode(" • ", $bits) . ")");
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Book Appointment - Screw Dheela</title>
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
      <h3 class="mb-1"><i class="bi bi-calendar-plus me-2"></i>Book Appointment</h3>
      <div class="text-muted">Pick a date, choose your vehicle, and tell us what’s wrong.</div>
    </div>
    <a class="btn btn-outline-secondary" href="customer_dashboard.php">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle me-2"></i>Please fix these:</div>
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm border-0">
    <div class="card-body p-4">
      <form method="post" novalidate>
        <?php if ($apptVehicleCol): ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">Vehicle</label>
            <select name="vehicle_id" class="form-select" required>
              <option value="">-- Select Vehicle --</option>
              <?php foreach ($vehicles as $v): ?>
                <option value="<?php echo (int)$v['vehicle_id']; ?>"
                  <?php echo ((string)$form['vehicle_id'] === (string)$v['vehicle_id']) ? 'selected' : ''; ?>>
                  <?php echo h(vehicleLabel($v, $brandCol, $modelCol, $yearCol, $regCol)); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($vehicles)): ?>
              <div class="form-text text-danger">
                You don’t have any vehicles yet. Add one first (My Vehicles).
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Date</label>
            <input type="date" name="date" class="form-control" value="<?php echo h($form['date']); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Time Slot</label>
            <select name="slot" class="form-select" <?php echo $apptSlotCol ? 'required' : ''; ?>>
              <?php for ($i=1; $i <= $maxSlots; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo ((int)$form['slot'] === $i) ? 'selected' : ''; ?>>
                  Slot <?php echo $i; ?>
                </option>
              <?php endfor; ?>
            </select>
            <?php if ($apptSlotCol === 'requested_slot'): ?>
              <div class="form-text">Your DB supports slots 1 to 4.</div>
            <?php else: ?>
              <div class="form-text">Slot numbers are used in your DB schema. We keep it simple.</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="mt-3">
          <label class="form-label fw-semibold">Problem Description</label>
          <textarea name="problem" class="form-control" rows="4" placeholder="Describe the issue..."><?php echo h($form['problem']); ?></textarea>
        </div>

        <div class="d-flex gap-2 mt-4">
          <button class="btn btn-success">
            <i class="bi bi-check-circle me-1"></i>Submit Request
          </button>
          <a class="btn btn-outline-secondary" href="my_appointments.php">View My Appointments</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
