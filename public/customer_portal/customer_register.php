<?php
// public/customer_register.php - Customer Registration Page

session_start();

require_once __DIR__ . "/../config/db.php";

// If already logged in, redirect to dashboard
if (!empty($_SESSION['customer_id'])) {
    header("Location: customer_dashboard.php");
    exit;
}

$error = "";
$success = "";
$form_data = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Store form data for repopulation
    $form_data = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'address' => $address
    ];
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ? LIMIT 1");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "An account with this email already exists.";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Hash the password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert new customer
            $insert_stmt = $conn->prepare("INSERT INTO customers (name, email, phone, address, password_hash, is_email_verified, created_at) 
                                          VALUES (?, ?, ?, ?, ?, 0, NOW())");
            $insert_stmt->bind_param("sssss", $name, $email, $phone, $address, $password_hash);
            
            if ($insert_stmt->execute()) {
                $customer_id = $insert_stmt->insert_id;
                $insert_stmt->close();
                
                // Auto-login the user
                $_SESSION['customer_id'] = $customer_id;
                $_SESSION['customer_name'] = $name;
                $_SESSION['customer_email'] = $email;
                $_SESSION['customer_phone'] = $phone;
                
                header("Location: customer_dashboard.php?welcome=1");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
                $insert_stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #198754;
            --primary-dark: #146c43;
            --secondary-color: #6c757d;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .register-container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .register-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        
        .register-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
            font-size: 0.875rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .register-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .register-icon i {
            font-size: 1.75rem;
        }
        
        .register-body {
            padding: 2rem 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-label .required {
            color: #dc3545;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            font-size: 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-right: none;
            border-radius: 8px 0 0 8px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 8px 8px 0;
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 8px;
            padding: 0.875rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .register-footer {
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            font-size: 0.875rem;
        }
        
        .register-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 8px;
            padding: 0.875rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .password-strength {
            height: 4px;
            background: #e9ecef;
            border-radius: 4px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: width 0.3s, background-color 0.3s;
            width: 0;
        }
        
        .password-strength-bar.weak {
            width: 33%;
            background: #dc3545;
        }
        
        .password-strength-bar.medium {
            width: 66%;
            background: #ffc107;
        }
        
        .password-strength-bar.strong {
            width: 100%;
            background: #198754;
        }
        
        .password-requirements {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        
        .password-requirements li {
            margin-bottom: 0.25rem;
        }
        
        .password-requirements .met {
            color: #198754;
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: var(--secondary-color);
            font-size: 0.875rem;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        
        .divider span {
            padding: 0 1rem;
        }
        
        /* Mobile Responsive */
        @media (max-width: 576px) {
            body {
                padding: 1rem;
            }
            
            .register-header {
                padding: 1.5rem 1rem;
            }
            
            .register-header h1 {
                font-size: 1.5rem;
            }
            
            .register-body {
                padding: 1.5rem 1rem;
            }
            
            .register-icon {
                width: 50px;
                height: 50px;
            }
            
            .register-icon i {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="register-icon">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h1>Create Your Account</h1>
                <p>Join Screw Dheela today</p>
            </div>
            
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="customer_register.php" novalidate id="registerForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            <i class="bi bi-person-fill me-1"></i>Full Name <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="form-control" 
                                   placeholder="Enter your full name" 
                                   required 
                                   autofocus
                                   value="<?php echo htmlspecialchars($form_data['name']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope-fill me-1"></i>Email Address <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   class="form-control" 
                                   placeholder="Enter your email" 
                                   required
                                   value="<?php echo htmlspecialchars($form_data['email']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">
                            <i class="bi bi-telephone-fill me-1"></i>Phone Number <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-phone"></i></span>
                            <input type="tel" 
                                   name="phone" 
                                   id="phone" 
                                   class="form-control" 
                                   placeholder="+1234567890" 
                                   required
                                   value="<?php echo htmlspecialchars($form_data['phone']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">
                            <i class="bi bi-geo-alt-fill me-1"></i>Address
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-house"></i></span>
                            <input type="text" 
                                   name="address" 
                                   id="address" 
                                   class="form-control" 
                                   placeholder="Enter your address"
                                   value="<?php echo htmlspecialchars($form_data['address']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i>Password <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control" 
                                   placeholder="Create a password" 
                                   required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <ul class="password-requirements" id="passwordReqs">
                            <li id="req-length">At least 6 characters</li>
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i>Confirm Password <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirm_password" 
                                   class="form-control" 
                                   placeholder="Re-enter your password" 
                                   required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-register">
                        <i class="bi bi-person-check-fill me-2"></i>Create Account
                    </button>
                </form>
                
                <div class="divider">
                    <span>Already have an account?</span>
                </div>
                
                <div class="text-center">
                    <a href="customer_login.php" class="btn btn-outline-secondary w-100" style="border-radius: 8px; padding: 0.75rem;">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </a>
                </div>
            </div>
            
            <div class="register-footer">
                <p class="mb-0">
                    <i class="bi bi-shield-check me-1"></i>
                    Your data is safe and secure with us
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const password = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const reqLength = document.getElementById('req-length');
            
            // Check length
            if (password.length >= 6) {
                reqLength.classList.add('met');
            } else {
                reqLength.classList.remove('met');
            }
            
            // Calculate strength
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            // Update strength bar
            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>
