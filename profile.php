<?php

include 'connection.php';
include 'header.php';
include 'encryption.php';
include 'activity-logger.php'; // Add activity logger

if (isset($_SESSION['userid'])) {
    // Check if the session has expired
    if (time() > $_SESSION['expire_time']) {
        // Session has expired, log the user out
        logActivity($_SESSION['userid'], 'profile.php', 'session_timeout', null,
            "Session timed out for user {$_SESSION['username']}");

        session_unset();
        session_destroy();
        header("Location: index.php");
        exit();
    } else {
        // Update the last activity time
        $_SESSION['last_activity'] = time();
    }
} else {
    // Redirect to login page if not logged in
    header("Location: index.php");
    exit();
}

// Fetch current user data
$user_query = "SELECT * FROM users WHERE userid = {$_SESSION['userid']}";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Get original user data for comparison in activity log
        $original_name = $_SESSION['name'];
        $original_username = $_SESSION['username'];
        $original_email = $_SESSION['email'];

        // Basic profile update
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);

        // Track changes for activity log
        $changed_fields = [];
        if ($original_name != $name) {
            $changed_fields[] = "name from '{$original_name}' to '{$name}'";
        }
        if ($original_username != $username) {
            $changed_fields[] = "username from '{$original_username}' to '{$username}'";
        }
        if ($original_email != $email) {
            $changed_fields[] = "email from '{$original_email}' to '{$email}'";
        }

        // Prepare update query
        $update_query = "UPDATE users SET
                        name = '$name',
                        username = '$username',
                        email = '$email'
                       WHERE userid = {$_SESSION['userid']}";

        if (mysqli_query($conn, $update_query)) {
            // Update session variables
            $_SESSION['name'] = $name;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $success_message = "Profile updated successfully!";

            // Log profile update if there were changes
            if (!empty($changed_fields)) {
                $changes_text = implode(", ", $changed_fields);
                logActivity($_SESSION['userid'], 'profile.php', 'update', $_SESSION['userid'],
                    "User updated their profile: Changed {$changes_text}");
            }

            // Refresh user data
            $user_result = mysqli_query($conn, $user_query);
            $user = mysqli_fetch_assoc($user_result);
        } else {
            $error_message = "Error updating profile: " . mysqli_error($conn);
        }
    }
    elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Check if current password is correct
        $check_query = "SELECT password FROM Users WHERE userid = {$_SESSION['userid']}";
        $check_result = mysqli_query($conn, $check_query);
        $check_data = mysqli_fetch_assoc($check_result);

        // Assuming there's a decryptPassword function that matches encryptPassword
        if (decryptPassword($check_data['password']) === $current_password) {
            // Check if new password matches confirmation
            if ($new_password === $confirm_password) {
                // Encrypt the new password
                $encrypted_password = encryptPassword($new_password);

                // Update the password
                $update_query = "UPDATE Users SET
                                password = '$encrypted_password'
                                WHERE userid = {$_SESSION['userid']}";

                if (mysqli_query($conn, $update_query)) {
                    $success_message = "Password changed successfully!";

                    // Log password change
                    logActivity($_SESSION['userid'], 'profile.php', 'update', $_SESSION['userid'],
                        "User changed their password");
                } else {
                    $error_message = "Error changing password: " . mysqli_error($conn);
                }
            } else {
                $error_message = "New password and confirmation do not match";
            }
        } else {
            $error_message = "Current password is incorrect";

            // Log failed password change attempt
            logActivity($_SESSION['userid'], 'profile.php', 'update', $_SESSION['userid'],
                "Failed password change attempt - incorrect current password");
        }
    }
}

// Log page view (optional, comment out if you don't want to log every view)
// logActivity($_SESSION['userid'], 'profile.php', 'view', null, "User viewed their profile page");

?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-12">
            <div class="page-title p-3">
                <h1 class="fw-bold text-primary">Edit Profile</h1>
                <p class="text-muted">Update your personal information and password</p>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if(!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if(!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row g-0">
        <div class="col-12 col-lg-6">
            <!-- Edit Profile Form -->
            <div class="card shadow-sm m-3">
                <div class="card-header bg-white p-3">
                    <h5 class="card-title m-0">Personal Information</h5>
                </div>
                <div class="card-body p-3">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required >
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="e" name="e" value="<?php echo htmlspecialchars($user['email']); ?>" disabled >
                            <input type="hidden" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly >
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" disabled>
                            <div class="form-text">Your role can only be changed by an administrator.</div>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary w-100">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <!-- Change Password Form -->
            <div class="card shadow-sm m-3">
                <div class="card-header bg-white p-3">
                    <h5 class="card-title m-0">Change Password</h5>
                </div>
                <div class="card-body p-3">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <span class="input-group-text toggle-password" data-target="current_password">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <span class="input-group-text toggle-password" data-target="new_password">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                            <div class="form-text">
                                Password must be at least 8 characters long and include uppercase, lowercase letters and numbers.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <span class="input-group-text toggle-password" data-target="confirm_password">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary w-100">Change Password</button>
                    </form>
                </div>
            </div>

            <!-- Activity History Card
            <div class="card shadow-sm m-3">
                <div class="card-header bg-white p-3">
                    <h5 class="card-title m-0">Recent Activity</h5>
                </div>
                <div class="card-body p-3">
                    <?php
                    // Fetch recent activities for this user
                    $activity_query = "SELECT * FROM user_activity
                                     WHERE user_id = {$_SESSION['userid']}
                                     ORDER BY timestamp DESC LIMIT 5";
                    $activity_result = mysqli_query($conn, $activity_query);

                    if (mysqli_num_rows($activity_result) > 0) {
                    ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>Page</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($activity = mysqli_fetch_assoc($activity_result)) { ?>
                                <tr>
                                    <td><?php echo date('M d, g:i A', strtotime($activity['timestamp'])); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($activity['action'])); ?></td>
                                    <td><?php
                                        $page_parts = explode('.', $activity['page']);
                                        echo ucfirst(htmlspecialchars($page_parts[0]));
                                    ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <?php } else { ?>
                        <p class="text-muted mb-0">No recent activity found.</p>
                    <?php } ?>

                    <?php if ($_SESSION['role'] === 'Manager') { ?>
                        <div class="mt-3">
                            <a href="user-activity-log.php" class="btn btn-outline-primary btn-sm w-100">View All Activity Logs</a>
                        </div>
                    <?php } ?>
                </div>
            </div> -->

            <!-- Account Info Card -->
            <div class="card shadow-sm m-3">
                <div class="card-header bg-white p-3">
                    <h5 class="card-title m-0">Account Information</h5>
                </div>
                <div class="card-body p-3">
                    <div class="row mb-3">
                        <div class="col-6">
                            <p class="text-muted mb-1">Account Created</p>
                            <p class="fw-bold"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1">Last Login</p>
                            <p class="fw-bold">
                                <?php echo !empty($user['last_login']) ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                            </p>
                        </div>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        If you need to update additional account information or have any issues, please contact an administrator.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordField = document.getElementById(targetId);
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
    });
</script>

<?php include 'footer.php'; ?>
