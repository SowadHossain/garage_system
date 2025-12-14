<?php
require_once __DIR__ . "/_guard.php";

$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $conn->prepare("
        SELECT
            v.vehicle_id,
            v.plate_no,
            v.make,
            v.model,
            v.year,
            v.color,
            c.name AS customer_name
        FROM vehicles v
        JOIN customers c ON c.customer_id = v.customer_id
        WHERE v.plate_no LIKE CONCAT('%', ?, '%')
           OR v.make LIKE CONCAT('%', ?, '%')
           OR v.model LIKE CONCAT('%', ?, '%')
           OR c.name LIKE CONCAT('%', ?, '%')
        ORDER BY v.vehicle_id DESC
        LIMIT 200
    ");
    $stmt->bind_param("ssss", $search, $search, $search, $search);
    $stmt->execute();
    $vehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $vehicles = $conn->query("
        SELECT
            v.vehicle_id,
            v.plate_no,
            v.make,
            v.model,
            v.year,
            v.color,
            c.name AS customer_name
        FROM vehicles v
        JOIN customers c ON c.customer_id = v.customer_id
        ORDER BY v.vehicle_id DESC
        LIMIT 200
    ")->fetch_all(MYSQLI_ASSOC);
}

ui_header_admin("Vehicles", $staff_name);
?>

<div class="dashboard-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1 class="dashboard-title mb-1">
            <i class="bi bi-car-front-fill me-2"></i>Vehicles
        </h1>
        <p class="dashboard-subtitle mb-0">Browse and search registered vehicles.</p>
    </div>

    <div class="d-flex gap-2">
        <a href="vehicles_add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Add Vehicle
        </a>
    </div>
</div>


<div class="data-card mb-3">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control"
                       value="<?php echo h($search); ?>"
                       placeholder="Search plate, make, model, customer...">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
        </div>
        <?php if ($search !== ''): ?>
            <div class="col-12">
                <a class="btn btn-sm btn-outline-secondary" href="vehicles.php">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="data-card">
    <h2 class="section-title"><i class="bi bi-list-ul"></i>Vehicles</h2>

    <?php if (empty($vehicles)): ?>
        <p class="text-muted mb-0">No vehicles found.</p>
    <?php else: ?>
        <?php foreach ($vehicles as $v): ?>
            <div class="list-item">
                <div class="item-header">
                    <div>
                        <div class="item-title"><?php echo h(trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? ''))); ?></div>
                        <div class="item-details">
                            <i class="bi bi-hash me-1"></i><?php echo h($v['plate_no']); ?>
                            <?php if (!empty($v['year'])): ?> • <i class="bi bi-calendar3 me-1"></i><?php echo h($v['year']); ?><?php endif; ?>
                            <?php if (!empty($v['color'])): ?> • <i class="bi bi-palette me-1"></i><?php echo h($v['color']); ?><?php endif; ?>
                            <br>
                            <i class="bi bi-person me-1"></i>Owner: <?php echo h($v['customer_name']); ?>
                        </div>
                    </div>
                    <span class="badge primary">#<?php echo (int)$v['vehicle_id']; ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php ui_footer_admin(); ?>
