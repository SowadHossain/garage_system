<?php
session_start();
require_once '../config/db.php';
require_once '../includes/role_check.php';

// Check if user is staff with customer management permission
requireRole(['admin', 'receptionist']);

$errors = [];
$success = false;
$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($customer_id <= 0) {
    header('Location: list.php');
    exit;
}

// Load customer data
$customer_stmt = $conn->prepare("SELECT customer_id, name, phone, email, address, created_at FROM customers WHERE customer_id = ?");
$customer_stmt->bind_param("i", $customer_id);
$customer_stmt->execute();
$customer_result = $customer_stmt->get_result();

if ($customer_result->num_rows === 0) {
    header('Location: list.php');
    exit;
}

$customer = $customer_result->fetch_assoc();
$customer_stmt->close();

$form_data = [
    'name' => $customer['name'],
    'phone' => $customer['phone'],
    'email' => $customer['email'],
    'address' => $customer['address']
];

// Handle delete
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
        // Check if customer has any active appointments
        $check_appointments = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE customer_id = ? AND status IN ('booked', 'pending')");
        $check_appointments->bind_param("i", $customer_id);
        $check_appointments->execute();
        $appt_count = $check_appointments->get_result()->fetch_assoc()['count'];
        $check_appointments->close();
        
        if ($appt_count > 0) {
            $errors[] = 'Cannot delete customer with active appointments. Cancel all appointments first.';
        } else {
            // Delete customer
            $delete_stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
            $delete_stmt->bind_param("i", $customer_id);
            
            if ($delete_stmt->execute()) {
                // Log activity
                require_once '../includes/activity_logger.php';
                logActivity(
                    'staff',
                    $_SESSION['staff_id'],
                    'delete',
                    'customer',
                    $customer_id,
                    ['name' => $customer['name'], 'phone' => $customer['phone']],
                    null
                );
                
                header("Location: list.php?success=Customer%20deleted%20successfully");
                exit;
            } else {
                $errors[] = 'Error deleting customer: ' . $conn->error;
            }
            $delete_stmt->close();
        }
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'delete')) {
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
        // Check if phone is unique (excluding current customer)
        $check_phone = $conn->prepare("SELECT customer_id FROM customers WHERE phone = ? AND customer_id != ?");
        $check_phone->bind_param("si", $form_data['phone'], $customer_id);
        $check_phone->execute();
        if ($check_phone->get_result()->num_rows > 0) {
            $errors[] = 'This phone number is already registered to another customer.';
        }
        $check_phone->close();
    }
    
    if (!empty($form_data['email'])) {
        if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            // Check if email is unique (excluding current customer)
            $check_email = $conn->prepare("SELECT customer_id FROM customers WHERE email = ? AND customer_id != ? AND email != ''");
            $check_email->bind_param("si", $form_data['email'], $customer_id);
            $check_email->execute();
            if ($check_email->get_result()->num_rows > 0) {
                $errors[] = 'This email address is already registered to another customer.';
            }
            $check_email->close();
        }
    }
    
    if (strlen($form_data['address']) > 255) {
        $errors[] = 'Address must not exceed 255 characters.';
    }
    
    // If no errors, update database
    if (empty($errors)) {
        $update_sql = "UPDATE customers SET name = ?, phone = ?, email = ?, address = ? WHERE customer_id = ?";
        $stmt = $conn->prepare($update_sql);
        
        if ($stmt) {
            $stmt->bind_param(
                "ssssi",
                $form_data['name'],
                $form_data['phone'],
                $form_data['email'],
                $form_data['address'],
                $customer_id
            );
            
            if ($stmt->execute()) {
                // Log activity
                require_once '../includes/activity_logger.php';
                logActivity(
                    'staff',
                    $_SESSION['staff_id'],
                    'update',
                    'customer',
                    $customer_id,
                    ['name' => $customer['name'], 'phone' => $customer['phone']],
                    ['name' => $form_data['name'], 'phone' => $form_data['phone']]
                );
                
                header("Location: list.php?success=Customer%20updated%20successfully");
                exit;
            } else {
                $errors[] = 'Error updating customer: ' . $conn->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
    }
}

$page_title = 'Edit Customer';
require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="list.php">Customers</a></li>
            <li class="breadcrumb-item active">Edit Customer</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-pencil me-2"></i>Edit Customer
            </h2>
            <p class="text-muted">Update customer information</p>
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
                            <strong><i class="bi bi-exclamation-circle me-2"></i>Error</strong>
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
                        <!-- Customer ID (Read-only) -->
                        <div class="mb-3">
                            <label class="form-label text-muted small">Customer ID</label>
                            <input type="text" class="form-control" value="<?php echo $customer_id; ?>" disabled>
                        </div>

                        <!-- Created Date (Read-only) -->
                        <div class="mb-4">
                            <label class="form-label text-muted small">Joined Date</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                value="<?php echo date('M d, Y h:i A', strtotime($customer['created_at'])); ?>" 
                                disabled
                            >
                        </div>

                        <hr>

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
                                <i class="bi bi-check-circle me-2"></i>Save Changes
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

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Info Card -->
            <div class="card border-info mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>Customer Details
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <small class="text-muted">Customer ID</small>
                            <div class="fw-bold">#<?php echo $customer_id; ?></div>
                        </li>
                        <li class="mb-3">
                            <small class="text-muted">Current Phone</small>
                            <div class="fw-bold"><?php echo htmlspecialchars($customer['phone']); ?></div>
                        </li>
                        <li class="mb-3">
                            <small class="text-muted">Member Since</small>
                            <div class="fw-bold"><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Delete Card -->
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-trash me-2"></i>Danger Zone
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Delete this customer record permanently. This action cannot be undone.
                    </p>
                    <button 
                        class="btn btn-danger btn-sm w-100" 
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteModal"
                    >
                        <i class="bi bi-trash me-1"></i>Delete Customer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete Customer?
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete <strong><?php echo htmlspecialchars($customer['name']); ?></strong>?
                </p>
                <p class="text-muted small mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    This action cannot be undone. All associated vehicles will also be deleted.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="confirm_delete" value="yes">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Delete Permanently
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
