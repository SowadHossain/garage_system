<?php
// public/staff_login.php - Staff Login Page (EMAIL-based, DB-compatible, same UI)

session_start();

require_once __DIR__ . "/../config/db.php";

// If already logged in as staff, go to dashboard
if (!empty($_SESSION['staff_id'])) {
    header("Location: staff_dashboard.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Please enter both email and password.";
    } else {
        // Prepared statement to prevent SQL injection
        $stmt = $conn->prepare("
            SELECT
                s.staff_id,
                s.name,
                r.role_name,
                s.password_hash,
                s.is_active
            FROM staff s
            JOIN roles r ON r.role_id = s.role_id
            WHERE s.email = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();
        $stmt->close();

        if ($staff && (int)$staff['is_active'] === 1) {
            if (password_verify($password, $staff['password_hash'])) {
                // Correct password
                $_SESSION['staff_id']   = $staff['staff_id'];
                $_SESSION['staff_name'] = $staff['name'];
                $_SESSION['staff_role'] = $staff['role_name'];
                $_SESSION['staff_email'] = $email;

                // Redirect to role-specific dashboard (correct paths)
                $role = $staff['role_name'];
                if ($role === 'admin') {
                    $redirect_url = 'admin_portal/admin_dashboard.php';
                } elseif ($role === 'receptionist') {
                    $redirect_url = 'receptionist_portal/receptionist_dashboard.php';
                } elseif ($role === 'mechanic') {
                    $redirect_url = 'mechanic_portal/mechanic_dashboard.php';
                } else {
                    $redirect_url = 'staff_dashboard.php';
                }

                header("Location: " . $redirect_url);
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0a58ca;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }
        
        .login-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            font-size: 0.875rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .login-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .login-icon i {
            font-size: 1.75rem;
        }
        
        .login-body {
            padding: 2rem 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
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
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
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
        
        .login-footer {
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            font-size: 0.875rem;
        }
        
        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 8px;
            padding: 0.875rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .form-check-label {
            font-size: 0.875rem;
            color: var(--secondary-color);
        }
        
        .forgot-password {
            font-size: 0.875rem;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .user-type-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            margin-top: 0.5rem;
        }
        
        /* Mobile Responsive */
        @media (max-width: 576px) {
            .login-header {
                padding: 1.5rem 1rem;
            }
            
            .login-header h1 {
                font-size: 1.25rem;
            }
            
            .login-body {
                padding: 1.5rem 1rem;
            }
            
            .login-icon {
                width: 50px;
                height: 50px;
            }
            
            .login-icon i {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="bi bi-person-badge"></i>
                </div>
                <h1>Screw Dheela</h1>
                <p>Management System</p>
                <div class="user-type-badge">Staff Portal</div>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="staff_login.php" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-person-circle me-1"></i>Email
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email"
                                   name="email"
                                   id="email"
                                   class="form-control"
                                   placeholder="Enter your email"
                                   required
                                   autofocus
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i>Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control" 
                                   placeholder="Enter your password" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </form>
                
                <div class="divider">
                    <span>OR</span>
                </div>
                
                <div class="text-center">
                    <a href="customer_portal/customer_login.php" class="btn btn-outline-secondary w-100" style="border-radius: 8px; padding: 0.75rem;">
                        <i class="bi bi-people-fill me-2"></i>Customer Login
                    </a>
                </div>
            </div>
            
            <div class="login-footer">
                <p class="mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Need an account? Contact your administrator
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
