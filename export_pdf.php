<?php
require('fpdf/fpdf/fpdf-master/fpdf.php'); // Include FPDF library

// Database connection
include 'connection.php';

// Fetch data with filters
$sql = "SELECT * FROM meter_info WHERE 1=1";
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

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// Add headers (excluding CustomerID)
$headers = ['Zone', 'Area', 'Feeder', 'Meter SN', 'STS Number', 'Enclosure SN', 'Customer No', 'Customer Name', 'CIU Serial No', 'Contact Number', 'Meter Status'];
foreach ($headers as $header) {
    $pdf->Cell(40, 10, $header, 1);
}
$pdf->Ln();

// Add data (excluding CustomerID)
while ($row = mysqli_fetch_assoc($result)) {
    $pdf->Cell(40, 10, $row['Zone'], 1);
    $pdf->Cell(40, 10, $row['Area'], 1);
    $pdf->Cell(40, 10, $row['Feeder'], 1);
    $pdf->Cell(40, 10, $row['Meter_SN'], 1);
    $pdf->Cell(40, 10, $row['STS_Number'], 1);
    $pdf->Cell(40, 10, $row['Enclosure_SN'], 1);
    $pdf->Cell(40, 10, $row['Customer_No'], 1);
    $pdf->Cell(40, 10, $row['Customer_Name'], 1);
    $pdf->Cell(40, 10, $row['CIU_Serial_No'], 1);
    $pdf->Cell(40, 10, $row['Contact_Number'], 1);
    $pdf->Cell(40, 10, $row['Meter_Status'], 1);
    $pdf->Ln();
}

// Output PDF
$pdf->Output('D', 'meter_info.pdf'); // Download the PDF
?>
