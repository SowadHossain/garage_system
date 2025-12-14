<?php
require_once __DIR__ . "/_guard.php";

$err = "";
$ok = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') {
        $err = "Customer name is required.";
    } else {
        $email = ($email === '') ? null : strtolower($email);
        $phone = ($phone === '') ? null : $phone;

        $stmt = $conn->prepare("INSERT INTO customers (name, email, phone) VALUES (?,?,?)");
        $stmt->bind_param("sss", $name, $email, $phone);

        if ($stmt->execute()) {
            $ok = "Customer added successfully.";
        } else {
            $err = "Failed to add customer. " . ($conn->errno === 1062 ? "Email already exists." : "Try again.");
        }
        $stmt->close();
    }
}

ui_header("Add Customer", $staff_name);
?>
<div class="card-soft">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0"><i class="bi bi-person-plus-fill"></i>Add Customer</h2>
        <a class="btn btn-outline-success" href="customers_list.php"><i class="bi bi-list-ul me-1"></i>Customers</a>
    </div>

    <?php if ($err): ?><div class="alert alert-danger"><?php echo h($err); ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="alert alert-success"><?php echo h($ok); ?></div><?php endif; ?>

    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Full Name *</label>
            <input class="form-control" name="name" required value="<?php echo h($_POST['name'] ?? ''); ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input class="form-control" name="phone" value="<?php echo h($_POST['phone'] ?? ''); ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Email (optional)</label>
            <input class="form-control" name="email" type="email" value="<?php echo h($_POST['email'] ?? ''); ?>">
            <div class="form-text">Email must be unique if provided.</div>
        </div>
        <div class="col-12">
            <button class="btn btn-sd"><i class="bi bi-check2-circle me-1"></i>Save Customer</button>
            <a class="btn btn-light" href="receptionist_dashboard.php">Back</a>
        </div>
    </form>
</div>
<?php ui_footer(); ?>
