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
<?php
$title = "Admin Login";
require 'includes/header.php';
?>

<style>
    .login-card {
        max-width: 400px;
        margin: 2rem auto;
    }

    .login-header {
        background: var(--primary-gradient);
        color: white;
        padding: 2rem;
        text-align: center;
        margin: -1.5rem -1.5rem 1.5rem;
        border-radius: 15px 15px 0 0;
    }

    .login-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .form-floating {
        margin-bottom: 1rem;
    }

    .error-shake {
        animation: shake 0.82s cubic-bezier(.36, .07, .19, .97) both;
    }

    @keyframes shake {

        10%,
        90% {
            transform: translate3d(-1px, 0, 0);
        }

        20%,
        80% {
            transform: translate3d(2px, 0, 0);
        }

        30%,
        50%,
        70% {
            transform: translate3d(-4px, 0, 0);
        }

        40%,
        60% {
            transform: translate3d(4px, 0, 0);
        }
    }

    @media (max-width: 576px) {
        .login-card {
            margin: 1rem;
        }

        .login-header {
            padding: 1.5rem;
        }
    }
</style>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card login-card p-4 animate__animated animate__fadeInDown">
                <div class="login-header">
                    <i class="bi bi-shield-lock login-icon"></i>
                    <h2 class="mb-0">Admin Login</h2>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show error-shake" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="form-floating mb-3">
                        <input type="text"
                            name="username"
                            class="form-control"
                            id="username"
                            placeholder="Username"
                            required
                            autofocus>
                        <label for="username">
                            <i class="bi bi-person"></i> Username
                        </label>
                        <div class="invalid-feedback">
                            Please enter your username
                        </div>
                    </div>

                    <div class="form-floating mb-4">
                        <input type="password"
                            name="password"
                            class="form-control"
                            id="password"
                            placeholder="Password"
                            required>
                        <label for="password">
                            <i class="bi bi-key"></i> Password
                        </label>
                        <div class="invalid-feedback">
                            Please enter your password
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                        <a href="index.php" class="btn btn-link text-decoration-none">
                            <i class="bi bi-house"></i> Back to Registration
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>