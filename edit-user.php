<?php
// Include database connection and encryption functions
include 'connection.php';
include 'encryption.php';
include 'activity-logger.php';

session_start();

// Check if user is logged in and is a Manager
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Manager') {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Manager') {
    header("Location: index.php");
    exit();
}

// Process edit user form
if (isset($_POST['edit_user'])) {
    $userid = mysqli_real_escape_string($conn, $_POST['userid']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Get original user data for comparison in activity log
    $original_query = "SELECT name, username, email, role FROM Users WHERE userid = '$userid'";
    $original_result = mysqli_query($conn, $original_query);
    $original_data = mysqli_fetch_assoc($original_result);

    // Track changes for activity log
    $changed_fields = [];
    if ($original_data['name'] != $name) {
        $changed_fields[] = "name from '{$original_data['name']}' to '{$name}'";
    }
    if ($original_data['username'] != $username) {
        $changed_fields[] = "username from '{$original_data['username']}' to '{$username}'";
    }
    if ($original_data['email'] != $email) {
        $changed_fields[] = "email from '{$original_data['email']}' to '{$email}'";
    }
    if ($original_data['role'] != $role) {
        $changed_fields[] = "role from '{$original_data['role']}' to '{$role}'";
    }

    // Prepare the base update query
    $update_query = "UPDATE Users SET
                     name = '$name',
                     username = '$username',
                     email = '$email',
                     role = '$role'
                     WHERE userid = '$userid'";

    $password_changed = false;

    // If password is provided, update it too
    if (isset($_POST['password']) && !empty($_POST['password'])) {
        $encrypted_password = encryptPassword(trim($_POST['password']));
        $update_query = "UPDATE Users SET
                         name = '$name',
                         username = '$username',
                         email = '$email',
                         password = '$encrypted_password',
                         role = '$role'
                         WHERE userid = '$userid'";
        $password_changed = true;
        $changed_fields[] = "password";
    }

    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success_message'] = "User updated successfully!";

        // Log user update
        if (!empty($changed_fields)) {
            $changes_text = implode(", ", $changed_fields);
            $log_details = "User '{$_SESSION['username']}' updated user '{$original_data['username']}': Changed {$changes_text}";
            logActivity($_SESSION['userid'], 'edit-user.php', 'update', $userid, $log_details);
        }
    } else {
        $_SESSION['error_message'] = "Error: " . mysqli_error($conn);
    }

    header("Location: manage-users.php");
    exit();
}

// Redirect if not a form submission
header("Location: manage-users.php");
exit();
?>
