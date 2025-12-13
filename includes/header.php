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
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Screw Dheela Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS (CDN) -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/garage_system/assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="/garage_system/public/index.php">
            <i class="bi bi-wrench-adjustable-circle me-2"></i>Screw Dheela
        </a>

        <?php if (!empty($_SESSION['staff_id'])): ?>
            <div class="d-flex align-items-center">
                <?php
                // Display role badge if role_check.php is loaded
                if (function_exists('getRoleBadge')) {
                    echo getRoleBadge() . ' ';
                }
                ?>
                <span class="navbar-text me-3 text-white">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['staff_name'] ?? 'Staff'); ?>
                </span>
                <a class="btn btn-outline-light btn-sm"
                   href="/garage_system/public/logout.php">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
