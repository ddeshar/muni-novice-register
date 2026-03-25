<?php
require 'db.php'; // db.php starts session
require 'includes/security.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            throw new Exception("Invalid request token");
        }

        if (!check_rate_limit('admin_login', 5, 300)) {
            throw new Exception("Too many login attempts");
        }

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
                session_regenerate_id(true);
                $_SESSION['admin'] = $user['id'];
                header("Location: admin.php");
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
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
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
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
