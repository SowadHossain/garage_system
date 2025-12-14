<?php
// public/mechanic_portal/vehicles_list.php - Vehicle lookup (Mechanic)
session_start();
require_once __DIR__ . "/../../config/db.php";

if (empty($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'mechanic') {
    header("Location: staff_login.php");
    exit;
}

$q = trim($_GET['q'] ?? '');
$rows = [];

if ($q !== '') {
    $stmt = $conn->prepare("
      SELECT
        v.vehicle_id, v.plate_no, v.make, v.model, v.year, v.color,
        c.customer_id, c.name AS customer_name, c.phone
      FROM vehicles v
      JOIN customers c ON v.customer_id=c.customer_id
      WHERE
        v.plate_no LIKE CONCAT('%', ?, '%')
        OR v.make LIKE CONCAT('%', ?, '%')
        OR v.model LIKE CONCAT('%', ?, '%')
        OR c.name LIKE CONCAT('%', ?, '%')
        OR c.phone LIKE CONCAT('%', ?, '%')
      ORDER BY v.vehicle_id DESC
      LIMIT 200
    ");
    $stmt->bind_param("sssss", $q, $q, $q, $q, $q);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Vehicles - Screw Dheela</title>
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
    .container-main{max-width:1200px;margin:1.5rem auto;padding:0 2rem}
    .cardish{background:#fff;border-radius:12px;padding:1.25rem;box-shadow:0 2px 8px rgba(0,0,0,.06)}
    .muted{color:#6b7280}
  </style>
</head>
<body>
  <nav class="top-nav">
    <div class="top-nav-content">
      <div class="logo"><i class="bi bi-car-front"></i> Vehicles</div>
      <div class="d-flex align-items-center gap-2">
        <a class="btnchip" href="mechanic_dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <a class="btnchip" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
      </div>
    </div>
  </nav>

  <div class="container-main">
    <div class="cardish mb-3">
      <form class="row g-2 align-items-end" method="GET">
        <div class="col-md-10">
          <label class="form-label fw-semibold">Search vehicle / customer</label>
          <input class="form-control" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="plate / make / model / customer name / phone">
        </div>
        <div class="col-md-2 d-grid">
          <button class="btn btn-warning fw-semibold"><i class="bi bi-search me-1"></i>Search</button>
        </div>
      </form>
      <div class="muted mt-2">Example: <span class="fw-semibold">DHA-11-1234</span></div>
    </div>

    <div class="cardish">
      <?php if ($q === ''): ?>
        <div class="muted">Enter a search term to find vehicles.</div>
      <?php elseif (empty($rows)): ?>
        <div class="muted">No vehicles found.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr class="text-nowrap">
                <th>Plate</th>
                <th>Vehicle</th>
                <th>Customer</th>
                <th>Color</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td class="fw-bold"><?php echo htmlspecialchars($r['plate_no']); ?></td>
                  <td>
                    <?php echo htmlspecialchars(trim(($r['make'] ?? '') . ' ' . ($r['model'] ?? ''))); ?>
                    <?php if (!empty($r['year'])): ?> (<?php echo (int)$r['year']; ?>)<?php endif; ?>
                  </td>
                  <td>
                    <?php echo htmlspecialchars($r['customer_name']); ?><br>
                    <span class="muted"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($r['phone'] ?? ''); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars($r['color'] ?? ''); ?></td>
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
