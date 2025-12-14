<?php
// public/receptionist_portal/_guard.php
session_start();
require_once __DIR__ . "/../../config/db.php";

if (empty($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'receptionist') {
    header("Location: ../staff_login.php");
    exit;
}

$staff_id = (int)($_SESSION['staff_id'] ?? 0);
$staff_name = $_SESSION['staff_name'] ?? 'Receptionist';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function ui_header($title, $staff_name) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo h($title); ?> - Screw Dheela</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <style>
            :root{--primary-color:#059669;--primary-dark:#047857;--secondary-color:#10b981;--accent-color:#14b8a6;--light-bg:#f0fdf4;}
            body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--light-bg);margin:0;padding:0;}
            .top-nav{background:linear-gradient(135deg,var(--primary-color),var(--accent-color));color:#fff;padding:1rem 2rem;box-shadow:0 2px 10px rgba(0,0,0,.1);}
            .top-nav-content{display:flex;justify-content:space-between;align-items:center;max-width:1400px;margin:0 auto;}
            .logo{font-size:1.5rem;font-weight:700;display:flex;align-items:center;gap:.5rem;}
            .user-info{display:flex;align-items:center;gap:1rem;}
            .user-badge{background:rgba(255,255,255,.2);padding:.5rem 1rem;border-radius:20px;font-size:.9rem;}
            .logout-btn{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);color:#fff;padding:.5rem 1rem;border-radius:8px;text-decoration:none;transition:background .2s;}
            .logout-btn:hover{background:rgba(255,255,255,.3);color:#fff;}
            .container-main{max-width:1400px;margin:2rem auto;padding:0 2rem;}
            .card-soft{background:#fff;border-radius:12px;padding:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.06);}
            .section-title{font-size:1.25rem;font-weight:600;color:#111827;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;}
            .btn-sd{background:linear-gradient(135deg,var(--primary-color),var(--secondary-color));border:none;color:#fff;}
            .btn-sd:hover{filter:brightness(.98);color:#fff;}
            .table thead th{color:#374151;font-weight:700;}
            .badge.success{background:#d1fae5;color:#065f46;}
            .badge.warning{background:#fef3c7;color:#92400e;}
            .badge.info{background:#dbeafe;color:#1e40af;}
            .badge.danger{background:#fee2e2;color:#991b1b;}
        </style>
    </head>
    <body>
    <nav class="top-nav">
        <div class="top-nav-content">
            <div class="logo">
                <i class="bi bi-person-badge"></i>
                Screw Dheela - Receptionist Portal
            </div>
            <div class="user-info">
                <span class="user-badge">
                    <i class="bi bi-person-circle me-2"></i><?php echo h($staff_name); ?>
                </span>
                <a href="../logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    <div class="container-main">
    <?php
}

function ui_footer() {
    ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
}

function statusBadgeClass($status) {
    switch ($status) {
        case 'requested': return 'warning';
        case 'booked':
        case 'in_progress': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'info';
    }
}
