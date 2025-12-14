<?php
require_once __DIR__ . "/_guard.php";

/**
 * Super Admin rule:
 * Your seed uses Admin User as staff_id = 1000.
 * Only that user can add services.
 */
$is_super_admin = ((int)($_SESSION['staff_id'] ?? 0) === 1000);

$err = '';
$ok  = '';

if (!$is_super_admin) {
    ui_header_admin("Services", $staff_name);
    ?>
    <div class="dashboard-header">
        <h1 class="dashboard-title"><i class="bi bi-tools me-2"></i>Services</h1>
        <p class="dashboard-subtitle">Service catalog management.</p>
    </div>

    <div class="alert alert-danger">
        <i class="bi bi-shield-lock me-1"></i>
        Access denied. Only Super Admin can manage services.
    </div>
    <?php
    ui_footer_admin();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $base = trim($_POST['base_price'] ?? '0');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '') {
        $err = "Service name is required.";
    } elseif (!is_numeric($base) || (float)$base < 0) {
        $err = "Base price must be a valid non-negative number.";
    } else {
        $baseVal = (float)$base;

        $stmt = $conn->prepare("INSERT INTO services (name, base_price, is_active) VALUES (?, ?, ?)");
        $stmt->bind_param("sdi", $name, $baseVal, $is_active);

        try {
            $stmt->execute();
            $ok = "Service added successfully.";
        } catch (mysqli_sql_exception $e) {
            $err = "Failed to add service: " . $e->getMessage();
        }
        $stmt->close();
    }
}

$services = $conn->query("
    SELECT service_id, name, base_price, is_active, created_at
    FROM services
    ORDER BY service_id DESC
")->fetch_all(MYSQLI_ASSOC);

ui_header_admin("Services", $staff_name);
?>

<div class="dashboard-header">
    <h1 class="dashboard-title"><i class="bi bi-tools me-2"></i>Services</h1>
    <p class="dashboard-subtitle">Super Admin service catalog management.</p>
</div>

<?php if ($err): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?php echo h($err); ?></div>
<?php endif; ?>

<?php if ($ok): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?php echo h($ok); ?></div>
<?php endif; ?>

<div class="content-row">
    <div class="data-card">
        <h2 class="section-title"><i class="bi bi-plus-circle"></i>Add Service</h2>

        <form method="POST" class="row g-3">
            <div class="col-12">
                <label class="form-label">Service Name <span class="text-danger">*</span></label>
                <input name="name" class="form-control" required placeholder="Engine Oil Change">
            </div>

            <div class="col-md-6">
                <label class="form-label">Base Price</label>
                <input name="base_price" type="number" step="0.01" min="0" class="form-control" value="0.00">
            </div>

            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Save Service
                </button>
                <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </form>
    </div>

    <div class="data-card">
        <h2 class="section-title"><i class="bi bi-list-ul"></i>Service List</h2>

        <?php if (empty($services)): ?>
            <p class="text-muted mb-0">No services found.</p>
        <?php else: ?>
            <?php foreach ($services as $s): ?>
                <div class="list-item">
                    <div class="item-header">
                        <div>
                            <div class="item-title"><?php echo h($s['name']); ?></div>
                            <div class="item-details">
                                <i class="bi bi-cash me-1"></i>৳<?php echo number_format((float)$s['base_price'], 2); ?>
                                <?php if (!empty($s['created_at'])): ?>
                                    • <i class="bi bi-clock me-1"></i><?php echo date('M d, Y', strtotime($s['created_at'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="badge <?php echo ((int)$s['is_active'] === 1) ? 'success' : 'danger'; ?>">
                            <?php echo ((int)$s['is_active'] === 1) ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php ui_footer_admin(); ?>
