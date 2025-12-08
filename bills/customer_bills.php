<?php
// bills/customer_bills.php - Customer Bills List

session_start();

require_once __DIR__ . "/../config/db.php";

// Check if customer is logged in
if (empty($_SESSION['customer_id'])) {
    header("Location: ../public/customer_login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

// Get all bills for this customer
$stmt = $conn->prepare("SELECT b.bill_id, b.job_id, b.total_amount, b.payment_status, b.payment_method, 
                               b.created_at, b.paid_at,
                               j.start_date, j.end_date, j.status as job_status,
                               v.registration_no, v.brand, v.model
                        FROM bills b
                        JOIN jobs j ON b.job_id = j.job_id
                        LEFT JOIN vehicles v ON j.vehicle_id = v.vehicle_id
                        WHERE j.customer_id = ?
                        ORDER BY b.created_at DESC");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$bills = [];
while ($row = $result->fetch_assoc()) {
    $bills[] = $row;
}
$stmt->close();

// Calculate statistics
$total_paid = 0;
$total_unpaid = 0;
$bill_count = count($bills);

foreach ($bills as $bill) {
    if ($bill['payment_status'] === 'paid') {
        $total_paid += $bill['total_amount'];
    } else {
        $total_unpaid += $bill['total_amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bills - Screw Dheela</title>
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
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #6c757d;
            font-size: 1rem;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #212529;
        }
        
        .stat-value.green {
            color: var(--primary-color);
        }
        
        .stat-value.red {
            color: #dc3545;
        }
        
        .bills-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }
        
        .bill-item {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s;
        }
        
        .bill-item:last-child {
            border-bottom: none;
        }
        
        .bill-item:hover {
            background: #f8f9fa;
        }
        
        .bill-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .bill-info {
            flex: 1;
        }
        
        .bill-id {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .bill-vehicle {
            font-size: 1.125rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.5rem;
        }
        
        .bill-date {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .bill-amount {
            text-align: right;
        }
        
        .amount-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .amount-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #212529;
        }
        
        .bill-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-paid {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .status-unpaid {
            background: #f8d7da;
            color: #842029;
        }
        
        .status-partial {
            background: #fff3cd;
            color: #664d03;
        }
        
        .bill-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .btn-view {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s;
        }
        
        .btn-view:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-download {
            background: white;
            border: 2px solid #dee2e6;
            color: #212529;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .btn-download:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
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
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .bill-header {
                flex-direction: column;
            }
            
            .bill-amount {
                text-align: left;
            }
            
            .amount-value {
                font-size: 1.5rem;
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
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-receipt me-2"></i>My Bills & Invoices
            </h1>
            <p class="page-subtitle">View and manage your service bills</p>
        </div>
        
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-label">Total Bills</div>
                <div class="stat-value"><?php echo $bill_count; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Paid</div>
                <div class="stat-value green">$<?php echo number_format($total_paid, 2); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Outstanding</div>
                <div class="stat-value red">$<?php echo number_format($total_unpaid, 2); ?></div>
            </div>
        </div>
        
        <div class="bills-container">
            <?php if (empty($bills)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <h2 class="empty-title">No Bills Yet</h2>
                    <p class="empty-text">You don't have any bills at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($bills as $bill): ?>
                    <div class="bill-item">
                        <div class="bill-header">
                            <div class="bill-info">
                                <p class="bill-id">
                                    <i class="bi bi-hash"></i>
                                    Bill #<?php echo str_pad($bill['bill_id'], 5, '0', STR_PAD_LEFT); ?>
                                </p>
                                <h3 class="bill-vehicle">
                                    <i class="bi bi-car-front me-2"></i>
                                    <?php echo htmlspecialchars($bill['registration_no'] . ' - ' . $bill['brand'] . ' ' . $bill['model']); ?>
                                </h3>
                                <p class="bill-date">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    Issued: <?php echo date('M d, Y', strtotime($bill['created_at'])); ?>
                                    <?php if ($bill['payment_status'] === 'paid' && $bill['paid_at']): ?>
                                        <span class="ms-2">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Paid: <?php echo date('M d, Y', strtotime($bill['paid_at'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="bill-amount">
                                <p class="amount-label">Total Amount</p>
                                <p class="amount-value">$<?php echo number_format($bill['total_amount'], 2); ?></p>
                            </div>
                        </div>
                        
                        <div class="bill-meta">
                            <div class="meta-item">
                                <i class="bi bi-tools"></i>
                                <span>Job #<?php echo str_pad($bill['job_id'], 5, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <?php if ($bill['payment_method']): ?>
                                <div class="meta-item">
                                    <i class="bi bi-credit-card"></i>
                                    <span><?php echo htmlspecialchars(ucfirst($bill['payment_method'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <div>
                                <span class="status-badge status-<?php echo $bill['payment_status']; ?>">
                                    <?php if ($bill['payment_status'] === 'paid'): ?>
                                        <i class="bi bi-check-circle-fill me-1"></i>Paid
                                    <?php elseif ($bill['payment_status'] === 'partial'): ?>
                                        <i class="bi bi-clock-fill me-1"></i>Partial
                                    <?php else: ?>
                                        <i class="bi bi-exclamation-circle-fill me-1"></i>Unpaid
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="bill-actions">
                            <a href="customer_invoice.php?id=<?php echo $bill['bill_id']; ?>" class="btn-view">
                                <i class="bi bi-eye"></i>
                                View Invoice
                            </a>
                            <a href="customer_invoice.php?id=<?php echo $bill['bill_id']; ?>&download=1" class="btn-download">
                                <i class="bi bi-download"></i>
                                Download PDF
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
