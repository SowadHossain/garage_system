<?php
session_start();
require_once '../../config/db.php';

// Check if user is admin
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'admin') {
    header('Location: ../staff_login.php');
    exit;
}

$page_title = 'Manage Staff';
require_once '../../includes/header.php';

// Get all staff
$staff_query = "SELECT staff_id, name, role, username, email, active, created_at 
                FROM staff 
                ORDER BY active DESC, name ASC";
$staff_result = $conn->query($staff_query);
$staff_list = $staff_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../admin_dashboard.php">Admin Dashboard</a></li>
            <li class="breadcrumb-item active">Manage Staff</li>
        </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="bi bi-people me-2"></i>Manage Staff
            </h2>
            <p class="text-muted">View and manage staff accounts</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-person-badge me-2"></i>Staff List
                    </h5>
                    <a href="../create_admin.php" class="btn btn-light btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>Add New Staff
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff_list as $staff): ?>
                                    <tr>
                                        <td><?php echo $staff['staff_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($staff['name']); ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($staff['role']) {
                                                    'admin' => 'danger',
                                                    'mechanic' => 'success',
                                                    'receptionist' => 'info',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo htmlspecialchars($staff['role']); ?>
                                            </span>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($staff['username']); ?></code></td>
                                        <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                        <td>
                                            <?php if ($staff['active']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i>Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-x-circle me-1"></i>Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $date = new DateTime($staff['created_at']);
                                            echo $date->format('M d, Y');
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Total: <strong><?php echo count($staff_list); ?></strong> staff member(s)
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Users Info -->
    <div class="row">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-database me-2"></i>Database Users (GRANT Permissions)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        The following database users have been created with specific privileges using SQL GRANT statements:
                    </p>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card border-secondary mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">reports_user</h6>
                                    <p class="card-text small text-muted mb-0">
                                        <i class="bi bi-eye me-1"></i>SELECT only (read-only)
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-warning mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">operations_user</h6>
                                    <p class="card-text small text-muted mb-0">
                                        <i class="bi bi-pencil me-1"></i>SELECT, INSERT, UPDATE
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-success mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">mechanic_user</h6>
                                    <p class="card-text small text-muted mb-0">
                                        <i class="bi bi-tools me-1"></i>Job & service management
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-danger mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">admin_user</h6>
                                    <p class="card-text small text-muted mb-0">
                                        <i class="bi bi-shield-check me-1"></i>ALL WITH GRANT OPTION
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>These users were created in <code>docker/mysql/init/grants.sql</code> using SQL GRANT statements to demonstrate database-level access control.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
