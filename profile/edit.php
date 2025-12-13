<?php
// Profile Edit Page (Universal for Staff & Customers)
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$page_title = "Edit Profile";

// Determine user type
if (isset($_SESSION['staff_id'])) {
    $user_type = 'staff';
    $user_id = $_SESSION['staff_id'];
    $table = 'staff';
    $id_field = 'staff_id';
} elseif (isset($_SESSION['customer_id'])) {
    $user_type = 'customer';
    $user_id = $_SESSION['customer_id'];
    $table = 'customers';
    $id_field = 'customer_id';
} else {
    header("Location: ../public/welcome.php");
    exit;
}

$success = '';
$error = '';

// Fetch current user data
$query = "SELECT * FROM $table WHERE $id_field = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        if (empty($name) || empty($email)) {
            $error = "Name and email are required.";
        } else {
            // Check if email already exists for another user
            $check_query = "SELECT $id_field FROM $table WHERE email = ? AND $id_field != ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("si", $email, $user_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "Email already in use by another user.";
            } else {
                // Update profile
                $update_query = "UPDATE $table SET name = ?, email = ?, phone = ?, bio = ? WHERE $id_field = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssssi", $name, $email, $phone, $bio, $user_id);
                
                if ($update_stmt->execute()) {
                    // Update session
                    if ($user_type === 'staff') {
                        $_SESSION['staff_name'] = $name;
                    } else {
                        $_SESSION['customer_name'] = $name;
                        $_SESSION['customer_email'] = $email;
                    }
                    
                    // Log activity
                    logCRUD('update', $table, $user_id, 
                        ['name' => $user['name'], 'email' => $user['email']], 
                        ['name' => $name, 'email' => $email]
                    );
                    
                    $success = "Profile updated successfully!";
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $user['phone'] = $phone;
                    $user['bio'] = $bio;
                } else {
                    $error = "Failed to update profile.";
                }
            }
        }
    }
    
    elseif ($action === 'upload_photo') {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
            $file = $_FILES['profile_photo'];
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                $error = "Invalid file type. Only JPG, PNG, and GIF allowed.";
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = "File too large. Maximum 5MB allowed.";
            } else {
                // Create uploads directory if not exists
                $upload_dir = __DIR__ . "/../uploads/profiles/$user_type/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $filename = $user_id . '_' . time() . '.' . $ext;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Delete old photo
                    if ($user['profile_photo'] && file_exists($upload_dir . $user['profile_photo'])) {
                        unlink($upload_dir . $user['profile_photo']);
                    }
                    
                    // Update database
                    $update_query = "UPDATE $table SET profile_photo = ? WHERE $id_field = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("si", $filename, $user_id);
                    $update_stmt->execute();
                    
                    // Log activity
                    logActivity(
                        'Updated Profile Photo',
                        LOG_ACTION_UPDATE,
                        $table,
                        $user_id,
                        ['profile_photo' => $user['profile_photo']],
                        ['profile_photo' => $filename],
                        'Uploaded new profile photo'
                    );
                    
                    $success = "Profile photo uploaded successfully!";
                    $user['profile_photo'] = $filename;
                } else {
                    $error = "Failed to upload file.";
                }
            }
        } else {
            $error = "No file selected or upload error.";
        }
    }
}

// Calculate profile completion
$completion = 0;
if (!empty($user['name'])) $completion += 20;
if (!empty($user['email'])) $completion += 20;
if (!empty($user['phone'])) $completion += 15;
if (!empty($user['bio'])) $completion += 15;
if (!empty($user['profile_photo'])) $completion += 15;
if (!empty($user['two_factor_enabled'])) $completion += 15;

// Update completion in database
$conn->query("UPDATE $table SET profile_completed = $completion WHERE $id_field = $user_id");

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.profile-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px 0;
    margin: -20px -15px 30px -15px;
}

.profile-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 30px;
    margin-bottom: 20px;
}

.profile-photo-section {
    text-align: center;
    margin-bottom: 30px;
}

.profile-photo {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid #667eea;
    margin-bottom: 15px;
}

.progress-circle {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 20px auto;
}

.progress-circle svg {
    transform: rotate(-90deg);
}

.progress-circle-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.5rem;
    font-weight: bold;
    color: #667eea;
}

.tab-content {
    padding: 20px 0;
}

.badge-2fa {
    font-size: 0.9rem;
    padding: 8px 15px;
}
</style>

<div class="profile-container">
    <div class="container">
        <h2 class="text-white mb-3">
            <i class="bi bi-person-circle me-2"></i>My Profile
        </h2>
        <p class="text-white-50">Manage your account settings and preferences</p>
    </div>
</div>

<div class="container">
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Photo & Completion -->
        <div class="col-md-4">
            <div class="profile-card">
                <div class="profile-photo-section">
                    <?php 
                    $photo_path = !empty($user['profile_photo']) 
                        ? "/garage_system/uploads/profiles/$user_type/" . $user['profile_photo']
                        : "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&size=150&background=667eea&color=fff";
                    ?>
                    <img src="<?php echo $photo_path; ?>" alt="Profile Photo" class="profile-photo">
                    
                    <h5><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted">
                        <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    
                    <!-- Upload Photo Form -->
                    <form method="POST" enctype="multipart/form-data" class="mt-3">
                        <input type="hidden" name="action" value="upload_photo">
                        <input type="file" name="profile_photo" id="profile_photo" class="d-none" accept="image/*" onchange="this.form.submit()">
                        <label for="profile_photo" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-camera me-1"></i>Change Photo
                        </label>
                    </form>
                </div>
                
                <!-- Profile Completion -->
                <div class="text-center">
                    <h6>Profile Completion</h6>
                    <div class="progress-circle">
                        <svg width="120" height="120">
                            <circle cx="60" cy="60" r="54" stroke="#e9ecef" stroke-width="8" fill="none"></circle>
                            <circle cx="60" cy="60" r="54" stroke="#667eea" stroke-width="8" fill="none" 
                                    stroke-dasharray="<?php echo 2 * 3.14159 * 54; ?>" 
                                    stroke-dashoffset="<?php echo 2 * 3.14159 * 54 * (1 - $completion/100); ?>"></circle>
                        </svg>
                        <div class="progress-circle-text"><?php echo $completion; ?>%</div>
                    </div>
                    <small class="text-muted">Complete your profile to unlock all features</small>
                </div>
                
                <!-- Quick Stats -->
                <hr>
                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>
                            <i class="bi bi-shield-check me-1"></i>2FA
                        </span>
                        <span class="badge <?php echo $user['two_factor_enabled'] ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $user['two_factor_enabled'] ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </div>
                    <?php if ($user_type === 'staff'): ?>
                        <div class="d-flex justify-content-between">
                            <span>
                                <i class="bi bi-person-badge me-1"></i>Role
                            </span>
                            <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Profile Forms -->
        <div class="col-md-8">
            <div class="profile-card">
                <!-- Tabs -->
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-tab">
                            <i class="bi bi-person me-1"></i>Profile
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#password-tab">
                            <i class="bi bi-key me-1"></i>Password
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#security-tab">
                            <i class="bi bi-shield-lock me-1"></i>Security
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile-tab">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Bio</label>
                                    <textarea name="bio" class="form-control" rows="4" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i>Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Password Tab -->
                    <div class="tab-pane fade" id="password-tab">
                        <form action="change_password.php" method="POST">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                For security, you'll need to enter your current password to change it.
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Current Password *</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">New Password *</label>
                                <input type="password" name="new_password" class="form-control" required minlength="8">
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password *</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-key me-1"></i>Change Password
                            </button>
                        </form>
                    </div>

                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security-tab">
                        <h5>Two-Factor Authentication (2FA)</h5>
                        <p class="text-muted">Add an extra layer of security to your account</p>
                        
                        <?php if ($user['two_factor_enabled']): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-shield-check me-2"></i>2FA is currently <strong>enabled</strong>
                            </div>
                            <a href="disable_2fa.php" class="btn btn-outline-danger">
                                <i class="bi bi-shield-x me-1"></i>Disable 2FA
                            </a>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-shield-exclamation me-2"></i>2FA is currently <strong>disabled</strong>
                            </div>
                            <a href="setup_2fa.php" class="btn btn-success">
                                <i class="bi bi-shield-check me-1"></i>Enable 2FA
                            </a>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <h5>Security Questions</h5>
                        <p class="text-muted">Set up security questions for account recovery</p>
                        <a href="security_questions.php" class="btn btn-outline-primary">
                            <i class="bi bi-question-circle me-1"></i>Manage Security Questions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
