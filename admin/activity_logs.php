<?php
// Activity Logs Viewer (Admin Only)
$page_title = "Activity Logs";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../config/db.php';

// Admin only
requireRole(['admin']);

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Filters
$filter_user_type = $_GET['user_type'] ?? '';
$filter_action_type = $_GET['action_type'] ?? '';
$filter_severity = $_GET['severity'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_entity_type = $_GET['entity_type'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_search = $_GET['search'] ?? '';

// Build query
$where_clauses = [];
$params = [];
$types = '';

if ($filter_user_type) {
    $where_clauses[] = "user_type = ?";
    $params[] = $filter_user_type;
    $types .= 's';
}

if ($filter_action_type) {
    $where_clauses[] = "action_type = ?";
    $params[] = $filter_action_type;
    $types .= 's';
}

if ($filter_severity) {
    $where_clauses[] = "severity = ?";
    $params[] = $filter_severity;
    $types .= 's';
}

if ($filter_status) {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($filter_entity_type) {
    $where_clauses[] = "entity_type = ?";
    $params[] = $filter_entity_type;
    $types .= 's';
}

if ($filter_date_from) {
    $where_clauses[] = "DATE(created_at) >= ?";
    $params[] = $filter_date_from;
    $types .= 's';
}

if ($filter_date_to) {
    $where_clauses[] = "DATE(created_at) <= ?";
    $params[] = $filter_date_to;
    $types .= 's';
}

if ($filter_search) {
    $where_clauses[] = "(action LIKE ? OR description LIKE ? OR username LIKE ? OR ip_address LIKE ?)";
    $search_term = "%$filter_search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'ssss';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Count total records
$count_query = "SELECT COUNT(*) as total FROM activity_logs $where_sql";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_records = $conn->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $per_page);

// Fetch logs
$query = "SELECT * FROM activity_logs 
          $where_sql 
          ORDER BY created_at DESC 
          LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs_result = $stmt->get_result();

// Get statistics
$stats_query = "SELECT 
                    COUNT(*) as total_logs,
                    COUNT(CASE WHEN action_type = 'login' THEN 1 END) as login_count,
                    COUNT(CASE WHEN action_type = 'create' THEN 1 END) as create_count,
                    COUNT(CASE WHEN action_type = 'update' THEN 1 END) as update_count,
                    COUNT(CASE WHEN action_type = 'delete' THEN 1 END) as delete_count,
                    COUNT(CASE WHEN severity = 'warning' THEN 1 END) as warning_count,
                    COUNT(CASE WHEN severity = 'error' THEN 1 END) as error_count,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count
                FROM activity_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$stats = $conn->query($stats_query)->fetch_assoc();
?>

<style>
.activity-log-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px 0;
    margin: -20px -15px 30px -15px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.filter-panel {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.log-entry {
    background: white;
    border-left: 4px solid #dee2e6;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 4px;
    transition: all 0.2s;
}

.log-entry:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.log-entry.severity-info { border-left-color: #0dcaf0; }
.log-entry.severity-warning { border-left-color: #ffc107; }
.log-entry.severity-error { border-left-color: #dc3545; }
.log-entry.severity-critical { border-left-color: #842029; }

.log-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
}

.log-action {
    font-weight: 600;
    font-size: 1.1rem;
    color: #212529;
}

.log-meta {
    font-size: 0.85rem;
    color: #6c757d;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.log-description {
    color: #495057;
    margin-bottom: 10px;
}

.log-changes {
    background: #f8f9fa;
    border-radius: 4px;
    padding: 10px;
    margin-top: 10px;
    font-family: monospace;
    font-size: 0.85rem;
}

.badge-action {
    font-size: 0.75rem;
    padding: 4px 8px;
}

.export-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
}
</style>

<div class="activity-log-container">
    <div class="container">
        <h2 class="text-white mb-3">
            <i class="bi bi-clock-history me-2"></i>Activity Logs & Audit Trail
        </h2>
        <p class="text-white-50">Track all user actions and system events</p>
    </div>
</div>

<div class="container">
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['total_logs']); ?></div>
            <div class="stat-label">Total Actions (24h)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['login_count']); ?></div>
            <div class="stat-label">Logins</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['create_count'] + $stats['update_count'] + $stats['delete_count']); ?></div>
            <div class="stat-label">Database Changes</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-warning"><?php echo number_format($stats['warning_count']); ?></div>
            <div class="stat-label">Warnings</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-danger"><?php echo number_format($stats['error_count']); ?></div>
            <div class="stat-label">Errors</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-danger"><?php echo number_format($stats['failed_count']); ?></div>
            <div class="stat-label">Failed Actions</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-panel">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">User Type</label>
                <select name="user_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="staff" <?php echo $filter_user_type === 'staff' ? 'selected' : ''; ?>>Staff</option>
                    <option value="customer" <?php echo $filter_user_type === 'customer' ? 'selected' : ''; ?>>Customer</option>
                    <option value="system" <?php echo $filter_user_type === 'system' ? 'selected' : ''; ?>>System</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Action Type</label>
                <select name="action_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="login" <?php echo $filter_action_type === 'login' ? 'selected' : ''; ?>>Login</option>
                    <option value="logout" <?php echo $filter_action_type === 'logout' ? 'selected' : ''; ?>>Logout</option>
                    <option value="create" <?php echo $filter_action_type === 'create' ? 'selected' : ''; ?>>Create</option>
                    <option value="update" <?php echo $filter_action_type === 'update' ? 'selected' : ''; ?>>Update</option>
                    <option value="delete" <?php echo $filter_action_type === 'delete' ? 'selected' : ''; ?>>Delete</option>
                    <option value="status_change" <?php echo $filter_action_type === 'status_change' ? 'selected' : ''; ?>>Status Change</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Severity</label>
                <select name="severity" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="info" <?php echo $filter_severity === 'info' ? 'selected' : ''; ?>>Info</option>
                    <option value="warning" <?php echo $filter_severity === 'warning' ? 'selected' : ''; ?>>Warning</option>
                    <option value="error" <?php echo $filter_severity === 'error' ? 'selected' : ''; ?>>Error</option>
                    <option value="critical" <?php echo $filter_severity === 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filter_date_from); ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filter_date_to); ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?php echo htmlspecialchars($filter_search); ?>">
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Apply Filters
                </button>
                <a href="activity_logs.php" class="btn btn-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
                <span class="text-muted ms-3">Showing <?php echo number_format($total_records); ?> results</span>
            </div>
        </form>
    </div>

    <!-- Activity Logs -->
    <div class="logs-list">
        <?php if ($logs_result->num_rows > 0): ?>
            <?php while ($log = $logs_result->fetch_assoc()): ?>
                <div class="log-entry severity-<?php echo $log['severity']; ?>">
                    <div class="log-header">
                        <div>
                            <div class="log-action"><?php echo htmlspecialchars($log['action']); ?></div>
                            <div class="log-meta">
                                <span>
                                    <i class="bi bi-person-circle me-1"></i>
                                    <?php echo htmlspecialchars($log['username']); ?>
                                    <span class="badge bg-secondary badge-action"><?php echo $log['user_type']; ?></span>
                                </span>
                                
                                <span>
                                    <i class="bi bi-tag me-1"></i>
                                    <span class="badge bg-info badge-action"><?php echo $log['action_type']; ?></span>
                                </span>
                                
                                <?php if ($log['entity_type']): ?>
                                    <span>
                                        <i class="bi bi-file-earmark me-1"></i>
                                        <?php echo htmlspecialchars($log['entity_type']); ?>#<?php echo $log['entity_id']; ?>
                                    </span>
                                <?php endif; ?>
                                
                                <span>
                                    <i class="bi bi-globe me-1"></i>
                                    <?php echo htmlspecialchars($log['ip_address']); ?>
                                </span>
                                
                                <span>
                                    <i class="bi bi-clock me-1"></i>
                                    <?php echo date('M d, Y g:i:s A', strtotime($log['created_at'])); ?>
                                </span>
                                
                                <?php if ($log['severity'] !== 'info'): ?>
                                    <span class="badge bg-<?php echo $log['severity'] === 'warning' ? 'warning' : 'danger'; ?> badge-action">
                                        <?php echo strtoupper($log['severity']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($log['status'] === 'failed'): ?>
                                    <span class="badge bg-danger badge-action">FAILED</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($log['description']): ?>
                        <div class="log-description">
                            <?php echo htmlspecialchars($log['description']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($log['old_values'] || $log['new_values']): ?>
                        <div class="log-changes">
                            <?php if ($log['old_values']): ?>
                                <strong>Before:</strong> <?php echo htmlspecialchars($log['old_values']); ?><br>
                            <?php endif; ?>
                            <?php if ($log['new_values']): ?>
                                <strong>After:</strong> <?php echo htmlspecialchars($log['new_values']); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Log pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No activity logs found matching your criteria.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Export Button -->
<a href="export_logs.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success btn-lg export-btn shadow">
    <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export to CSV
</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
