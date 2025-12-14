<?php
require_once __DIR__ . "/_guard.php";

$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $like = "%".$q."%";
    $stmt = $conn->prepare("
        SELECT customer_id, name, email, phone, created_at
        FROM customers
        WHERE name LIKE ? OR phone LIKE ? OR email LIKE ?
        ORDER BY created_at DESC
        LIMIT 200
    ");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $rows = $conn->query("
        SELECT customer_id, name, email, phone, created_at
        FROM customers
        ORDER BY created_at DESC
        LIMIT 200
    ")->fetch_all(MYSQLI_ASSOC);
}

ui_header("Customers", $staff_name);
?>
<div class="card-soft">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title mb-0"><i class="bi bi-people"></i>Customers</h2>
        <div class="d-flex gap-2">
            <a class="btn btn-sd" href="add_customer.php"><i class="bi bi-person-plus me-1"></i>Add</a>
            <a class="btn btn-outline-success" href="receptionist_dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        </div>
    </div>

    <form class="row g-2 mb-3" method="get">
        <div class="col-md-10">
            <input class="form-control" name="q" placeholder="Search name / phone / email" value="<?php echo h($q); ?>">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-outline-success"><i class="bi bi-search me-1"></i>Search</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Registered</th><th style="width:180px;">Actions</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6" class="text-muted">No customers found.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $c): ?>
                <tr>
                    <td>#<?php echo (int)$c['customer_id']; ?></td>
                    <td class="fw-semibold"><?php echo h($c['name']); ?></td>
                    <td><?php echo h($c['phone'] ?? ''); ?></td>
                    <td><?php echo h($c['email'] ?? ''); ?></td>
                    <td class="text-muted small"><?php echo h($c['created_at']); ?></td>
                    <td>
                        <a class="btn btn-sm btn-outline-success" href="book_appointment.php?customer_id=<?php echo (int)$c['customer_id']; ?>">
                            <i class="bi bi-calendar-plus me-1"></i>Appointment
                        </a>
                        <a class="btn btn-sm btn-outline-success" href="add_vehicle.php">
                            <i class="bi bi-car-front me-1"></i>Vehicle
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php ui_footer(); ?>
