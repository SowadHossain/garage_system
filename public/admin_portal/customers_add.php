<?php
require_once __DIR__ . "/_guard.php";

$err = '';
$ok  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');

    $pass  = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password_confirm'] ?? '');

    if ($name === '') {
        $err = "Customer name is required.";
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Invalid email address.";
    } elseif ($pass !== '' && strlen($pass) < 6) {
        $err = "Password must be at least 6 characters.";
    } elseif ($pass !== '' && $pass !== $pass2) {
        $err = "Passwords do not match.";
    } else {
        $hash = null;
        if ($pass !== '') {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
        }

        $stmt = $conn->prepare("
            INSERT INTO customers (name, email, phone, password_hash)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $name, $email, $phone, $hash);

        try {
            $stmt->execute();
            $new_id = $conn->insert_id;
            $ok = "Customer added successfully (ID: " . (int)$new_id . ").";
        } catch (mysqli_sql_exception $e) {
            // likely duplicate email if email is unique in schema
            $err = "Failed to add customer: " . $e->getMessage();
        }

        $stmt->close();
    }
}

ui_header_admin("Add Customer", $staff_name);
?>

<div class="dashboard-header">
    <h1 class="dashboard-title"><i class="bi bi-person-plus me-2"></i>Add Customer</h1>
    <p class="dashboard-subtitle">Create a customer record and optionally set their login password.</p>
</div>

<?php if ($err): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-1"></i><?php echo h($err); ?>
    </div>
<?php endif; ?>

<?php if ($ok): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle me-1"></i><?php echo h($ok); ?>
    </div>
<?php endif; ?>

<div class="data-card">
    <h2 class="section-title"><i class="bi bi-card-list"></i>Customer Details</h2>

    <form method="POST" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input name="name" class="form-control" required placeholder="Rahim Uddin">
        </div>

        <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input name="phone" class="form-control" placeholder="01810000001">
        </div>

        <div class="col-12">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" placeholder="rahim@gmail.com">
            <div class="form-text">If you set an email + password, the customer can log in.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Password (optional)</label>
            <input name="password" type="password" class="form-control" placeholder="Cust@1234">
        </div>

        <div class="col-md-6">
            <label class="form-label">Confirm Password</label>
            <input name="password_confirm" type="password" class="form-control" placeholder="Cust@1234">
        </div>

        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary">
                <i class="bi bi-save me-1"></i>Save Customer
            </button>
            <a href="customers.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </form>
</div>

<?php ui_footer_admin(); ?>
