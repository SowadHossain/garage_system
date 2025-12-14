<?php
// vehicles/list.php - View All Vehicles

session_start();

require_once __DIR__ . "/../config/db.php";

// Check if customer is logged in
if (empty($_SESSION['customer_id'])) {
    header("Location: ../public/customer_login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

$success = $_GET['success'] ?? '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch vehicles for this customer with optional LIKE search
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT vehicle_id, registration_no, brand, model, year, vehicle_type 
                            FROM vehicles 
                            WHERE customer_id = ? 
                            AND (registration_no LIKE CONCAT('%', ?, '%')
                                 OR brand LIKE CONCAT('%', ?, '%')
                                 OR model LIKE CONCAT('%', ?, '%')
                                 OR vehicle_type LIKE CONCAT('%', ?, '%'))
                            ORDER BY vehicle_id DESC");
    $stmt->bind_param("issss", $customer_id, $search, $search, $search, $search);
} else {
    $stmt = $conn->prepare("SELECT vehicle_id, registration_no, brand, model, year, vehicle_type 
                            FROM vehicles 
                            WHERE customer_id = ? 
                            ORDER BY vehicle_id DESC");
    $stmt->bind_param("i", $customer_id);
}
$stmt->execute();
$result = $stmt->get_result();
$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $vehicles[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vehicles - Screw Dheela</title>
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
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin: 0;
        }
        
        .btn-add {
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
        
        .btn-add:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .vehicle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        
        .vehicle-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .vehicle-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .vehicle-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .vehicle-icon {
            font-size: 3rem;
            opacity: 0.9;
        }
        
        .vehicle-title {
            flex: 1;
        }
        
        .vehicle-reg {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
        }
        
        .vehicle-model {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .vehicle-body {
            padding: 1.5rem;
        }
        
        .vehicle-info {
            display: grid;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .info-icon {
            width: 36px;
            height: 36px;
            background: #e7f7ef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-label {
            font-size: 0.75rem;
            color: #6c757d;
            margin: 0;
        }
        
        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #212529;
            margin: 0;
        }
        
        .vehicle-actions {
            display: flex;
            gap: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }
        
        .btn-action {
            flex: 1;
            padding: 0.625rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }
        
        .btn-edit {
            background: #0d6efd;
            color: white;
            border: none;
        }
        
        .btn-edit:hover {
            background: #0b5ed7;
            color: white;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
        }
        
        .btn-delete:hover {
            background: #bb2d3b;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            border: 2px dashed #dee2e6;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        
        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
        }
        
        .empty-text {
            color: #6c757d;
            margin-bottom: 2rem;
        }
        
        .type-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .type-car { background: #cfe2ff; color: #084298; }
        .type-motorcycle { background: #f8d7da; color: #842029; }
        .type-truck { background: #fff3cd; color: #664d03; }
        .type-van { background: #d1e7dd; color: #0f5132; }
        .type-suv { background: #e2d9f3; color: #59359a; }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .vehicle-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .btn-add {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <a href="../public/customer_dashboard.php" class="nav-brand">
            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
        </a>
        <a href="../public/customer_logout.php" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </nav>
    
    <div class="main-content">
        <?php if ($success === '1'): ?>
            <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>Vehicle added successfully!</div>
            </div>
        <?php elseif ($success === 'deleted'): ?>
            <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>Vehicle deleted successfully!</div>
            </div>
        <?php elseif ($success === 'updated'): ?>
            <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>Vehicle updated successfully!</div>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-car-front-fill me-2"></i>My Vehicles
                </h1>
                <p class="text-muted mb-0"><?php echo count($vehicles); ?> vehicle(s) registered</p>
            </div>
            <a href="add.php" class="btn-add">
                <i class="bi bi-plus-circle"></i>
                Add New Vehicle
            </a>
        </div>
        
        <?php if (empty($vehicles)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-car-front"></i>
                </div>
                <h2 class="empty-title">No Vehicles Yet</h2>
                <p class="empty-text">You haven't registered any vehicles yet. Add your first vehicle to get started.</p>
                <a href="add.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Add Your First Vehicle
                </a>
            </div>
        <?php else: ?>
            <div class="vehicle-grid">
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="vehicle-card">
                        <div class="vehicle-header">
                            <div class="vehicle-icon">
                                <?php
                                $icons = [
                                    'car' => 'bi-car-front-fill',
                                    'motorcycle' => 'bi-bicycle',
                                    'truck' => 'bi-truck',
                                    'van' => 'bi-truck-front-fill',
                                    'suv' => 'bi-car-front-fill'
                                ];
                                $icon = $icons[$vehicle['vehicle_type']] ?? 'bi-car-front-fill';
                                ?>
                                <i class="bi <?php echo $icon; ?>"></i>
                            </div>
                            <div class="vehicle-title">
                                <h3 class="vehicle-reg"><?php echo htmlspecialchars($vehicle['registration_no']); ?></h3>
                                <p class="vehicle-model"><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></p>
                            </div>
                        </div>
                        
                        <div class="vehicle-body">
                            <div class="vehicle-info">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="bi bi-calendar3"></i>
                                    </div>
                                    <div class="info-content">
                                        <p class="info-label">Year</p>
                                        <p class="info-value"><?php echo htmlspecialchars($vehicle['year']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="bi bi-tag-fill"></i>
                                    </div>
                                    <div class="info-content">
                                        <p class="info-label">Type</p>
                                        <p class="info-value">
                                            <span class="type-badge type-<?php echo $vehicle['vehicle_type']; ?>">
                                                <?php echo htmlspecialchars(ucfirst($vehicle['vehicle_type'])); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($vehicle['color'])): ?>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="bi bi-palette"></i>
                                    </div>
                                    <div class="info-content">
                                        <p class="info-label">Color</p>
                                        <p class="info-value"><?php echo htmlspecialchars($vehicle['color']); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($vehicle['vin'])): ?>
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="bi bi-fingerprint"></i>
                                    </div>
                                    <div class="info-content">
                                        <p class="info-label">VIN</p>
                                        <p class="info-value" style="font-size: 0.85rem;"><?php echo htmlspecialchars($vehicle['vin']); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="vehicle-actions">
                                <a href="edit.php?id=<?php echo $vehicle['vehicle_id']; ?>" class="btn-action btn-edit">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </a>
                                <button type="button" class="btn-action btn-delete" onclick="confirmDelete(<?php echo $vehicle['vehicle_id']; ?>, '<?php echo htmlspecialchars($vehicle['registration_no'], ENT_QUOTES); ?>')">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                        Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete vehicle <strong id="vehicleReg"></strong>?</p>
                    <p class="text-muted mb-0"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="delete.php" id="deleteForm">
                        <input type="hidden" name="vehicle_id" id="deleteVehicleId">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i>Delete Vehicle
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(vehicleId, regNo) {
            document.getElementById('deleteVehicleId').value = vehicleId;
            document.getElementById('vehicleReg').textContent = regNo;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
</body>
</html>
