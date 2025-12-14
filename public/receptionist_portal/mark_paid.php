<?php
require_once __DIR__ . "/_guard.php";

$bill_id = (int)($_GET['bill_id'] ?? ($_POST['bill_id'] ?? 0));
if ($bill_id <= 0) { header("Location: billing.php"); exit; }

$err = "";
$ok = "";

$stmt = $conn->prepare("SELECT bill_id, bill_no, payment_status FROM bills WHERE bill_id=?");
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bill) { header("Location: billing.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = trim($_POST['payment_method'] ?? '');
    $allowed = ['cash','bkash','nagad','card','bank','other'];

    if (!in_array($method, $allowed, true)) {
        $err = "Invalid payment method.";
    } else {
        $stmt = $conn->prepare("
            UPDATE bills
            SET payment_status='paid', payment_method=?, paid_by_staff_id=?, paid_at=NOW()
            WHERE bill_id=? AND payment_status='unpaid'
        ");
        $stmt->bind_param("sii", $method, $staff_id, $bill_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            header("Location: view_bill.php?bill_id=".$bill_id);
            exit;
        } else {
            $err = "Bill is already paid (or could not update).";
        }
    }
}

ui_header("Mark Paid", $staff_name);
?>
<div class="card-soft">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0"><i class="bi bi-cash-coin"></i>Mark Bill as Paid</h2>
        <a class="btn btn-outline-success" href="view_bill.php?bill_id=<?php echo (int)$bill_id; ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <?php if ($err): ?><div class="alert alert-danger"><?php echo h($err); ?></div><?php endif; ?>

    <div class="p-3 rounded bg-light mb-3">
        <div class="fw-semibold"><?php echo h($bill['bill_no']); ?></div>
        <div class="text-muted small">Current status: <?php echo h(strtoupper($bill['payment_status'])); ?></div>
    </div>

    <form method="post" class="row g-3">
        <input type="hidden" name="bill_id" value="<?php echo (int)$bill_id; ?>">
        <div class="col-md-6">
            <label class="form-label">Payment Method *</label>
            <select class="form-select" name="payment_method" required>
                <option value="">Select method</option>
                <?php foreach (['cash','bkash','nagad','card','bank','other'] as $m): ?>
                    <option value="<?php echo h($m); ?>"><?php echo h(strtoupper($m)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <button class="btn btn-sd"><i class="bi bi-check2-circle me-1"></i>Confirm Payment</button>
            <a class="btn btn-light" href="billing.php">Cancel</a>
        </div>
    </form>
</div>
<?php ui_footer(); ?>
