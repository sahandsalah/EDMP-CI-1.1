<?php
// Start session
session_start();

// Include database connection
include 'connection.php';
include 'encryption.php';
include 'activity-logger.php'; // Add activity logger

// Initialize variables
$error_message = "";

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Validate input
    if (empty($email) || empty($password)) {
        $error_message = "Email and password are required.";
    } else {
        // Query to check user credentials
        $sql = "SELECT userid, name, username, email, password, role FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            // Determine if the password uses the new encryption or the old hashing
            if (substr($user['password'], 0, 1) === '$') {
                // This is a password_hash format, use password_verify
                $passwordValid = password_verify($password, $user['password']);
            } else {
                // This is our encrypted format, decrypt and compare
                $decryptedPassword = decryptPassword($user['password']);
                $passwordValid = ($password === $decryptedPassword);
            }

            // Verify the password
            if ($passwordValid) {
                // Password is correct, set session variables
                $_SESSION['userid'] = $user['userid'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time(); // Set the last activity time

                // Update last login time
                $update_login = "UPDATE users SET last_login = NOW() WHERE userid = '{$user['userid']}'";
                mysqli_query($conn, $update_login);

                // Log the login activity
                logActivity($user['userid'], 'index.php', 'login', null, "User {$user['username']} logged in");

                // Set session expiration based on "Remember me" option
                if (isset($_POST['remember'])) {
                    // Set a longer session expiration time (e.g., 30 days)
                    $_SESSION['expire_time'] = time() + (30 * 24 * 60 * 60);
                } else {
                    // Set a shorter session expiration time (e.g., 1 hour)
                    $_SESSION['expire_time'] = time() + (60 * 60);
                }

                // Redirect based on role
                if ($user['role'] == 'Manager' || $user['role'] == 'Admin') {
                    header("Location: dashboard.php");
                } elseif ($user['role'] == 'Cashier') {
                    header("Location: customer-info.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error_message = "Invalid email or password.";

                // Log failed login attempt
                if (isset($user['userid'])) {
                    logActivity($user['userid'], 'index.php', 'login_failed', null, "Failed login attempt for {$email}");
                }
            }
        } else {
            $error_message = "Invalid email or password.";

            // Log failed login attempt for unknown user
            logActivity(null, 'index.php', 'login_failed', null, "Failed login attempt for unknown user: {$email}");
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDMP - Customer Information</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header text-center mb-4">
                        <h1 class="brand-name">EDMP</h1>
                        <p class="text-muted">Admin Dashboard</p>
                    </div>
                    <div class="card shadow-lg">
                        <div class="card-body p-5">
                            <h2 class="text-center mb-4">Sign In</h2>

                            <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>

                            <form id="loginForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="mb-4">
                                    <label for="email" class="form-label">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg w-100">Sign In</button>
                            </form>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <p class="d-inline-block text-truncate" style="color:#ffffff;">© 2025 EDMP. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
    // Toggle password visibility
    document.querySelector('.toggle-password').addEventListener('click', function() {
        const passwordField = document.getElementById('password');
        const icon = this.querySelector('i');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    </script>
</body>
</html>
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>
