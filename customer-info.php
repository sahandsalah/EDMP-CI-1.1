<?php
ob_start(); // Start output buffering

include 'connection.php';
include 'header.php';
date_default_timezone_set('Asia/Baghdad');
$currentDateTime = date('Y-m-d H:i:s');

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
 // Include your database connection file
?>

<!-- Customer Information Content -->

<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-12">
            <div class="page-title p-3">
            <h1 class="fw-bold text-primary">Customer Information</h1>
            <p class="text-muted">Search for Customer Details </p>
        </div>
    </div>
</div>
<?php
// Function to log changes to audit_log table
function logAudit($conn, $record_id, $field_name, $old_value, $new_value) {
    // Get current user info - adjust based on your authentication system
    $user_id = $_SESSION['userid']; // Default to 0 if not logged in
    $username= $_SESSION['username'];


    $page = 'customer-info.php';
    $action = 'update';

    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, username, page, action, record_id, field_name, old_value, new_value)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $user_id, $username, $page, $action, $record_id, $field_name, $old_value, $new_value);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // Database connection

    // Sanitize input data
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $meter_sn = mysqli_real_escape_string($conn, $_POST['meter_sn']);
    $ciu_serial_no = mysqli_real_escape_string($conn, $_POST['ciu_serial_no']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $meter_status = mysqli_real_escape_string($conn, $_POST['meter_status']);

    // Get original data for comparison
    $original_query = "SELECT CIU_Serial_No, Contact_Number, Meter_Status FROM customer_info WHERE Meter_SN = '$meter_sn'";
    $original_result = mysqli_query($conn, $original_query);
    $original_data = mysqli_fetch_assoc($original_result);

    // Build change description for audit log
    $changes = [];
    if ($original_data['CIU_Serial_No'] != $ciu_serial_no) {
        logAudit($conn, $meter_sn, 'CIU_Serial_No', $original_data['CIU_Serial_No'], $ciu_serial_no);
    }
    if ($original_data['Contact_Number'] != $contact_number) {
        logAudit($conn, $meter_sn, 'Contact_Number', $original_data['Contact_Number'], $contact_number);
    }
    if ($original_data['Meter_Status'] != $meter_status) {
        logAudit($conn, $meter_sn, 'Meter_Status', $original_data['Meter_Status'], $meter_status);
    }

    // Update query
    $sql = "UPDATE customer_info
            SET CIU_Serial_No = '$ciu_serial_no',
                Contact_Number = '$contact_number',
                Meter_Status = '$meter_status'
            WHERE Meter_SN = '$meter_sn'";

    if (mysqli_query($conn, $sql)) {
                ob_end_clean(); // Discard any output before sending headers

        // Redirect to the same page with a success parameter instead of exiting
        header("Location: customer-info.php?updated=true&search=" . urlencode($_POST['meter_sn']));
        exit();
    } else {
        echo '<div class="row mt-4">
                <div class="col-md-8 offset-md-2">
                    <div class="alert alert-danger">Error updating record: ' . mysqli_error($conn) . '</div>
                </div>
              </div>';
    }

    // Close the database connection
    mysqli_close($conn);
}
if (isset($_GET['updated']) && $_GET['updated'] == 'true') {
    echo '<div class="row mt-4">
            <div class="col-md-8 offset-md-2">
                <div class="alert alert-success">Customer information updated successfully!</div>
            </div>
          </div>';
}
?>
<!-- Search Form -->
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Search Customer</h5>
            </div>
            <div class="card-body">
                <form  method="GET">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="search" placeholder="Enter Customer No, CIU, Meter SN or Phone No" required>
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Display Search Results -->
<?php
if (isset($_GET['search'])) {
    // Database connection

    // Sanitize the search input
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    mysqli_query($conn, "SET SQL_BIG_SELECTS=1;");

    // Query to fetch customer information with joined prepayment_mode table
    $sql = "SELECT c.*, p.Tariff, p.Payment_Mode, p.Purchased_Credit as Purchased_Credit, p.Prepayment_Date as Prepayment_Date
            FROM customer_info c
            LEFT JOIN prepaymentmode p ON c.Meter_SN = p.Meter_Plant_No
            WHERE c.Customer_No = '$search'
            OR c.CIU_Serial_No = '$search'
            OR c.Meter_SN = '$search'
            OR c.Contact_Number = '$search'
            OR c.Enclosure_SN = '$search'";

    $result = mysqli_query($conn, $sql);



    $sqlCI = "SELECT * FROM customer_info
            WHERE Customer_No = '$search'
            OR CIU_Serial_No = '$search'
            OR Meter_SN = '$search'
            OR Contact_Number = '$search'
            OR Enclosure_SN = '$search'";

    $resultCI = mysqli_query($conn, $sqlCI);

    if (mysqli_num_rows($result) > 0) {
        // Loop through all matching rows
        while ($row = mysqli_fetch_assoc($result)) {
            ?>
            <!-- Display Customer Information in a Card -->
            <div class="row mt-4">
                <div class="col-md-8 offset-md-2">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Customer Details</h5>
                        </div>
                        <div class="card-body">
                            <form  method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <h6>Area</h6>
                                            <p>  <?php echo $row['Area']; ?></p>
                                        </div>
                                        <div class="form-group">
                                            <h6>Feeder</h6>
                                            <p><?php echo $row['Feeder']; ?>  </p>
                                        </div>
                                        <div class="form-group">
                                            <h6>Meter SN</h6>
                                            <p><?php echo $row['Meter_SN']; ?>  </p>
                                            <input type="hidden" class="form-control" name="meter_sn" value="<?php echo $row['Meter_SN']; ?>"  />

                                        </div>
                                        <div class="form-group">
                                            <h6>Tariff</h6>
                                            <p> <?php echo $row['Tariff']; ?></p>
                                        </div>
                                        <div class="form-group">
                                            <h6>Payment Mode</h6>
                                          <p> <?php echo $row['Payment_Mode']; ?></p>
                                        </div>
                                        <div class="form-group">
                                            <h6>Purchased Credit</h6>
                                          <p> <?php echo $row['Purchased_Credit']; ?></p>
                                        </div>
                                        <div class="form-group">
                                            <h6>Prepayment Date</h6>
                                            <p><?php echo $row['Prepayment_Date']; ?>  </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <h6>Customer No</h6>
                                            <p><?php echo $row['Customer_No']; ?>  </p>
                                        </div>
                                        <div class="form-group">
                                            <h6>Customer Name</h6>
                                              <p><?php echo $row['Customer_Name']; ?>  </p>
                                        </div>
                                        <div class="form-group">
                                            <h6>STS Number</h6>
                                            <p><?php echo $row['STS_Number']; ?>  </p>
                                        </div>
                                        <div class="form-group">
                                            <h6>Enclosure SN</h6>
                                              <p><?php echo $row['Enclosure_SN']; ?>  </p>
                                        </div>

                                        <div class="form-group">
                                            <label>CIU Serial No</label>
                                            <input type="text" class="form-control" name="ciu_serial_no" value="<?php echo $row['CIU_Serial_No']; ?>"  required>
                                        </div>
                                        <div class="form-group">
                                            <h6>Contact Number</h6>
                                            <input type="text" class="form-control" name="contact_number" value="<?php echo $row['Contact_Number']; ?>"  required>
                                        </div>
                                        <div class="form-group">
                                          <h6>Meter Status</h6>
                                          <select id="meter_status" name="meter_status" class="form-select" required>
                                            <option value="Done" <?php echo ($row['Meter_Status'] === 'Done') ? 'selected' : ''; ?> >Done</option>
                                            <option value="Meter Fault" <?php echo ($row['Meter_Status'] === 'Meter Fault') ? 'selected' : ''; ?> >Meter Fault</option>
                                            <option value="Site Issue" <?php echo ($row['Meter_Status'] === 'Site Issue') ? 'selected' : ''; ?> >Site Issue</option>
                                            <option value="Closed" <?php echo ($row['Meter_Status'] === 'Closed') ? 'selected' : ''; ?> >Closed</option>
                                          </select>
                                        </div>
                                        <br>
                                        <br>
                                    </div>
                                </div>
                                <input type="hidden" name="id" value="<?php echo $row['Meter_SN']; ?>">
                                <button type="submit" name="update" class="btn btn-primary">Update Information</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // Fetch recent changes for this meter
            $audit_query = "SELECT * FROM audit_log WHERE record_id = '{$row['Meter_SN']}' ORDER BY timestamp DESC LIMIT 5";
            $audit_result = mysqli_query($conn, $audit_query);

            if (mysqli_num_rows($audit_result) > 0) {
                ?>
                <!-- Recent Changes Card -->

              <div class="row mt-4">
                  <div class="col-md-8 offset-md-2">
                      <div class="card">
                          <div class="card-header">
                              <h5 class="card-title">Recent Changes</h5>
                          </div>
                          <div class="card-body">
                              <!-- Desktop version - visible only on md screens and up -->
                              <div class="d-none d-md-block">
                                  <table class="table table-sm table-bordered">
                                      <thead>
                                          <tr>
                                              <th>Date/Time</th>
                                              <th>User</th>
                                              <th>Field</th>
                                              <th>From</th>
                                              <th>To</th>
                                          </tr>
                                      </thead>
                                      <tbody>
                                          <?php while ($change = mysqli_fetch_assoc($audit_result)) {
                                              // Get simplified field name
                                              $fieldName = "";
                                              switch($change['field_name']) {
                                                  case 'Contact_Number':
                                                      $fieldName = 'Contact';
                                                      break;
                                                  case 'CIU_Serial_No':
                                                      $fieldName = 'CIU';
                                                      break;
                                                  case 'Meter_Status':
                                                      $fieldName = 'Status';
                                                      break;
                                                  default:
                                                      $fieldName = $change['field_name'];
                                              }
                                          ?>
                                              <tr>
                                                  <td><?php echo date('M d, Y g:i A', strtotime($change['timestamp'])); ?></td>
                                                  <td><?php echo htmlspecialchars($change['username']); ?></td>
                                                  <td><?php echo $fieldName; ?></td>
                                                  <td><?php echo htmlspecialchars($change['old_value']); ?></td>
                                                  <td><?php echo htmlspecialchars($change['new_value']); ?></td>
                                              </tr>
                                          <?php } ?>
                                      </tbody>
                                  </table>
                              </div>

                              <!-- Mobile version - shows as stacked cards instead of table -->
                              <div class="d-md-none">
                                  <?php
                                  // Reset the result pointer to the beginning
                                  mysqli_data_seek($audit_result, 0);
                                  while ($change = mysqli_fetch_assoc($audit_result)) {
                                      // Get simplified field name
                                      $fieldName = "";
                                      switch($change['field_name']) {
                                          case 'Contact_Number':
                                              $fieldName = 'Contact';
                                              break;
                                          case 'CIU_Serial_No':
                                              $fieldName = 'CIU';
                                              break;
                                          case 'Meter_Status':
                                              $fieldName = 'Status';
                                              break;
                                          default:
                                              $fieldName = $change['field_name'];
                                      }
                                  ?>
                                      <div class="card mb-2 border">
                                          <div class="card-header py-1 bg-light">
                                              <small class="text-muted"><?php echo date('M d, Y g:i A', strtotime($change['timestamp'])); ?></small>
                                              <span class="badge bg-secondary float-end"><?php echo htmlspecialchars($change['username']); ?></span>
                                          </div>
                                          <div class="card-body py-2">
                                              <p class="mb-1"><strong><?php echo $fieldName; ?></strong></p>
                                              <div class="row">
                                                  <div class="col-5">
                                                      <small class="text-muted">From:</small><br>
                                                      <?php echo htmlspecialchars($change['old_value']); ?>
                                                  </div>
                                                  <div class="col-2 text-center">
                                                      <i class="fas fa-arrow-right"></i>
                                                  </div>
                                                  <div class="col-5">
                                                      <small class="text-muted">To:</small><br>
                                                      <?php echo htmlspecialchars($change['new_value']); ?>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                  <?php } ?>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
                <?php
            }
        }
    }

    elseif (mysqli_num_rows($resultCI) > 0) {
      while ($rowCI = mysqli_fetch_assoc($resultCI)) {
          ?>
          <!-- Display Customer Information in a Card -->
          <div class="row mt-4">
              <div class="col-md-8 offset-md-2">
                  <div class="card">
                      <div class="card-header">
                          <h5 class="card-title">Customer Details</h5>
                      </div>
                      <div class="card-body">
                          <form  method="POST">
                              <div class="row">
                                  <div class="col-md-6">
                                      <div class="form-group">
                                          <h6>Area</h6>
                                          <p>  <?php echo $row['Area']; ?></p>
                                      </div>
                                      <div class="form-group">
                                          <h6>Feeder</h6>
                                          <p><?php echo $row['Feeder']; ?>  </p>
                                      </div>
                                      <div class="form-group">
                                          <h6>Meter SN</h6>
                                          <p><?php echo $row['Meter_SN']; ?>  </p>
                                          <input type="hidden" class="form-control" name="meter_sn" value="<?php echo $row['Meter_SN']; ?>"  />

                                      </div>
                                      <div class="form-group">
                                          <h6>Tariff</h6>
                                          <p> <?php echo $row['Tariff']; ?></p>
                                      </div>
                                      <div class="form-group">
                                          <h6>Payment Mode</h6>
                                        <p> <?php echo $row['Payment_Mode']; ?></p>
                                      </div>
                                      <div class="form-group">
                                          <h6>Purchased Credit</h6>
                                        <p> <?php echo $row['Purchased_Credit']; ?></p>
                                      </div>
                                      <div class="form-group">
                                          <h6>Prepayment Date</h6>
                                          <p><?php echo $row['Prepayment_Date']; ?>  </p>
                                      </div>
                                  </div>
                                  <div class="col-md-6">
                                      <div class="form-group">
                                          <h6>Customer No</h6>
                                          <p><?php echo $row['Customer_No']; ?>  </p>
                                      </div>
                                      <div class="form-group">
                                          <h6>Customer Name</h6>
                                            <p><?php echo $row['Customer_Name']; ?>  </p>
                                      </div>
                                      <div class="form-group">
                                          <h6>STS Number</h6>
                                          <p><?php echo $row['STS_Number']; ?>  </p>
                                      </div>
                                      <div class="form-group">
                                          <h6>Enclosure SN</h6>
                                            <p><?php echo $row['Enclosure_SN']; ?>  </p>
                                      </div>

                                      <div class="form-group">
                                          <label>CIU Serial No</label>
                                          <input type="text" class="form-control" name="ciu_serial_no" value="<?php echo $row['CIU_Serial_No']; ?>"  required>
                                      </div>
                                      <div class="form-group">
                                          <h6>Contact Number</h6>
                                          <input type="text" class="form-control" name="contact_number" value="<?php echo $row['Contact_Number']; ?>"  required>
                                      </div>
                                      <div class="form-group">
                                        <h6>Meter Status</h6>
                                        <select id="meter_status" name="meter_status" class="form-select" required>
                                          <option value="Done" <?php echo ($row['Meter_Status'] === 'Done') ? 'selected' : ''; ?> >Done</option>
                                          <option value="Meter Fault" <?php echo ($row['Meter_Status'] === 'Meter Fault') ? 'selected' : ''; ?> >Meter Fault</option>
                                          <option value="Site Issue" <?php echo ($row['Meter_Status'] === 'Site Issue') ? 'selected' : ''; ?> >Site Issue</option>
                                          <option value="Closed" <?php echo ($row['Meter_Status'] === 'Closed') ? 'selected' : ''; ?> >Closed</option>
                                        </select>
                                      </div>
                                      <br>
                                      <br>
                                  </div>
                              </div>
                              <input type="hidden" name="id" value="<?php echo $row['Meter_SN']; ?>">
                              <button type="submit" name="update" class="btn btn-primary">Update Information</button>
                          </form>
                      </div>
                  </div>
              </div>
          </div>

          <?php
          // Fetch recent changes for this meter
          $audit_query = "SELECT * FROM audit_log WHERE record_id = '{$row['Meter_SN']}' ORDER BY timestamp DESC LIMIT 5";
          $audit_result = mysqli_query($conn, $audit_query);

          if (mysqli_num_rows($audit_result) > 0) {
              ?>
              <!-- Recent Changes Card -->

            <div class="row mt-4">
                <div class="col-md-8 offset-md-2">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Recent Changes</h5>
                        </div>
                        <div class="card-body">
                            <!-- Desktop version - visible only on md screens and up -->
                            <div class="d-none d-md-block">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Date/Time</th>
                                            <th>User</th>
                                            <th>Field</th>
                                            <th>From</th>
                                            <th>To</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($change = mysqli_fetch_assoc($audit_result)) {
                                            // Get simplified field name
                                            $fieldName = "";
                                            switch($change['field_name']) {
                                                case 'Contact_Number':
                                                    $fieldName = 'Contact';
                                                    break;
                                                case 'CIU_Serial_No':
                                                    $fieldName = 'CIU';
                                                    break;
                                                case 'Meter_Status':
                                                    $fieldName = 'Status';
                                                    break;
                                                default:
                                                    $fieldName = $change['field_name'];
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo date('M d, Y g:i A', strtotime($change['timestamp'])); ?></td>
                                                <td><?php echo htmlspecialchars($change['username']); ?></td>
                                                <td><?php echo $fieldName; ?></td>
                                                <td><?php echo htmlspecialchars($change['old_value']); ?></td>
                                                <td><?php echo htmlspecialchars($change['new_value']); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile version - shows as stacked cards instead of table -->
                            <div class="d-md-none">
                                <?php
                                // Reset the result pointer to the beginning
                                mysqli_data_seek($audit_result, 0);
                                while ($change = mysqli_fetch_assoc($audit_result)) {
                                    // Get simplified field name
                                    $fieldName = "";
                                    switch($change['field_name']) {
                                        case 'Contact_Number':
                                            $fieldName = 'Contact';
                                            break;
                                        case 'CIU_Serial_No':
                                            $fieldName = 'CIU';
                                            break;
                                        case 'Meter_Status':
                                            $fieldName = 'Status';
                                            break;
                                        default:
                                            $fieldName = $change['field_name'];
                                    }
                                ?>
                                    <div class="card mb-2 border">
                                        <div class="card-header py-1 bg-light">
                                            <small class="text-muted"><?php echo date('M d, Y g:i A', strtotime($change['timestamp'])); ?></small>
                                            <span class="badge bg-secondary float-end"><?php echo htmlspecialchars($change['username']); ?></span>
                                        </div>
                                        <div class="card-body py-2">
                                            <p class="mb-1"><strong><?php echo $fieldName; ?></strong></p>
                                            <div class="row">
                                                <div class="col-5">
                                                    <small class="text-muted">From:</small><br>
                                                    <?php echo htmlspecialchars($change['old_value']); ?>
                                                </div>
                                                <div class="col-2 text-center">
                                                    <i class="fas fa-arrow-right"></i>
                                                </div>
                                                <div class="col-5">
                                                    <small class="text-muted">To:</small><br>
                                                    <?php echo htmlspecialchars($change['new_value']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
              <?php
          }
      }
    }



     else {
      $sql2 = "SELECT * FROM prepaymentmode
              WHERE STS_No = '$search'
              OR Meter_Plant_No = '$search'
              OR Customer_No = '$search'";

      $result2 = mysqli_query($conn, $sql2);
      if (mysqli_num_rows($result2) > 0) {
        $row2 = mysqli_fetch_assoc($result2) ?>

        <div class="row mt-4">
                <div class="col-md-8 offset-md-2">
                    <div class="alert alert-warning"><?php echo htmlspecialchars($_GET['search']); ?> | Has no data on [KRG Database]  </div>

                </div>
              </div>';

              <?php
}
else {
        // No results found
        echo '<div class="row mt-4">
                <div class="col-md-8 offset-md-2">
                    <div class="alert alert-danger">Information is not available for now.</div>
                </div>
              </div>';
            }
    }

    // Close the database connection
    mysqli_close($conn);
}
?>

<?php include 'footer.php'; ?>
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>
