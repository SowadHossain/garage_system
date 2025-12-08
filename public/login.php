<?php
// public/login.php

session_start();

require_once __DIR__ . "/../config/db.php";

// If already logged in, go to dashboard
if (!empty($_SESSION['staff_id'])) {
    header("Location: index.php");
    exit;
}

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

<div class="row justify-content-center">
    <div class="col-md-4">
        <h3 class="mb-3">Staff Login</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input
                    type="text"
                    name="username"
                    id="username"
                    class="form-control"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
