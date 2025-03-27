<?php
// Include database connection
include 'connection.php';

// Get filter parameters from the URL
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$username = isset($_GET['username']) ? $_GET['username'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$meter = isset($_GET['meter']) ? $_GET['meter'] : '';

// Prepare the base query
$sql = "SELECT * FROM audit_log WHERE timestamp BETWEEN ? AND ? ";
$params = [];
$types = "ss";

// Add start and end dates to the parameter array
$params[] = $start_date . " 00:00:00";
$params[] = $end_date . " 23:59:59";

// Add additional filters
if (!empty($username)) {
    $sql .= "AND username = ? ";
    $params[] = $username;
    $types .= "s";
}

if (!empty($action)) {
    $sql .= "AND action = ? ";
    $params[] = $action;
    $types .= "s";
}

if (!empty($meter)) {
    $sql .= "AND record_id = ? ";
    $params[] = $meter;
    $types .= "s";
}

// Order by timestamp descending
$sql .= "ORDER BY timestamp DESC";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="audit_log_export.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['Date/Time', 'User', 'Page', 'Action', 'Meter SN', 'Field', 'Old Value', 'New Value']);

// Add data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['timestamp'],
        $row['username'],
        $row['page'],
        $row['action'],
        $row['record_id'],
        $row['field_name'],
        $row['old_value'],
        $row['new_value']
    ]);
}

// Close the output stream
fclose($output);
exit;
?>
