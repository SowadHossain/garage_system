<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Screw Dheela Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #11998e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .welcome-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .welcome-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .welcome-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .welcome-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .welcome-header p {
            font-size: 1.125rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        
        .logo-icon i {
            font-size: 2.5rem;
        }
        
        .welcome-body {
            padding: 3rem 2rem;
        }
        
        .login-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .login-option {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 2px solid #e9ecef;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .login-option:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }
        
        .login-option.staff {
            border-color: #0d6efd;
        }
        
        .login-option.staff:hover {
            background: linear-gradient(135deg, #e7f1ff 0%, #ffffff 100%);
            border-color: #0d6efd;
        }
        
        .login-option.customer {
            border-color: #198754;
        }
        
        .login-option.customer:hover {
            background: linear-gradient(135deg, #e7f7ef 0%, #ffffff 100%);
            border-color: #198754;
        }
        
        .option-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }
        
        .login-option.staff .option-icon {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
        }
        
        .login-option.customer .option-icon {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            color: white;
        }
        
        .option-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #212529;
        }
        
        .option-description {
            font-size: 0.95rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .option-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .login-option.staff .option-button {
            background: #0d6efd;
            color: white;
        }
        
        .login-option.customer .option-button {
            background: #198754;
            color: white;
        }
        
        .features {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
            margin-top: 2rem;
        }
        
        .features h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
            color: #212529;
        }
        
        .feature-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .feature-item {
            display: flex;
            align-items: start;
            gap: 0.75rem;
        }
        
        .feature-item i {
            color: #667eea;
            font-size: 1.25rem;
            margin-top: 0.25rem;
        }
        
        .feature-item span {
            font-size: 0.9rem;
            color: #495057;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .welcome-header {
                padding: 2rem 1.5rem;
            }
            
            .welcome-header h1 {
                font-size: 1.75rem;
            }
            
            .welcome-header p {
                font-size: 1rem;
            }
            
            .logo-icon {
                width: 60px;
                height: 60px;
            }
            
            .logo-icon i {
                font-size: 2rem;
            }
            
            .welcome-body {
                padding: 2rem 1.5rem;
            }
            
            .login-options {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .option-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .option-title {
                font-size: 1.25rem;
            }
            
            .feature-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="welcome-card">
            <div class="welcome-header">
                <div class="logo-icon">
                    <i class="bi bi-gear-wide-connected"></i>
                </div>
                <h1>Screw Dheela</h1>
                <p>Management System</p>
            </div>
            
            <div class="welcome-body">
                <h2 class="text-center mb-4" style="color: #212529; font-weight: 700;">Choose Your Portal</h2>
                
                <div class="login-options">
                    <a href="staff_login.php" class="login-option staff">
                        <div class="option-icon">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div class="option-title">Staff Portal</div>
                        <p class="option-description">
                            Access staff dashboard, manage appointments, jobs, billing, and customer communications.
                        </p>
                        <span class="option-button">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Staff Login
                        </span>
                    </a>
                    
                    <a href="customer_login.php" class="login-option customer">
                        <div class="option-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="option-title">Customer Portal</div>
                        <p class="option-description">
                            Book appointments, track your vehicle service history, view bills, and chat with our team.
                        </p>
                        <span class="option-button">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Customer Login
                        </span>
                    </a>
                </div>
                
                <div class="features">
                    <h3><i class="bi bi-stars me-2"></i>System Features</h3>
                    <div class="feature-list">
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Appointment Management</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Job Tracking</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Digital Billing</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Real-time Chat</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Email Notifications</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Vehicle History</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
