<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit();
}

// Check if user has appropriate role for exporting logs
if ($_SESSION['role'] != 'Manager') {
    header("Location: dashboard.php");
    exit();
}

// Initialize variables for filtering
$filter_user = isset($_GET['filter_user']) ? $_GET['filter_user'] : '';
$filter_action = isset($_GET['filter_action']) ? $_GET['filter_action'] : '';
$filter_date_from = isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : '';
$filter_date_to = isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : '';
$filter_meter = isset($_GET['filter_meter']) ? $_GET['filter_meter'] : '';

// Build the query based on filters
$where_clauses = [];
$params = [];

if (!empty($filter_user)) {
    $where_clauses[] = "(user_name LIKE ? OR user_id = ?)";
    $params[] = "%$filter_user%";
    $params[] = $filter_user;
}

if (!empty($filter_action)) {
    $where_clauses[] = "action_type = ?";
    $params[] = $filter_action;
}

if (!empty($filter_date_from)) {
    $where_clauses[] = "DATE(timestamp) >= ?";
    $params[] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $where_clauses[] = "DATE(timestamp) <= ?";
    $params[] = $filter_date_to;
}

if (!empty($filter_meter)) {
    $where_clauses[] = "(affected_id = ? AND affected_table = 'meter_info')";
    $params[] = $filter_meter;
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Prepare the SQL query
$sql = "SELECT log_id, user_id, user_name, action_type, description, affected_id, affected_table, timestamp 
        FROM audit_log 
        $where_clause 
        ORDER BY timestamp DESC";

// Set headers for CSV download
$filename = "audit_log_export_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create a file handle for output
$output = fopen('php://output', 'w');

// Add UTF-8 BOM to fix Excel CSV encoding
fputs($output, "\xEF\xBB\xBF");

// Add header row
fputcsv($output, [
    'ID', 
    'Timestamp', 
    'User ID', 
    'User Name', 
    'Action Type', 
    'Description', 
    'Affected ID', 
    'Affected Table'
]);

// Execute the query with prepared statement
$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Write each data row
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['log_id'],
        $row['timestamp'],
        $row['user_id'],
        $row['user_name'],
        $row['action_type'],
        $row['description'],
        $row['affected_id'],
        $row['affected_table']
    ]);
}

// Close file handle
fclose($output);
exit();
?>
