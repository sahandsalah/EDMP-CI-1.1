<?php
include 'connection.php';
include 'activity-logger.php';

session_start();
     $user_id = $_SESSION['userid'];
    $username = $_SESSION['username'];

    // Log logout
    logActivity($user_id, 'index.php', 'logout', null, "User {$username} logged out");

    // Destroy the session
    session_unset();
    session_destroy();

    // Redirect to login page
    header("Location: index.php");
    exit();

?>
