<?php
session_start();
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../includes/role_check.php";

// Check if user is admin
if (!isset($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'admin') {
    header('Location: ../staff_login.php'); // FIXED path
    exit;
}

$errors = [];
$success = false;
$form_data = [
    'name' => '',
    'username' => '', // kept for UI only (NOT saved in DB)
    'email' => '',
    'role' => 'receptionist',
    'password' => ''
];

$page_title = 'Add New Staff Member';
require_once __DIR__ . "/../../includes/header.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $form_data['name'] = isset($_POST['name']) ? trim($_POST['name']) : '';
    $form_data['username'] = isset($_POST['username']) ? trim($_POST['username']) : ''; // UI only
    $form_data['email'] = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
    $form_data['role'] = isset($_POST['role']) ? trim($_POST['role']) : 'receptionist';
    $form_data['password'] = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? (string)$_POST['password_confirm'] : '';

    // Validation
    if (empty($form_data['name'])) {
        $errors[] = 'Staff name is required.';
    } elseif (strlen($form_data['name']) > 150) {
        $errors[] = 'Staff name must not exceed 150 characters.';
    }

    // Username validation stays (because your UI has it), but NO DB checks, NO insert
    if (empty($form_data['username'])) {
        $errors[] = 'Username is required.';
    } elseif (strlen($form_data['username']) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $form_data['username'])) {
        $errors[] = 'Username can only contain letters, numbers, underscores, and hyphens.';
    }

    // Email validation (optional in your UI)
    if (!empty($form_data['email'])) {
        if (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            // Check if email is unique
            $check_email = $conn->prepare("SELECT staff_id FROM staff WHERE email = ? AND email != '' LIMIT 1");
            $check_email->bind_param("s", $form_data['email']);
            $check_email->execute();
            if ($check_email->get_result()->num_rows > 0) {
                $errors[] = 'This email address is already registered.';
            }
            $check_email->close();
        }
    }

    if (!in_array($form_data['role'], ['admin', 'receptionist', 'mechanic'], true)) {
        $errors[] = 'Invalid role selected.';
    }

    if (empty($form_data['password'])) {
        $errors[] = 'Password is required.';
    } elseif (strlen($form_data['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($form_data['password'] !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // If no errors, insert into database
    if (empty($errors)) {
        // Map role_name -> role_id
        $role_id = null;
        $role_stmt = $conn->prepare("SELECT role_id FROM roles WHERE role_name = ? LIMIT 1");
        $role_stmt->bind_param("s", $form_data['role']);
        $role_stmt->execute();
        $role_row = $role_stmt->get_result()->fetch_assoc();
        $role_stmt->close();

        if (!$role_row) {
            $errors[] = 'Invalid role selected (role not found in DB).';
        } else {
            $role_id = (int)$role_row['role_id'];
            $password_hash = password_hash($form_data['password'], PASSWORD_BCRYPT);

            // NOTE: staff table does NOT have username/role columns, it uses role_id
            // Also: your form has no phone field, so we store empty string.
            $phone = '';

            $insert_sql = "
                INSERT INTO staff (role_id, name, email, phone, password_hash, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, 1, NOW())
            ";
            $stmt = $conn->prepare($insert_sql);

            if ($stmt) {
                $stmt->bind_param(
                    "issss",
                    $role_id,
                    $form_data['name'],
                    $form_data['email'],
                    $phone,
                    $password_hash
                );

                if ($stmt->execute()) {

                    header("Location: manage_staff.php?success=Staff%20member%20added%20successfully");
                    exit;
                } else {
                    $errors[] = 'Error adding staff: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = 'Database error: ' . $conn->error;
            }
        }
    }
}
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin_dashboard.php">Admin Dashboard</a></li>
            <li class="breadcrumb-item"><a href="manage_staff.php">Manage Staff</a></li>
            <li class="breadcrumb-item active">Add New Staff</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-person-plus me-2"></i>Add New Staff Member
            </h2>
            <p class="text-muted">Create a new staff account with appropriate permissions</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-form-check me-2"></i>Staff Account Information
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
                                placeholder="e.g., John Mechanic"
                                required
                            >
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Maximum 150 characters
                            </small>
                        </div>

                        <!-- Username Field -->
                        <div class="mb-4">
                            <label for="username" class="form-label fw-bold">
                                Username <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                class="form-control form-control-lg"
                                id="username"
                                name="username"
                                value="<?php echo htmlspecialchars($form_data['username']); ?>"
                                placeholder="e.g., john_mechanic"
                                required
                            >
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Min 3 characters, letters/numbers/underscore only
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

                        <!-- Role Field -->
                        <div class="mb-4">
                            <label for="role" class="form-label fw-bold">
                                Role <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-lg" id="role" name="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="receptionist" <?php echo $form_data['role'] === 'receptionist' ? 'selected' : ''; ?>>
                                    Receptionist (Book appointments, manage customers)
                                </option>
                                <option value="mechanic" <?php echo $form_data['role'] === 'mechanic' ? 'selected' : ''; ?>>
                                    Mechanic (Update jobs, manage work orders)
                                </option>
                                <option value="admin" <?php echo $form_data['role'] === 'admin' ? 'selected' : ''; ?>>
                                    Administrator (Full system access)
                                </option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Role determines what features staff can access
                            </small>
                        </div>

                        <hr class="my-4">

                        <!-- Password Field -->
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">
                                Password <span class="text-danger">*</span>
                            </label>
                            <input
                                type="password"
                                class="form-control form-control-lg"
                                id="password"
                                name="password"
                                placeholder="Enter secure password"
                                required
                            >
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Minimum 6 characters, use mix of letters and numbers
                            </small>
                        </div>

                        <!-- Confirm Password Field -->
                        <div class="mb-4">
                            <label for="password_confirm" class="form-label fw-bold">
                                Confirm Password <span class="text-danger">*</span>
                            </label>
                            <input
                                type="password"
                                class="form-control form-control-lg"
                                id="password_confirm"
                                name="password_confirm"
                                placeholder="Re-enter password"
                                required
                            >
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Must match password above
                            </small>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-2 pt-3">
                            <button
                                type="submit"
                                class="btn btn-primary btn-lg"
                            >
                                <i class="bi bi-check-circle me-2"></i>Create Staff Account
                            </button>
                            <a
                                href="manage_staff.php"
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
                        <i class="bi bi-shield-lock me-2"></i>Role Permissions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong class="d-block mb-2">
                            <i class="bi bi-person-badge text-warning me-2"></i>Receptionist
                        </strong>
                        <ul class="list-unstyled small text-muted ps-3">
                            <li><i class="bi bi-check me-1"></i>Book appointments</li>
                            <li><i class="bi bi-check me-1"></i>Manage customers</li>
                            <li><i class="bi bi-check me-1"></i>View jobs</li>
                            <li><i class="bi bi-x me-1"></i>No admin features</li>
                        </ul>
                    </div>
                    <div class="mb-3">
                        <strong class="d-block mb-2">
                            <i class="bi bi-tools text-success me-2"></i>Mechanic
                        </strong>
                        <ul class="list-unstyled small text-muted ps-3">
                            <li><i class="bi bi-check me-1"></i>Manage jobs</li>
                            <li><i class="bi bi-check me-1"></i>Update job status</li>
                            <li><i class="bi bi-check me-1"></i>Add services to jobs</li>
                            <li><i class="bi bi-x me-1"></i>No admin features</li>
                        </ul>
                    </div>
                    <div>
                        <strong class="d-block mb-2">
                            <i class="bi bi-shield-check text-danger me-2"></i>Administrator
                        </strong>
                        <ul class="list-unstyled small text-muted ps-3">
                            <li><i class="bi bi-check me-1"></i>All features</li>
                            <li><i class="bi bi-check me-1"></i>Manage staff</li>
                            <li><i class="bi bi-check me-1"></i>View reports</li>
                            <li><i class="bi bi-check me-1"></i>Full system access</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card border-warning mt-3">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>Important
                    </h5>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        Passwords are hashed securely using bcrypt. Staff should change their password after first login.
                        Admin accounts should have strong passwords.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../../includes/footer.php"; ?>
