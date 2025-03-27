<?php
 include 'header.php';
include 'connection.php';

// Restrict access: Only 'Admin' can view this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit(); // Stop further execution
}

// Check if user session exists
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit();
}

// Check if session has expired
if (isset($_SESSION['expire_time']) && time() > $_SESSION['expire_time']) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
} else {
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Default to last 7 days if no dates provided
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-7 days'));

// Get filter values from GET parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $start_date;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $end_date;
$username = isset($_GET['username']) ? $_GET['username'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$meter = isset($_GET['meter']) ? $_GET['meter'] : '';

// Function to convert page filenames to friendly names
function getFriendlyPageName($pageName) {
    $pageMap = [
        'customer-info.php' => 'Customer Details',
        'meter-management.php' => 'Meter Management',
        'billing.php' => 'Billing Portal',
        'user-management.php' => 'User Admin',
        'dashboard.php' => 'Dashboard',
        'reports.php' => 'Reports',
        'settings.php' => 'System Settings',
        // Add other page mappings as needed
    ];

    return isset($pageMap[$pageName]) ? $pageMap[$pageName] : 'Other Page';
}
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-12">
            <div class="page-title p-3">
            <h1 class="fw-bold text-primary">Audit Log</h1>
            <p class="text-muted">Track all changes made to customer information</p>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Filter Logs</h5>
            </div>
            <div class="card-body">
                <form action="audit-log.php" method="GET">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="username" class="form-label">User</label>
                            <select class="form-select" id="username" name="username">
                                <option value="">All Users</option>
                                <?php
                                // Get list of users who have made changes
                                $user_query = "SELECT DISTINCT username FROM audit_log ORDER BY username";
                                $user_result = mysqli_query($conn, $user_query);
                                while ($user_row = mysqli_fetch_assoc($user_result)) {
                                    $selected = ($username == $user_row['username']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($user_row['username']) . "' $selected>" .
                                         htmlspecialchars($user_row['username']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="action" class="form-label">Action</label>
                            <select class="form-select" id="action" name="action">
                                <option value="">All Actions</option>
                                <option value="update" <?php echo ($action == 'update') ? 'selected' : ''; ?>>Update</option>
                                <option value="delete" <?php echo ($action == 'delete') ? 'selected' : ''; ?>>Delete</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="meter" class="form-label">Meter SN</label>
                            <input type="text" class="form-control" id="meter" name="meter"
                                   placeholder="Filter by Meter Serial Number" value="<?php echo htmlspecialchars($meter); ?>">
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="audit-log.php" class="btn btn-secondary">Reset</a>
<br><br>
                            <!-- Export Button -->
                            <a href="export-audit-log.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&username=<?php echo urlencode($username); ?>&action=<?php echo urlencode($action); ?>&meter=<?php echo urlencode($meter); ?>" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Export to Excel
                              </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Audit Log Table -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <!-- Desktop version - visible only on md screens and up -->
                <div class="d-none d-md-block">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>User</th>
                                <th>Page</th>
                                <th>Action</th>
                                <th>Meter SN</th>
                                <th>Field</th>
                                <th>Old Value</th>
                                <th>New Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Prepare the base query
                            $sql = "SELECT * FROM audit_log WHERE timestamp BETWEEN ? AND ? ";

                            // Add additional filters
                            $params = [];
                            $types = "";

                            // Add start and end dates to the parameter array
                            $params[] = $start_date . " 00:00:00";
                            $params[] = $end_date . " 23:59:59";
                            $types .= "ss";

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

                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    // Get simplified field name
                                    $fieldName = "";
                                    switch($row['field_name']) {
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
                                            $fieldName = $row['field_name'];
                                    }

                                    // Convert page name to friendly name
                                    $friendlyPageName = getFriendlyPageName($row['page']);

                                    echo "<tr>";
                                    echo "<td>" . date('M d, Y g:i A', strtotime($row['timestamp'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                    echo "<td>" . htmlspecialchars($friendlyPageName) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['action']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['record_id']) . "</td>";
                                    echo "<td>" . $fieldName . "</td>";
                                    echo "<td>" . htmlspecialchars($row['old_value']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['new_value']) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No records found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile version - shows as stacked cards -->
                <div class="d-md-none">
                    <?php
                    // Reset and re-execute for mobile display
                    if (isset($stmt)) $stmt->close();
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Get simplified field name
                            $fieldName = "";
                            switch($row['field_name']) {
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
                                    $fieldName = $row['field_name'];
                            }

                            // Convert page name to friendly name
                            $friendlyPageName = getFriendlyPageName($row['page']);
                    ?>
                        <div class="card mb-3 border">
                            <div class="card-header py-2 bg-light">
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted"><?php echo date('M d, Y g:i A', strtotime($row['timestamp'])); ?></small>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($row['username']); ?></span>
                                </div>
                                <div class="mt-1 d-flex justify-content-between">
                                  <!--  <span class="badge bg-info">Meter: <?php echo htmlspecialchars($row['record_id']); ?></span> -->
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($row['action']); ?></span>
                                </div>
                            </div>
                            <div class="card-body py-2">
                                <p class="mb-1"><strong><?php echo $fieldName; ?></strong></p>
                                <div class="row">
                                    <div class="col-5">
                                        <small class="text-muted">From:</small><br>
                                        <?php echo htmlspecialchars($row['old_value']); ?>
                                    </div>
                                    <div class="col-2 text-center">
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                    <div class="col-5">
                                        <small class="text-muted">To:</small><br>
                                        <?php echo htmlspecialchars($row['new_value']); ?>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small style="font-weight:bold;" class="text-muted">Page: <?php echo htmlspecialchars($friendlyPageName); ?></small><br>
                                    <small style="font-weight:bold;" class="text-muted">Edited by: <?php echo htmlspecialchars($row['username']); ?></small>

                                </div>
                            </div>
                        </div>
                    <?php
                        }
                    } else {
                        echo '<div class="alert alert-info">No records found</div>';
                    }

                    $stmt->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include 'footer.php'; ?>
