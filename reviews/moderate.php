<?php
// reviews/moderate.php - Admin Review Moderation Panel
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is admin
if (!isset($_SESSION['staff_id']) || $_SESSION['staff_role'] !== 'admin') {
    header('Location: ../public/staff_login.php');
    exit;
}

$staff_id = $_SESSION['staff_id'];
$staff_name = $_SESSION['staff_name'];
$message = '';
$error = '';

// Handle review response submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_review'])) {
    $review_id = (int)$_POST['review_id'];
    $staff_response = trim($_POST['staff_response']);
    
    if (!empty($staff_response)) {
        $respond_stmt = $conn->prepare("UPDATE reviews 
                                       SET staff_response = ?, 
                                           responded_at = NOW(), 
                                           responded_by = ?
                                       WHERE review_id = ?");
        $respond_stmt->bind_param("sii", $staff_response, $staff_id, $review_id);
        
        if ($respond_stmt->execute()) {
            $message = "Response submitted successfully!";
        } else {
            $error = "Failed to submit response.";
        }
    }
}

// Handle review approval toggle
if (isset($_GET['toggle_approve'])) {
    $review_id = (int)$_GET['toggle_approve'];
    $conn->query("UPDATE reviews SET is_approved = NOT is_approved WHERE review_id = $review_id");
    $message = "Review approval status updated.";
}

// Handle featured toggle
if (isset($_GET['toggle_featured'])) {
    $review_id = (int)$_GET['toggle_featured'];
    $conn->query("UPDATE reviews SET is_featured = NOT is_featured WHERE review_id = $review_id");
    $message = "Review featured status updated.";
}

// Handle delete
if (isset($_GET['delete'])) {
    $review_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM reviews WHERE review_id = $review_id");
    $message = "Review deleted successfully.";
}

// Get filter from URL
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
$where_clause = "";
switch ($filter) {
    case 'pending_response':
        $where_clause = "WHERE r.staff_response IS NULL";
        break;
    case 'responded':
        $where_clause = "WHERE r.staff_response IS NOT NULL";
        break;
    case 'featured':
        $where_clause = "WHERE r.is_featured = TRUE";
        break;
    case 'unapproved':
        $where_clause = "WHERE r.is_approved = FALSE";
        break;
    case '5_star':
        $where_clause = "WHERE r.rating = 5";
        break;
    case 'low_rating':
        $where_clause = "WHERE r.rating <= 2";
        break;
}

// Get all reviews with customer and job info
$reviews_query = "SELECT r.*, 
                        c.name as customer_name, 
                        c.email as customer_email,
                        j.job_id,
                        v.brand, v.model, v.registration_no,
                        s.name as mechanic_name,
                        resp_staff.name as responder_name
                  FROM reviews r
                  JOIN customers c ON r.customer_id = c.customer_id
                  JOIN jobs j ON r.job_id = j.job_id
                  JOIN appointments a ON j.appointment_id = a.appointment_id
                  LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                  LEFT JOIN staff s ON j.mechanic_id = s.staff_id
                  LEFT JOIN staff resp_staff ON r.responded_by = resp_staff.staff_id
                  $where_clause
                  ORDER BY r.created_at DESC";

$reviews = $conn->query($reviews_query)->fetch_all(MYSQLI_ASSOC);

// Get statistics
$total_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];
$pending_response = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE staff_response IS NULL")->fetch_assoc()['count'];
$avg_rating = $conn->query("SELECT AVG(rating) as avg FROM reviews WHERE is_approved = TRUE")->fetch_assoc()['avg'];
$featured_count = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE is_featured = TRUE")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Moderation - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --accent-color: #6610f2;
        }
        
        body {
            background: #f0f4ff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .top-nav {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .container-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        .filter-btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .review-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid #dee2e6;
        }
        
        .review-card.featured {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, #fff9e6, white);
        }
        
        .review-card.unapproved {
            border-left-color: #dc3545;
            opacity: 0.7;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .customer-info {
            font-weight: 600;
            color: #111827;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 1.25rem;
        }
        
        .review-text {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .response-section {
            background: #e7f1ff;
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .job-details {
            font-size: 0.9rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="bi bi-star-fill me-2"></i>Review Moderation Panel
            </h4>
            <div>
                <span class="me-3"><?php echo htmlspecialchars($staff_name); ?></span>
                <a href="../public/admin_dashboard.php" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Dashboard
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container-main">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_reviews; ?></div>
                <div class="stat-label">Total Reviews</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $pending_response; ?></div>
                <div class="stat-label">Pending Response</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($avg_rating, 1); ?> <i class="bi bi-star-fill text-warning"></i></div>
                <div class="stat-label">Average Rating</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $featured_count; ?></div>
                <div class="stat-label">Featured Reviews</div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filter Reviews</h5>
            <a href="?" class="btn btn-<?php echo $filter === 'all' ? 'primary' : 'outline-primary'; ?> filter-btn">
                All Reviews
            </a>
            <a href="?filter=pending_response" class="btn btn-<?php echo $filter === 'pending_response' ? 'warning' : 'outline-warning'; ?> filter-btn">
                Pending Response (<?php echo $pending_response; ?>)
            </a>
            <a href="?filter=responded" class="btn btn-<?php echo $filter === 'responded' ? 'success' : 'outline-success'; ?> filter-btn">
                Responded
            </a>
            <a href="?filter=featured" class="btn btn-<?php echo $filter === 'featured' ? 'warning' : 'outline-warning'; ?> filter-btn">
                Featured
            </a>
            <a href="?filter=5_star" class="btn btn-<?php echo $filter === '5_star' ? 'info' : 'outline-info'; ?> filter-btn">
                5 Star Reviews
            </a>
            <a href="?filter=low_rating" class="btn btn-<?php echo $filter === 'low_rating' ? 'danger' : 'outline-danger'; ?> filter-btn">
                Low Ratings
            </a>
            <a href="?filter=unapproved" class="btn btn-<?php echo $filter === 'unapproved' ? 'secondary' : 'outline-secondary'; ?> filter-btn">
                Unapproved
            </a>
        </div>
        
        <!-- Reviews List -->
        <div class="reviews-list">
            <?php if (empty($reviews)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                    <p class="mt-3">No reviews found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card <?php echo $review['is_featured'] ? 'featured' : ''; ?> <?php echo !$review['is_approved'] ? 'unapproved' : ''; ?>">
                        <div class="review-header">
                            <div>
                                <div class="customer-info">
                                    <?php echo htmlspecialchars($review['customer_name']); ?>
                                    <?php if ($review['is_featured']): ?>
                                        <span class="badge bg-warning text-dark ms-2">
                                            <i class="bi bi-star-fill"></i> Featured
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!$review['is_approved']): ?>
                                        <span class="badge bg-danger ms-2">Unapproved</span>
                                    <?php endif; ?>
                                </div>
                                <div class="job-details">
                                    <i class="bi bi-car-front me-1"></i><?php echo htmlspecialchars($review['brand'] . ' ' . $review['model']); ?> 
                                    (<?php echo htmlspecialchars($review['registration_no']); ?>) • 
                                    <i class="bi bi-wrench me-1"></i><?php echo htmlspecialchars($review['mechanic_name'] ?? 'N/A'); ?> • 
                                    <i class="bi bi-calendar3 me-1"></i><?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                            <div class="rating-stars">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="bi bi-star<?php echo $i < $review['rating'] ? '-fill' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="review-text">
                            <strong>Customer Review:</strong><br>
                            <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                        </div>
                        
                        <?php if ($review['staff_response']): ?>
                            <div class="response-section">
                                <strong><i class="bi bi-reply me-1"></i>Staff Response</strong> 
                                <small class="text-muted">by <?php echo htmlspecialchars($review['responder_name']); ?> 
                                on <?php echo date('M d, Y', strtotime($review['responded_at'])); ?></small>
                                <br>
                                <?php echo nl2br(htmlspecialchars($review['staff_response'])); ?>
                            </div>
                        <?php else: ?>
                            <!-- Response Form -->
                            <div class="response-section">
                                <form method="POST" action="">
                                    <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-reply me-1"></i>Write Response
                                    </label>
                                    <textarea class="form-control mb-2" name="staff_response" rows="3" 
                                              required placeholder="Thank the customer and address their feedback..."></textarea>
                                    <button type="submit" name="respond_review" class="btn btn-primary btn-sm">
                                        <i class="bi bi-send me-1"></i>Submit Response
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <a href="?toggle_approve=<?php echo $review['review_id']; ?>" 
                               class="btn btn-sm btn-<?php echo $review['is_approved'] ? 'success' : 'warning'; ?>">
                                <i class="bi bi-<?php echo $review['is_approved'] ? 'check-circle' : 'x-circle'; ?> me-1"></i>
                                <?php echo $review['is_approved'] ? 'Approved' : 'Unapproved'; ?>
                            </a>
                            
                            <a href="?toggle_featured=<?php echo $review['review_id']; ?>" 
                               class="btn btn-sm btn-<?php echo $review['is_featured'] ? 'warning' : 'outline-warning'; ?>">
                                <i class="bi bi-star me-1"></i>
                                <?php echo $review['is_featured'] ? 'Featured' : 'Feature'; ?>
                            </a>
                            
                            <a href="?delete=<?php echo $review['review_id']; ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Are you sure you want to delete this review?');">
                                <i class="bi bi-trash me-1"></i>Delete
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
