<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .access-denied-container {
            text-align: center;
            color: white;
            padding: 2rem;
        }
        
        .denied-icon {
            font-size: 8rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }
        
        .denied-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            max-width: 600px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        h1 {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .role-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 1rem;
            border-radius: 10px;
            margin: 1.5rem 0;
        }
        
        .btn-custom {
            background: white;
            color: #667eea;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="access-denied-container">
        <div class="denied-card">
            <div class="denied-icon">
                <i class="bi bi-shield-fill-x"></i>
            </div>
            
            <h1>Access Denied</h1>
            <p class="lead mb-4">You don't have permission to access this page</p>
            
            <?php if (isset($_SESSION['staff_id'])): ?>
                <div class="role-info">
                    <p class="mb-2">
                        <strong>Current User:</strong> <?php echo htmlspecialchars($_SESSION['staff_name'] ?? 'Unknown'); ?>
                    </p>
                    <p class="mb-0">
                        <strong>Your Role:</strong> 
                        <span class="badge bg-light text-dark">
                            <?php echo ucfirst($_SESSION['staff_role'] ?? 'Unknown'); ?>
                        </span>
                    </p>
                </div>
                
                <p class="mt-3">
                    This page requires different permissions. Please contact your administrator if you believe this is an error.
                </p>
                
                <div class="mt-4">
                    <a href="/garage_system/public/staff_dashboard.php" class="btn-custom">
                        <i class="bi bi-house-door me-2"></i>Go to Dashboard
                    </a>
                    <a href="javascript:history.back()" class="btn-custom">
                        <i class="bi bi-arrow-left me-2"></i>Go Back
                    </a>
                </div>
            <?php else: ?>
                <p class="mt-3">Please log in to continue.</p>
                <div class="mt-4">
                    <a href="/garage_system/public/staff_login.php" class="btn-custom">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="mt-4 pt-3 border-top border-white border-opacity-25">
                <p class="small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Need different access? Contact your system administrator
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
