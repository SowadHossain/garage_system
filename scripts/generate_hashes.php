<?php
// Generate correct password hashes
echo "Generating password hashes...\n\n";

$staffpass_hash = password_hash('staffpass', PASSWORD_BCRYPT);
$customer123_hash = password_hash('customer123', PASSWORD_BCRYPT);

echo "For 'staffpass':\n";
echo $staffpass_hash . "\n\n";

echo "For 'customer123':\n";
echo $customer123_hash . "\n\n";

// Verify they work
echo "Verification:\n";
echo "staffpass matches: " . (password_verify('staffpass', $staffpass_hash) ? 'YES' : 'NO') . "\n";
echo "customer123 matches: " . (password_verify('customer123', $customer123_hash) ? 'YES' : 'NO') . "\n";

// Generate SQL update statements
echo "\n\n-- SQL UPDATE STATEMENTS:\n\n";
echo "UPDATE staff SET password_hash = '$staffpass_hash' WHERE staff_id IN (1000, 1001, 1002);\n\n";
echo "UPDATE customers SET password_hash = '$customer123_hash' WHERE customer_id IN (2000, 2001);\n";
