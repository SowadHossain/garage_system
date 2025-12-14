<?php
require_once __DIR__ . "/_guard.php";

$err = '';
$ok  = '';

/** Load customers for dropdown */
$customers = $conn->query("
    SELECT customer_id, name, phone
    FROM customers
    ORDER BY customer_id DESC
    LIMIT 500
")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $plate_no    = trim($_POST['plate_no'] ?? '');
    $make        = trim($_POST['make'] ?? '');
    $model       = trim($_POST['model'] ?? '');
    $year        = trim($_POST['year'] ?? '');
    $color       = trim($_POST['color'] ?? '');

    if ($customer_id <= 0 || $plate_no === '' || $make === '' || $model === '') {
        $err = "Please fill required fields (Customer, Plate No, Make, Model).";
    } else {
        $yearVal = ($year === '' ? null : (int)$year);

        $stmt = $conn->prepare("
            INSERT INTO vehicles (customer_id, plate_no, make, model, year, color)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssis", $customer_id, $plate_no, $make, $model, $yearVal, $color);

        try {
            $stmt->execute();
            $ok = "Vehicle added successfully.";
        } catch (mysqli_sql_exception $e) {
            $err = "Failed to add vehicle: " . $e->getMessage();
        }
        $stmt->close();
    }
}

ui_header_admin("Add Vehicle", $staff_name);
?>

<div class="dashboard-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1 class="dashboard-title mb-1"><i class="bi bi-car-front-fill me-2"></i>Add Vehicle</h1>
        <p class="dashboard-subtitle mb-0">Register a vehicle for an existing customer.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="vehicles.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<?php if ($err): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?php echo h($err); ?></div>
<?php endif; ?>

<?php if ($ok): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?php echo h($ok); ?></div>
<?php endif; ?>

<div class="data-card">
    <h2 class="section-title"><i class="bi bi-pencil-square"></i>Vehicle Details</h2>

    <form method="POST" class="row g-3">
        <div class="col-12">
            <label class="form-label">Customer <span class="text-danger">*</span></label>
            <select name="customer_id" class="form-select" required>
                <option value="">Select customer...</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?php echo (int)$c['customer_id']; ?>">
                        #<?php echo (int)$c['customer_id']; ?> — <?php echo h($c['name']); ?>
                        <?php if (!empty($c['phone'])): ?> (<?php echo h($c['phone']); ?>)<?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Plate No <span class="text-danger">*</span></label>
            <input name="plate_no" class="form-control" required placeholder="DHA-11-1234">
        </div>

        <div class="col-md-4">
            <label class="form-label">Make <span class="text-danger">*</span></label>
            <input name="make" class="form-control" required placeholder="Toyota">
        </div>

        <div class="col-md-4">
            <label class="form-label">Model <span class="text-danger">*</span></label>
            <input name="model" class="form-control" required placeholder="Corolla">
        </div>

        <div class="col-md-6">
            <label class="form-label">Year</label>
            <input name="year" type="number" min="1950" max="2100" class="form-control" placeholder="2018">
        </div>

        <div class="col-md-6">
            <label class="form-label">Color</label>
            <input name="color" class="form-control" placeholder="White">
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">
                <i class="bi bi-save me-1"></i>Save Vehicle
            </button>
            <a href="vehicles.php" class="btn btn-outline-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php ui_footer_admin(); ?>
