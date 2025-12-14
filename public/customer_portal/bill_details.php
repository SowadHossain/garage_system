<?php
// public/customer_portal/bill_details.php

session_start();
require_once __DIR__ . "/../../config/db.php";

if (empty($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit;
}

$customer_id = (int)$_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'] ?? 'Customer';

$bill_id = (int)($_GET['id'] ?? 0);
if ($bill_id <= 0) {
    header("Location: my_bills.php");
    exit;
}

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

/* Detect bills cols */
$bCols = getTableColumns($conn, "bills");
$idCol       = detectColumn($bCols, ['bill_id','id']);
$billNoCol   = detectColumn($bCols, ['bill_no','invoice_no','invoice_number']);
$subtotalCol = detectColumn($bCols, ['subtotal','sub_total']);
$discountCol = detectColumn($bCols, ['discount','discount_amount']);
$totalCol    = detectColumn($bCols, ['total','total_amount','grand_total','amount','total_price']);
$dateCol     = detectColumn($bCols, ['bill_date','created_at','issued_at','date']);
$statusCol   = detectColumn($bCols, ['payment_status','status']);
$methodCol   = detectColumn($bCols, ['payment_method','method']);

$billIdExpr = $idCol ? "b.`$idCol`" : "b.bill_id";

$selId       = $idCol       ? "b.`$idCol` AS bill_id,"       : "b.bill_id AS bill_id,";
$selBillNo   = $billNoCol   ? "b.`$billNoCol` AS bill_no,"   : "NULL AS bill_no,";
$selSubtotal = $subtotalCol ? "b.`$subtotalCol` AS subtotal," : "0 AS subtotal,";
$selDiscount = $discountCol ? "b.`$discountCol` AS discount," : "0 AS discount,";
$selTotal    = $totalCol    ? "b.`$totalCol` AS total,"       : "0 AS total,";
$selDate     = $dateCol     ? "b.`$dateCol` AS bill_date,"    : "NULL AS bill_date,";
$selStatus   = $statusCol   ? "b.`$statusCol` AS payment_status," : "NULL AS payment_status,";
$selMethod   = $methodCol   ? "b.`$methodCol` AS payment_method," : "NULL AS payment_method,";

/* Vehicles column detection */
$vCols = getTableColumns($conn, "vehicles");
$plateCol = detectColumn($vCols, ['plate_no','license_plate','registration_no','registration_number','reg_no','plate']);
$makeCol  = detectColumn($vCols, ['make','brand']);
$modelCol = detectColumn($vCols, ['model']);
$yearCol  = detectColumn($vCols, ['year','model_year','vehicle_year']);

/* Fetch bill meta and verify ownership */
$sql = "
    SELECT
        $selId
        $selBillNo
        $selSubtotal
        $selDiscount
        $selTotal
        $selDate
        $selStatus
        $selMethod
        b.job_id,

        j.appointment_id,
        a.requested_date, a.requested_slot, a.problem_text, a.status AS appointment_status,

        c.name AS customer_name, c.phone, c.email,

        v.vehicle_id,
        " . ($plateCol ? "v.`$plateCol` AS plate_no," : "NULL AS plate_no,") . "
        " . ($makeCol  ? "v.`$makeCol` AS make," : "NULL AS make,") . "
        " . ($modelCol ? "v.`$modelCol` AS model," : "NULL AS model,") . "
        " . ($yearCol  ? "v.`$yearCol` AS model_year" : "NULL AS model_year") . "
    FROM bills b
    JOIN jobs j ON b.job_id = j.job_id
    JOIN appointments a ON j.appointment_id = a.appointment_id
    JOIN customers c ON a.customer_id = c.customer_id
    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE $billIdExpr = ?
      AND a.customer_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $bill_id, $customer_id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bill) {
    header("Location: my_bills.php");
    exit;
}

/* Detect bill_items cols (THIS fixes your error) */
$biCols = getTableColumns($conn, "bill_items");
$biIdCol     = detectColumn($biCols, ['bill_item_id','item_id','id']);
$descCol     = detectColumn($biCols, ['description','item_description','name','title']);
$qtyCol      = detectColumn($biCols, ['qty','quantity']);
$unitCol     = detectColumn($biCols, ['unit_price','price']);
$lineTotalCol= detectColumn($biCols, ['line_total','total','amount']);
$createdCol  = detectColumn($biCols, ['created_at','added_at']);

$selBiId   = $biIdCol ? "`$biIdCol` AS item_id" : "NULL AS item_id";
$selDesc   = $descCol ? "`$descCol` AS description" : "'' AS description";
$selQty    = $qtyCol ? "`$qtyCol` AS qty" : "0 AS qty";
$selUnit   = $unitCol ? "`$unitCol` AS unit_price" : "0 AS unit_price";
$selLine   = $lineTotalCol ? "`$lineTotalCol` AS line_total" : "0 AS line_total";

$orderItems = "1";
if ($biIdCol) $orderItems = "`$biIdCol` ASC";
elseif ($createdCol) $orderItems = "`$createdCol` ASC";

/* Fetch bill items safely */
$items = [];
$itemSql = "
    SELECT $selBiId, $selDesc, $selQty, $selUnit, $selLine
    FROM bill_items
    WHERE bill_id = ?
    ORDER BY $orderItems
";
$itemStmt = $conn->prepare($itemSql);
$itemStmt->bind_param("i", $bill_id);
$itemStmt->execute();
$items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$itemStmt->close();

function statusBadge($s): string {
    $x = strtolower((string)$s);
    return match($x) {
        'paid' => 'success',
        'unpaid' => 'warning',
        'partial' => 'info',
        default => 'secondary'
    };
}

$billNo = $bill['bill_no'] ?: ('BILL-' . $bill_id);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bill Details - Screw Dheela</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: #fff !important; }
      .card { box-shadow: none !important; border: 1px solid #ddd !important; }
      .table { font-size: 12px; }
    }
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white border-bottom sticky-top no-print">
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
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 no-print">
    <div>
      <h3 class="mb-1"><i class="bi bi-receipt me-2"></i>Bill Details</h3>
      <div class="text-muted">Invoice view. Print it if you want a hard copy.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="my_bills.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
      <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <div class="h4 mb-1 fw-bold text-success">Screw Dheela</div>
          <div class="text-muted">Garage Invoice</div>
        </div>
        <div class="text-end">
          <div class="fw-bold">Invoice: <?php echo h($billNo); ?></div>
          <div class="text-muted">
            Date:
            <?php
              $d = $bill['bill_date'] ?? '';
              echo $d ? h(date('M d, Y', strtotime($d))) : '-';
            ?>
          </div>
          <div class="mt-1">
            <span class="badge bg-<?php echo h(statusBadge($bill['payment_status'] ?? '')); ?>">
              <?php echo h(ucfirst((string)($bill['payment_status'] ?? 'unknown'))); ?>
            </span>
          </div>
        </div>
      </div>

      <hr class="my-4">

      <div class="row g-3">
        <div class="col-md-6">
          <div class="fw-bold mb-1">Billed To</div>
          <div><?php echo h($bill['customer_name'] ?? ''); ?></div>
          <div class="text-muted small"><?php echo h($bill['phone'] ?? ''); ?><?php echo !empty($bill['email']) ? " • ".h($bill['email']) : ""; ?></div>
        </div>

        <div class="col-md-6">
          <div class="fw-bold mb-1">Vehicle & Appointment</div>
          <div class="text-muted small">
            <?php
              $veh = trim((string)($bill['make'] ?? '') . " " . (string)($bill['model'] ?? ''));
              $yr  = $bill['model_year'] ?? '';
              $pl  = $bill['plate_no'] ?? '';
              echo $veh !== '' ? h($veh) : "Vehicle: -";
              if ($yr !== '' && $yr !== null) echo " " . h("($yr)");
              if ($pl !== '' && $pl !== null) echo " • " . h($pl);
            ?>
          </div>
          <div class="text-muted small">
            Appointment:
            <?php
              $rd = $bill['requested_date'] ?? '';
              echo $rd ? h(date('M d, Y', strtotime($rd))) : '-';
            ?>
            • Slot <?php echo (int)($bill['requested_slot'] ?? 0); ?>
          </div>
          <?php if (!empty($bill['problem_text'])): ?>
            <div class="text-muted small mt-1">Issue: <?php echo h($bill['problem_text']); ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-4">
        <div class="fw-bold mb-2">Items</div>

        <?php if (empty($items)): ?>
          <div class="alert alert-warning mb-0">
            No bill items found. (Mechanic may have completed without adding services.)
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead class="table-light">
                <tr>
                  <th>Description</th>
                  <th class="text-end">Qty</th>
                  <th class="text-end">Unit Price</th>
                  <th class="text-end">Line Total</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $it): ?>
                  <tr>
                    <td class="fw-semibold"><?php echo h($it['description']); ?></td>
                    <td class="text-end"><?php echo (int)$it['qty']; ?></td>
                    <td class="text-end">৳<?php echo number_format((float)$it['unit_price'], 2); ?></td>
                    <td class="text-end fw-bold">৳<?php echo number_format((float)$it['line_total'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="row justify-content-end mt-3">
        <div class="col-md-5">
          <div class="border rounded p-3">
            <div class="d-flex justify-content-between">
              <div class="text-muted">Subtotal</div>
              <div class="fw-semibold">৳<?php echo number_format((float)($bill['subtotal'] ?? 0), 2); ?></div>
            </div>
            <div class="d-flex justify-content-between mt-1">
              <div class="text-muted">Discount</div>
              <div class="fw-semibold">৳<?php echo number_format((float)($bill['discount'] ?? 0), 2); ?></div>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between">
              <div class="fw-bold">Total</div>
              <div class="fw-bold">৳<?php echo number_format((float)($bill['total'] ?? 0), 2); ?></div>
            </div>
            <div class="text-muted small mt-2">
              Payment method: <?php echo !empty($bill['payment_method']) ? h($bill['payment_method']) : '-'; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="text-center text-muted small mt-4">
        Thanks for choosing Screw Dheela. Keep the wheels rolling. 🛞
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
