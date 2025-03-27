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
$page = isset($_GET['page']) ? $_GET['page'] : '';

// Function to convert page filenames to friendly names
function getFriendlyPageName($pageName) {
    $pageMap = [
        'profile.php' => 'User Profile',
        'manage-users.php' => 'User Management',
        'edit-user.php' => 'Edit User',
        'index.php' => 'Login Page',
        'dashboard.php' => 'Dashboard',
        'billing.php' => 'Billing Portal',
        'customer-info.php' => 'Customer Details',
        'meter-management.php' => 'Meter Management',
        'reports.php' => 'Reports',
        'settings.php' => 'System Settings',
        // Add other page mappings as needed
    ];

    return isset($pageMap[$pageName]) ? $pageMap[$pageName] : ucfirst(str_replace(['-', '.php'], [' ', ''], $pageName));
}
?>

<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-12">
            <div class="page-title p-3">
                <h1 class="fw-bold text-primary">User Activity Log</h1>
                <p class="text-muted">Track all user activities in the system</p>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="row g-0">
        <div class="col-12">
            <div class="card shadow-sm m-3">
                <div class="card-header bg-white p-3">
                    <h5 class="card-title m-0">Filter Logs</h5>
                </div>
                <div class="card-body p-3">
                    <form action="" method="GET">
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
                                    // Get list of users who have activities logged
                                    $user_query = "SELECT DISTINCT u.username, u.userid
                                                 FROM user_activity ua
                                                 JOIN users u ON ua.user_id = u.userid
                                                 ORDER BY u.username";
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
                                    <option value="create" <?php echo ($action == 'create') ? 'selected' : ''; ?>>Create</option>
                                    <option value="update" <?php echo ($action == 'update') ? 'selected' : ''; ?>>Update</option>
                                    <option value="delete" <?php echo ($action == 'delete') ? 'selected' : ''; ?>>Delete</option>
                                    <option value="login" <?php echo ($action == 'login') ? 'selected' : ''; ?>>Login</option>
                                    <option value="logout" <?php echo ($action == 'logout') ? 'selected' : ''; ?>>Logout</option>
                                    <option value="session_timeout" <?php echo ($action == 'session_timeout') ? 'selected' : ''; ?>>Session Timeout</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="page" class="form-label">Page</label>
                                <select class="form-select" id="page" name="page">
                                    <option value="">All Pages</option>
                                    <?php
                                    // Get list of distinct pages
                                    $page_query = "SELECT DISTINCT page FROM user_activity ORDER BY page";
                                    $page_result = mysqli_query($conn, $page_query);
                                    while ($page_row = mysqli_fetch_assoc($page_result)) {
                                        $selected = ($page == $page_row['page']) ? 'selected' : '';
                                        $friendly_name = getFriendlyPageName($page_row['page']);
                                        echo "<option value='" . htmlspecialchars($page_row['page']) . "' $selected>" .
                                             htmlspecialchars($friendly_name) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="user-activity-log.php" class="btn btn-secondary">Reset</a>

                                <!-- Export Button -->
                                <a href="export-user-activity-log.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&username=<?php echo urlencode($username); ?>&action=<?php echo urlencode($action); ?>&page=<?php echo urlencode($page); ?>" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Export to Excel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Log Table -->
    <div class="row g-0">
        <div class="col-12">
            <div class="card shadow-sm m-3">
                <div class="card-header bg-white p-3">
                    <h5 class="card-title m-0">User Activity History</h5>
                </div>
                <div class="card-body p-0">
                    <!-- Desktop version - visible only on md screens and up -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-striped table-bordered m-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Date/Time</th>
                                    <th>User</th>
                                    <th>Page</th>
                                      <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Prepare the base query
                                $sql = "SELECT ua.*, u.username
                                       FROM user_activity ua
                                       LEFT JOIN users u ON ua.user_id = u.userid
                                       WHERE ua.timestamp BETWEEN ? AND ? ";

                                // Add additional filters
                                $params = [];
                                $types = "";

                                // Add start and end dates to the parameter array
                                $params[] = $start_date . " 00:00:00";
                                $params[] = $end_date . " 23:59:59";
                                $types .= "ss";

                                if (!empty($username)) {
                                    $sql .= "AND u.username = ? ";
                                    $params[] = $username;
                                    $types .= "s";
                                }

                                if (!empty($action)) {
                                    $sql .= "AND ua.action = ? ";
                                    $params[] = $action;
                                    $types .= "s";
                                }

                                if (!empty($page)) {
                                    $sql .= "AND ua.page = ? ";
                                    $params[] = $page;
                                    $types .= "s";
                                }

                                // Order by timestamp descending
                                $sql .= "ORDER BY ua.timestamp DESC";

                                // Prepare and execute the statement
                                $stmt = $conn->prepare($sql);
                                if($stmt) {
                                    $stmt->bind_param($types, ...$params);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            // Convert page name to friendly name
                                            $friendlyPageName = getFriendlyPageName($row['page']);

                                            // Format target ID for display
                                            $targetId = !empty($row['target_id']) ? $row['target_id'] : '-';

                                            echo "<tr>";
                                            echo "<td>" . date('M d, Y g:i A', strtotime($row['timestamp'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['username'] ?? 'Unknown') . "</td>";
                                            echo "<td>" . htmlspecialchars($friendlyPageName) . "</td>";
                                              echo "<td>" . htmlspecialchars($row['details'] ?? 'No details provided') . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7' class='text-center'>No activity records found</td></tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center text-danger'>Error preparing statement: " . mysqli_error($conn) . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile version - shows as stacked cards -->
                    <div class="d-md-none">
                        <?php
                        // Reset and re-execute for mobile display
                        if (isset($stmt) && $stmt) {
                            $stmt->close();
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param($types, ...$params);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    // Convert page name to friendly name
                                    $friendlyPageName = getFriendlyPageName($row['page']);

                                    // Get action class
                                    $actionClass = '';
                                    switch(strtolower($row['action'])) {
                                        case 'create':
                                            $actionClass = 'bg-success';
                                            break;
                                        case 'update':
                                            $actionClass = 'bg-primary';
                                            break;
                                        case 'delete':
                                            $actionClass = 'bg-danger';
                                            break;
                                        case 'login':
                                            $actionClass = 'bg-info';
                                            break;
                                        case 'logout':
                                            $actionClass = 'bg-secondary';
                                            break;
                                        case 'session_timeout':
                                            $actionClass = 'bg-warning';
                                            break;
                                        default:
                                            $actionClass = 'bg-secondary';
                                    }
                        ?>
                            <div class="card mb-3 border">
                                <div class="card-header py-2 bg-light">
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted"><?php echo date('M d, Y g:i A', strtotime($row['timestamp'])); ?></small>
                                        <span class="badge <?php echo $actionClass; ?>"><?php echo ucfirst(htmlspecialchars($row['action'])); ?></span>
                                    </div>
                                </div>
                                <div class="card-body py-2">
                                    <p class="mb-1"><strong><?php echo htmlspecialchars($row['username'] ?? 'Unknown'); ?></strong></p>
                                    <p class="mb-1 text-muted"><?php echo htmlspecialchars($friendlyPageName); ?></p>

                                <!--    <?php if(!empty($row['target_id'])): ?>
                                    <p class="mb-1 small">Target ID: <?php echo htmlspecialchars($row['target_id']); ?></p>
                                  <?php endif; ?> -->

                                    <?php if(!empty($row['details'])): ?>
                                    <div class="mt-2 small border-top pt-2">
                                        <?php echo htmlspecialchars($row['details']); ?>
                                    </div>
                                    <?php endif; ?>

                                  <!--  <div class="mt-2 small text-muted text-end">
                                        <?php echo htmlspecialchars($row['ip_address']); ?>
                                    </div> -->
                                </div>
                            </div>
                        <?php
                                }
                            } else {
                                echo '<div class="alert alert-info m-3">No activity records found</div>';
                            }

                            $stmt->close();
                        } else {
                            echo '<div class="alert alert-danger m-3">Error preparing statement: ' . mysqli_error($conn) . '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
