<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "edmi"; // Based on your screenshot

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set appropriate headers for file download
$backup_file_name = $dbname . '_backup_' . date('Y-m-d_H-i-s') . '.sql';
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . $backup_file_name);

// Get all tables
$tables_query = "SHOW TABLES";
$tables_result = $conn->query($tables_query);
$tables = array();

while ($row = $tables_result->fetch_row()) {
    $tables[] = $row[0];
}

// Disable foreign key checks
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

// Export each table
foreach ($tables as $table) {
    // Get table structure
    $structure_query = "SHOW CREATE TABLE `$table`";
    $structure_result = $conn->query($structure_query);
    $structure_row = $structure_result->fetch_row();

    // Output table drop and create statements
    echo "DROP TABLE IF EXISTS `$table`;\n";
    echo $structure_row[1] . ";\n\n";

    // Get table data
    $data_query = "SELECT * FROM `$table`";
    $data_result = $conn->query($data_query);

    if ($data_result->num_rows > 0) {
        // Start INSERT statement
        $column_count = $data_result->field_count;
        $row_count = $data_result->num_rows;

        // Retrieve column names
        $fields = array();
        for ($i = 0; $i < $column_count; $i++) {
            $field = $data_result->fetch_field();
            $fields[] = $field->name;
        }

        // Insert data in batches of 100 rows
        $batch_size = 100;
        $current_row = 0;

        while ($current_row < $row_count) {
            echo "INSERT INTO `$table` (`" . implode("`, `", $fields) . "`) VALUES\n";

            $values = array();
            $counter = 0;

            while ($row = $data_result->fetch_row()) {
                $current_row++;
                $counter++;

                // Prepare values with proper escaping
                $row_values = array();
                foreach ($row as $value) {
                    if ($value === null) {
                        $row_values[] = "NULL";
                    } else {
                        $row_values[] = "'" . $conn->real_escape_string($value) . "'";
                    }
                }

                $values[] = "(" . implode(", ", $row_values) . ")";

                // Add this batch if we've reached the batch size or end of data
                if ($counter >= $batch_size || $current_row >= $row_count) {
                    echo implode(",\n", $values) . ";\n\n";
                    $values = array();
                    $counter = 0;
                    break;
                }
            }
        }
    }
}

// Re-enable foreign key checks
echo "SET FOREIGN_KEY_CHECKS=1;\n";

// Close connection
$conn->close();
?>
