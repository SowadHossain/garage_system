<?php
// config/db.php
// Reads DB connection info from environment variables so the app can run in Docker

// Defaults kept for local XAMPP development. In Docker, set these via compose env.
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'garage_user';
$pass = getenv('DB_PASSWORD') ?: 'GaragePass123!';
$db   = getenv('DB_NAME') ?: 'garage_db';

// Create mysqli connection
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    // In production do not echo detailed errors. We'll stop execution with a generic message
    error_log('Database connection failed: ' . $conn->connect_error);
    die('Database connection failed. Check server logs.');
}

$conn->set_charset('utf8mb4');
