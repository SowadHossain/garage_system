<?php
// Test password hashes from seed.sql
require_once __DIR__ . '/../config/db.php';

echo "=== Testing Password Hashes ===\n\n";

// The hash from seed.sql
$stored_hash = '$2y$10$QY05j2FE31Am7yuPi0mIhOILHkCwfPeI6cM7tit8dWiqQcVk0gug6';

// Test passwords
$test_passwords = ['staffpass', 'customer123', 'admin', 'test'];

echo "Hash from seed.sql: $stored_hash\n\n";

foreach ($test_passwords as $pwd) {
    $result = password_verify($pwd, $stored_hash);
    echo "Password '$pwd': " . ($result ? '✓ MATCHES' : '✗ DOES NOT MATCH') . "\n";
}

echo "\n=== Checking Database Contents ===\n\n";

// Check staff table
echo "--- Staff Accounts ---\n";
$staff_result = $conn->query("SELECT staff_id, username, role, email, active, password_hash FROM staff ORDER BY staff_id");
if ($staff_result && $staff_result->num_rows > 0) {
    while ($row = $staff_result->fetch_assoc()) {
        echo "ID: {$row['staff_id']}, Username: {$row['username']}, Role: {$row['role']}, Active: {$row['active']}\n";
        echo "  Email: {$row['email']}\n";
        echo "  Hash: {$row['password_hash']}\n";
        
        // Test if staffpass works
        $test = password_verify('staffpass', $row['password_hash']);
        echo "  Test 'staffpass': " . ($test ? '✓ WORKS' : '✗ FAILS') . "\n\n";
    }
} else {
    echo "No staff found or error: " . $conn->error . "\n\n";
}

// Check customers table
echo "--- Customer Accounts ---\n";
$customer_result = $conn->query("SELECT customer_id, name, email, phone, is_email_verified, password_hash FROM customers ORDER BY customer_id");
if ($customer_result && $customer_result->num_rows > 0) {
    while ($row = $customer_result->fetch_assoc()) {
        echo "ID: {$row['customer_id']}, Name: {$row['name']}, Email: {$row['email']}\n";
        echo "  Phone: {$row['phone']}, Verified: {$row['is_email_verified']}\n";
        echo "  Hash: {$row['password_hash']}\n";
        
        // Test if customer123 works
        $test = password_verify('customer123', $row['password_hash']);
        echo "  Test 'customer123': " . ($test ? '✓ WORKS' : '✗ FAILS') . "\n\n";
    }
} else {
    echo "No customers found or error: " . $conn->error . "\n\n";
}

echo "=== Generating Correct Hashes ===\n\n";
echo "For 'staffpass': " . password_hash('staffpass', PASSWORD_BCRYPT) . "\n";
echo "For 'customer123': " . password_hash('customer123', PASSWORD_BCRYPT) . "\n";
