<?php
require_once __DIR__ . "/_guard.php";

$customers = $conn->query("SELECT customer_id, name, phone, email FROM customers ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

$err = "";
$ok = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $plate_no = strtoupper(trim($_POST['plate_no'] ?? ''));
    $make = trim($_POST['make'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $color = trim($_POST['color'] ?? '');

    if ($customer_id <= 0 || $plate_no === '' || $make === '' || $model === '') {
        $err = "Customer, plate number, make, and model are required.";
    } else {
        $yearVal = ($year === '') ? null : (int)$year;
        $colorVal = ($color === '') ? null : $color;

        $stmt = $conn->prepare("INSERT INTO vehicles (customer_id, plate_no, make, model, year, color) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isssis", $customer_id, $plate_no, $make, $model, $yearVal, $colorVal);

        if ($stmt->execute()) {
            $ok = "Vehicle added successfully.";
        } else {
            $err = "Failed to add vehicle. " . ($conn->errno === 1062 ? "Plate number already exists." : "Try again.");
        }
        $stmt->close();
    }
}

ui_header("Add Vehicle", $staff_name);
?>
<div class="card-soft">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0"><i class="bi bi-car-front-fill"></i>Add Vehicle</h2>
        <a class="btn btn-outline-success" href="customers_list.php"><i class="bi bi-people me-1"></i>Customers</a>
    </div>

    <?php if ($err): ?><div class="alert alert-danger"><?php echo h($err); ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert alert-success"><?php echo h($ok); ?></div><?php endif; ?>

    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Customer *</label>
            <select class="form-select" name="customer_id" required>
                <option value="">Select customer</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?php echo (int)$c['customer_id']; ?>" <?php echo ((int)($_POST['customer_id'] ?? 0) === (int)$c['customer_id']) ? 'selected' : ''; ?>>
                        <?php echo h($c['name']); ?> (<?php echo h($c['phone'] ?? ''); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Plate No *</label>
            <input class="form-control" name="plate_no" required value="<?php echo h($_POST['plate_no'] ?? ''); ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">Make *</label>
            <input class="form-control" name="make" required value="<?php echo h($_POST['make'] ?? ''); ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">Model *</label>
            <input class="form-control" name="model" required value="<?php echo h($_POST['model'] ?? ''); ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label">Year</label>
            <input class="form-control" name="year" type="number" min="1950" max="2100" value="<?php echo h($_POST['year'] ?? ''); ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label">Color</label>
            <input class="form-control" name="color" value="<?php echo h($_POST['color'] ?? ''); ?>">
        </div>

        <div class="col-12">
            <button class="btn btn-sd"><i class="bi bi-check2-circle me-1"></i>Save Vehicle</button>
            <a class="btn btn-light" href="receptionist_dashboard.php">Back</a>
        </div>
    </form>
</div>
<?php ui_footer(); ?>
