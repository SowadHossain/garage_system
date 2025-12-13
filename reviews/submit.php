<?php
// reviews/submit.php - Customer Review Submission Form
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: ../public/customer_login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];
$message = '';
$error = '';

// Get job_id from URL
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

// Verify the job belongs to this customer and is completed
if ($job_id > 0) {
    $job_stmt = $conn->prepare("SELECT j.job_id, j.status, a.appointment_datetime, 
                                        v.brand, v.model, v.registration_no,
                                        s.name as mechanic_name
                                FROM jobs j
                                JOIN appointments a ON j.appointment_id = a.appointment_id
                                LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                                LEFT JOIN staff s ON j.mechanic_id = s.staff_id
                                WHERE j.job_id = ? AND a.customer_id = ?");
    $job_stmt->bind_param("ii", $job_id, $customer_id);
    $job_stmt->execute();
    $job = $job_stmt->get_result()->fetch_assoc();
    
    if (!$job) {
        $error = "Job not found or you don't have permission to review it.";
        $job_id = 0;
    } elseif ($job['status'] !== 'completed') {
        $error = "You can only review completed jobs.";
        $job_id = 0;
    }
    
    // Check if already reviewed
    if ($job_id > 0) {
        $check_stmt = $conn->prepare("SELECT review_id FROM reviews WHERE job_id = ? AND customer_id = ?");
        $check_stmt->bind_param("ii", $job_id, $customer_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = "You have already submitted a review for this job.";
            $job_id = 0;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $job_id = (int)$_POST['job_id'];
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    
    // Validate
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a rating between 1 and 5 stars.";
    } elseif (empty($review_text)) {
        $error = "Please write a review.";
    } elseif (strlen($review_text) < 10) {
        $error = "Review must be at least 10 characters long.";
    } else {
        // Insert review
        $insert_stmt = $conn->prepare("INSERT INTO reviews (job_id, customer_id, rating, review_text) 
                                       VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("iiis", $job_id, $customer_id, $rating, $review_text);
        
        if ($insert_stmt->execute()) {
            $message = "Thank you for your review! Your feedback helps us improve our service.";
            $job_id = 0; // Clear form
        } else {
            $error = "Failed to submit review. Please try again.";
        }
    }
}

// Get customer's completed jobs without reviews
$completed_jobs = $conn->query("SELECT j.job_id, a.appointment_datetime, 
                                       v.brand, v.model, v.registration_no,
                                       s.name as mechanic_name
                                FROM jobs j
                                JOIN appointments a ON j.appointment_id = a.appointment_id
                                LEFT JOIN vehicles v ON a.vehicle_id = v.vehicle_id
                                LEFT JOIN staff s ON j.mechanic_id = s.staff_id
                                WHERE a.customer_id = $customer_id 
                                AND j.status = 'completed'
                                AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.job_id = j.job_id)
                                ORDER BY a.appointment_datetime DESC
                                LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Review - Screw Dheela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --accent-color: #6610f2;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .review-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .review-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 2.5rem;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header-section h1 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .star-rating {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0;
        }
        
        .star-rating input[type="radio"] {
            display: none;
        }
        
        .star-rating label {
            font-size: 3rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input[type="radio"]:checked ~ label {
            color: #ffc107;
            transform: scale(1.1);
        }
        
        .star-rating {
            flex-direction: row-reverse;
            justify-content: center;
        }
        
        .job-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .job-info strong {
            color: var(--primary-color);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13,110,253,0.3);
        }
        
        .completed-jobs {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .job-item {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }
        
        .job-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(13,110,253,0.1);
        }
    </style>
</head>
<body>
    <div class="review-container">
        <!-- Back Button -->
        <div class="mb-3">
            <a href="../public/customer_dashboard.php" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error && $job_id === 0): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($job_id > 0 && isset($job)): ?>
            <!-- Review Submission Form -->
            <div class="review-card">
                <div class="header-section">
                    <h1><i class="bi bi-star-fill me-2"></i>Write a Review</h1>
                    <p class="text-muted">Share your experience with us</p>
                </div>
                
                <div class="job-info">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Vehicle:</strong> <?php echo htmlspecialchars($job['brand'] . ' ' . $job['model']); ?><br>
                            <strong>Reg No:</strong> <?php echo htmlspecialchars($job['registration_no']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Service Date:</strong> <?php echo date('M d, Y', strtotime($job['appointment_datetime'])); ?><br>
                            <strong>Mechanic:</strong> <?php echo htmlspecialchars($job['mechanic_name'] ?? 'N/A'); ?>
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                    
                    <!-- Star Rating -->
                    <div class="text-center mb-3">
                        <label class="form-label fw-bold">How would you rate our service?</label>
                        <div class="star-rating">
                            <input type="radio" name="rating" id="star5" value="5" required>
                            <label for="star5"><i class="bi bi-star-fill"></i></label>
                            
                            <input type="radio" name="rating" id="star4" value="4">
                            <label for="star4"><i class="bi bi-star-fill"></i></label>
                            
                            <input type="radio" name="rating" id="star3" value="3">
                            <label for="star3"><i class="bi bi-star-fill"></i></label>
                            
                            <input type="radio" name="rating" id="star2" value="2">
                            <label for="star2"><i class="bi bi-star-fill"></i></label>
                            
                            <input type="radio" name="rating" id="star1" value="1">
                            <label for="star1"><i class="bi bi-star-fill"></i></label>
                        </div>
                    </div>
                    
                    <!-- Review Text -->
                    <div class="mb-4">
                        <label for="review_text" class="form-label fw-bold">Tell us about your experience</label>
                        <textarea class="form-control" id="review_text" name="review_text" rows="6" 
                                  required minlength="10" maxlength="1000"
                                  placeholder="What did you like? What could be improved? Your feedback helps us serve you better!"></textarea>
                        <div class="form-text">Minimum 10 characters, maximum 1000 characters.</div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" name="submit_review" class="btn btn-primary btn-submit">
                            <i class="bi bi-send me-2"></i>Submit Review
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- List of Completed Jobs -->
            <div class="completed-jobs">
                <h2 class="mb-4">
                    <i class="bi bi-clipboard-check me-2"></i>Completed Services
                </h2>
                
                <?php if (empty($completed_jobs)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                        <p class="mt-3">No completed services to review yet.</p>
                        <a href="../public/customer_dashboard.php" class="btn btn-primary">
                            Go to Dashboard
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-3">Select a completed service to write a review:</p>
                    
                    <?php foreach ($completed_jobs as $completed_job): ?>
                        <div class="job-item">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-1">
                                        <i class="bi bi-car-front me-2"></i>
                                        <?php echo htmlspecialchars($completed_job['brand'] . ' ' . $completed_job['model']); ?>
                                    </h5>
                                    <p class="mb-0 text-muted small">
                                        <strong>Reg:</strong> <?php echo htmlspecialchars($completed_job['registration_no']); ?> • 
                                        <strong>Date:</strong> <?php echo date('M d, Y', strtotime($completed_job['appointment_datetime'])); ?> • 
                                        <strong>Mechanic:</strong> <?php echo htmlspecialchars($completed_job['mechanic_name'] ?? 'N/A'); ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <a href="?job_id=<?php echo $completed_job['job_id']; ?>" class="btn btn-primary">
                                        <i class="bi bi-pencil-square me-1"></i>Write Review
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
