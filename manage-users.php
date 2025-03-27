<!-- Database Connection -->

<?php
 include 'connection.php'; // Include your database connection file
include 'header.php';
include 'encryption.php';
include 'activity-logger.php';
if (!isset($_SESSION['role']) && $_SESSION['role'] !== 'Manager') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit(); // Stop further execution
}
if (!isset($_SESSION['role']) && $_SESSION['role'] !== 'Admin') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit(); // Stop further execution
}
if (isset($_SESSION['userid'])) {
    // Check if the session has expired
    if (time() > $_SESSION['expire_time']) {
        // Session has expired, log the user out
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
// Insert new user
// Insert new user
if (isset($_POST['add_user'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Use encryption instead of hashing
    $password = encryptPassword(trim($_POST['password']));
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    $insert_query = "INSERT INTO Users (name, username, email, password, role, created_at)
                    VALUES ('$name', '$username', '$email', '$password', '$role', NOW())";

    if (mysqli_query($conn, $insert_query)) {
        $success_message = "User added successfully!";
        $new_user_id = mysqli_insert_id($conn);

        // Log user creation
        logActivity(
            $_SESSION['userid'],
            'manage-users.php',
            'create',
            $new_user_id,
            "User '{$_SESSION['username']}' created new user '{$username}' with role '{$role}'"
        );
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $userid = mysqli_real_escape_string($conn, $_GET['delete']);

    // Get username before deleting for activity log
    $get_username_query = "SELECT username, role FROM Users WHERE userid = '$userid'";
    $username_result = mysqli_query($conn, $get_username_query);
    $user_data = mysqli_fetch_assoc($username_result);
    $deleted_username = $user_data['username'];
    $deleted_role = $user_data['role'];

    $delete_query = "DELETE FROM Users WHERE userid = '$userid'";

    if (mysqli_query($conn, $delete_query)) {
        $success_message = "User deleted successfully!";

        // Log user deletion
        logActivity(
            $_SESSION['userid'],
            'manage-users.php',
            'delete',
            $userid,
            "User '{$_SESSION['username']}' deleted user '{$deleted_username}' with role '{$deleted_role}'"
        );
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// Fetch all users
$users_query = "SELECT * FROM users WHERE role != 'Admin' ORDER BY role ASC";
$users_result = mysqli_query($conn, $users_query);
?>

<!-- Additional Mobile-Specific CSS -->
<style>
    @media (max-width: 767.98px) {
        .container-fluid {
            padding: 0 !important;
        }
        .card {
            border-radius: 0 !important;
            margin: 0 0 10px 0 !important;
        }
        .page-title {
            padding: 10px !important;
        }
        .card-header, .card-body {
            padding: 10px !important;
        }
        .table-responsive {
            margin: 0 !important;
        }
        .alert {
            margin: 0 0 10px 0 !important;
            border-radius: 0 !important;
        }
        .table td, .table th {
            padding: 8px 5px !important;
        }
        .btn-group .btn {
            padding: 0.25rem 0.5rem !important;
        }
    }
</style>

<!-- Page Content -->
<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-12">
            <div class="page-title p-3">
                <h1 class="fw-bold text-primary">Manage Users</h1>
                <p class="text-muted">Add, edit, and manage system users</p>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if(isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show mx-0 my-2" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if(isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show mx-0 my-2" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row g-0">
        <!-- Add User Form -->
        <div class="col-12">
            <div class="card shadow-sm mx-0 my-2">
                <div class="card-header bg-white">
                    <h5 class="card-title m-0">Add New User</h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <span class="input-group-text toggle-password" onclick="togglePasswordVisibility()">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="" disabled selected>Select Role</option>
                                <option value="Manager">Manager</option>
                                <option value="Cashier">Cashier</option>
                            </select>
                        </div>
                        <button type="submit" name="add_user" class="btn btn-primary w-100">Add User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-0">
        <!-- Users Table -->
        <div class="col-12">
            <div class="card mx-0 my-2">
                <div class="card-header bg-white">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
                        <h5 class="card-title m-0">User Management</h5>
                        <div class="input-group search-form w-100 w-md-auto">
                            <input type="text" class="form-control" id="search-users" placeholder="Search users...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped text-center m-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th class="d-none d-md-table-cell">Email</th>
                                    <th>Name</th>
                                    <th class="d-none d-md-table-cell">Username</th>
                                    <th>Role</th>
                                    <th class="d-none d-md-table-cell">Created</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($users_result) > 0): ?>
                                    <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                        <tr>
                                            <td class="d-none d-md-table-cell"><?php echo $user['email']; ?></td>
                                            <td>
                                                <?php echo $user['name']; ?>
                                                <div class="d-md-none small text-muted">
                                                    <?php echo $user['username']; ?><br>
                                                    <?php echo $user['email']; ?>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell"><?php echo $user['username']; ?></td>
                                            <td>
                                                <?php if(!empty($user['role'])): ?>
                                              <i class="fa-solid fa-<?php
                                              echo ($user['role'] == 'Cashier') ? 'user' :
                                              (($user['role'] == 'Manager') ? 'user-plus' : 'user-tie');
                                              ?>">
                                            <?php else: ?>
                                                <div class="badge rounded-pill text-bg-secondary"></div>
                                            <?php endif; ?>
                                            </i>




                                                <?php if(!empty($user['role'])): ?>
                                                    <p class="text text-<?php
                                                    echo ($user['role'] == 'Cashier') ? 'danger' :
                                                    (($user['role'] == 'Manager') ? 'success' : 'info');
                                                    ?>">
                                                    <?php echo ucfirst($user['role']); ?>

                                                </p>
                                                <?php else: ?>
                                                    <div class="badge rounded-pill text-bg-secondary">No Role</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="d-none d-md-table-cell"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editUserModal<?php echo $user['userid']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button href="manage-users.php?delete=<?php echo $user['userid']; ?>" disabled
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Are you sure you want to delete this user?')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>

                                                <!-- Edit User Modal -->
                                                <div class="modal fade" id="editUserModal<?php echo $user['userid']; ?>" tabindex="-1"
                                                    aria-labelledby="editUserModalLabel<?php echo $user['userid']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editUserModalLabel<?php echo $user['userid']; ?>">
                                                                    Edit User: <?php echo $user['name']; ?>
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form action="edit-user.php" method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="userid" value="<?php echo $user['userid']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="edit-name<?php echo $user['userid']; ?>" class="form-label">Full Name</label>
                                                                        <input type="text" class="form-control" id="edit-name<?php echo $user['userid']; ?>"
                                                                            name="name" value="<?php echo $user['name']; ?>" required>
                                                                    </div>
                                                                    <div class="row g-2">
                                                                        <div class="col-12 col-sm-6 mb-3">
                                                                            <label for="edit-username<?php echo $user['userid']; ?>" class="form-label">Username</label>
                                                                            <input type="text" class="form-control" id="edit-username<?php echo $user['userid']; ?>"
                                                                                name="username" value="<?php echo $user['username']; ?>" required>
                                                                        </div>
                                                                        <div class="col-12 col-sm-6 mb-3">
                                                                            <label for="edit-email<?php echo $user['userid']; ?>" class="form-label">Email Address</label>
                                                                            <input type="email" class="form-control" id="edit-email<?php echo $user['userid']; ?>"
                                                                                name="email" value="<?php echo $user['email']; ?>" required>
                                                                        </div>
                                                                    </div>
                                                                  <!--  <div class="mb-3">
                                                                        <label for="edit-password<?php echo $user['userid']; ?>" class="form-label">
                                                                            Password (Leave blank to keep current)
                                                                        </label>
                                                                        <input type="password" class="form-control" id="edit-password<?php echo $user['userid']; ?>"
                                                                            name="password">
                                                                    </div> -->
                                                                    <div class="mb-3">
                                                                        <label for="edit-role<?php echo $user['userid']; ?>" class="form-label">Role</label>
                                                                        <select class="form-select" id="edit-role<?php echo $user['userid']; ?>" name="role" required>

                                                                            <option value="Manager" <?php echo ($user['role'] == 'Manager') ? 'selected' : ''; ?>>
                                                                                Manager
                                                                            </option>
                                                                            <option value="Cashier" <?php echo ($user['role'] == 'Cashier') ? 'selected' : ''; ?>>
                                                                                Cashier
                                                                            </option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No users found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    function togglePasswordVisibility() {
        const passwordField = document.getElementById('password');
        const icon = document.querySelector('.toggle-password i');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Search functionality
    document.getElementById('search-users').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const table = document.querySelector('table');
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            let match = false;
            const cells = row.querySelectorAll('td');

            cells.forEach(cell => {
                if (cell.textContent.toLowerCase().includes(searchValue)) {
                    match = true;
                }
            });

            if (match) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

<?php
// Close the database connection
mysqli_close($conn);
?>

<?php include 'footer.php'; ?>
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>
