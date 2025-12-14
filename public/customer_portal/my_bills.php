<?php
// public/customer_portal/my_bills.php

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

$bCols = getTableColumns($conn, "bills");

$idCol = detectColumn($bCols, ['bill_id','id']);
$totalCol = detectColumn($bCols, ['total','total_amount','grand_total','amount','total_price']);
$dateCol  = detectColumn($bCols, ['bill_date','created_at','issued_at','date']);
$statusCol = detectColumn($bCols, ['payment_status','status']);
$methodCol = detectColumn($bCols, ['payment_method','method']);

// Select with aliases so UI consistent
$selId = $idCol ? "b.`$idCol` AS bill_id," : "NULL AS bill_id,";
$selTotal = $totalCol ? "b.`$totalCol` AS total_amount," : "0 AS total_amount,";
$selDate = $dateCol ? "b.`$dateCol` AS bill_date," : "NULL AS bill_date,";
$selStatus = $statusCol ? "b.`$statusCol` AS payment_status," : "NULL AS payment_status,";
$selMethod = $methodCol ? "b.`$methodCol` AS payment_method," : "NULL AS payment_method,";

$orderBy = $dateCol ? "b.`$dateCol` DESC" : ($idCol ? "b.`$idCol` DESC" : "b.job_id DESC");

$sql = "
    SELECT
        $selId
        $selTotal
        $selDate
        $selStatus
        $selMethod
        b.job_id,
        a.appointment_id
    FROM bills b
    JOIN jobs j ON b.job_id = j.job_id
    JOIN appointments a ON j.appointment_id = a.appointment_id
    WHERE a.customer_id = ?
    ORDER BY $orderBy
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function statusBadge($s): string {
    $x = strtolower((string)$s);
    return match($x) {
        'paid' => 'success',
        'unpaid' => 'warning',
        'partial' => 'info',
        default => 'secondary'
    };
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Bills - Screw Dheela</title>
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
      <h3 class="mb-1"><i class="bi bi-receipt-cutoff me-2"></i>My Bills</h3>
      <div class="text-muted">All your invoices and payment status.</div>
    </div>
    <a class="btn btn-outline-secondary" href="customer_dashboard.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <?php if (empty($bills)): ?>
        <div class="p-4 text-center text-muted">
          <div class="display-6 mb-2"><i class="bi bi-receipt"></i></div>
          No bills found yet.
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Bill #</th>
                <th>Date</th>
                <th class="text-end">Total</th>
                <th>Status</th>
                <th>Method</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bills as $b): ?>
                <tr>
                  <td class="fw-semibold"><?php echo h($b['bill_id'] ?? '-'); ?></td>
                  <td>
                    <?php
                      $d = $b['bill_date'] ?? '';
                      echo $d ? h(date('M d, Y', strtotime($d))) : '-';
                    ?>
                  </td>
                  <td class="text-end fw-bold">৳<?php echo number_format((float)($b['total_amount'] ?? 0), 2); ?></td>
                  <td><span class="badge bg-<?php echo h(statusBadge($b['payment_status'] ?? '')); ?>"><?php echo h(ucfirst((string)($b['payment_status'] ?? 'unknown'))); ?></span></td>
                  <td><?php echo !empty($b['payment_method']) ? h($b['payment_method']) : '<span class="text-muted">-</span>'; ?></td>
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
