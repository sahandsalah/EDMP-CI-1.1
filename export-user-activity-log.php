<?php
// Include database connection
include 'connection.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has Manager role
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'Manager') {
    // Redirect unauthorized users
    header("Location: index.php");
    exit();
}

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

// Get filter values from GET parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$username = isset($_GET['username']) ? $_GET['username'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$page = isset($_GET['page']) ? $_GET['page'] : '';

// Function to convert page filenames to friendly names (same as in the original file)
function getFriendlyPageName($pageName) {
    $pageMap = [
        'profile.php' => 'User Profile',
        'manage-users.php' => 'User Management',
        'edit-user.php' => 'Edit User',
        'index.php' => 'Login Page',
        'dashboard.php' => 'Dashboard',
        'billing.php' => 'Billing Portal',
        'customer-info.php' => 'Customer Details',
        'meter-management.php' => 'Meter Management',
        'reports.php' => 'Reports',
        'settings.php' => 'System Settings',
        // Add other page mappings as needed
    ];

    return isset($pageMap[$pageName]) ? $pageMap[$pageName] : ucfirst(str_replace(['-', '.php'], [' ', ''], $pageName));
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="user_activity_log_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Prepare the base query
$sql = "SELECT ua.*, u.username
       FROM user_activity ua
       LEFT JOIN Users u ON ua.user_id = u.userid
       WHERE ua.timestamp BETWEEN ? AND ? ";

// Add additional filters
$params = [];
$types = "";

// Add start and end dates to the parameter array
$params[] = $start_date . " 00:00:00";
$params[] = $end_date . " 23:59:59";
$types .= "ss";

if (!empty($username)) {
    $sql .= "AND u.username = ? ";
    $params[] = $username;
    $types .= "s";
}

if (!empty($action)) {
    $sql .= "AND ua.action = ? ";
    $params[] = $action;
    $types .= "s";
}

if (!empty($page)) {
    $sql .= "AND ua.page = ? ";
    $params[] = $page;
    $types .= "s";
}

// Order by timestamp descending
$sql .= "ORDER BY ua.timestamp DESC";

// Create the Excel file content
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
echo '<Worksheet ss:Name="User Activity Log">';
echo '<Table>';

// Header row
echo '<Row>';
echo '<Cell><Data ss:Type="String">Date/Time</Data></Cell>';
echo '<Cell><Data ss:Type="String">User</Data></Cell>';
echo '<Cell><Data ss:Type="String">Page</Data></Cell>';
echo '<Cell><Data ss:Type="String">Action</Data></Cell>';
echo '<Cell><Data ss:Type="String">IP Address</Data></Cell>';
echo '<Cell><Data ss:Type="String">Target ID</Data></Cell>';
echo '<Cell><Data ss:Type="String">Details</Data></Cell>';
echo '</Row>';

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Convert page name to friendly name
            $friendlyPageName = getFriendlyPageName($row['page']);

            echo '<Row>';
            echo '<Cell><Data ss:Type="String">' . date('Y-m-d H:i:s', strtotime($row['timestamp'])) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['username'] ?? 'Unknown') . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($friendlyPageName) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['action']) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['ip_address'] ?? '') . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['target_id'] ?? '') . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['details'] ?? '') . '</Data></Cell>';
            echo '</Row>';
        }
    } else {
        // No records found - add an empty row with message
        echo '<Row>';
        echo '<Cell><Data ss:Type="String">No activity records found</Data></Cell>';
        echo '</Row>';
    }

    $stmt->close();
} else {
    // Error in SQL statement
    echo '<Row>';
    echo '<Cell><Data ss:Type="String">Error preparing statement: ' . mysqli_error($conn) . '</Data></Cell>';
    echo '</Row>';
}

echo '</Table>';
echo '</Worksheet>';
echo '</Workbook>';

// Close database connection
mysqli_close($conn);
exit;
?>
