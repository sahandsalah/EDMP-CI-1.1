<?php
include 'connection.php';
include 'header.php';
date_default_timezone_set('Asia/Baghdad');
$currentDateTime = date('Y-m-d H:i:s');
error_reporting(0);

// Session validation and security
if (isset($_SESSION['userid'])) {
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
} else {
    // Redirect to login page if not logged in
    header("Location: index.php");
    exit();
}

// Initialize variables
$customer_message = "";
$customer_message_type = "";
$meter_message = "";
$meter_message_type = "";
$record_count = 0;

// Handle customer info CSV file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_customer'])) {
    if (isset($_FILES['customer_csv_file']) && $_FILES['customer_csv_file']['error'] == 0) {
        $file = $_FILES['customer_csv_file']['tmp_name'];
        $handle = fopen($file, 'r');

        // Skip the header row
        fgetcsv($handle);
        $inserted_count = 0;
        $duplicate_count = 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            // Prepare data for insertion
            $area = mysqli_real_escape_string($conn, $data[0]);
            $feeder = mysqli_real_escape_string($conn, $data[1]);
            $meter_sn = mysqli_real_escape_string($conn, $data[2]);
            $sts_number = mysqli_real_escape_string($conn, $data[3]);
            $enclosure_sn = mysqli_real_escape_string($conn, $data[4]);
            $customer_no = mysqli_real_escape_string($conn, $data[5]);
            $customer_name = mysqli_real_escape_string($conn, $data[6]);
            $ciu_serial_no = mysqli_real_escape_string($conn, $data[7]);
            $contact_number = mysqli_real_escape_string($conn, $data[8]);
            $meter_status = mysqli_real_escape_string($conn, $data[9]);

            // Check if the record already exists based on a unique field (e.g., Meter_SN)
            $check_query = "SELECT * FROM customer_info WHERE Meter_SN = '$meter_sn'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) == 0) {
                // Insert data into the database if it doesn't exist
                $sql = "INSERT INTO customer_info (Area, Feeder, Meter_SN, STS_Number, Enclosure_SN, Customer_No, Customer_Name, CIU_Serial_No, Contact_Number, Meter_Status)
                        VALUES ('$area', '$feeder', '$meter_sn', '$sts_number', '$enclosure_sn', '$customer_no', '$customer_name', '$ciu_serial_no', '$contact_number', '$meter_status')";

                if (mysqli_query($conn, $sql)) {
                    $inserted_count++;
                } else {
                    $customer_message .= '<div class="alert alert-danger">Error inserting record: ' . mysqli_error($conn) . '</div>';
                }
            } else {
                $duplicate_count++;
            }
        }

        fclose($handle);
        $customer_message = '<div class="alert alert-success">CSV file uploaded successfully! ' . $inserted_count . ' records inserted. ' . $duplicate_count . ' duplicate records skipped.</div>';
        $customer_message_type = "success";
    } else {
        $customer_message = '<div class="alert alert-danger">Error uploading file.</div>';
        $customer_message_type = "danger";
    }
}

// Handle meter data CSV file upload (requires Admin or Manager role)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_meter']) && ($_SESSION['role'] === 'Manager' || $_SESSION['role'] === 'Admin')) {
    if (isset($_FILES['meter_csv_file']) && $_FILES['meter_csv_file']['error'] == 0) {
        $file = $_FILES['meter_csv_file'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Get database columns
        $columns_query = "SHOW COLUMNS FROM prepaymentmode";
        $columns_result = mysqli_query($conn, $columns_query);
        $db_columns = [];

        if ($columns_result) {
            while ($column = mysqli_fetch_assoc($columns_result)) {
                $db_columns[] = $column['Field'];
            }
        } else {
            $meter_message = "Error retrieving database structure: " . mysqli_error($conn);
            $meter_message_type = "danger";
        }

        // Check file extension
        if ($file_ext === 'csv' && !empty($db_columns)) {
            // Begin transaction
            mysqli_begin_transaction($conn);

            try {
                // Clear existing data
                $truncate_query = "TRUNCATE TABLE prepaymentmode";
                mysqli_query($conn, $truncate_query);

                // Open uploaded CSV file
                $csv_file = fopen($file_tmp, 'r');

                // Get header row
                $header = fgetcsv($csv_file);

                // Map CSV headers to database columns
                $column_mapping = [];
                $db_columns_lower = array_map('strtolower', $db_columns);

                foreach ($header as $index => $csv_column) {
                    $csv_column_lower = strtolower($csv_column);
                    $csv_column_underscore = str_replace(' ', '_', $csv_column_lower);
                    $csv_column_no_underscore = str_replace('_', ' ', $csv_column_lower);

                    // Try multiple matching approaches
                    if (in_array($csv_column, $db_columns)) {
                        $column_mapping[$index] = $csv_column;
                    } elseif (in_array($csv_column_lower, $db_columns_lower)) {
                        $db_index = array_search($csv_column_lower, $db_columns_lower);
                        $column_mapping[$index] = $db_columns[$db_index];
                    } elseif (in_array($csv_column_underscore, $db_columns_lower)) {
                        $db_index = array_search($csv_column_underscore, $db_columns_lower);
                        $column_mapping[$index] = $db_columns[$db_index];
                    } elseif (in_array($csv_column_no_underscore, $db_columns_lower)) {
                        $db_index = array_search($csv_column_no_underscore, $db_columns_lower);
                        $column_mapping[$index] = $db_columns[$db_index];
                    }
                }

                // Process each row in the CSV file
                while (($data = fgetcsv($csv_file)) !== FALSE) {
                    // Skip empty rows
                    if (count($data) <= 1 && empty($data[0])) {
                        continue;
                    }

                    $insert_columns = [];
                    $insert_values = [];

                    foreach ($column_mapping as $csv_index => $db_column) {
                        if ($db_column != 'id' && isset($data[$csv_index])) { // Skip 'id' column
                            $insert_columns[] = "`" . $db_column . "`"; // Add backticks to handle special characters
                            $insert_values[] = "'" . mysqli_real_escape_string($conn, $data[$csv_index]) . "'";
                        } elseif ($db_column == 'sts_no' && !isset($data[$csv_index])) {
                            // Handle sts_no column explicitly if it is not in the CSV
                            $insert_columns[] = "`sts_no`";
                            $insert_values[] = "NULL";
                        }
                    }

                    if (!empty($insert_columns)) {
                        $columns_str = implode(", ", $insert_columns);
                        $values_str = implode(", ", $insert_values);

                        $insert_query = "INSERT INTO prepaymentmode ($columns_str) VALUES ($values_str)";
                        if (mysqli_query($conn, $insert_query)) {
                            $record_count++;
                        } else {
                            throw new Exception("Insert error: " . mysqli_error($conn) . " Query: " . $insert_query);
                        }
                    }
                }

                fclose($csv_file);

                // Commit transaction
                mysqli_commit($conn);

                $meter_message = "Upload successful! All previous data has been replaced with $record_count new records.";
                $meter_message_type = "success";

            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                $meter_message = "Error: " . $e->getMessage();
                $meter_message_type = "danger";
            }
        } else {
            $meter_message = "Only CSV files are allowed.";
            $meter_message_type = "danger";
        }
    } else {
        $meter_message = "Error uploading file. Error code: " . $_FILES['meter_csv_file']['error'];
        $meter_message_type = "danger";
    }
}
?>

<!-- Page Content -->
<div class="container-fluid px-4 py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title">
                <h2 class="fw-bold text-primary">Data Upload System</h2>
                <p class="text-muted">Manage customer information and meter status in one place</p>
                <hr class="my-4">
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="row">
        <div class="col-12">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customerTab" type="button" role="tab" aria-controls="customerTab" aria-selected="true">
                        <i class="fas fa-users me-2"></i>Customer Information
                    </button>
                </li>
                <?php if($_SESSION['role'] === 'Manager' || $_SESSION['role'] === 'Admin'): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="meter-tab" data-bs-toggle="tab" data-bs-target="#meterTab" type="button" role="tab" aria-controls="meterTab" aria-selected="false">
                        <i class="fas fa-tachometer-alt me-2"></i>Meter Status
                    </button>
                </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content pt-4" id="myTabContent">
                <!-- Customer Information Tab -->
                <div class="tab-pane fade show active" id="customerTab" role="tabpanel" aria-labelledby="customer-tab">
                    <div class="row">
                        <div class="col-md-10 col-lg-8 mx-auto">
                            <div class="card shadow-sm border-0 rounded-3">
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-upload me-2 fs-4"></i>
                                        <h5 class="card-title mb-0">New Meter List by MoE</h5>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <?php if(!empty($customer_message)): ?>
                                        <?php echo $customer_message; ?>
                                    <?php endif; ?>

                                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                        <div class="mb-4">
                                            <label for="customer_csv_file" class="form-label fw-semibold">Select CSV File</label>
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="customer_csv_file" name="customer_csv_file" required accept=".csv">
                                                <label class="input-group-text" for="customer_csv_file">Browse</label>
                                            </div>
                                            <div class="form-text mt-2">
                                                <i class="fas fa-info-circle me-1"></i>Please upload a CSV file with the format: Area, Feeder, Meter_SN, STS_Number, Enclosure_SN, Customer_No, Customer_Name, CIU_Serial_No, Contact_Number, Meter_Status
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="submit" name="upload_customer" class="btn btn-primary px-4">
                                                <i class="fas fa-upload me-2"></i>Upload Customer Data
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Instructions Card -->
                            <div class="card mt-4 shadow-sm border-0 rounded-3">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Instructions</h5>
                                </div>
                                <div class="card-body p-4">
                                    <ol class="mb-0">
                                        <li class="mb-2">Prepare your CSV file with the correct column headers</li>
                                        <li class="mb-2">Ensure all required fields have values</li>
                                        <li class="mb-2">Click the "Upload Customer Data" button</li>
                                        <li class="mb-2">The system will automatically skip duplicate meter serial numbers</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Meter Status Tab (Only visible to Managers and Admins) -->
                <?php if($_SESSION['role'] === 'Manager' || $_SESSION['role'] === 'Admin'): ?>
                <div class="tab-pane fade" id="meterTab" role="tabpanel" aria-labelledby="meter-tab">
                    <div class="row">
                        <div class="col-md-10 col-lg-8 mx-auto">
                            <div class="card shadow-sm border-0 rounded-3">
                                <div class="card-header bg-danger text-white">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle me-2 fs-4"></i>
                                        <h5 class="card-title mb-0">New Meter Status (Data Replacement)</h5>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <?php if(!empty($meter_message)): ?>
                                        <div class="alert alert-<?php echo $meter_message_type; ?> alert-dismissible fade show">
                                            <?php echo $meter_message; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>

                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <strong>Warning:</strong> Uploading a new file will replace ALL existing data in the database. This action cannot be undone.
                                    </div>

                                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                        <div class="mb-4">
                                            <label for="meter_csv_file" class="form-label fw-semibold">Select CSV File</label>
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="meter_csv_file" name="meter_csv_file" required accept=".csv">
                                                <label class="input-group-text" for="meter_csv_file">Browse</label>
                                            </div>
                                            <div class="form-text mt-2">
                                                <i class="fas fa-info-circle me-1"></i>Ensure your CSV file contains all required meter data fields
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#confirmModal">
                                                <i class="fas fa-upload me-2"></i>Upload and Replace All Data
                                            </button>
                                        </div>

                                        <!-- Confirmation Modal -->
                                        <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title" id="confirmModalLabel">Confirm Data Replacement</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to replace ALL existing meter data? This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="upload_meter" class="btn btn-danger">Confirm Replacement</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Add Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

<!-- Add Font Awesome for Icons -->
<script src="https://kit.fontawesome.com/your-code-here.js" crossorigin="anonymous"></script>

<!-- Custom JavaScript -->
<script>
// Form validation
(function () {
    'use strict'

    // Fetch all forms we want to apply validation styles to
    var forms = document.querySelectorAll('.needs-validation')

    // Loop over them and prevent submission
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }

                form.classList.add('was-validated')
            }, false)
        })
})()

// File input validation
document.addEventListener('DOMContentLoaded', function() {
    const customerFileInput = document.getElementById('customer_csv_file');
    const meterFileInput = document.getElementById('meter_csv_file');

    if (customerFileInput) {
        customerFileInput.addEventListener('change', function() {
            validateFileInput(this, 'csv');
        });
    }

    if (meterFileInput) {
        meterFileInput.addEventListener('change', function() {
            validateFileInput(this, 'csv');
        });
    }

    function validateFileInput(input, allowedExtension) {
        const filePath = input.value;
        const extension = filePath.substring(filePath.lastIndexOf('.') + 1).toLowerCase();

        if (extension !== allowedExtension) {
            alert('Please select only ' + allowedExtension.toUpperCase() + ' files');
            input.value = '';
            return false;
        }

        // Show file name in custom file input
        const fileName = input.files[0].name;
        const nextSibling = input.nextElementSibling;
        if (nextSibling && nextSibling.classList.contains('input-group-text')) {
            if (fileName.length > 20) {
                nextSibling.textContent = fileName.substring(0, 17) + '...';
            } else {
                nextSibling.textContent = fileName;
            }
        }

        return true;
    }

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-warning)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

<?php include 'footer.php'; ?>
