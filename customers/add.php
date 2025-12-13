<?php
session_start();
require_once '../config/db.php';
require_once '../includes/role_check.php';

// Check if user is staff with customer management permission
requireRole(['admin', 'receptionist']);

$errors = [];
$success = false;
$form_data = [
    'name' => '',
    'phone' => '',
    'email' => '',
    'address' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $form_data['name'] = isset($_POST['name']) ? trim($_POST['name']) : '';
    $form_data['phone'] = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $form_data['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';
    $form_data['address'] = isset($_POST['address']) ? trim($_POST['address']) : '';
    
    // Validation
    if (empty($form_data['name'])) {
        $errors[] = 'Customer name is required.';
    } elseif (strlen($form_data['name']) > 100) {
        $errors[] = 'Customer name must not exceed 100 characters.';
    }
    
    if (empty($form_data['phone'])) {
        $errors[] = 'Phone number is required.';
    } else {
        // Check if phone is unique
        $check_phone = $conn->prepare("SELECT customer_id FROM customers WHERE phone = ?");
        $check_phone->bind_param("s", $form_data['phone']);
        $check_phone->execute();
        if ($check_phone->get_result()->num_rows > 0) {
            $errors[] = 'This phone number is already registered.';
        }
        $check_phone->close();
    }
    
    if (!empty($form_data['email'])) {
        if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            // Check if email is unique (if provided)
            $check_email = $conn->prepare("SELECT customer_id FROM customers WHERE email = ? AND email != ''");
            $check_email->bind_param("s", $form_data['email']);
            $check_email->execute();
            if ($check_email->get_result()->num_rows > 0) {
                $errors[] = 'This email address is already registered.';
            }
            $check_email->close();
        }
    }
    
    if (strlen($form_data['address']) > 255) {
        $errors[] = 'Address must not exceed 255 characters.';
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $insert_sql = "INSERT INTO customers (name, phone, email, address, created_at) 
                       VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_sql);
        
        if ($stmt) {
            $stmt->bind_param(
                "ssss",
                $form_data['name'],
                $form_data['phone'],
                $form_data['email'],
                $form_data['address']
            );
            
            if ($stmt->execute()) {
                // Log activity
                require_once '../includes/activity_logger.php';
                logActivity(
                    'staff',
                    $_SESSION['staff_id'],
                    'create',
                    'customer',
                    $stmt->insert_id,
                    null,
                    ['name' => $form_data['name'], 'phone' => $form_data['phone']]
                );
                
                $success = true;
                header("Location: list.php?success=Customer%20added%20successfully");
                exit;
            } else {
                $errors[] = 'Error adding customer: ' . $conn->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
    }
}

$page_title = 'Add Customer';
require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="list.php">Customers</a></li>
            <li class="breadcrumb-item active">Add New Customer</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-person-plus me-2"></i>Add New Customer
            </h2>
            <p class="text-muted">Register a new customer in the system</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-form-check me-2"></i>Customer Information
                    </h5>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong><i class="bi bi-exclamation-circle me-2"></i>Validation Errors</strong>
                            <hr>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" novalidate>
                        <!-- Name Field -->
                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control form-control-lg" 
                                id="name" 
                                name="name"
                                value="<?php echo htmlspecialchars($form_data['name']); ?>"
                                placeholder="e.g., John Doe"
                                required
                            >
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Maximum 100 characters
                            </small>
                        </div>

                        <!-- Phone Field -->
                        <div class="mb-4">
                            <label for="phone" class="form-label fw-bold">
                                Phone Number <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="tel" 
                                class="form-control form-control-lg" 
                                id="phone" 
                                name="phone"
                                value="<?php echo htmlspecialchars($form_data['phone']); ?>"
                                placeholder="e.g., +1-555-123-4567"
                                required
                            >
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Phone number must be unique
                            </small>
                        </div>

                        <!-- Email Field -->
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">
                                Email Address
                            </label>
                            <input 
                                type="email" 
                                class="form-control form-control-lg" 
                                id="email" 
                                name="email"
                                value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                placeholder="e.g., john@example.com"
                            >
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Optional - must be valid email format
                            </small>
                        </div>

                        <!-- Address Field -->
                        <div class="mb-4">
                            <label for="address" class="form-label fw-bold">
                                Address
                            </label>
                            <textarea 
                                class="form-control" 
                                id="address" 
                                name="address"
                                rows="3"
                                placeholder="e.g., 123 Main Street, Apartment 4B..."
                            ><?php echo htmlspecialchars($form_data['address']); ?></textarea>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Maximum 255 characters
                            </small>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-2 pt-3">
                            <button 
                                type="submit" 
                                class="btn btn-primary btn-lg"
                            >
                                <i class="bi bi-check-circle me-2"></i>Add Customer
                            </button>
                            <a 
                                href="list.php" 
                                class="btn btn-secondary btn-lg"
                            >
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="col-lg-4">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Tips
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <strong>Required Fields:</strong>
                            <p class="text-muted mb-0 small">Name and Phone are required</p>
                        </li>
                        <li class="mb-3">
                            <strong>Unique Phone:</strong>
                            <p class="text-muted mb-0 small">Each customer must have a unique phone number</p>
                        </li>
                        <li class="mb-3">
                            <strong>Email Format:</strong>
                            <p class="text-muted mb-0 small">If providing email, it must be in valid format</p>
                        </li>
                        <li>
                            <strong>Next Steps:</strong>
                            <p class="text-muted mb-0 small">After adding, you can register vehicles and book appointments</p>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card border-warning mt-3">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-check me-2"></i>Data Privacy
                    </h5>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        Customer information is stored securely and accessible only to authorized staff members. 
                        Phone numbers are unique to prevent duplicates.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
