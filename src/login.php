<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db.php'; // ✅ Ensure this defines $conn properly

$error = "";
// echo password_hash('admin123', PASSWORD_DEFAULT);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            throw new Exception("Username or password is empty");
        }

        $stmt = $conn->prepare("SELECT id, password FROM admin_users WHERE username = ?");
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result === false) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['admin'] = $user['id'];
                header("Location: admin.php");
                exit;
            } else {
                $error = "Invalid credentials. (password mismatch)";
            }
        } else {
            $error = "Invalid credentials. (user not found)";
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "An error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #6f86d6 0%, #48c6ef 100%); min-height: 100vh;}
        .card { margin-top: 65px; box-shadow: 0 8px 32px 0 rgba( 31, 38, 135, 0.18 ); }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4">
                <div class="card-header bg-primary text-white text-center rounded-3">
                    <h2>
                        <i class="bi bi-shield-lock"></i> Admin Logindd
                    </h2>
                </div>
                <div class="card-body">
                    <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <form method="POST">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" required class="form-control mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" required class="form-control mb-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </form>
                    <p class="text-center mt-3">
                        <a href="index.php" class="btn btn-link"><i class="bi bi-house"></i> Back to Registration</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>