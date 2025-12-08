<?php
// vehicles/delete.php - Delete Vehicle

session_start();

require_once __DIR__ . "/../config/db.php";

// Check if customer is logged in
if (empty($_SESSION['customer_id'])) {
    header("Location: ../public/customer_login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
    
    if ($vehicle_id > 0) {
        // Delete vehicle (only if it belongs to this customer)
        $stmt = $conn->prepare("DELETE FROM vehicles WHERE vehicle_id = ? AND customer_id = ?");
        $stmt->bind_param("ii", $vehicle_id, $customer_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: list.php?success=deleted");
        exit;
    }
}

header("Location: list.php");
exit;
