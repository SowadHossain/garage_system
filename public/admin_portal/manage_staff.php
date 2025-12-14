<?php
// public/admin_portal/manage_staff.php
session_start();
require_once __DIR__ . '/../../config/db.php';

// Admin only
if (empty($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'admin') {
    header('Location: ../staff_login.php');
    exit;
}

$page_title = 'Manage Staff';
require_once __DIR__ . '/../../includes/header.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function roleBadgeClass($role){
    return match($role) {
        'admin' => 'danger',
        'mechanic' => 'success',
        'receptionist' => 'info',
        default => 'secondary'
    };
}

// Get all staff (schema-correct: role_id -> roles.role_name)
$staff_list = [];
$res = $conn->query("
    SELECT
        s.staff_id,
        s.name,
        r.role_name AS role_name,
        s.email,
        s.phone,
        s.is_active,
        s.created_at
    FROM staff s
    JOIN roles r ON r.role_id = s.role_id
    ORDER BY s.is_active DESC, r.role_name ASC, s.name ASC
");

if ($res instanceof mysqli_result) {
    $staff_list = $res->fetch_all(MYSQLI_ASSOC);
}
?>

<style>
    body { background:#f0f4ff; }
    .page-wrap { max-width: 1400px; margin: 0 auto; }
    .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); border: 0; }
    .card-header { border-top-left-radius: 12px; border-top-right-radius: 12px; }
</style>

<div class="container-fluid py-4 page-wrap">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admin_dashboard.php">Admin Dashboard</a></li>
            <li class="breadcrumb-item active">Manage Staff</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h2 class="mb-1"><i class="bi bi-people me-2"></i>Manage Staff</h2>
            <div class="text-muted">View staff accounts and roles</div>
        </div>

        <!-- Keep this link if your create page exists here -->
        <a href="create_admin.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Add Staff
        </a>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Staff List</h5>
            <span class="small opacity-75">Total: <?php echo count($staff_list); ?></span>
        </div>

        <div class="card-body">
            <?php if (empty($staff_list)): ?>
                <p class="text-muted mb-0">No staff found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_list as $s): ?>
                                <?php
                                    $role = $s['role_name'] ?? 'staff';
                                    $isActive = (int)($s['is_active'] ?? 0) === 1;
                                ?>
                                <tr>
                                    <td><?php echo (int)$s['staff_id']; ?></td>
                                    <td class="fw-semibold"><?php echo h($s['name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo roleBadgeClass($role); ?>">
                                            <?php echo h($role); ?>
                                        </span>
                                    </td>
                                    <td><?php echo h($s['email']); ?></td>
                                    <td><?php echo h($s['phone'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-x-circle me-1"></i>Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo !empty($s['created_at']) ? date('M d, Y', strtotime($s['created_at'])) : ''; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
