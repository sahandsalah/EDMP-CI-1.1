<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    // Delete the record
    $sql = "DELETE FROM meter_info WHERE CustomerID = $id";
     mysqli_query($conn, $sql);
    // Close the database connection
    mysqli_close($conn);
}
?>
