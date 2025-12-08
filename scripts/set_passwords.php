<?php
require_once __DIR__ . '/../config/db.php';

function set_staff_password($username, $plain) {
    global $conn;
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE staff SET password_hash = ? WHERE username = ?');
    $stmt->bind_param('ss', $hash, $username);
    $stmt->execute();
    $stmt->close();
    echo "Set staff password for $username\n";
}

function set_customer_password($customer_id, $plain) {
    global $conn;
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE customers SET password_hash = ? WHERE customer_id = ?');
    $stmt->bind_param('si', $hash, $customer_id);
    $stmt->execute();
    $stmt->close();
    echo "Set customer password for id $customer_id\n";
}

// Developer convenience passwords (change these in production)
set_staff_password('seed_admin', 'seedpass');
set_staff_password('admin', 'admin123');

set_customer_password(2000, 'alice123');
set_customer_password(2001, 'bob123');

echo "All passwords set.\n";
