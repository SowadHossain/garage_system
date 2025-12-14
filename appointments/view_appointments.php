<?php
// appointments/view_appointments.php - View Customer Appointments (DB compatible, same UI)

session_start();
require_once __DIR__ . "/../config/db.php";

// Check if customer is logged in
if (empty($_SESSION['customer_id'])) {
    header("Location: /garage_system/public/customer_portal/customer_login.php");
    exit;
}

$customer_id   = (int)$_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'] ?? 'Customer';

// Slot -> time (edit if you want different times)
$slotTimes = [
    1 => '10:00',
    2 => '12:00',
    3 => '14:00',
    4 => '16:00',
];

// Fetch appointments with vehicle details (NEW schema)
$appointments_stmt = $conn->prepare("
    SELECT
        a.appointment_id,
        a.requested_date,
        a.requested_slot,
        a.problem_text,
        a.status,
        a.created_at,
        v.plate_no,
        v.make,
        v.model,
        v.year AS model_year
    FROM appointments a
    LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
    WHERE a.customer_id = ?
    ORDER BY a.requested_date DESC, a.requested_slot DESC, a.created_at DESC
");
$appointments_stmt->bind_param("i", $customer_id);
$appointments_stmt->execute();
$appointments = $appointments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$appointments_stmt->close();

// Group appointments by status
$upcoming = [];
$completed = [];
$cancelled = [];

foreach ($appointments as $appointment) {
    if ($appointment['status'] === 'completed') {
        $completed[] = $appointment;
    } elseif ($appointment['status'] === 'cancelled') {
        $cancelled[] = $appointment;
    } else {
        $upcoming[] = $appointment;
    }
}

function badgeForStatus($status) {
    return match($status) {
        'requested'   => 'warning',
        'booked'      => 'primary',
        'in_progress' => 'info',
        'completed'   => 'success',
        'cancelled'   => 'danger',
        default       => 'secondary',
    };
}

function prettyStatus($status) {
    return ucfirst(str_replace('_', ' ', (string)$status));
}

function dtFromDateSlot($date, $slot, $slotTimes) {
    $t = $slotTimes[(int)$slot] ?? '10:00';
    $dt = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $t);
    if (!$dt) $dt = new DateTime($date);
    return $dt;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - Screw Dheela</title>
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
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .page-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
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
        
        .appointments-section {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .appointment-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .appointment-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            color: #212529;
        }
        
        .date-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }
        
        .date-icon .day {
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .vehicle-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .vehicle-icon {
            width: 50px;
            height: 50px;
            background: #e9ecef;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #6c757d;
        }
        
        .vehicle-details h4 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #212529;
        }
        
        .vehicle-details p {
            margin: 0;
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .problem-description {
            padding: 1rem;
            background: #fff8e1;
            border-left: 4px solid #ffc107;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .problem-description strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #212529;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #6c757d;
            opacity: 0.5;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .appointment-header {
                flex-direction: column;
            }
            
            .vehicle-info {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <a href="/garage_system/public/customer_portal/customer_dashboard.php" class="nav-brand">
            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
        </a>
        <a href="/garage_system/public/customer_portal/customer_logout.php" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </nav>
    
    <div class="main-content">
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Success!</strong> Your appointment has been booked successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-calendar3 me-2"></i>My Appointments
            </h1>
            <a href="book.php" class="btn-primary-custom">
                <i class="bi bi-plus-circle"></i>Book New Appointment
            </a>
        </div>
        
        <!-- Upcoming Appointments -->
        <div class="appointments-section">
            <h2 class="section-title">
                <i class="bi bi-calendar-check text-primary"></i>
                Upcoming Appointments (<?php echo count($upcoming); ?>)
            </h2>
            
            <?php if (empty($upcoming)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <h3>No Upcoming Appointments</h3>
                    <p class="text-muted mb-3">Book an appointment to get started!</p>
                    <a href="book.php" class="btn btn-primary-custom">
                        <i class="bi bi-plus-circle me-2"></i>Book Appointment
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($upcoming as $appt): 
                    $datetime = dtFromDateSlot($appt['requested_date'], $appt['requested_slot'], $slotTimes);
                ?>
                    <div class="appointment-card">
                        <div class="appointment-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="date-icon">
                                    <div><?php echo $datetime->format('M'); ?></div>
                                    <div class="day"><?php echo $datetime->format('d'); ?></div>
                                </div>
                                <div>
                                    <div class="appointment-date">
                                        <i class="bi bi-clock"></i>
                                        <?php echo $datetime->format('l, F j, Y'); ?>
                                        <span class="text-muted">• Slot <?php echo (int)$appt['requested_slot']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-<?php echo badgeForStatus($appt['status']); ?>">
                                <?php echo htmlspecialchars(prettyStatus($appt['status'])); ?>
                            </span>
                        </div>
                        
                        <div class="vehicle-info">
                            <div class="vehicle-icon">
                                <i class="bi bi-car-front-fill"></i>
                            </div>
                            <div class="vehicle-details">
                                <h4>
                                    <?php echo htmlspecialchars(trim(($appt['make'] ?? '') . ' ' . ($appt['model'] ?? '')) ?: 'Vehicle'); ?>
                                </h4>
                                <p>
                                    <?php
                                        $plate = $appt['plate_no'] ?? '';
                                        $year  = $appt['model_year'] ?? '';
                                        echo htmlspecialchars(trim(($plate ? $plate : 'No plate') . ($year ? ' • ' . $year : '')));
                                    ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="problem-description">
                            <strong><i class="bi bi-chat-square-text me-1"></i>Problem Description:</strong>
                            <?php echo htmlspecialchars($appt['problem_text'] ?? ''); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Completed Appointments -->
        <?php if (!empty($completed)): ?>
            <div class="appointments-section">
                <h2 class="section-title">
                    <i class="bi bi-check-circle text-success"></i>
                    Completed Appointments (<?php echo count($completed); ?>)
                </h2>
                
                <?php foreach (array_slice($completed, 0, 5) as $appt): 
                    $datetime = dtFromDateSlot($appt['requested_date'], $appt['requested_slot'], $slotTimes);
                ?>
                    <div class="appointment-card">
                        <div class="appointment-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="date-icon" style="background: linear-gradient(135deg, #6c757d, #495057);">
                                    <div><?php echo $datetime->format('M'); ?></div>
                                    <div class="day"><?php echo $datetime->format('d'); ?></div>
                                </div>
                                <div>
                                    <div class="appointment-date">
                                        <i class="bi bi-clock"></i>
                                        <?php echo $datetime->format('l, F j, Y'); ?>
                                        <span class="text-muted">• Slot <?php echo (int)$appt['requested_slot']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <span class="badge bg-success">Completed</span>
                        </div>
                        
                        <div class="vehicle-info">
                            <div class="vehicle-icon">
                                <i class="bi bi-car-front-fill"></i>
                            </div>
                            <div class="vehicle-details">
                                <h4>
                                    <?php echo htmlspecialchars(trim(($appt['make'] ?? '') . ' ' . ($appt['model'] ?? '')) ?: 'Vehicle'); ?>
                                </h4>
                                <p>
                                    <?php
                                        $plate = $appt['plate_no'] ?? '';
                                        $year  = $appt['model_year'] ?? '';
                                        echo htmlspecialchars(trim(($plate ? $plate : 'No plate') . ($year ? ' • ' . $year : '')));
                                    ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="problem-description" style="background: #e7f7ef; border-color: #198754;">
                            <strong><i class="bi bi-chat-square-text me-1"></i>Problem Description:</strong>
                            <?php echo htmlspecialchars($appt['problem_text'] ?? ''); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
