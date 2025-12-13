<?php
session_start();
require_once '../config/db.php';
require_once '../includes/role_check.php';

// Check if user is staff with customer management permission (admin or receptionist only)
requireRole(['admin', 'receptionist']);

// Get search term (demonstrates LIKE pattern matching)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with LIKE search
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT customer_id, name, email, phone, address, is_email_verified, created_at 
                            FROM customers 
                            WHERE name LIKE CONCAT('%', ?, '%') 
                               OR email LIKE CONCAT('%', ?, '%') 
                               OR phone LIKE CONCAT('%', ?, '%')
                            ORDER BY created_at DESC");
    $stmt->bind_param("sss", $search, $search, $search);
} else {
    $stmt = $conn->prepare("SELECT customer_id, name, email, phone, address, is_email_verified, created_at 
                            FROM customers 
                            ORDER BY created_at DESC");
}

$stmt->execute();
$customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'Customer List';
require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-people-fill me-2"></i>Customers
                    </h5>
                    <a href="add.php" class="btn btn-light btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>Add Customer
                    </a>
                </div>
                <div class="card-body">
                    <!-- Search Form (demonstrates LIKE pattern matching) -->
                    <form method="GET" action="" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" 
                                           class="form-control" 
                                           name="search" 
                                           value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Search by name, email, or phone...">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-search me-1"></i>Search
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="list.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-1"></i>Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <?php if (!empty($search)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Found <strong><?php echo count($customers); ?></strong> customer(s) matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
                        </div>
                    <?php endif; ?>

                    <?php if (empty($customers)): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?php echo !empty($search) ? 'No customers found matching your search.' : 'No customers found.'; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($customer['customer_id']); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($customer['email']): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>">
                                                        <?php echo htmlspecialchars($customer['email']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($customer['phone']): ?>
                                                    <a href="tel:<?php echo htmlspecialchars($customer['phone']); ?>">
                                                        <?php echo htmlspecialchars($customer['phone']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $customer['address'] ? htmlspecialchars($customer['address']) : '<span class="text-muted">N/A</span>'; ?>
                                            </td>
                                            <td>
                                                <?php if ($customer['is_email_verified']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-check-circle me-1"></i>Verified
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-exclamation-circle me-1"></i>Unverified
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $date = new DateTime($customer['created_at']);
                                                echo $date->format('M d, Y'); 
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit.php?id=<?php echo $customer['customer_id']; ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="../vehicles/list.php?customer_id=<?php echo $customer['customer_id']; ?>" 
                                                       class="btn btn-outline-info" 
                                                       title="View Vehicles">
                                                        <i class="bi bi-car-front"></i>
                                                    </a>
                                                    <a href="../appointments/view_appointments.php?customer_id=<?php echo $customer['customer_id']; ?>" 
                                                       class="btn btn-outline-success" 
                                                       title="View Appointments">
                                                        <i class="bi bi-calendar-check"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <p class="text-muted mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Total: <strong><?php echo count($customers); ?></strong> customer(s)
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
