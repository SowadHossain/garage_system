<?php
// vehicles/add.php - Add New Vehicle

session_start();

require_once __DIR__ . "/../config/db.php";

// Check if customer is logged in
if (empty($_SESSION['customer_id'])) {
    header("Location: ../public/customer_login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_no = trim($_POST['registration_no'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year = (int)($_POST['year'] ?? 0);
    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $vin = trim($_POST['vin'] ?? '');
    
    // Validation
    if (empty($registration_no) || empty($brand) || empty($model) || $year === 0 || empty($vehicle_type)) {
        $error = "Please fill in all required fields.";
    } elseif ($year < 1900 || $year > (date('Y') + 1)) {
        $error = "Please enter a valid year.";
    } else {
        // Check if registration number already exists
        $check_stmt = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE registration_no = ? LIMIT 1");
        $check_stmt->bind_param("s", $registration_no);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "A vehicle with this registration number already exists.";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Insert vehicle
            $insert_stmt = $conn->prepare("INSERT INTO vehicles 
                (customer_id, registration_no, brand, model, year, vehicle_type) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("isssis", $customer_id, $registration_no, $brand, $model, $year, $vehicle_type);
            
            if ($insert_stmt->execute()) {
                $vehicle_id = $insert_stmt->insert_id;
                $insert_stmt->close();
                
                header("Location: list.php?success=1");
                exit;
            } else {
                $error = "Failed to add vehicle. Please try again.";
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
    <title>Add Vehicle - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #198754;
            --primary-dark: #146c43;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8f9fa;
        }
        
        .top-nav {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .main-content {
            margin-top: 70px;
            padding: 2rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #6c757d;
            font-size: 1rem;
        }
        
        .vehicle-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }
        
        .form-label {
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.5rem;
        }
        
        .form-label .required {
            color: #dc3545;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            color: white;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .info-box {
            background: #e7f7ef;
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .info-box i {
            color: var(--primary-color);
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-right: none;
            border-radius: 8px 0 0 8px;
        }
        
        .input-group .form-control, .input-group .form-select {
            border-left: none;
            border-radius: 0 8px 8px 0;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .vehicle-card {
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <a href="../public/customer_dashboard.php" class="nav-brand">
            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
        </a>
        <a href="../public/customer_logout.php" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </nav>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-car-front-fill me-2"></i>Add New Vehicle
            </h1>
            <p class="page-subtitle">Register a new vehicle to your account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Note:</strong> Please ensure all vehicle information is accurate for service records.
        </div>
        
        <div class="vehicle-card">
            <form method="POST" action="add.php" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="registration_no" class="form-label">
                            <i class="bi bi-credit-card-2-front me-1"></i>Registration Number <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-hash"></i></span>
                            <input type="text" 
                                   name="registration_no" 
                                   id="registration_no" 
                                   class="form-control text-uppercase" 
                                   placeholder="ABC-1234" 
                                   required 
                                   autofocus
                                   value="<?php echo htmlspecialchars($_POST['registration_no'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="vehicle_type" class="form-label">
                            <i class="bi bi-car-front me-1"></i>Vehicle Type <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-list"></i></span>
                            <select name="vehicle_type" id="vehicle_type" class="form-select" required>
                                <option value="">Select type...</option>
                                <option value="car" <?php echo (($_POST['vehicle_type'] ?? '') === 'car') ? 'selected' : ''; ?>>Car</option>
                                <option value="motorcycle" <?php echo (($_POST['vehicle_type'] ?? '') === 'motorcycle') ? 'selected' : ''; ?>>Motorcycle</option>
                                <option value="truck" <?php echo (($_POST['vehicle_type'] ?? '') === 'truck') ? 'selected' : ''; ?>>Truck</option>
                                <option value="van" <?php echo (($_POST['vehicle_type'] ?? '') === 'van') ? 'selected' : ''; ?>>Van</option>
                                <option value="suv" <?php echo (($_POST['vehicle_type'] ?? '') === 'suv') ? 'selected' : ''; ?>>SUV</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="brand" class="form-label">
                            <i class="bi bi-buildings me-1"></i>Brand/Make <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                            <input type="text" 
                                   name="brand" 
                                   id="brand" 
                                   class="form-control" 
                                   placeholder="e.g., Toyota, Honda" 
                                   required
                                   value="<?php echo htmlspecialchars($_POST['brand'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="model" class="form-label">
                            <i class="bi bi-bookmark me-1"></i>Model <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-tag-fill"></i></span>
                            <input type="text" 
                                   name="model" 
                                   id="model" 
                                   class="form-control" 
                                   placeholder="e.g., Corolla, Civic" 
                                   required
                                   value="<?php echo htmlspecialchars($_POST['model'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="year" class="form-label">
                            <i class="bi bi-calendar3 me-1"></i>Year <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                            <input type="number" 
                                   name="year" 
                                   id="year" 
                                   class="form-control" 
                                   placeholder="<?php echo date('Y'); ?>" 
                                   required
                                   min="1900"
                                   max="<?php echo date('Y') + 1; ?>"
                                   value="<?php echo htmlspecialchars($_POST['year'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="color" class="form-label">
                            <i class="bi bi-palette me-1"></i>Color
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-droplet"></i></span>
                            <input type="text" 
                                   name="color" 
                                   id="color" 
                                   class="form-control" 
                                   placeholder="e.g., Red, Blue"
                                   value="<?php echo htmlspecialchars($_POST['color'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="vin" class="form-label">
                        <i class="bi bi-upc-scan me-1"></i>VIN (Vehicle Identification Number)
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-fingerprint"></i></span>
                        <input type="text" 
                               name="vin" 
                               id="vin" 
                               class="form-control text-uppercase" 
                               placeholder="17-character VIN"
                               maxlength="17"
                               value="<?php echo htmlspecialchars($_POST['vin'] ?? ''); ?>">
                    </div>
                    <small class="text-muted">Optional: 17-character unique identifier</small>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-check-circle me-2"></i>Add Vehicle
                    </button>
                    <a href="../public/customer_dashboard.php" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-uppercase registration number
        document.getElementById('registration_no').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Auto-uppercase VIN
        document.getElementById('vin').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Validate year
        document.getElementById('year').addEventListener('input', function() {
            const year = parseInt(this.value);
            const currentYear = new Date().getFullYear();
            
            if (year < 1900) {
                this.value = 1900;
            } else if (year > currentYear + 1) {
                this.value = currentYear + 1;
            }
        });
    </script>
</body>
</html>
