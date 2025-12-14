<?php
require_once __DIR__ . "/_guard.php";

$bill_id = (int)($_GET['bill_id'] ?? 0);
if ($bill_id <= 0) { header("Location: billing.php"); exit; }

$stmt = $conn->prepare("
    SELECT
        b.*, c.name AS customer_name, c.phone, c.email,
        v.plate_no, v.make, v.model, v.year, v.color,
        a.appointment_id, a.requested_date, a.requested_slot,
        s1.name AS created_by_name,
        s2.name AS paid_by_name
    FROM bills b
    JOIN jobs j ON b.job_id=j.job_id
    JOIN appointments a ON j.appointment_id=a.appointment_id
    JOIN customers c ON a.customer_id=c.customer_id
    JOIN vehicles v ON a.vehicle_id=v.vehicle_id
    LEFT JOIN staff s1 ON b.created_by_staff_id=s1.staff_id
    LEFT JOIN staff s2 ON b.paid_by_staff_id=s2.staff_id
    WHERE b.bill_id=?
");
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$bill) { header("Location: billing.php"); exit; }

$stmt = $conn->prepare("SELECT description, qty, unit_price, line_total FROM bill_items WHERE bill_id=? ORDER BY bill_item_id ASC");
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

ui_header("Invoice " . ($bill['bill_no'] ?? ''), $staff_name);
?>
<div class="card-soft">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0"><i class="bi bi-receipt-cutoff"></i>Invoice</h2>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-success" href="billing.php"><i class="bi bi-arrow-left me-1"></i>Billing</a>
            <?php if (($bill['payment_status'] ?? '') !== 'paid'): ?>
                <a class="btn btn-sd" href="mark_paid.php?bill_id=<?php echo (int)$bill_id; ?>"><i class="bi bi-cash-coin me-1"></i>Mark Paid</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="p-3 rounded bg-light">
                <div class="fw-semibold"><?php echo h($bill['customer_name']); ?></div>
                <div class="text-muted small">
                    <i class="bi bi-telephone me-1"></i><?php echo h($bill['phone'] ?? ''); ?><br>
                    <i class="bi bi-envelope me-1"></i><?php echo h($bill['email'] ?? ''); ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded bg-light">
                <div class="fw-semibold"><?php echo h($bill['plate_no']); ?></div>
                <div class="text-muted small">
                    <?php echo h(trim(($bill['make'] ?? '').' '.($bill['model'] ?? ''))); ?>
                    <?php if (!empty($bill['year'])): ?> (<?php echo (int)$bill['year']; ?>)<?php endif; ?>
                    <?php if (!empty($bill['color'])): ?> - <?php echo h($bill['color']); ?><?php endif; ?><br>
                    Appointment: #<?php echo (int)$bill['appointment_id']; ?> on <?php echo h($bill['requested_date']); ?> (Slot <?php echo (int)$bill['requested_slot']; ?>)
                </div>
            </div>
        </div>
    </div>

    <hr>

    <div class="d-flex justify-content-between align-items-center">
        <div>
            <div class="fw-semibold"><?php echo h($bill['bill_no']); ?></div>
            <div class="text-muted small">
                Created by: <?php echo h($bill['created_by_name'] ?? ''); ?> | Created at: <?php echo h($bill['created_at'] ?? ''); ?>
            </div>
        </div>
        <span class="badge <?php echo h(($bill['payment_status'] ?? '')==='paid'?'success':'warning'); ?>">
            <?php echo h(strtoupper($bill['payment_status'] ?? '')); ?>
        </span>
    </div>

    <div class="table-responsive mt-3">
        <table class="table">
            <thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit</th><th class="text-end">Total</th></tr></thead>
            <tbody>
            <?php if (!$items): ?>
                <tr><td colspan="4" class="text-muted">No line items.</td></tr>
            <?php endif; ?>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?php echo h($it['description']); ?></td>
                    <td class="text-end"><?php echo (int)$it['qty']; ?></td>
                    <td class="text-end">৳ <?php echo number_format((float)$it['unit_price'], 2); ?></td>
                    <td class="text-end">৳ <?php echo number_format((float)$it['line_total'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="row g-2 justify-content-end">
        <div class="col-md-4">
            <div class="p-3 rounded bg-light">
                <div class="d-flex justify-content-between"><span>Subtotal</span><span>৳ <?php echo number_format((float)$bill['subtotal'], 2); ?></span></div>
                <div class="d-flex justify-content-between"><span>Discount</span><span>৳ <?php echo number_format((float)$bill['discount'], 2); ?></span></div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fw-bold"><span>Total</span><span>৳ <?php echo number_format((float)$bill['total'], 2); ?></span></div>
                <?php if (($bill['payment_status'] ?? '') === 'paid'): ?>
                    <div class="text-muted small mt-2">
                        Paid via <?php echo h($bill['payment_method'] ?? ''); ?> at <?php echo h($bill['paid_at'] ?? ''); ?><br>
                        Confirmed by <?php echo h($bill['paid_by_name'] ?? ''); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php ui_footer(); ?>
