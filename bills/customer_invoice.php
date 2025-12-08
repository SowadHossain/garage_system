<?php
// bills/customer_invoice.php - Customer Invoice Detail View

session_start();

require_once __DIR__ . "/../config/db.php";

// Check if customer is logged in
if (empty($_SESSION['customer_id'])) {
    header("Location: ../public/customer_login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];
$bill_id = (int)($_GET['id'] ?? 0);

if ($bill_id === 0) {
    header("Location: customer_bills.php");
    exit;
}

// Get bill details with customer verification
$stmt = $conn->prepare("SELECT b.bill_id, b.job_id, b.total_amount, b.payment_status, b.payment_method, 
                               b.created_at, b.paid_at, b.notes,
                               j.start_date, j.end_date, j.status as job_status, j.description as job_description,
                               v.registration_no, v.brand, v.model, v.year, v.vin,
                               c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                               c.address as customer_address
                        FROM bills b
                        JOIN jobs j ON b.job_id = j.job_id
                        JOIN customers c ON j.customer_id = c.customer_id
                        LEFT JOIN vehicles v ON j.vehicle_id = v.vehicle_id
                        WHERE b.bill_id = ? AND j.customer_id = ?
                        LIMIT 1");
$stmt->bind_param("ii", $bill_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: customer_bills.php");
    exit;
}

$bill = $result->fetch_assoc();
$stmt->close();

// Get bill items/services
$items_stmt = $conn->prepare("SELECT service_name, description, quantity, unit_price, total_price
                               FROM bill_items
                               WHERE bill_id = ?
                               ORDER BY service_name");
$items_stmt->bind_param("i", $bill_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$bill_items = [];
while ($row = $items_result->fetch_assoc()) {
    $bill_items[] = $row;
}
$items_stmt->close();

// Calculate subtotal and tax (if applicable)
$subtotal = 0;
foreach ($bill_items as $item) {
    $subtotal += $item['total_price'];
}
$tax_rate = 0; // You can add tax calculation here
$tax_amount = $subtotal * $tax_rate;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo str_pad($bill_id, 5, '0', STR_PAD_LEFT); ?> - Screw Dheela</title>
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
        
        .nav-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .main-content {
            margin-top: 70px;
            padding: 2rem;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .invoice-container {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .company-info h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .company-tagline {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .invoice-meta {
            text-align: right;
        }
        
        .invoice-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
        }
        
        .invoice-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .status-paid {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .status-unpaid {
            background: #f8d7da;
            color: #842029;
        }
        
        .invoice-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .party-section h3 {
            font-size: 0.875rem;
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }
        
        .party-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.5rem;
        }
        
        .party-details {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .vehicle-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .vehicle-section h3 {
            font-size: 0.875rem;
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }
        
        .vehicle-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .vehicle-item {
            display: flex;
            flex-direction: column;
        }
        
        .vehicle-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .vehicle-value {
            font-size: 1rem;
            font-weight: 600;
            color: #212529;
        }
        
        .items-table {
            width: 100%;
            margin-bottom: 2rem;
        }
        
        .items-table thead {
            background: #f8f9fa;
        }
        
        .items-table th {
            padding: 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .items-table th.text-end {
            text-align: right;
        }
        
        .items-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            color: #212529;
        }
        
        .items-table td.text-end {
            text-align: right;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .item-description {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 2rem;
        }
        
        .totals-table {
            width: 350px;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .totals-row.total {
            border-top: 2px solid #212529;
            border-bottom: 2px solid #212529;
            padding: 1rem 0;
            margin-top: 0.5rem;
        }
        
        .totals-label {
            font-weight: 600;
            color: #212529;
        }
        
        .totals-value {
            font-weight: 600;
            color: #212529;
        }
        
        .totals-row.total .totals-label,
        .totals-row.total .totals-value {
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .notes-section {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .notes-section h3 {
            font-size: 0.875rem;
            font-weight: 700;
            color: #664d03;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
        }
        
        .notes-content {
            color: #664d03;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .payment-info {
            background: #d1e7dd;
            border-left: 4px solid var(--primary-color);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .payment-info h3 {
            font-size: 0.875rem;
            font-weight: 700;
            color: #0f5132;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
        }
        
        .payment-details {
            color: #0f5132;
            font-size: 0.9rem;
        }
        
        .footer-text {
            text-align: center;
            color: #6c757d;
            font-size: 0.875rem;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }
        
        @media print {
            .top-nav,
            .no-print {
                display: none !important;
            }
            
            body {
                background: white;
            }
            
            .main-content {
                margin-top: 0;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
                border: none;
            }
        }
        
        @media (max-width: 768px) {
            .invoice-container {
                padding: 1.5rem;
            }
            
            .invoice-header {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .invoice-meta {
                text-align: left;
            }
            
            .invoice-parties {
                grid-template-columns: 1fr;
            }
            
            .vehicle-details {
                grid-template-columns: 1fr;
            }
            
            .items-table {
                font-size: 0.875rem;
            }
            
            .items-table th,
            .items-table td {
                padding: 0.75rem 0.5rem;
            }
            
            .totals-table {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="top-nav no-print">
        <a href="customer_bills.php" class="nav-brand">
            <i class="bi bi-arrow-left me-2"></i>Back to Bills
        </a>
        <div class="nav-actions">
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-printer me-1"></i>Print
            </button>
            <a href="../public/customer_logout.php" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </nav>
    
    <div class="main-content">
        <div class="invoice-container">
            <!-- Header -->
            <div class="invoice-header">
                <div class="company-info">
                    <h1><i class="bi bi-tools me-2"></i>Screw Dheela</h1>
                    <p class="company-tagline">Professional Auto Service & Repair</p>
                </div>
                <div class="invoice-meta">
                    <div class="invoice-number">INVOICE #<?php echo str_pad($bill_id, 5, '0', STR_PAD_LEFT); ?></div>
                    <div class="invoice-date">
                        Date: <?php echo date('F d, Y', strtotime($bill['created_at'])); ?>
                    </div>
                    <div>
                        <span class="status-badge status-<?php echo $bill['payment_status']; ?>">
                            <?php echo strtoupper($bill['payment_status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Billing Parties -->
            <div class="invoice-parties">
                <div class="party-section">
                    <h3>Bill To</h3>
                    <div class="party-name"><?php echo htmlspecialchars($bill['customer_name']); ?></div>
                    <div class="party-details">
                        <?php if ($bill['customer_address']): ?>
                            <?php echo nl2br(htmlspecialchars($bill['customer_address'])); ?><br>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($bill['customer_email']); ?><br>
                        <?php echo htmlspecialchars($bill['customer_phone']); ?>
                    </div>
                </div>
                
                <div class="party-section">
                    <h3>Job Information</h3>
                    <div class="party-details">
                        <strong>Job ID:</strong> #<?php echo str_pad($bill['job_id'], 5, '0', STR_PAD_LEFT); ?><br>
                        <strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($bill['start_date'])); ?><br>
                        <?php if ($bill['end_date']): ?>
                            <strong>End Date:</strong> <?php echo date('M d, Y', strtotime($bill['end_date'])); ?><br>
                        <?php endif; ?>
                        <strong>Status:</strong> <?php echo ucfirst(str_replace('_', ' ', $bill['job_status'])); ?>
                    </div>
                </div>
            </div>
            
            <!-- Vehicle Information -->
            <div class="vehicle-section">
                <h3><i class="bi bi-car-front me-2"></i>Vehicle Details</h3>
                <div class="vehicle-details">
                    <div class="vehicle-item">
                        <span class="vehicle-label">Registration</span>
                        <span class="vehicle-value"><?php echo htmlspecialchars($bill['registration_no']); ?></span>
                    </div>
                    <div class="vehicle-item">
                        <span class="vehicle-label">Make & Model</span>
                        <span class="vehicle-value"><?php echo htmlspecialchars($bill['brand'] . ' ' . $bill['model']); ?></span>
                    </div>
                    <div class="vehicle-item">
                        <span class="vehicle-label">Year</span>
                        <span class="vehicle-value"><?php echo htmlspecialchars($bill['year']); ?></span>
                    </div>
                    <?php if ($bill['vin']): ?>
                        <div class="vehicle-item">
                            <span class="vehicle-label">VIN</span>
                            <span class="vehicle-value"><?php echo htmlspecialchars($bill['vin']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Services/Items -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bill_items)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #6c757d;">
                                No itemized services available
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bill_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="item-name"><?php echo htmlspecialchars($item['service_name']); ?></div>
                                    <?php if ($item['description']): ?>
                                        <div class="item-description"><?php echo htmlspecialchars($item['description']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?php echo $item['quantity']; ?></td>
                                <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-end">$<?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Totals -->
            <div class="totals-section">
                <div class="totals-table">
                    <?php if (!empty($bill_items)): ?>
                        <div class="totals-row">
                            <span class="totals-label">Subtotal</span>
                            <span class="totals-value">$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <?php if ($tax_amount > 0): ?>
                            <div class="totals-row">
                                <span class="totals-label">Tax (<?php echo ($tax_rate * 100); ?>%)</span>
                                <span class="totals-value">$<?php echo number_format($tax_amount, 2); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="totals-row total">
                        <span class="totals-label">Total Amount</span>
                        <span class="totals-value">$<?php echo number_format($bill['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Information -->
            <?php if ($bill['payment_status'] === 'paid'): ?>
                <div class="payment-info">
                    <h3><i class="bi bi-check-circle me-2"></i>Payment Received</h3>
                    <div class="payment-details">
                        <strong>Payment Date:</strong> <?php echo date('F d, Y', strtotime($bill['paid_at'])); ?><br>
                        <?php if ($bill['payment_method']): ?>
                            <strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($bill['payment_method'])); ?><br>
                        <?php endif; ?>
                        <strong>Amount Paid:</strong> $<?php echo number_format($bill['total_amount'], 2); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Notes -->
            <?php if ($bill['notes']): ?>
                <div class="notes-section">
                    <h3><i class="bi bi-sticky me-2"></i>Notes</h3>
                    <div class="notes-content">
                        <?php echo nl2br(htmlspecialchars($bill['notes'])); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <div class="footer-text">
                <p>Thank you for your business!</p>
                <p class="mt-2">
                    <small>
                        If you have any questions about this invoice, please contact us.<br>
                        Screw Dheela Management System
                    </small>
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
