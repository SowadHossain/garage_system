<?php
// appointments/book.php - Book New Appointment

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

// Fetch customer vehicles
$vehicles_stmt = $conn->prepare("SELECT * FROM vehicles WHERE customer_id = ? ORDER BY created_at DESC");
$vehicles_stmt->bind_param("i", $customer_id);
$vehicles_stmt->execute();
$vehicles = $vehicles_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$vehicles_stmt->close();

// Fetch available services
$services_result = $conn->query("SELECT * FROM services ORDER BY category, name");
$services = $services_result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $appointment_time = trim($_POST['appointment_time'] ?? '');
    $problem_description = trim($_POST['problem_description'] ?? '');
    
    // Validation
    if ($vehicle_id === 0 || empty($appointment_date) || empty($appointment_time) || empty($problem_description)) {
        $error = "All fields are required.";
    } else {
        // Verify vehicle belongs to customer
        $verify_stmt = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE vehicle_id = ? AND customer_id = ? LIMIT 1");
        $verify_stmt->bind_param("ii", $vehicle_id, $customer_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 0) {
            $error = "Invalid vehicle selected.";
            $verify_stmt->close();
        } else {
            $verify_stmt->close();
            
            // Combine date and time
            $appointment_datetime = $appointment_date . ' ' . $appointment_time . ':00';
            
            // Check if datetime is in the future
            if (strtotime($appointment_datetime) <= time()) {
                $error = "Appointment must be scheduled for a future date and time.";
            } else {
                // Insert appointment
                $insert_stmt = $conn->prepare("INSERT INTO appointments 
                    (customer_id, vehicle_id, appointment_datetime, problem_description, status, created_at) 
                    VALUES (?, ?, ?, ?, 'booked', NOW())");
                $insert_stmt->bind_param("iiss", $customer_id, $vehicle_id, $appointment_datetime, $problem_description);
                
                if ($insert_stmt->execute()) {
                    $appointment_id = $insert_stmt->insert_id;
                    $insert_stmt->close();
                    
                    header("Location: view_appointments.php?success=1");
                    exit;
                } else {
                    $error = "Failed to book appointment. Please try again.";
                    $insert_stmt->close();
                }
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
    <title>Book Appointment - Screw Dheela</title>
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
        
        .booking-card {
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
        
        .vehicle-option {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .vehicle-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .booking-card {
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
                <i class="bi bi-calendar-plus me-2"></i>Book an Appointment
            </h1>
            <p class="page-subtitle">Schedule a service for your vehicle</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (empty($vehicles)): ?>
            <div class="booking-card text-center">
                <i class="bi bi-car-front" style="font-size: 4rem; color: #6c757d; opacity: 0.5;"></i>
                <h3 class="mt-3">No Vehicles Registered</h3>
                <p class="text-muted mb-4">You need to add a vehicle before booking an appointment.</p>
                <a href="../vehicles/add.php" class="btn btn-primary-custom">
                    <i class="bi bi-plus-circle me-2"></i>Add Your First Vehicle
                </a>
            </div>
        <?php else: ?>
            <div class="info-box">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Operating Hours:</strong> Monday - Saturday, 8:00 AM - 6:00 PM
            </div>
            
            <div class="booking-card">
                <form method="POST" action="book.php" novalidate>
                    <div class="mb-4">
                        <label for="vehicle_id" class="form-label">
                            <i class="bi bi-car-front-fill me-1"></i>Select Vehicle <span class="required">*</span>
                        </label>
                        <select name="vehicle_id" id="vehicle_id" class="form-select" required>
                            <option value="">Choose a vehicle...</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['vehicle_id']; ?>">
                                    <?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'] . ' (' . $vehicle['registration_no'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="appointment_date" class="form-label">
                                <i class="bi bi-calendar3 me-1"></i>Date <span class="required">*</span>
                            </label>
                            <input type="date" 
                                   name="appointment_date" 
                                   id="appointment_date" 
                                   class="form-control" 
                                   required
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="appointment_time" class="form-label">
                                <i class="bi bi-clock me-1"></i>Time <span class="required">*</span>
                            </label>
                            <input type="time" 
                                   name="appointment_time" 
                                   id="appointment_time" 
                                   class="form-control" 
                                   required
                                   min="08:00"
                                   max="18:00">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="problem_description" class="form-label">
                            <i class="bi bi-chat-square-text me-1"></i>Problem Description <span class="required">*</span>
                        </label>
                        <textarea name="problem_description" 
                                  id="problem_description" 
                                  class="form-control" 
                                  rows="4" 
                                  placeholder="Describe the issue with your vehicle..."
                                  required></textarea>
                        <small class="text-muted">Please provide as much detail as possible to help us serve you better.</small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="bi bi-check-circle me-2"></i>Confirm Booking
                        </button>
                        <a href="../public/customer_dashboard.php" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to tomorrow
        const dateInput = document.getElementById('appointment_date');
        const today = new Date();
        today.setDate(today.getDate() + 1);
        const minDate = today.toISOString().split('T')[0];
        dateInput.min = minDate;
        
        // Validate time is within business hours
        document.getElementById('appointment_time').addEventListener('change', function() {
            const time = this.value;
            if (time < '08:00' || time > '18:00') {
                alert('Please select a time between 8:00 AM and 6:00 PM');
                this.value = '';
            }
        });
    </script>
</body>
</html>
