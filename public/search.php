<?php
session_start();
require_once '../config/db.php';
require_once '../includes/role_check.php';

// Check if user is staff with search permission (admin or receptionist only)
requireRole(['admin', 'receptionist']);

$page_title = 'Global Search';
require_once '../includes/header.php';

// Get search term
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = ['customers' => [], 'vehicles' => [], 'appointments' => []];
$total_results = 0;

if (!empty($search) && strlen($search) >= 2) {
    // Search customers (LIKE)
    $customers_stmt = $conn->prepare("SELECT customer_id, name, email, phone, address 
                                      FROM customers 
                                      WHERE name LIKE CONCAT('%', ?, '%') 
                                         OR email LIKE CONCAT('%', ?, '%') 
                                         OR phone LIKE CONCAT('%', ?, '%')
                                      LIMIT 20");
    $customers_stmt->bind_param("sss", $search, $search, $search);
    $customers_stmt->execute();
    $results['customers'] = $customers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Search vehicles (LIKE)
    $vehicles_stmt = $conn->prepare("SELECT v.*, c.name as customer_name 
                                      FROM vehicles v
                                      JOIN customers c ON v.customer_id = c.customer_id
                                      WHERE v.registration_no LIKE CONCAT('%', ?, '%') 
                                         OR v.brand LIKE CONCAT('%', ?, '%') 
                                         OR v.model LIKE CONCAT('%', ?, '%')
                                      LIMIT 20");
    $vehicles_stmt->bind_param("sss", $search, $search, $search);
    $vehicles_stmt->execute();
    $results['vehicles'] = $vehicles_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Search appointments (LIKE)
    $appointments_stmt = $conn->prepare("SELECT a.*, c.name as customer_name, v.registration_no
                                         FROM appointments a
                                         JOIN customers c ON a.customer_id = c.customer_id
                                         LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                                         WHERE c.name LIKE CONCAT('%', ?, '%') 
                                            OR v.registration_no LIKE CONCAT('%', ?, '%')
                                            OR a.problem_description LIKE CONCAT('%', ?, '%')
                                         ORDER BY a.appointment_datetime DESC
                                         LIMIT 20");
    $appointments_stmt->bind_param("sss", $search, $search, $search);
    $appointments_stmt->execute();
    $results['appointments'] = $appointments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $total_results = count($results['customers']) + count($results['vehicles']) + count($results['appointments']);
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-search me-2"></i>Global Search
            </h2>
            <p class="text-muted">Search across customers, vehicles, and appointments</p>
        </div>
    </div>

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-10">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" 
                                           class="form-control" 
                                           name="q" 
                                           value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Search by name, email, phone, registration, brand, model..."
                                           autofocus>
                                </div>
                                <small class="text-muted">Uses SQL LIKE pattern matching across multiple tables</small>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-search me-1"></i>Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($search)): ?>
        <?php if ($total_results > 0): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Found <strong><?php echo $total_results; ?></strong> result(s) for "<strong><?php echo htmlspecialchars($search); ?></strong>"
            </div>

            <!-- Customers Results -->
            <?php if (!empty($results['customers'])): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-people me-2"></i>Customers (<?php echo count($results['customers']); ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Address</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($results['customers'] as $customer): ?>
                                                <tr>
                                                    <td><?php echo $customer['customer_id']; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($customer['name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                                    <td><?php echo htmlspecialchars($customer['address']); ?></td>
                                                    <td>
                                                        <a href="../customers/edit.php?id=<?php echo $customer['customer_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Vehicles Results -->
            <?php if (!empty($results['vehicles'])): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-car-front me-2"></i>Vehicles (<?php echo count($results['vehicles']); ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Registration</th>
                                                <th>Brand</th>
                                                <th>Model</th>
                                                <th>Year</th>
                                                <th>Type</th>
                                                <th>Owner</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($results['vehicles'] as $vehicle): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($vehicle['registration_no']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($vehicle['brand']); ?></td>
                                                    <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                                    <td><?php echo $vehicle['year']; ?></td>
                                                    <td><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></td>
                                                    <td><?php echo htmlspecialchars($vehicle['customer_name']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Appointments Results -->
            <?php if (!empty($results['appointments'])): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-calendar-check me-2"></i>Appointments (<?php echo count($results['appointments']); ?>)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date/Time</th>
                                                <th>Customer</th>
                                                <th>Vehicle</th>
                                                <th>Problem</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($results['appointments'] as $appt): ?>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        $date = new DateTime($appt['appointment_datetime']);
                                                        echo $date->format('M d, Y H:i');
                                                        ?>
                                                    </td>
                                                    <td><strong><?php echo htmlspecialchars($appt['customer_name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($appt['registration_no'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($appt['problem_description'], 0, 50)) . '...'; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo match($appt['status']) {
                                                                'booked' => 'primary',
                                                                'confirmed' => 'info',
                                                                'completed' => 'success',
                                                                'cancelled' => 'danger',
                                                                default => 'secondary'
                                                            };
                                                        ?>">
                                                            <?php echo htmlspecialchars($appt['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No results found for "<strong><?php echo htmlspecialchars($search); ?></strong>". Try different search terms.
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Enter a search term to find customers, vehicles, or appointments. Minimum 2 characters required.
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
