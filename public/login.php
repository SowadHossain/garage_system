<?php
// public/login.php - Redirect to welcome page

header("Location: welcome.php");
exit;

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } else {
        // Prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT staff_id, name, role, password_hash, active 
                                FROM staff 
                                WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();
        $stmt->close();

        if ($staff && (int)$staff['active'] === 1) {
            if (password_verify($password, $staff['password_hash'])) {
                // Correct password
                $_SESSION['staff_id']   = $staff['staff_id'];
                $_SESSION['staff_name'] = $staff['name'];
                $_SESSION['staff_role'] = $staff['role'];

                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<?php include __DIR__ . "/../includes/header.php"; ?>

<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card shadow-sm" style="width: 420px;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h2 class="h4 mb-1">Screw Dheela Management System</h2>
                <div class="text-muted">Staff sign in</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" action="login.php" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Enter your username" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="#" class="small">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100">Sign in</button>
            </form>

            <div class="text-center mt-3 small text-muted">
                Need an account? Ask the admin to create one or run <code>create_admin.php</code>.
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
