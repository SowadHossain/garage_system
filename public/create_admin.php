<?php
require_once __DIR__ . "/../config/db.php";

$username = "admin";
$plainPassword = "admin123"; // change to something stronger
$hash = password_hash($plainPassword, PASSWORD_DEFAULT);

$sql = "INSERT INTO staff (name, role, username, email, password_hash, is_email_verified, active)
        VALUES ('Administrator', 'admin', ?, 'admin@example.com', ?, 1, 1)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $hash);

if ($stmt->execute()) {
    echo "Admin user created successfully.";
} else {
    echo "Error: " . $conn->error;
}

$stmt->close();
