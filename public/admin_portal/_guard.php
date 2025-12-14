<?php
// public/admin_portal/_guard.php
session_start();
require_once __DIR__ . "/../../config/db.php";

if (empty($_SESSION['staff_id']) || ($_SESSION['staff_role'] ?? '') !== 'admin') {
    header("Location: ../staff_login.php");
    exit;
}

$staff_id = (int)($_SESSION['staff_id'] ?? 0);
$staff_name = $_SESSION['staff_name'] ?? 'Admin';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

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

function ui_header_admin($title, $staff_name) {
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
        :root {
            --primary-color: #0d6efd;
            --primary-dark: #0a58ca;
            --secondary-color: #0dcaf0;
            --accent-color: #6610f2;
            --light-bg: #f0f4ff;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light-bg);
            margin: 0;
            padding: 0;
        }

        .top-nav {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .top-nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .container-main {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .dashboard-header { margin-bottom: 2rem; }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            color: #6b7280;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(13,110,253,0.15);
        }

        .stat-card.blue { border-left-color: #0d6efd; }
        .stat-card.purple { border-left-color: #6610f2; }
        .stat-card.cyan { border-left-color: #0dcaf0; }
        .stat-card.green { border-left-color: #198754; }
        .stat-card.orange { border-left-color: #fd7e14; }
        .stat-card.red { border-left-color: #dc3545; }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.blue { background: #cfe2ff; color: #084298; }
        .stat-icon.purple { background: #e0cffc; color: #3d0a91; }
        .stat-icon.cyan { background: #cff4fc; color: #055160; }
        .stat-icon.green { background: #d1e7dd; color: #0a3622; }
        .stat-icon.orange { background: #ffe5d0; color: #984c0c; }
        .stat-icon.red { background: #f8d7da; color: #58151c; }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }

        .quick-actions, .reports-section, .data-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1.25rem;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13,110,253,0.3);
            color: white;
        }

        .action-btn i { font-size: 1.75rem; }
        .action-text { flex: 1; }
        .action-title { font-weight: 600; font-size: 1.05rem; margin-bottom: 0.25rem; }
        .action-subtitle { font-size: 0.85rem; opacity: 0.9; }

        .report-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .report-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 1.5rem;
            border: 2px solid #dee2e6;
            transition: all 0.2s;
        }

        .report-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(13,110,253,0.15);
        }

        .report-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }

        .report-card.revenue .report-icon { background: #d1e7dd; color: #0a3622; }
        .report-card.services .report-icon { background: #cff4fc; color: #055160; }
        .report-card.customers .report-icon { background: #ffe5d0; color: #984c0c; }

        .report-title { font-size: 1.1rem; font-weight: 600; color: #111827; margin-bottom: 0.5rem; }
        .report-description { color: #6b7280; font-size: 0.9rem; margin-bottom: 1rem; }

        .content-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 992px) { .content-row { grid-template-columns: 1fr; } }

        .list-item {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
        }
        .list-item:last-child { border-bottom: none; }
        .list-item:hover { background: #f0f4ff; }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        .item-title { font-weight: 600; color: #111827; }
        .item-details { color: #6b7280; font-size: 0.9rem; }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge.success { background: #d1e7dd; color: #0a3622; }
        .badge.warning { background: #fff3cd; color: #664d03; }
        .badge.info { background: #cfe2ff; color: #084298; }
        .badge.danger { background: #f8d7da; color: #58151c; }
        .badge.primary { background: #e0cffc; color: #3d0a91; }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.3); color: white; }
    </style>
</head>
<body>
<nav class="top-nav">
    <div class="top-nav-content">
        <div class="logo">
            <i class="bi bi-shield-check"></i>
            Screw Dheela - Admin Portal
        </div>
        <div class="user-info">
            <span class="user-badge">
                <i class="bi bi-person-circle me-2"></i>
                <?php echo h($staff_name); ?>
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

function ui_footer_admin() {
?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
