<?php
// config/db.php

$host = "localhost";
$user = "garage_user";        // EXACTLY this
$pass = "GaragePass123!";     // EXACTLY what you used in CREATE USER
$db   = "garage_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
