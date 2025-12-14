<?php
session_start();
require_once __DIR__ . "/../../config/db.php";

if (empty($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'mechanic') {
    header("Location: staff_login.php");
    exit;
}

$staff_id = (int)($_SESSION['staff_id'] ?? 0);
$staff_name = $_SESSION['staff_name'] ?? 'Mechanic';

$status = trim($_GET['status'] ?? '');
$q = trim($_GET['q'] ?? '');

$allowedStatuses = ['', 'open', 'in_progress', 'completed', 'cancelled'];
if (!in_array($status, $allowedStatuses, true)) $status = '';

$flash = "";

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['job_id'])) {
    $action = (string)$_POST['action'];
    $job_id = (int)$_POST['job_id'];

    // must be mine
    $chk = $conn->prepare("SELECT mechanic_id FROM jobs WHERE job_id=? LIMIT 1");
    $chk->bind_param("i", $job_id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$row || (int)$row['mechanic_id'] !== $staff_id) {
        $flash = "Not allowed.";
    } else {
        if ($action === 'start') {
            $stmt = $conn->prepare("UPDATE jobs SET status='in_progress', started_at=COALESCE(started_at, NOW()) WHERE job_id=?");
            $stmt->bind_param("i", $job_id);
            $stmt->execute();
            $stmt->close();

            $conn->query("UPDATE appointments a JOIN jobs j ON a.appointment_id=j.appointment_id SET a.status='in_progress' WHERE j.job_id=".(int)$job_id);
            $flash = "Job started.";
        } elseif ($action === 'complete') {
            $stmt = $conn->prepare("UPDATE jobs SET status='completed', completed_at=NOW() WHERE job_id=?");
            $stmt->bind_param("i", $job_id);
            $stmt->execute();
            $stmt->close();

            $conn->query("UPDATE appointments a JOIN jobs j ON a.appointment_id=j.appointment_id SET a.status='completed' WHERE j.job_id=".(int)$job_id);
            $flash = "Job completed.";
        } elseif ($action === 'cancel') {
            $stmt = $conn->prepare("UPDATE jobs SET status='cancelled' WHERE job_id=?");
            $stmt->bind_param("i", $job_id);
            $stmt->execute();
            $stmt->close();

            $conn->query("UPDATE appointments a JOIN jobs j ON a.appointment_id=j.appointment_id SET a.status='cancelled' WHERE j.job_id=".(int)$job_id);
            $flash = "Job cancelled.";
        }
    }
}

function badgeClassJob($status) {
    switch ($status) {
        case 'open':        return 'warning';
        case 'in_progress': return 'primary';
        case 'completed':   return 'success';
        case 'cancelled':   return 'danger';
        default:            return 'primary';
    }
}

$where = "WHERE j.mechanic_id = ? ";
$params = [$staff_id];
$types = "i";

if ($status !== '') {
    $where .= " AND j.status = ? ";
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
        OR CAST(j.job_id AS CHAR) LIKE CONCAT('%', ?, '%')
    ) ";
    $types .= "sssssss";
    array_push($params, $q, $q, $q, $q, $q, $q, $q);
}

$sql = "
    SELECT
        j.job_id, j.status, j.created_at, j.started_at, j.completed_at,
        a.appointment_id, a.requested_date, a.requested_slot, a.problem_text,
        c.name AS customer_name, c.phone,
        v.plate_no, v.make, v.model, v.year AS model_year
    FROM jobs j
    JOIN appointments a ON j.appointment_id=a.appointment_id
    JOIN customers c ON a.customer_id=c.customer_id
    LEFT JOIN vehicles v ON a.vehicle_id=v.vehicle_id
    $where
    ORDER BY j.created_at DESC
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
        <div class="fw-bold"><i class="bi bi-wrench-adjustable-circle me-1"></i>My Jobs</div>
        <a class="btn btn-outline-warning btn-sm" href="add_services.php"><i class="bi bi-plus-circle me-1"></i>Add Services</a>
      </div>

      <?php if (empty($rows)): ?>
        <div class="muted">No jobs found.</div>
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
                <tr>
                  <td class="fw-semibold">#<?php echo (int)$r['job_id']; ?></td>
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
                    <span class="badge <?php echo badgeClassJob($r['status']); ?>">
                      <?php echo htmlspecialchars(ucfirst($r['status'])); ?>
                    </span>
                  </td>
                  <td class="text-end text-nowrap">
                    <a class="btn btn-outline-warning btn-sm" href="add_services.php?job_id=<?php echo (int)$r['job_id']; ?>">
                      <i class="bi bi-plus-circle me-1"></i>Services
                    </a>

                    <?php if ($r['status'] === 'open'): ?>
                      <form class="d-inline" method="POST">
                        <input type="hidden" name="job_id" value="<?php echo (int)$r['job_id']; ?>">
                        <input type="hidden" name="action" value="start">
                        <button class="btn btn-warning btn-sm"><i class="bi bi-play-fill me-1"></i>Start</button>
                      </form>
                    <?php elseif ($r['status'] === 'in_progress'): ?>
                      <form class="d-inline" method="POST">
                        <input type="hidden" name="job_id" value="<?php echo (int)$r['job_id']; ?>">
                        <input type="hidden" name="action" value="complete">
                        <button class="btn btn-success btn-sm"><i class="bi bi-check2-circle me-1"></i>Complete</button>
                      </form>
                    <?php endif; ?>

                    <?php if ($r['status'] !== 'completed' && $r['status'] !== 'cancelled'): ?>
                      <form class="d-inline" method="POST" onsubmit="return confirm('Cancel this job?');">
                        <input type="hidden" name="job_id" value="<?php echo (int)$r['job_id']; ?>">
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
