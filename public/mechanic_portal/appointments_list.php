<?php
// public/mechanic_portal/appointments_list.php - Appointments (Mechanic only)
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

$allowed = ['', 'requested', 'booked', 'in_progress', 'completed', 'cancelled'];
if (!in_array($status, $allowed, true)) $status = '';

$where = "WHERE a.mechanic_id = ? ";
$params = [$staff_id];
$types = "i";

if ($status !== '') {
    $where .= " AND a.status = ? ";
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
    ) ";
    $types .= "sssssss";
    array_push($params, $q, $q, $q, $q, $q, $q, $q);
}

$sql = "
  SELECT
    a.appointment_id, a.status, a.requested_date, a.requested_slot, a.problem_text,
    c.name AS customer_name, c.phone,
    v.plate_no, v.make, v.model, v.year AS model_year
  FROM appointments a
  JOIN customers c ON a.customer_id=c.customer_id
  LEFT JOIN vehicles v ON a.vehicle_id=v.vehicle_id
  $where
  ORDER BY a.requested_date ASC, a.requested_slot ASC
  LIMIT 300
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function apptLabel($s){ return ucfirst(str_replace('_',' ', (string)$s)); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Appointments - Screw Dheela</title>
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
    .badge.info{background:#dbeafe;color:#1e40af}
    .muted{color:#6b7280}
  </style>
</head>
<body>
  <nav class="top-nav">
    <div class="top-nav-content">
      <div class="logo"><i class="bi bi-calendar2-week"></i> Appointments</div>
      <div class="d-flex align-items-center gap-2">
        <a class="btnchip" href="mechanic_dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <a class="btnchip" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
      </div>
    </div>
  </nav>

  <div class="container-main">
    <div class="cardish mb-3">
      <form class="row g-2 align-items-end" method="GET">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Search</label>
          <input class="form-control" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="customer / plate / problem / id">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Status</label>
          <select class="form-select" name="status">
            <option value="" <?php echo $status===''?'selected':''; ?>>All</option>
            <option value="booked" <?php echo $status==='booked'?'selected':''; ?>>Booked</option>
            <option value="in_progress" <?php echo $status==='in_progress'?'selected':''; ?>>In progress</option>
            <option value="completed" <?php echo $status==='completed'?'selected':''; ?>>Completed</option>
            <option value="cancelled" <?php echo $status==='cancelled'?'selected':''; ?>>Cancelled</option>
            <option value="requested" <?php echo $status==='requested'?'selected':''; ?>>Requested</option>
          </select>
        </div>
        <div class="col-md-2 d-grid">
          <button class="btn btn-warning fw-semibold"><i class="bi bi-search me-1"></i>Filter</button>
        </div>
      </form>
    </div>

    <div class="cardish">
      <?php if (empty($rows)): ?>
        <div class="muted">No appointments assigned to you.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr class="text-nowrap">
                <th>ID</th>
                <th>Customer</th>
                <th>Vehicle</th>
                <th>When</th>
                <th>Problem</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td class="fw-semibold">#<?php echo (int)$r['appointment_id']; ?></td>
                  <td>
                    <?php echo htmlspecialchars($r['customer_name']); ?><br>
                    <span class="muted"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($r['phone'] ?? ''); ?></span>
                  </td>
                  <td>
                    <?php if (!empty($r['plate_no'])): ?>
                      <?php echo htmlspecialchars(trim(($r['make'] ?? '').' '.($r['model'] ?? ''))); ?>
                      <?php if (!empty($r['model_year'])): ?> (<?php echo (int)$r['model_year']; ?>)<?php endif; ?><br>
                      <span class="muted"><i class="bi bi-hash me-1"></i><?php echo htmlspecialchars($r['plate_no']); ?></span>
                    <?php else: ?>
                      <span class="muted">No vehicle</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-nowrap">
                    <?php echo htmlspecialchars(date('M d, Y', strtotime($r['requested_date']))); ?><br>
                    <span class="muted">Slot <?php echo (int)$r['requested_slot']; ?></span>
                  </td>
                  <td style="max-width:420px;">
                    <?php echo htmlspecialchars(mb_strimwidth((string)$r['problem_text'], 0, 120, '...')); ?>
                  </td>
                  <td>
                    <span class="badge info"><?php echo htmlspecialchars(apptLabel($r['status'])); ?></span>
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
