<?php
// public/customer_portal/my_vehicles.php

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

$vehCols  = getTableColumns($conn, "vehicles");
$regCol   = detectColumn($vehCols, ['registration_no','registration_number','plate_no','license_plate','reg_no','plate']);
$brandCol = detectColumn($vehCols, ['brand','make']);
$modelCol = detectColumn($vehCols, ['model']);
$yearCol  = detectColumn($vehCols, ['year','model_year','vehicle_year']);
$colorCol = detectColumn($vehCols, ['color','vehicle_color']);
$vinCol   = detectColumn($vehCols, ['vin','chassis_no','chassis_number']);

$errors = [];
$form = ['brand'=>'','model'=>'','year'=>'','reg'=>'','color'=>'','vin'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $form['brand'] = trim($_POST['brand'] ?? '');
    $form['model'] = trim($_POST['model'] ?? '');
    $form['year']  = trim($_POST['year'] ?? '');
    $form['reg']   = trim($_POST['reg'] ?? '');
    $form['color'] = trim($_POST['color'] ?? '');
    $form['vin']   = trim($_POST['vin'] ?? '');

    if ($brandCol && $form['brand'] === '') $errors[] = "Brand/Make is required.";
    if ($modelCol && $form['model'] === '') $errors[] = "Model is required.";
    if ($regCol && $form['reg'] === '') $errors[] = "Registration/Plate is required.";

    if (empty($errors)) {
        $cols = ['customer_id'];
        $types = "i";
        $vals = [$customer_id];

        if ($brandCol) { $cols[] = "`$brandCol`"; $types.="s"; $vals[]=$form['brand']; }
        if ($modelCol) { $cols[] = "`$modelCol`"; $types.="s"; $vals[]=$form['model']; }
        if ($yearCol && $form['year'] !== '') { $cols[]="`$yearCol`"; $types.="s"; $vals[]=$form['year']; }
        if ($regCol) { $cols[]="`$regCol`"; $types.="s"; $vals[]=$form['reg']; }
        if ($colorCol && $form['color'] !== '') { $cols[]="`$colorCol`"; $types.="s"; $vals[]=$form['color']; }
        if ($vinCol && $form['vin'] !== '') { $cols[]="`$vinCol`"; $types.="s"; $vals[]=$form['vin']; }

        // created_at if exists
        $createdAt = detectColumn($vehCols, ['created_at','added_at']);
        $placeholders = array_fill(0, count($cols), "?");
        if ($createdAt) { $cols[]="`$createdAt`"; $placeholders[]="NOW()"; }

        $sql = "INSERT INTO vehicles (" . implode(",", $cols) . ") VALUES (" . implode(",", $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = "DB error: " . $conn->error;
        } else {
            $stmt->bind_param($types, ...$vals);
            if ($stmt->execute()) {
                header("Location: my_vehicles.php?added=1");
                exit;
            }
            $errors[] = "Error: " . $stmt->error;
            $stmt->close();
        }
    }
}

// Load vehicles
$st = $conn->prepare("SELECT * FROM vehicles WHERE customer_id = ? ORDER BY vehicle_id DESC");
$st->bind_param("i", $customer_id);
$st->execute();
$vehicles = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

function vget(array $v, string $col, string $fallbackKey = '') {
    if ($col && isset($v[$col])) return $v[$col];
    if ($fallbackKey && isset($v[$fallbackKey])) return $v[$fallbackKey];
    return '';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Vehicles - Screw Dheela</title>
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
      <h3 class="mb-1"><i class="bi bi-car-front me-2"></i>My Vehicles</h3>
      <div class="text-muted">Manage your registered vehicles.</div>
    </div>
    <a class="btn btn-outline-secondary" href="customer_dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>

  <?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Vehicle added.</div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle me-2"></i>Please fix these:</div>
      <ul class="mb-0"><?php foreach($errors as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h5 class="mb-3"><i class="bi bi-plus-circle me-2"></i>Add Vehicle</h5>

          <?php if (!$brandCol && !$modelCol && !$regCol): ?>
            <div class="alert alert-warning mb-0">
              Your <code>vehicles</code> table doesn’t have recognizable fields for brand/model/registration.
              Tell me your vehicles table columns and I’ll map it properly.
            </div>
          <?php else: ?>
            <form method="post" novalidate>
              <input type="hidden" name="add_vehicle" value="1">

              <?php if ($brandCol): ?>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Brand/Make</label>
                  <input class="form-control" name="brand" value="<?php echo h($form['brand']); ?>" required>
                </div>
              <?php endif; ?>

              <?php if ($modelCol): ?>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Model</label>
                  <input class="form-control" name="model" value="<?php echo h($form['model']); ?>" required>
                </div>
              <?php endif; ?>

              <?php if ($yearCol): ?>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Year</label>
                  <input class="form-control" name="year" value="<?php echo h($form['year']); ?>" placeholder="e.g. 2020">
                </div>
              <?php endif; ?>

              <?php if ($regCol): ?>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Registration/Plate</label>
                  <input class="form-control" name="reg" value="<?php echo h($form['reg']); ?>" required>
                </div>
              <?php endif; ?>

              <?php if ($colorCol): ?>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Color (optional)</label>
                  <input class="form-control" name="color" value="<?php echo h($form['color']); ?>">
                </div>
              <?php endif; ?>

              <?php if ($vinCol): ?>
                <div class="mb-3">
                  <label class="form-label fw-semibold">VIN/Chassis (optional)</label>
                  <input class="form-control" name="vin" value="<?php echo h($form['vin']); ?>">
                </div>
              <?php endif; ?>

              <button class="btn btn-success">
                <i class="bi bi-check-circle me-1"></i>Add Vehicle
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm border-0">
        <div class="card-body p-0">
          <div class="p-4 border-bottom">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Registered Vehicles</h5>
          </div>

          <?php if (empty($vehicles)): ?>
            <div class="p-4 text-center text-muted">
              <div class="display-6 mb-2"><i class="bi bi-car-front"></i></div>
              No vehicles yet.
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Vehicle</th>
                    <th>Plate</th>
                    <th>Year</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($vehicles as $v): ?>
                    <?php
                      $b = vget($v, $brandCol, 'brand');
                      $m = vget($v, $modelCol, 'model');
                      $y = vget($v, $yearCol, 'year');
                      $r = vget($v, $regCol, 'registration_no');
                    ?>
                    <tr>
                      <td class="fw-semibold"><?php echo h(trim($b.' '.$m)); ?></td>
                      <td><?php echo $r ? h($r) : '<span class="text-muted">-</span>'; ?></td>
                      <td><?php echo $y ? h($y) : '<span class="text-muted">-</span>'; ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
