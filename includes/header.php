<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Screw Dheela Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS (CDN) -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="/garage_system/assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="/garage_system/public/index.php">Screw Dheela</a>

        <?php if (!empty($_SESSION['staff_id'])): ?>
            <div class="d-flex">
                <span class="navbar-text me-3">
                    Logged in as: <?php echo htmlspecialchars($_SESSION['staff_name'] ?? 'Staff'); ?>
                    (<?php echo htmlspecialchars($_SESSION['staff_role'] ?? ''); ?>)
                </span>
                <a class="btn btn-outline-light btn-sm"
                   href="/garage_system/public/logout.php">Logout</a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
