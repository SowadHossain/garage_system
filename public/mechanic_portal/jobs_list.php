<?php
session_start();
require_once __DIR__ . "/../../config/db.php";

if (empty($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'mechanic') {
    header("Location: staff_login.php");
    exit;
}

$staff_id   = (int)($_SESSION['staff_id'] ?? 0);
$staff_name = $_SESSION['staff_name'] ?? 'Mechanic';

$status = trim($_GET['status'] ?? '');
$q      = trim($_GET['q'] ?? '');

$allowedStatuses = ['', 'open', 'in_progress', 'completed', 'cancelled'];
if (!in_array($status, $allowedStatuses, true)) $status = '';

$flash = "";

/** UI badge */
function badgeClassJob(string $status): string {
    switch ($status) {
        case 'open':        return 'warning';
        case 'in_progress': return 'primary';
        case 'completed':   return 'success';
        case 'cancelled':   return 'danger';
        default:            return 'primary';
    }
}

/**
 * Ensure a job exists for appointment, return job_id.
 */
function ensureJob(mysqli $conn, int $appointment_id, int $mechanic_id): int {
    $get = $conn->prepare("SELECT job_id FROM jobs WHERE appointment_id=? LIMIT 1");
    $get->bind_param("i", $appointment_id);
    $get->execute();
    $row = $get->get_result()->fetch_assoc();
    $get->close();

    if (!empty($row['job_id'])) return (int)$row['job_id'];

    $ins = $conn->prepare("INSERT INTO jobs (appointment_id, mechanic_id, status) VALUES (?, ?, 'open')");
    $ins->bind_param("ii", $appointment_id, $mechanic_id);
    $ins->execute();
    $ins->close();

    $get2 = $conn->prepare("SELECT job_id FROM jobs WHERE appointment_id=? LIMIT 1");
    $get2->bind_param("i", $appointment_id);
    $get2->execute();
    $row2 = $get2->get_result()->fetch_assoc();
    $get2->close();

    return (int)($row2['job_id'] ?? 0);
}

/**
 * Create or update bill for a job and rebuild bill_items from job_services.
 * Returns: [ok(bool), message(string), bill_id(int)]
 */
function generateBillForJob(mysqli $conn, int $job_id, int $created_by_staff_id): array {
    // subtotal from job_services (line_total is generated in your schema)
    $sum = $conn->prepare("SELECT COALESCE(SUM(line_total), 0) AS subtotal FROM job_services WHERE job_id=?");
    $sum->bind_param("i", $job_id);
    $sum->execute();
    $subtotal = (float)($sum->get_result()->fetch_assoc()['subtotal'] ?? 0);
    $sum->close();

    $discount = 0.00;
    $total    = $subtotal - $discount;

    // Make a stable bill number per job
    $bill_no = "BILL-" . date("Ymd") . "-" . $job_id;

    // Create bill or update totals if bill already exists (bills.job_id is UNIQUE in your schema)
    $ins = $conn->prepare("
        INSERT INTO bills (job_id, bill_no, subtotal, discount, total, payment_status, created_by_staff_id)
        VALUES (?, ?, ?, ?, ?, 'unpaid', ?)
        ON DUPLICATE KEY UPDATE
            subtotal = VALUES(subtotal),
            discount = VALUES(discount),
            total    = VALUES(total)
    ");
    $ins->bind_param("isdddi", $job_id, $bill_no, $subtotal, $discount, $total, $created_by_staff_id);
    if (!$ins->execute()) {
        $msg = "Bill create/update failed: " . $ins->error;
        $ins->close();
        return [false, $msg, 0];
    }
    $ins->close();

    // Fetch bill_id
    $get = $conn->prepare("SELECT bill_id FROM bills WHERE job_id=? LIMIT 1");
    $get->bind_param("i", $job_id);
    $get->execute();
    $bill_id = (int)($get->get_result()->fetch_assoc()['bill_id'] ?? 0);
    $get->close();

    if ($bill_id <= 0) return [false, "Bill row not found after insert.", 0];

    // Rebuild bill_items from job_services
    $del = $conn->prepare("DELETE FROM bill_items WHERE bill_id=?");
    $del->bind_param("i", $bill_id);
    if (!$del->execute()) {
        $msg = "Bill items delete failed: " . $del->error;
        $del->close();
        return [false, $msg, $bill_id];
    }
    $del->close();

    // Copy: description = service name
    $copy = $conn->prepare("
        INSERT INTO bill_items (bill_id, description, qty, unit_price)
        SELECT ?, s.name, js.qty, js.unit_price
        FROM job_services js
        JOIN services s ON s.service_id = js.service_id
        WHERE js.job_id = ?
        ORDER BY js.created_at ASC
    ");
    $copy->bind_param("ii", $bill_id, $job_id);
    if (!$copy->execute()) {
        $msg = "Bill items copy failed: " . $copy->error;
        $copy->close();
        return [false, $msg, $bill_id];
    }
    $copy->close();

    return [true, "Bill generated: {$bill_no}", $bill_id];
}

/**
 * ACTIONS
 * We act on appointment_id (because jobs may not exist yet).
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['appointment_id'])) {
    $action         = (string)$_POST['action'];
    $appointment_id = (int)$_POST['appointment_id'];

    // Must be assigned to me
    $chk = $conn->prepare("SELECT appointment_id, mechanic_id, status FROM appointments WHERE appointment_id=? LIMIT 1");
    $chk->bind_param("i", $appointment_id);
    $chk->execute();
    $appt = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$appt || (int)$appt['mechanic_id'] !== $staff_id) {
        $flash = "Not allowed (appointment not assigned to you).";
    } else {
        if ($action === 'start') {
            $job_id = ensureJob($conn, $appointment_id, $staff_id);
            if ($job_id <= 0) {
                $flash = "Could not create job.";
            } else {
                $conn->begin_transaction();
                try {
                    $upd = $conn->prepare("UPDATE jobs SET status='in_progress', started_at=COALESCE(started_at, NOW()) WHERE job_id=?");
                    $upd->bind_param("i", $job_id);
                    if (!$upd->execute()) throw new Exception($upd->error);
                    $upd->close();

                    $upd2 = $conn->prepare("UPDATE appointments SET status='in_progress' WHERE appointment_id=?");
                    $upd2->bind_param("i", $appointment_id);
                    if (!$upd2->execute()) throw new Exception($upd2->error);
                    $upd2->close();

                    $conn->commit();
                    $flash = "Job started.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $flash = "Start failed: " . $e->getMessage();
                }
            }
        }

        if ($action === 'complete') {
            $job_id = ensureJob($conn, $appointment_id, $staff_id);
            if ($job_id <= 0) {
                $flash = "Could not locate/create job.";
            } else {
                $conn->begin_transaction();
                try {
                    // Mark completed
                    $upd = $conn->prepare("UPDATE jobs SET status='completed', completed_at=NOW() WHERE job_id=?");
                    $upd->bind_param("i", $job_id);
                    if (!$upd->execute()) throw new Exception($upd->error);
                    $upd->close();

                    $upd2 = $conn->prepare("UPDATE appointments SET status='completed' WHERE appointment_id=?");
                    $upd2->bind_param("i", $appointment_id);
                    if (!$upd2->execute()) throw new Exception($upd2->error);
                    $upd2->close();

                    // Generate bill from job_services
                    [$ok, $msg, $bill_id] = generateBillForJob($conn, $job_id, $staff_id);
                    if (!$ok) throw new Exception($msg);

                    $conn->commit();
                    $flash = "Job completed. {$msg} (Bill ID: {$bill_id})";
                } catch (Exception $e) {
                    $conn->rollback();
                    $flash = "Complete failed: " . $e->getMessage();
                }
            }
        }

        if ($action === 'cancel') {
            $job_id = ensureJob($conn, $appointment_id, $staff_id);
            if ($job_id <= 0) {
                $flash = "Could not locate/create job.";
            } else {
                $conn->begin_transaction();
                try {
                    $upd = $conn->prepare("UPDATE jobs SET status='cancelled' WHERE job_id=?");
                    $upd->bind_param("i", $job_id);
                    if (!$upd->execute()) throw new Exception($upd->error);
                    $upd->close();

                    $upd2 = $conn->prepare("UPDATE appointments SET status='cancelled' WHERE appointment_id=?");
                    $upd2->bind_param("i", $appointment_id);
                    if (!$upd2->execute()) throw new Exception($upd2->error);
                    $upd2->close();

                    $conn->commit();
                    $flash = "Job cancelled.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $flash = "Cancel failed: " . $e->getMessage();
                }
            }
        }
    }
}

/**
 * LIST: show appointments assigned to mechanic, left join jobs.
 */
$where  = "WHERE a.mechanic_id = ? ";
$params = [$staff_id];
$types  = "i";

if ($status !== '') {
    $where .= " AND COALESCE(j.status, 'open') = ? ";
    $types .= "s";
    $params[] = $status;
}

if ($q !== '') {
    $where .= " AND (
        c.name LIKE CONCAT('%', ?, '%')
        OR c.phone LIKE CONCAT('%', ?, '%')
        OR v.plate_no LIKE CONCAT('%', ?, '%')
        OR v.make LIKE CONCAT('%', ?, '%')
        OR v.model LIKE CONCAT('%', ?, '%')
        OR a.problem_text LIKE CONCAT('%', ?, '%')
        OR CAST(a.appointment_id AS CHAR) LIKE CONCAT('%', ?, '%')
        OR CAST(j.job_id AS CHAR) LIKE CONCAT('%', ?, '%')
    ) ";
    $types .= "ssssssss";
    array_push($params, $q, $q, $q, $q, $q, $q, $q, $q);
}

$sql = "
    SELECT
        a.appointment_id,
        a.status AS appointment_status,
        a.requested_date, a.requested_slot, a.problem_text,
        c.name AS customer_name, c.phone,
        v.plate_no, v.make, v.model, v.year AS model_year,
        j.job_id,
        COALESCE(j.status, 'open') AS job_status,
        j.created_at, j.started_at, j.completed_at
    FROM appointments a
    LEFT JOIN jobs j ON j.appointment_id = a.appointment_id
    JOIN customers c ON a.customer_id = c.customer_id
    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    $where
    ORDER BY a.requested_date DESC, a.requested_slot ASC
    LIMIT 200
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Jobs - Screw Dheela</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    :root{--primary-color:#f59e0b;--accent-color:#ea580c;--light-bg:#fffbeb}
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--light-bg);margin:0}
    .top-nav{background:linear-gradient(135deg,var(--accent-color),var(--primary-color));color:#fff;padding:1rem 2rem;box-shadow:0 2px 10px rgba(0,0,0,.1)}
    .top-nav-content{display:flex;justify-content:space-between;align-items:center;max-width:1400px;margin:0 auto}
    .logo{font-size:1.3rem;font-weight:700;display:flex;align-items:center;gap:.5rem}
    .btnchip{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);color:#fff;padding:.5rem 1rem;border-radius:8px;text-decoration:none}
    .btnchip:hover{background:rgba(255,255,255,.3);color:#fff}
    .container-main{max-width:1400px;margin:1.5rem auto;padding:0 2rem}
    .cardish{background:#fff;border-radius:12px;padding:1.25rem;box-shadow:0 2px 8px rgba(0,0,0,.06)}
    .badge.success{background:#d1fae5;color:#065f46}
    .badge.warning{background:#fef3c7;color:#92400e}
    .badge.danger{background:#fee2e2;color:#991b1b}
    .badge.primary{background:#fed7aa;color:#9a3412}
    .muted{color:#6b7280}
  </style>
</head>
<body>
  <nav class="top-nav">
    <div class="top-nav-content">
      <div class="logo"><i class="bi bi-list-task"></i> Jobs</div>
      <div class="d-flex align-items-center gap-2">
        <a class="btnchip" href="mechanic_dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <a class="btnchip" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
      </div>
    </div>
  </nav>

  <div class="container-main">
    <?php if ($flash !== ""): ?>
      <div class="alert alert-warning"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <div class="cardish mb-3">
      <form class="row g-2 align-items-end" method="GET">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Search</label>
          <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="job id / customer / plate / problem">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Status</label>
          <select class="form-select" name="status">
            <option value="" <?php echo $status===''?'selected':''; ?>>All</option>
            <option value="open" <?php echo $status==='open'?'selected':''; ?>>Open</option>
            <option value="in_progress" <?php echo $status==='in_progress'?'selected':''; ?>>In progress</option>
            <option value="completed" <?php echo $status==='completed'?'selected':''; ?>>Completed</option>
            <option value="cancelled" <?php echo $status==='cancelled'?'selected':''; ?>>Cancelled</option>
          </select>
        </div>
        <div class="col-md-2 d-grid">
          <button class="btn btn-warning fw-semibold"><i class="bi bi-search me-1"></i>Filter</button>
        </div>
      </form>
    </div>

    <div class="cardish">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-bold"><i class="bi bi-wrench-adjustable-circle me-1"></i>My Assigned Work</div>
      </div>

      <?php if (empty($rows)): ?>
        <div class="muted">
          No assigned appointments/jobs found.
          <br>Usually means the appointment is not assigned to your mechanic account (appointments.mechanic_id).
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr class="text-nowrap">
                <th>Job</th>
                <th>Customer</th>
                <th>Vehicle</th>
                <th>Appt</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <?php
                  $job_id = (int)($r['job_id'] ?? 0);
                  $job_status = (string)($r['job_status'] ?? 'open');
                ?>
                <tr>
                  <td class="fw-semibold">
                    <?php if ($job_id > 0): ?>
                      #<?php echo $job_id; ?>
                    <?php else: ?>
                      <span class="muted">Not created</span><br>
                      <span class="muted">Appt #<?php echo (int)$r['appointment_id']; ?></span>
                    <?php endif; ?>
                  </td>

                  <td>
                    <?php echo htmlspecialchars($r['customer_name']); ?><br>
                    <span class="muted"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($r['phone'] ?? ''); ?></span>
                  </td>

                  <td>
                    <?php if (!empty($r['plate_no'])): ?>
                      <?php echo htmlspecialchars(($r['make'] ?? '').' '.($r['model'] ?? '').(!empty($r['model_year'])?' ('.(int)$r['model_year'].')':'')); ?><br>
                      <span class="muted"><i class="bi bi-hash me-1"></i><?php echo htmlspecialchars($r['plate_no']); ?></span>
                    <?php else: ?>
                      <span class="muted">No vehicle</span>
                    <?php endif; ?>
                  </td>

                  <td class="text-nowrap">
                    <?php echo htmlspecialchars(date('M d, Y', strtotime($r['requested_date']))); ?><br>
                    <span class="muted">Slot <?php echo (int)$r['requested_slot']; ?></span>
                  </td>

                  <td>
                    <span class="badge <?php echo badgeClassJob($job_status); ?>">
                      <?php echo htmlspecialchars(ucfirst(str_replace('_',' ', $job_status))); ?>
                    </span>
                    <div class="muted small">Appt: <?php echo htmlspecialchars($r['appointment_status']); ?></div>
                  </td>

                  <td class="text-end text-nowrap">
                    <?php if ($job_id > 0): ?>
                      <a class="btn btn-outline-warning btn-sm" href="add_services.php?job_id=<?php echo $job_id; ?>">
                        <i class="bi bi-plus-circle me-1"></i>Services
                      </a>
                    <?php else: ?>
                      <button class="btn btn-outline-secondary btn-sm" disabled title="Start the job first">
                        <i class="bi bi-lock me-1"></i>Services
                      </button>
                    <?php endif; ?>

                    <?php if ($job_status === 'open'): ?>
                      <form class="d-inline" method="POST">
                        <input type="hidden" name="appointment_id" value="<?php echo (int)$r['appointment_id']; ?>">
                        <input type="hidden" name="action" value="start">
                        <button class="btn btn-warning btn-sm"><i class="bi bi-play-fill me-1"></i>Start</button>
                      </form>
                    <?php elseif ($job_status === 'in_progress'): ?>
                      <form class="d-inline" method="POST">
                        <input type="hidden" name="appointment_id" value="<?php echo (int)$r['appointment_id']; ?>">
                        <input type="hidden" name="action" value="complete">
                        <button class="btn btn-success btn-sm"><i class="bi bi-check2-circle me-1"></i>Complete</button>
                      </form>
                    <?php endif; ?>

                    <?php if ($job_status !== 'completed' && $job_status !== 'cancelled'): ?>
                      <form class="d-inline" method="POST" onsubmit="return confirm('Cancel this job?');">
                        <input type="hidden" name="appointment_id" value="<?php echo (int)$r['appointment_id']; ?>">
                        <input type="hidden" name="action" value="cancel">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Cancel</button>
                      </form>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
