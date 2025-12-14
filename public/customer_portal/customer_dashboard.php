<?php
// public/customer_dashboard.php - Customer Dashboard

session_start();

require_once __DIR__ . "/../../config/db.php";

// Check if customer is logged in
if (empty($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

// Fetch customer vehicles
$vehicles_stmt = $conn->prepare("SELECT * FROM vehicles WHERE customer_id = ? ORDER BY vehicle_id DESC");
$vehicles_stmt->bind_param("i", $customer_id);
$vehicles_stmt->execute();
$vehicles = $vehicles_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$vehicles_stmt->close();

// Fetch recent appointments
$appointments_stmt = $conn->prepare("
    SELECT a.*, v.registration_no, v.brand, v.model 
    FROM appointments a
    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE a.customer_id = ?
    ORDER BY a.appointment_datetime DESC
    LIMIT 5
");
$appointments_stmt->bind_param("i", $customer_id);
$appointments_stmt->execute();
$appointments = $appointments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$appointments_stmt->close();

// Count totals
$total_vehicles = count($vehicles);
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE customer_id = $customer_id")->fetch_assoc()['count'];

// Count bills (join through appointments to get customer_id)
$bills_result = $conn->query("
    SELECT COUNT(*) as count 
    FROM bills b 
    JOIN jobs j ON b.job_id = j.job_id 
    JOIN appointments a ON j.appointment_id = a.appointment_id 
    WHERE a.customer_id = $customer_id
");
$total_bills = $bills_result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #198754;
            --primary-dark: #146c43;
            --sidebar-width: 260px;
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
        
        /* Top Navigation */
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
        
        .nav-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Main Content */
        .main-content {
            margin-top: 70px;
            padding: 2rem;
            max-width: 1200px;
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card.vehicles .stat-icon {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: white;
        }
        
        .stat-card.appointments .stat-icon {
            background: linear-gradient(135deg, #198754, #146c43);
            color: white;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        /* Section Cards */
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #212529;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        /* Table */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        table {
            width: 100%;
            margin-bottom: 0;
        }
        
        table th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            color: #6c757d;
            padding: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <a href="customer_dashboard.php" class="nav-brand">
            <i class="bi bi-gear-wide-connected me-2"></i>Screw Dheela
        </a>
        <div class="nav-user">
            <span class="d-none d-md-inline"><?php echo htmlspecialchars($customer_name); ?></span>
            <div class="user-avatar">
                <?php echo strtoupper(substr($customer_name, 0, 1)); ?>
            </div>
            <a href="customer_logout.php" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($_GET['welcome']) && $_GET['welcome'] == 1): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Welcome to Screw Dheela!</strong> Your account has been created successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1 class="page-title">Welcome back, <?php echo htmlspecialchars(explode(' ', $customer_name)[0]); ?>!</h1>
            <p class="page-subtitle">Manage your vehicles and appointments</p>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card vehicles">
                <div class="stat-icon">
                    <i class="bi bi-car-front-fill"></i>
                </div>
                <div class="stat-value"><?php echo $total_vehicles; ?></div>
                <div class="stat-label">My Vehicles</div>
            </div>
            
            <div class="stat-card appointments">
                <div class="stat-icon">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div class="stat-value"><?php echo $total_appointments; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-receipt-cutoff"></i>
                </div>
                <div class="stat-value"><?php echo $total_bills; ?></div>
                <div class="stat-label">My Bills</div>
            </div>
        </div>
        
        <!-- Recent Appointments -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="bi bi-calendar3 me-2"></i>Recent Appointments
                </h2>
                <a href="../appointments/book.php" class="btn-primary-custom">
                    <i class="bi bi-plus-circle"></i>New Appointment
                </a>
            </div>
            
            <?php if (empty($appointments)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <p>No appointments yet. Book your first appointment!</p>
                    <a href="../appointments/book.php" class="btn btn-primary btn-sm mt-2">Book Now</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Problem</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appt): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($appt['appointment_datetime'])); ?></td>
                                    <td><?php echo htmlspecialchars($appt['brand'] . ' ' . $appt['model']); ?></td>
                                    <td><?php echo htmlspecialchars($appt['problem_description']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $appt['status'] === 'completed' ? 'success' : 
                                                ($appt['status'] === 'booked' ? 'primary' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($appt['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="../appointments/view_appointments.php" class="btn btn-outline-secondary">
                        <i class="bi bi-calendar3 me-2"></i>View All Appointments
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- My Vehicles -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="bi bi-car-front me-2"></i>My Vehicles
                </h2>
                <a href="../vehicles/list.php" class="btn-primary-custom">
                    <i class="bi bi-car-front"></i>View All Vehicles
                </a>
            </div>
            
            <?php if (empty($vehicles)): ?>
                <div class="empty-state">
                    <i class="bi bi-car-front"></i>
                    <p>No vehicles registered. Add your first vehicle!</p>
                    <a href="../vehicles/list.php" class="btn btn-primary btn-sm mt-2">Go to Vehicles</a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($vehicles as $vehicle): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></h5>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3"></i> <?php echo $vehicle['year']; ?> •
                                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($vehicle['registration_no']); ?>
                                        </small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Links Section -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="section-card">
                    <div class="text-center py-4">
                        <p class="text-muted mb-3">
                            <i class="bi bi-receipt-cutoff" style="font-size: 3rem; opacity: 0.3;"></i>
                        </p>
                        <h3 class="h5 mb-2">Bills & Invoices</h3>
                        <p class="text-muted mb-3">View all your service bills</p>
                        <a href="../bills/customer_bills.php" class="btn btn-primary">
                            <i class="bi bi-receipt me-2"></i>View Bills
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-card">
                    <div class="text-center py-4">
                        <p class="text-muted mb-3">
                            <i class="bi bi-chat-dots" style="font-size: 3rem; opacity: 0.3;"></i>
                        </p>
                        <h3 class="h5 mb-2">Messages</h3>
                        <p class="text-muted mb-3">Chat with our support team</p>
                        <a href="../chat/customer_chat.php" class="btn btn-primary">
                            <i class="bi bi-chat-dots me-2"></i>Open Chat
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-card">
                    <div class="text-center py-4">
                        <p class="text-muted mb-3">
                            <i class="bi bi-star-fill" style="font-size: 3rem; opacity: 0.3; color: #ffc107;"></i>
                        </p>
                        <h3 class="h5 mb-2">Write a Review</h3>
                        <p class="text-muted mb-3">Share your experience with us</p>
                        <a href="../reviews/submit.php" class="btn btn-warning">
                            <i class="bi bi-pencil-square me-2"></i>Write Review
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="section-card">
                    <div class="text-center py-4">
                        <p class="text-muted mb-3">
                            <i class="bi bi-star-half" style="font-size: 3rem; opacity: 0.3;"></i>
                        </p>
                        <h3 class="h5 mb-2">Customer Reviews</h3>
                        <p class="text-muted mb-3">See what others are saying</p>
                        <a href="../reviews/list.php" class="btn btn-outline-primary">
                            <i class="bi bi-eye me-2"></i>View Reviews
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
