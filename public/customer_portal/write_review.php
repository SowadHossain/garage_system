<?php
session_start();
if (empty($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Write Review - Screw Dheela</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="card shadow-sm border-0">
      <div class="card-body p-4 text-center">
        <div class="display-6 mb-2"><i class="bi bi-pencil-square text-success"></i></div>
        <h3 class="mb-2">Write a Review</h3>
        <p class="text-muted mb-4">Coming soon ✨</p>
        <a class="btn btn-primary" href="customer_dashboard.php">
          <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
      </div>
    </div>
  </div>
</body>
</html>
