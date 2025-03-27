<?php
/**
 * Log user activity
 * 
 * @param int $user_id The ID of the user performing the action
 * @param string $page The page where the action occurred
 * @param string $action The type of action (create, update, delete, login, logout)
 * @param int|null $target_id The ID of the affected record (if applicable)
 * @param string|null $details Additional details about the action
 * @return bool Whether the logging was successful
 */
function logActivity($user_id, $page, $action, $target_id = null, $details = null) {
    global $conn;
    
    // Get the user's IP address
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Prepare the query
    $query = "INSERT INTO user_activity (user_id, page, action, target_id, details, ip_address) 
              VALUES (?, ?, ?, ?, ?, ?)";
    
    // Use prepared statements to prevent SQL injection
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ississ", $user_id, $page, $action, $target_id, $details, $ip_address);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    
    return false;
}
?>
