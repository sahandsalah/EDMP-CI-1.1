<?php

  include 'header.php';
  include 'connection.php';

  if (!isset($_SESSION['role']) && $_SESSION['role'] !== 'Manager') {
      session_unset();
      session_destroy();
      header("Location: index.php");
      exit(); // Stop further execution
  }
  if (!isset($_SESSION['role']) && $_SESSION['role'] !== 'Admin') {
      session_unset();
      session_destroy();
      header("Location: index.php");
      exit(); // Stop further execution
  }
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
  function logAudit($conn, $record_id, $field_name, $old_value, $new_value) {
      // Get current user info
      $user_id = $_SESSION['user_id'] ?? 0; // Default to 0 if not logged in
      $username = $_SESSION['username'] ?? 'Unknown';

      $page = 'summary.php';
      $action = 'update';

      $stmt = $conn->prepare("INSERT INTO audit_log (user_id, username, page, action, record_id, field_name, old_value, new_value)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("isssssss", $user_id, $username, $page, $action, $record_id, $field_name, $old_value, $new_value);
      $stmt->execute();
      $stmt->close();
  }

// Process update request
if (isset($_POST['update'])) {
    // Whitelist of allowed fields
    $allowed_fields = [
        'Area', 'Feeder', 'Meter_SN', 'STS_Number',
        'Enclosure_SN', 'Customer_No', 'Customer_Name',
        'CIU_Serial_No', 'Contact_Number', 'Meter_Status'
    ];

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $field = $_POST['field'] ?? null;
    $value = $_POST['value'] ?? '';

    // Validate input
    if ($id && in_array($field, $allowed_fields)) {
          // Get the original value first
          $orig_stmt = $conn->prepare("SELECT Meter_SN, $field FROM customer_info WHERE CustomerID = ?");
          $orig_stmt->bind_param("i", $id);
          $orig_stmt->execute();
          $orig_result = $orig_stmt->get_result();
          $orig_data = $orig_result->fetch_assoc();
          $orig_stmt->close();
        // Prepared statement
        $stmt = $conn->prepare("UPDATE customer_info SET `$field` = ? WHERE CustomerID = ?");
        $stmt->bind_param("si", $value, $id);

        if ($stmt->execute()) {
            $update_success = "Record updated successfully";
        } else {
            $update_error = "Error updating record: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $update_error = "Invalid input";
    }
}

// Process delete request
if (isset($_POST['delete'])) {
   $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

   if ($id) {
       // First get the Meter_SN for logging
       $meter_stmt = $conn->prepare("SELECT Meter_SN FROM customer_info WHERE CustomerID = ?");
       $meter_stmt->bind_param("i", $id);
       $meter_stmt->execute();
       $meter_result = $meter_stmt->get_result();
       $meter_data = $meter_result->fetch_assoc();
       $meter_stmt->close();

       // Delete the record
       $stmt = $conn->prepare("DELETE FROM customer_info WHERE CustomerID = ?");
       $stmt->bind_param("i", $id);

       if ($stmt->execute()) {
           // Log the deletion
           $user_id = $_SESSION['user_id'] ?? 0;
           $username = $_SESSION['username'] ?? 'Unknown';
           $delete_stmt = $conn->prepare("INSERT INTO audit_log (user_id, username, page, action, record_id, field_name, old_value, new_value)
                                         VALUES (?, ?, 'summary.php', 'delete', ?, 'entire_record', 'existed', 'deleted')");
           $delete_stmt->bind_param("iss", $user_id, $username, $meter_data['Meter_SN']);
           $delete_stmt->execute();
           $delete_stmt->close();

           $delete_success = "Record deleted successfully";
       } else {
           $delete_error = "Error deleting record: " . $stmt->error;
       }

       $stmt->close();
   } else {
       $delete_error = "Invalid input";
   }
}


// Process mobile edit form submissions
if (isset($_POST['mobile_update'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($id) {
        // Get original values first
        $orig_sql = "SELECT * FROM customer_info WHERE CustomerID = ?";
        $orig_stmt = $conn->prepare($orig_sql);
        $orig_stmt->bind_param("i", $id);
        $orig_stmt->execute();
        $orig_result = $orig_stmt->get_result();
        $orig_data = $orig_result->fetch_assoc();
        $orig_stmt->close();

        $updates = [];
        $params = [];
        $types = "";

        // Only include fields that are present in the form
        $possible_fields = ['Customer_Name', 'Meter_SN', 'Area', 'Feeder', 'STS_Number',
                          'Customer_No', 'Contact_Number', 'Meter_Status', 'Enclosure_SN', 'CIU_Serial_No'];

        foreach ($possible_fields as $field) {
            if (isset($_POST[$field])) {
                $updates[] = "`$field` = ?";
                $params[] = $_POST[$field];
                $types .= "s";

                // Log the change if value is different
                if ($orig_data[$field] != $_POST[$field]) {
                    logAudit($conn, $orig_data['Meter_SN'], $field, $orig_data[$field], $_POST[$field]);
                }
            }
        }

        if (!empty($updates)) {
            $sql = "UPDATE customer_info SET " . implode(", ", $updates) . " WHERE CustomerID = ?";
            $params[] = $id;
            $types .= "i";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $update_success = "Record updated successfully";
            } else {
                $update_error = "Error updating record: " . $stmt->error;
            }

            $stmt->close();
        }
    } else {
        $update_error = "Invalid input";
    }
}


// Process desktop edit actions
if (isset($_POST['edit_record'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $show_edit_form = true;

    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM customer_info WHERE CustomerID = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $record_to_edit = $result->fetch_assoc();
        $stmt->close();
    }
}
?>

<!-- Alert messages -->
<?php if (isset($update_success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Success!</strong> <?php echo $update_success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (isset($update_error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>Error!</strong> <?php echo $update_error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (isset($delete_success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Success!</strong> <?php echo $delete_success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (isset($delete_error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>Error!</strong> <?php echo $delete_error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-12">
            <div class="page-title p-3">
            <h1 class="fw-bold text-primary">Customer Information Summary</h1>
            <p class="text-muted">Search for Customer Details </p>
        </div>
    </div>
</div>

<?php if (isset($show_edit_form) && isset($record_to_edit)): ?>
<!-- Desktop Edit Form -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Edit Customer Record</h5>
            </div>
            <div class="card-body">
                <form action="summary.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $record_to_edit['CustomerID']; ?>">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="Customer_Name" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="Customer_Name" name="Customer_Name"
                                value="<?php echo htmlspecialchars($record_to_edit['Customer_Name']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="Meter_SN" class="form-label">Meter SN</label>
                            <input type="text" class="form-control" id="Meter_SN" name="Meter_SN"
                                value="<?php echo htmlspecialchars($record_to_edit['Meter_SN']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="Area" class="form-label">Area</label>
                            <input type="text" class="form-control" id="Area" name="Area"
                                value="<?php echo htmlspecialchars($record_to_edit['Area']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="Feeder" class="form-label">Feeder</label>
                            <input type="text" class="form-control" id="Feeder" name="Feeder"
                                value="<?php echo htmlspecialchars($record_to_edit['Feeder']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="STS_Number" class="form-label">STS Number</label>
                            <input type="text" class="form-control" id="STS_Number" name="STS_Number"
                                value="<?php echo htmlspecialchars($record_to_edit['STS_Number']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="Enclosure_SN" class="form-label">Enclosure SN</label>
                            <input type="text" class="form-control" id="Enclosure_SN" name="Enclosure_SN"
                                value="<?php echo htmlspecialchars($record_to_edit['Enclosure_SN']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="Customer_No" class="form-label">Customer No</label>
                            <input type="text" class="form-control" id="Customer_No" name="Customer_No"
                                value="<?php echo htmlspecialchars($record_to_edit['Customer_No']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="CIU_Serial_No" class="form-label">CIU Serial No</label>
                            <input type="text" class="form-control" id="CIU_Serial_No" name="CIU_Serial_No"
                                value="<?php echo htmlspecialchars($record_to_edit['CIU_Serial_No']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="Contact_Number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="Contact_Number" name="Contact_Number"
                                value="<?php echo htmlspecialchars($record_to_edit['Contact_Number']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="Meter_Status" class="form-label">Meter Status</label>
                            <select class="form-select" id="Meter_Status" name="Meter_Status">
                                <?php
                                $status_query = "SELECT DISTINCT Meter_Status FROM customer_info WHERE Meter_Status IS NOT NULL AND Meter_Status != '' ORDER BY Meter_Status";
                                $status_result = mysqli_query($conn, $status_query);
                                while ($status_row = mysqli_fetch_assoc($status_result)) {
                                    $selected = ($record_to_edit['Meter_Status'] == $status_row['Meter_Status']) ? 'selected' : '';
                                    echo "<option value='{$status_row['Meter_Status']}' $selected>{$status_row['Meter_Status']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-6">
                            <button type="submit" name="mobile_update" class="btn btn-primary w-100">Save Changes</button>
                        </div>
                        <div class="col-6">
                            <a href="summary.php<?php echo isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" class="btn btn-secondary w-100">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Filters</h5>
            </div>
            <div class="card-body">
                <form action="summary.php" method="GET">
                    <div class="row g-3">
                        <!-- Area Dropdown -->
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">Area</label>
                                <select class="form-select" name="area">
                                    <option value="">All Areas</option>
                                    <?php
                                    // Fetch unique areas
                                    $area_query = "SELECT DISTINCT Area FROM customer_info WHERE Area IS NOT NULL AND Area != '' ORDER BY Area";
                                    $area_result = mysqli_query($conn, $area_query);
                                    while ($area_row = mysqli_fetch_assoc($area_result)) {
                                        $selected = (isset($_GET['area']) && $_GET['area'] == $area_row['Area']) ? 'selected' : '';
                                        echo "<option value='{$area_row['Area']}' $selected>{$area_row['Area']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Status Dropdown -->
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Statuses</option>
                                    <?php
                                    // Fetch unique statuses
                                    $status_query = "SELECT DISTINCT Meter_Status FROM customer_info WHERE Meter_Status IS NOT NULL AND Meter_Status != '' ORDER BY Meter_Status";
                                    $status_result = mysqli_query($conn, $status_query);
                                    while ($status_row = mysqli_fetch_assoc($status_result)) {
                                        $selected = (isset($_GET['status']) && $_GET['status'] == $status_row['Meter_Status']) ? 'selected' : '';
                                        echo "<option value='{$status_row['Meter_Status']}' $selected>{$status_row['Meter_Status']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Meter SN Search -->
                        <div class="col-12 col-md-4">
                            <div class="form-group">
                                <label class="form-label">Search by Meter SN</label>
                                <input type="text" class="form-control" name="meter_sn" placeholder="Enter Meter SN" value="<?php echo isset($_GET['meter_sn']) ? htmlspecialchars($_GET['meter_sn']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100 mb-2">Search</button>
                            <a href="summary.php" class="btn btn-secondary w-100">Reset Filters</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Export Button -->
<div class="row" style="margin-top:10px;">
    <div class="col-md-6 offset-md-3">
        <div class="d-grid">
            <a href="export_excel.php?area=<?php echo isset($_GET['area']) ? urlencode($_GET['area']) : ''; ?>&status=<?php echo isset($_GET['status']) ? urlencode($_GET['status']) : ''; ?>&meter_sn=<?php echo isset($_GET['meter_sn']) ? urlencode($_GET['meter_sn']) : ''; ?>" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export to Excel
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <!-- Desktop Table View -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered table-striped text-center" id="meterTable">
                        <thead class="thead-dark">
                            <tr>
                                 <th>Feeder</th>
                                <th>Meter SN</th>
                                <th>STS Number</th>
                                <th>Enclosure SN</th>
                                <th>Customer No</th>
                                <th>Customer Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                          <?php
                          // Pagination
                          $limit = 5; // Number of rows per page
                          $page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Current page
                          $offset = ($page - 1) * $limit; // Calculate offset

                          // Initialize filter variables
                          $area = isset($_GET['area']) ? $_GET['area'] : '';
                          $status = isset($_GET['status']) ? $_GET['status'] : '';
                          $meter_sn = isset($_GET['meter_sn']) ? $_GET['meter_sn'] : '';

                          // Build the base query
                          $sql = "SELECT * FROM customer_info WHERE 1=1";

                          // Initialize parameter variables
                          $param_types = '';
                          $param_values = [];

                          // Add filters to the query if they are set
                          if (!empty($area)) {
                              $sql .= " AND Area = ?";
                              $param_types .= 's';
                              $param_values[] = $area;
                          }
                          if (!empty($status)) {
                              $sql .= " AND Meter_Status = ?";
                              $param_types .= 's';
                              $param_values[] = $status;
                          }
                          if (!empty($meter_sn)) {
                              $sql .= " AND Meter_SN LIKE ?";
                              $param_types .= 's';
                              $param_values[] = "%$meter_sn%";
                          }

                          // Add pagination to the query
                          $sql .= " LIMIT ? OFFSET ?";
                          $param_types .= 'ii';
                          $param_values[] = $limit;
                          $param_values[] = $offset;

                          // Prepare the statement
                          $stmt = mysqli_prepare($conn, $sql);

                          if ($stmt) {
                              // Bind parameters
                              if (!empty($param_values)) {
                                  mysqli_stmt_bind_param($stmt, $param_types, ...$param_values);
                              }

                              // Execute the query
                              mysqli_stmt_execute($stmt);

                              // Get the result
                              $result = mysqli_stmt_get_result($stmt);

                              // Fetch and display data
                              while ($row = mysqli_fetch_assoc($result)) {
                                  echo "<tr>
                                           <td>{$row['Feeder']}</td>
                                          <td>{$row['Meter_SN']}</td>
                                          <td>{$row['STS_Number']}</td>
                                          <td>{$row['Enclosure_SN']}</td>
                                          <td>{$row['Customer_No']}</td>
                                          <td>{$row['Customer_Name']}</td>
                                          <td>
                                              <div class='btn-group'>
                                                  <form method='post' action='' style='display:inline; margin-right:5px;'>
                                                      <input type='hidden' name='id' value='{$row['CustomerID']}'>
                                                      <button type='submit' name='edit_record' class='btn btn-sm btn-outline-warning'>
                                                          <i class='fas fa-edit'></i> Edit
                                                      </button>
                                                  </form>
                                                  <form method='post' action='' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this record?\");'>
                                                      <input type='hidden' name='id' value='{$row['CustomerID']}'>
                                                      <button type='submit' name='delete' class='btn btn-sm btn-outline-danger'>
                                                          <i class='fas fa-trash'></i> Delete
                                                      </button>
                                                  </form>
                                              </div>
                                          </td>
                                        </tr>";
                              }

                              // Close the statement
                              mysqli_stmt_close($stmt);
                          } else {
                              echo "Error preparing statement: " . mysqli_error($conn);
                          }

                          ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="d-md-none">
                    <?php
                    // Reuse the existing query logic but display as cards
                    $limit = 5;
                    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                    $offset = ($page - 1) * $limit;

                    // Initialize filter variables
                    $area = isset($_GET['area']) ? $_GET['area'] : '';
                    $status = isset($_GET['status']) ? $_GET['status'] : '';
                    $meter_sn = isset($_GET['meter_sn']) ? $_GET['meter_sn'] : '';

                    // Build the base query
                    $sql = "SELECT * FROM customer_info WHERE 1=1";

                    // Initialize parameter variables
                    $param_types = '';
                    $param_values = [];

                    // Add filters to the query if they are set
                    if (!empty($area)) {
                        $sql .= " AND Area = ?";
                        $param_types .= 's';
                        $param_values[] = $area;
                    }
                    if (!empty($status)) {
                        $sql .= " AND Meter_Status = ?";
                        $param_types .= 's';
                        $param_values[] = $status;
                    }
                    if (!empty($meter_sn)) {
                        $sql .= " AND Meter_SN LIKE ?";
                        $param_types .= 's';
                        $param_values[] = "%$meter_sn%";
                    }

                    // Add pagination to the query
                    $sql .= " LIMIT ? OFFSET ?";
                    $param_types .= 'ii';
                    $param_values[] = $limit;
                    $param_values[] = $offset;

                    // Prepare the statement
                    $stmt = mysqli_prepare($conn, $sql);

                    if ($stmt) {
                        // Bind parameters
                        if (!empty($param_values)) {
                            mysqli_stmt_bind_param($stmt, $param_types, ...$param_values);
                        }

                        // Execute the query
                        mysqli_stmt_execute($stmt);

                        // Get the result
                        $result = mysqli_stmt_get_result($stmt);

                        // Fetch and display data as mobile cards
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<div class='card mb-3'>
                                    <div class='card-body'>
                                        <div class='d-flex justify-content-between align-items-center mb-2'>
                                            <h5 class='card-title mb-0'>{$row['Customer_Name']}</h5>
                                            <span class='badge bg-" .
                                            (($row['Meter_Status'] == 'Done') ? 'success' :
                                             (($row['Meter_Status'] == 'Closed') ? 'danger' :
                                             (($row['Meter_Status'] == 'Meter Fault') ? 'info' : 'warning'))) .
                                            "'>{$row['Meter_Status']}</span>
                                        </div>
                                        <div class='row'>
                                            <div class='col-6'>
                                                <strong>Meter SN:</strong> {$row['Meter_SN']}
                                            </div>
                                            <div class='col-6'>
                                                <strong>Area:</strong> {$row['Area']}
                                            </div>
                                            <div class='col-6'>
                                                <strong>Customer No:</strong> {$row['Customer_No']}
                                            </div>
                                            <div class='col-6'>
                                                <strong>Feeder:</strong> {$row['Feeder']}
                                            </div>
                                            <div class='col-6'>
                                                <strong>Phone:</strong> {$row['Contact_Number']}
                                            </div>
                                            <div class='col-6'>
                                                <strong>STS Number:</strong> {$row['STS_Number']}
                                            </div>
                                        </div>
                                        <div class='mt-3 d-flex justify-content-between'>
                                            <form method='post' action='' style='display:inline;'>
                                                <input type='hidden' name='id' value='{$row['CustomerID']}'>
                                                <button type='submit' name='edit_record' class='btn btn-sm btn-warning'>
                                                    <i class='fas fa-edit'></i> Edit
                                                </button>
                                            </form>
                                            <form method='post' action='' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this record?\");'>
                                                <input type='hidden' name='id' value='{$row['CustomerID']}'>
                                                <button type='submit' name='delete' class='btn btn-sm btn-danger'>
                                                    <i class='fas fa-trash'></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>";
                        }

                        // Close the statement
                        mysqli_stmt_close($stmt);
                    } else {
                        echo "Error preparing statement: " . mysqli_error($conn);
                    }
                    ?>
                </div>

                <!-- Pagination -->
                <?php
                // Count total records for pagination
                $count_sql = "SELECT COUNT(*) as total FROM customer_info WHERE 1=1";

                // Initialize parameter variables for count query
                $count_types = '';
                $count_params = [];

                if (!empty($area)) {
                    $count_sql .= " AND Area = ?";
                    $count_types .= 's';
                    $count_params[] = $area;
                }
                if (!empty($status)) {
                    $count_sql .= " AND Meter_Status = ?";
                    $count_types .= 's';
                    $count_params[] = $status;
                }
                if (!empty($meter_sn)) {
                    $count_sql .= " AND Meter_SN LIKE ?";
                    $count_types .= 's';
                    $count_params[] = "%$meter_sn%";
                }

                $count_stmt = mysqli_prepare($conn, $count_sql);

                if ($count_stmt) {
                    // Bind parameters for count query
                    if (!empty($count_params)) {
                        mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
                    }

                    mysqli_stmt_execute($count_stmt);
                    $count_result = mysqli_stmt_get_result($count_stmt);
                    $count_row = mysqli_fetch_assoc($count_result);
                    $total_records = $count_row['total'];
                    $total_pages = ceil($total_records / $limit);

                    if ($total_pages > 1) {
                        echo '<nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">';

                        // Previous page link
                        $prev_disabled = ($page <= 1) ? 'disabled' : '';
                        echo '<li class="page-item ' . $prev_disabled . '">
                                <a class="page-link" href="summary.php?page=' . ($page - 1) .
                                (isset($_GET['area']) ? '&area=' . urlencode($_GET['area']) : '') .
                                (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '') .
                                (isset($_GET['meter_sn']) ? '&meter_sn=' . urlencode($_GET['meter_sn']) : '') .
                                '" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>';

                        // Page number links
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active = ($i == $page) ? 'active' : '';
                            echo '<li class="page-item ' . $active . '">
                                    <a class="page-link" href="summary.php?page=' . $i .
                                    (isset($_GET['area']) ? '&area=' . urlencode($_GET['area']) : '') .
                                    (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '') .
                                    (isset($_GET['meter_sn']) ? '&meter_sn=' . urlencode($_GET['meter_sn']) : '') .
                                    '">' . $i . '</a>
                                </li>';
                        }

                        // Next page link
                        $next_disabled = ($page >= $total_pages) ? 'disabled' : '';
                        echo '<li class="page-item ' . $next_disabled . '">
                                <a class="page-link" href="summary.php?page=' . ($page + 1) .
                                (isset($_GET['area']) ? '&area=' . urlencode($_GET['area']) : '') .
                                (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '') .
                                (isset($_GET['meter_sn']) ? '&meter_sn=' . urlencode($_GET['meter_sn']) : '') .
                                '" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>';

                        echo '</ul>
                            </nav>';
                    }

                    mysqli_stmt_close($count_stmt);
                }
                ?>
            </div>
        </div>
    </div>
</div>
</div>
<?php include 'footer.php'; ?>
 <script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>
