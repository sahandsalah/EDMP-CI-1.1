<?php
// Database connection
include 'connection.php';

// Fetch data with filters
$sql = "SELECT * FROM customer_info WHERE 1=1";
if (!empty($_GET['area'])) {
    $sql .= " AND Area = '{$_GET['area']}'";
}
if (!empty($_GET['status'])) {
    $sql .= " AND Meter_Status = '{$_GET['status']}'";
}
if (!empty($_GET['meter_sn'])) {
    $sql .= " AND Meter_SN LIKE '%{$_GET['meter_sn']}%'";
}
$result = mysqli_query($conn, $sql);

// Set headers for CSV download with UTF-8 encoding
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="meter_info.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM (Byte Order Mark) for UTF-8
fputs($output, "\xEF\xBB\xBF");

// Add headers (excluding CustomerID)
$headers = ['Area', 'Feeder', 'Meter SN', 'STS Number', 'Enclosure SN', 'Customer No', 'Customer Name', 'CIU Serial No', 'Contact Number', 'Meter Status'];
fputcsv($output, $headers);

// Add data (excluding CustomerID)
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['Area'],
        $row['Feeder'],
        $row['Meter_SN'],
        $row['STS_Number'],
        $row['Enclosure_SN'],
        $row['Customer_No'],
        $row['Customer_Name'],
        $row['CIU_Serial_No'],
        $row['Contact_Number'],
        $row['Meter_Status']
    ]);
}

// Close output stream
fclose($output);
?>
