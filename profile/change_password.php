<?php
// Change Password Page
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$page_title = "Change Password";

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Get current password hash
        $query = "SELECT password_hash FROM $table WHERE $id_field = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!password_verify($current_password, $user['password_hash'])) {
            $error = "Current password is incorrect.";
            
            // Log failed attempt
            logActivity(
                'Failed Password Change',
                LOG_ACTION_UPDATE,
                $table,
                $user_id,
                null,
                null,
                'User entered incorrect current password',
                LOG_SEVERITY_WARNING,
                'failed'
            );
        } else {
            // Check password history (prevent reuse of last 5 passwords)
            $history_query = "SELECT password_hash FROM password_history 
                            WHERE user_type = ? AND user_id = ? 
                            ORDER BY changed_at DESC LIMIT 5";
            $history_stmt = $conn->prepare($history_query);
            $history_stmt->bind_param("si", $user_type, $user_id);
            $history_stmt->execute();
            $history_result = $history_stmt->get_result();
            
            $password_reused = false;
            while ($old = $history_result->fetch_assoc()) {
                if (password_verify($new_password, $old['password_hash'])) {
                    $password_reused = true;
                    break;
                }
            }
            
            if ($password_reused) {
                $error = "Cannot reuse any of your last 5 passwords.";
            } else {
                // Hash new password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_query = "UPDATE $table SET password_hash = ?, last_password_change = NOW() WHERE $id_field = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $new_hash, $user_id);
                
                if ($update_stmt->execute()) {
                    // Save to password history
                    $history_insert = "INSERT INTO password_history (user_type, user_id, password_hash, changed_by) VALUES (?, ?, ?, ?)";
                    $history_insert_stmt = $conn->prepare($history_insert);
                    $changed_by = $_SESSION['staff_name'] ?? $_SESSION['customer_name'] ?? 'User';
                    $history_insert_stmt->bind_param("siss", $user_type, $user_id, $new_hash, $changed_by);
                    $history_insert_stmt->execute();
                    
                    // Log successful password change
                    logActivity(
                        'Changed Password',
                        LOG_ACTION_UPDATE,
                        $table,
                        $user_id,
                        null,
                        null,
                        'User changed their password successfully',
                        LOG_SEVERITY_INFO,
                        'success'
                    );
                    
                    $success = "Password changed successfully!";
                } else {
                    $error = "Failed to update password. Please try again.";
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.password-container {
    max-width: 600px;
    margin: 50px auto;
}

.password-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 30px;
}

.password-strength {
    height: 5px;
    background: #e9ecef;
    border-radius: 3px;
    margin-top: 5px;
    overflow: hidden;
}

.password-strength-bar {
    height: 100%;
    transition: all 0.3s;
}

.strength-weak { background: #dc3545; width: 33%; }
.strength-medium { background: #ffc107; width: 66%; }
.strength-strong { background: #198754; width: 100%; }
</style>

<div class="container">
    <div class="password-container">
        <div class="password-card">
            <div class="text-center mb-4">
                <div class="mb-3">
                    <i class="bi bi-key-fill" style="font-size: 3rem; color: #667eea;"></i>
                </div>
                <h3>Change Password</h3>
                <p class="text-muted">Update your account password</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                    <div class="mt-2">
                        <a href="edit.php" class="btn btn-sm btn-success">Return to Profile</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
                <form method="POST" id="passwordForm">
                    <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8" oninput="checkPasswordStrength()">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <small class="text-muted" id="strengthText">Minimum 8 characters</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password *</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key me-1"></i>Change Password
                        </button>
                        <a href="edit.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Cancel
                        </a>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="alert alert-info mb-0">
                    <h6><i class="bi bi-shield-check me-2"></i>Password Requirements:</h6>
                    <ul class="mb-0">
                        <li>Minimum 8 characters long</li>
                        <li>Cannot reuse your last 5 passwords</li>
                        <li>Use a mix of letters, numbers, and symbols for stronger security</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = event.currentTarget.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function checkPasswordStrength() {
    const password = document.getElementById('new_password').value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    strengthBar.className = 'password-strength-bar';
    
    if (strength <= 1) {
        strengthBar.classList.add('strength-weak');
        strengthText.textContent = 'Weak password';
        strengthText.style.color = '#dc3545';
    } else if (strength <= 3) {
        strengthBar.classList.add('strength-medium');
        strengthText.textContent = 'Medium strength';
        strengthText.style.color = '#ffc107';
    } else {
        strengthBar.classList.add('strength-strong');
        strengthText.textContent = 'Strong password';
        strengthText.style.color = '#198754';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
