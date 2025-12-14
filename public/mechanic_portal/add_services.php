<?php
// public/mechanic_portal/add_services.php - Add services to a job (Mechanic)
session_start();
require_once __DIR__ . "/../../config/db.php";

if (empty($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'mechanic') {
    header("Location: staff_login.php");
    exit;
}

$staff_id = (int)($_SESSION['staff_id'] ?? 0);
$staff_name = $_SESSION['staff_name'] ?? 'Mechanic';

$job_id = (int)($_GET['job_id'] ?? ($_POST['job_id'] ?? 0));
$flash = "";

// Fetch my jobs for dropdown
$jobs_stmt = $conn->prepare("
  SELECT 
    j.job_id, j.status, j.created_at,
    a.requested_date, a.requested_slot,
    c.name AS customer_name,
    v.plate_no, v.make, v.model, v.year AS model_year
  FROM jobs j
  JOIN appointments a ON j.appointment_id=a.appointment_id
  JOIN customers c ON a.customer_id=c.customer_id
  LEFT JOIN vehicles v ON a.vehicle_id=v.vehicle_id
  WHERE j.mechanic_id = ?
  ORDER BY j.created_at DESC
  LIMIT 200
");
$jobs_stmt->bind_param("i", $staff_id);
$jobs_stmt->execute();
$my_jobs = $jobs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$jobs_stmt->close();

// Active services catalog
$svc_stmt = $conn->prepare("SELECT service_id, name, base_price FROM services WHERE is_active=1 ORDER BY name ASC");
$svc_stmt->execute();
$services = $svc_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$svc_stmt->close();

// Add / update service line
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_id'], $_POST['qty']) && !isset($_POST['delete_job_service_id'])) {
    $job_id = (int)($_POST['job_id'] ?? 0);
    $service_id = (int)($_POST['service_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    $unit_price = (float)($_POST['unit_price'] ?? 0);

    if ($job_id <= 0 || $service_id <= 0 || $qty < 1) {
        $flash = "Invalid input.";
    } else {
        // Ensure job belongs to this mechanic
        $chk = $conn->prepare("SELECT job_id FROM jobs WHERE job_id = ? AND mechanic_id = ? LIMIT 1");
        $chk->bind_param("ii", $job_id, $staff_id);
        $chk->execute();
        $ok = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$ok) {
            $flash = "Not allowed.";
        } else {
            // Auto unit price from service base price if empty/0
            if ($unit_price <= 0) {
                $bp = $conn->prepare("SELECT base_price FROM services WHERE service_id = ? LIMIT 1");
                $bp->bind_param("i", $service_id);
                $bp->execute();
                $row = $bp->get_result()->fetch_assoc();
                $bp->close();
                $unit_price = (float)($row['base_price'] ?? 0);
            }

            // Merge line if already exists for this job/service
            $find = $conn->prepare("SELECT job_service_id, qty FROM job_services WHERE job_id=? AND service_id=? LIMIT 1");
            $find->bind_param("ii", $job_id, $service_id);
            $find->execute();
            $existing = $find->get_result()->fetch_assoc();
            $find->close();

            if ($existing) {
                $newQty = (int)$existing['qty'] + $qty;
                $upd = $conn->prepare("UPDATE job_services SET qty=?, unit_price=? WHERE job_service_id=?");
                $upd->bind_param("idi", $newQty, $unit_price, $existing['job_service_id']);
                $upd->execute();
                $upd->close();
                $flash = "Service updated (+qty).";
            } else {
                $ins = $conn->prepare("INSERT INTO job_services (job_id, service_id, qty, unit_price) VALUES (?,?,?,?)");
                $ins->bind_param("iiid", $job_id, $service_id, $qty, $unit_price);
                $ins->execute();
                $ins->close();
                $flash = "Service added.";
            }
        }
    }
}

// Delete line
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_job_service_id'])) {
    $jsid = (int)$_POST['delete_job_service_id'];

    $chk = $conn->prepare("
      SELECT js.job_service_id
      FROM job_services js
      JOIN jobs j ON js.job_id=j.job_id
      WHERE js.job_service_id=? AND j.mechanic_id=?
      LIMIT 1
    ");
    $chk->bind_param("ii", $jsid, $staff_id);
    $chk->execute();
    $ok = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($ok) {
        $del = $conn->prepare("DELETE FROM job_services WHERE job_service_id=?");
        $del->bind_param("i", $jsid);
        $del->execute();
        $del->close();
        $flash = "Line removed.";
    } else {
        $flash = "Not allowed.";
    }
}

// Load selected job meta + lines
$lines = [];
$job_meta = null;

if ($job_id > 0) {
    $meta = $conn->prepare("
      SELECT 
        j.job_id, j.status,
        a.appointment_id, a.requested_date, a.requested_slot,
        c.name AS customer_name, c.phone,
        v.plate_no, v.make, v.model, v.year AS model_year
      FROM jobs j
      JOIN appointments a ON j.appointment_id=a.appointment_id
      JOIN customers c ON a.customer_id=c.customer_id
      LEFT JOIN vehicles v ON a.vehicle_id=v.vehicle_id
      WHERE j.job_id=? AND j.mechanic_id=?
      LIMIT 1
    ");
    $meta->bind_param("ii", $job_id, $staff_id);
    $meta->execute();
    $job_meta = $meta->get_result()->fetch_assoc();
    $meta->close();

    if ($job_meta) {
        $ls = $conn->prepare("
          SELECT js.job_service_id, s.name, js.qty, js.unit_price, js.line_total, js.created_at
          FROM job_services js
          JOIN services s ON js.service_id=s.service_id
          WHERE js.job_id=?
          ORDER BY js.created_at DESC
        ");
        $ls->bind_param("i", $job_id);
        $ls->execute();
        $lines = $ls->get_result()->fetch_all(MYSQLI_ASSOC);
        $ls->close();
    }
}

$subtotal = 0.0;
foreach ($lines as $ln) $subtotal += (float)$ln['line_total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add Services - Screw Dheela</title>
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
    .container-main{max-width:1100px;margin:1.5rem auto;padding:0 2rem}
    .cardish{background:#fff;border-radius:12px;padding:1.25rem;box-shadow:0 2px 8px rgba(0,0,0,.06)}
    .muted{color:#6b7280}
  </style>
</head>
<body>
  <nav class="top-nav">
    <div class="top-nav-content">
      <div class="logo"><i class="bi bi-plus-circle"></i> Add Services</div>
      <div class="d-flex align-items-center gap-2">
        <a class="btnchip" href="mechanic_dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <a class="btnchip" href="jobs_list.php"><i class="bi bi-list-task me-1"></i>Jobs</a>
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
        <div class="col-md-10">
          <label class="form-label fw-semibold">Select Job</label>
          <select class="form-select" name="job_id" onchange="this.form.submit()">
            <option value="0">Choose a job...</option>
            <?php foreach ($my_jobs as $j): ?>
              <option value="<?php echo (int)$j['job_id']; ?>" <?php echo ((int)$j['job_id']===$job_id)?'selected':''; ?>>
                #<?php echo (int)$j['job_id']; ?> | <?php echo htmlspecialchars($j['customer_name']); ?>
                <?php if (!empty($j['plate_no'])): ?> | <?php echo htmlspecialchars($j['plate_no']); ?><?php endif; ?>
                | <?php echo htmlspecialchars(date('M d', strtotime($j['requested_date']))); ?> (Slot <?php echo (int)$j['requested_slot']; ?>)
                | <?php echo htmlspecialchars($j['status']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-grid">
          <a class="btn btn-outline-warning" href="jobs_list.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
      </form>

      <?php if ($job_meta): ?>
        <div class="mt-3 muted">
          <div><strong>Customer:</strong> <?php echo htmlspecialchars($job_meta['customer_name']); ?> (<?php echo htmlspecialchars($job_meta['phone'] ?? ''); ?>)</div>
          <div><strong>Vehicle:</strong>
            <?php echo htmlspecialchars(trim(($job_meta['make'] ?? '').' '.($job_meta['model'] ?? ''))); ?>
            <?php if (!empty($job_meta['model_year'])): ?> (<?php echo (int)$job_meta['model_year']; ?>)<?php endif; ?>
            <?php if (!empty($job_meta['plate_no'])): ?> - <?php echo htmlspecialchars($job_meta['plate_no']); ?><?php endif; ?>
          </div>
          <div><strong>Appointment:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($job_meta['requested_date']))); ?> (Slot <?php echo (int)$job_meta['requested_slot']; ?>)</div>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!$job_meta): ?>
      <div class="cardish"><div class="muted">Pick a job to add services.</div></div>
    <?php else: ?>
      <div class="cardish mb-3">
        <h5 class="fw-bold mb-3"><i class="bi bi-tools me-2"></i>Add a Service Line</h5>
        <form class="row g-2" method="POST">
          <input type="hidden" name="job_id" value="<?php echo (int)$job_id; ?>">

          <div class="col-md-6">
            <label class="form-label fw-semibold">Service</label>
            <select class="form-select" name="service_id" required>
              <option value="">Choose...</option>
              <?php foreach ($services as $s): ?>
                <option value="<?php echo (int)$s['service_id']; ?>">
                  <?php echo htmlspecialchars($s['name']); ?> (৳<?php echo number_format((float)$s['base_price'], 2); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-2">
            <label class="form-label fw-semibold">Qty</label>
            <input type="number" min="1" class="form-control" name="qty" value="1" required>
          </div>

          <div class="col-md-2">
            <label class="form-label fw-semibold">Unit Price</label>
            <input type="number" step="0.01" min="0" class="form-control" name="unit_price" placeholder="auto">
          </div>

          <div class="col-md-2 d-grid">
            <label class="form-label fw-semibold">&nbsp;</label>
            <button class="btn btn-warning fw-semibold"><i class="bi bi-plus-lg me-1"></i>Add</button>
          </div>
        </form>
        <div class="muted mt-2">Leave Unit Price empty to use the service base price.</div>
      </div>

      <div class="cardish">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-bold"><i class="bi bi-receipt me-2"></i>Current Lines</div>
          <div class="fw-bold">Subtotal: ৳<?php echo number_format($subtotal, 2); ?></div>
        </div>

        <?php if (empty($lines)): ?>
          <div class="muted">No services added yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Service</th>
                  <th class="text-end">Qty</th>
                  <th class="text-end">Unit</th>
                  <th class="text-end">Line Total</th>
                  <th class="text-end">Remove</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lines as $ln): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($ln['name']); ?></td>
                    <td class="text-end"><?php echo (int)$ln['qty']; ?></td>
                    <td class="text-end">৳<?php echo number_format((float)$ln['unit_price'], 2); ?></td>
                    <td class="text-end">৳<?php echo number_format((float)$ln['line_total'], 2); ?></td>
                    <td class="text-end">
                      <form method="POST" onsubmit="return confirm('Remove this line?');">
                        <input type="hidden" name="job_id" value="<?php echo (int)$job_id; ?>">
                        <input type="hidden" name="delete_job_service_id" value="<?php echo (int)$ln['job_service_id']; ?>">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <div class="mt-3 d-flex gap-2">
          <a class="btn btn-outline-warning" href="jobs_list.php"><i class="bi bi-list-task me-1"></i>Back to Jobs</a>
        </div>

        <div class="muted mt-2">
          Billing is handled by your existing bills pages. If your bill flow is missing/broken, paste it and I’ll fix it next.
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
