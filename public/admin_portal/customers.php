<?php
require_once __DIR__ . "/_guard.php";

$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $conn->prepare("
        SELECT customer_id, name, email, phone, created_at
        FROM customers
        WHERE name LIKE CONCAT('%', ?, '%')
           OR email LIKE CONCAT('%', ?, '%')
           OR phone LIKE CONCAT('%', ?, '%')
        ORDER BY customer_id DESC
        LIMIT 200
    ");
    $stmt->bind_param("sss", $search, $search, $search);
    $stmt->execute();
    $customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $customers = $conn->query("
        SELECT customer_id, name, email, phone, created_at
        FROM customers
        ORDER BY customer_id DESC
        LIMIT 200
    ")->fetch_all(MYSQLI_ASSOC);
}

ui_header_admin("Customers", $staff_name);
?>

<div class="dashboard-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
        <h1 class="dashboard-title mb-1">
            <i class="bi bi-people-fill me-2"></i>Customers
        </h1>
        <p class="dashboard-subtitle mb-0">Browse and search customer records.</p>
    </div>

    <div class="d-flex gap-2">
        <a href="customers_add.php" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i>Add Customer
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
                       placeholder="Search by name, email, phone...">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button>
        </div>

        <?php if ($search !== ''): ?>
            <div class="col-12">
                <a class="btn btn-sm btn-outline-secondary" href="customers.php">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="data-card">
    <h2 class="section-title"><i class="bi bi-list-ul"></i>Customer List</h2>

    <?php if (empty($customers)): ?>
        <p class="text-muted mb-0">No customers found.</p>
    <?php else: ?>
        <?php foreach ($customers as $c): ?>
            <div class="list-item">
                <div class="item-header">
                    <div>
                        <div class="item-title"><?php echo h($c['name']); ?></div>
                        <div class="item-details">
                            <i class="bi bi-envelope me-1"></i><?php echo h($c['email']); ?><br>
                            <i class="bi bi-telephone me-1"></i><?php echo h($c['phone']); ?>
                        </div>
                    </div>
                    <span class="badge primary">#<?php echo (int)$c['customer_id']; ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php ui_footer_admin(); ?>
