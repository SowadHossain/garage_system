<?php
// reviews/list.php - Public Review Listing
require_once __DIR__ . '/../config/db.php';

// Get approved reviews with customer info
$reviews = $conn->query("SELECT r.*, 
                               c.name as customer_name,
                               v.brand, v.model,
                               r.staff_response IS NOT NULL as has_response
                        FROM reviews r
                        JOIN customers c ON r.customer_id = c.customer_id
                        JOIN jobs j ON r.job_id = j.job_id
                        JOIN appointments a ON j.appointment_id = a.appointment_id
                        LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                        WHERE r.is_approved = TRUE
                        ORDER BY r.is_featured DESC, r.created_at DESC
                        LIMIT 50")->fetch_all(MYSQLI_ASSOC);

// Get statistics
$total_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE is_approved = TRUE")->fetch_assoc()['count'];
$avg_rating = $conn->query("SELECT AVG(rating) as avg FROM reviews WHERE is_approved = TRUE")->fetch_assoc()['avg'];

// Count by rating
$rating_counts = [];
for ($i = 5; $i >= 1; $i--) {
    $rating_counts[$i] = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE is_approved = TRUE AND rating = $i")->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .reviews-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .header-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 2.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .rating-summary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
        }
        
        .big-rating {
            font-size: 4rem;
            font-weight: 700;
            color: #ffc107;
        }
        
        .rating-breakdown {
            text-align: left;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .bar {
            flex: 1;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 100%;
            background: #ffc107;
        }
        
        .review-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .review-card.featured {
            border: 2px solid #ffc107;
            box-shadow: 0 6px 20px rgba(255,193,7,0.3);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 1.25rem;
        }
        
        .response-box {
            background: #e7f1ff;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            border-left: 3px solid #0d6efd;
        }
    </style>
</head>
<body>
    <div class="reviews-container">
        <!-- Header with Statistics -->
        <div class="header-card">
            <h1><i class="bi bi-star-fill text-warning me-2"></i>Customer Reviews</h1>
            <p class="text-muted">See what our customers are saying</p>
            
            <div class="rating-summary">
                <div>
                    <div class="big-rating"><?php echo number_format($avg_rating, 1); ?></div>
                    <div class="rating-stars">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <i class="bi bi-star<?php echo $i < round($avg_rating) ? '-fill' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="text-muted"><?php echo $total_reviews; ?> reviews</div>
                </div>
                
                <div class="rating-breakdown">
                    <?php foreach ($rating_counts as $stars => $count): ?>
                        <div class="rating-bar">
                            <span><?php echo $stars; ?> <i class="bi bi-star-fill text-warning"></i></span>
                            <div class="bar" style="width: 150px;">
                                <div class="bar-fill" style="width: <?php echo $total_reviews > 0 ? ($count / $total_reviews * 100) : 0; ?>%;"></div>
                            </div>
                            <span class="text-muted"><?php echo $count; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Reviews List -->
        <?php if (empty($reviews)): ?>
            <div class="review-card text-center">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                <p class="mt-3 text-muted">No reviews yet. Be the first to review!</p>
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-card <?php echo $review['is_featured'] ? 'featured' : ''; ?>">
                    <div class="review-header">
                        <div>
                            <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                            <?php if ($review['is_featured']): ?>
                                <span class="badge bg-warning text-dark ms-2">
                                    <i class="bi bi-star-fill"></i> Featured Review
                                </span>
                            <?php endif; ?>
                            <br>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($review['brand'] . ' ' . $review['model']); ?> • 
                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                            </small>
                        </div>
                        <div class="rating-stars">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="bi bi-star<?php echo $i < $review['rating'] ? '-fill' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <p><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                    
                    <?php if ($review['staff_response']): ?>
                        <div class="response-box">
                            <strong><i class="bi bi-reply me-1"></i>Response from Screw Dheela</strong>
                            <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($review['staff_response'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
