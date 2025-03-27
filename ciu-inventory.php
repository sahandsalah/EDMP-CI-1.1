<?php
// connection.php - Keep your existing connection file as is
// This page assumes your existing connection.php contains the database connection variable $conn

// ciu_inventory.php - Main inventory page
include 'connection.php';
include 'header.php';

// Check if user is logged in - similar to your dashboard.php approach
if (!isset($_SESSION['userid'])) {
    // Redirect to login page if not logged in
    header("Location: index.php");
    exit();
}

// Check if session has expired
if (isset($_SESSION['expire_time']) && time() > $_SESSION['expire_time']) {
    // Session has expired, log the user out
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
} else {
    // Update the last activity time
    $_SESSION['last_activity'] = time();
}

// Handle form submission for adding new inventory data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_inventory'])) {
    $entry_type = mysqli_real_escape_string($conn, $_POST['entry_type']);
    $quantity = !empty($_POST['quantity']) ? intval($_POST['quantity']) : NULL;
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $entry_date = mysqli_real_escape_string($conn, $_POST['entry_date']);
    $single_phase = !empty($_POST['single_phase']) ? intval($_POST['single_phase']) : NULL;
    $three_phase = !empty($_POST['three_phase']) ? intval($_POST['three_phase']) : NULL;
    $damaged = !empty($_POST['damaged']) ? intval($_POST['damaged']) : NULL;
    $user_id = $_SESSION['userid'];

    // SQL to insert data
    $sql = "INSERT INTO ciu_inventory (entry_type, quantity, location, entry_date, single_phase, three_phase, damaged, added_by, added_date)
            VALUES ('$entry_type', " . ($quantity === NULL ? "NULL" : $quantity) . ", '$location', '$entry_date',
            " . ($single_phase === NULL ? "NULL" : $single_phase) . ",
            " . ($three_phase === NULL ? "NULL" : $three_phase) . ",
            " . ($damaged === NULL ? "NULL" : $damaged) . ",
            $user_id, NOW())";

    if (mysqli_query($conn, $sql)) {
        $success_message = "Inventory record added successfully";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// Create ciu_inventory table if it doesn't exist
$check_table_sql = "SHOW TABLES LIKE 'ciu_inventory'";
$table_exists = mysqli_query($conn, $check_table_sql);

if (mysqli_num_rows($table_exists) == 0) {
    // Table doesn't exist, create it
    $create_table_sql = "CREATE TABLE `ciu_inventory` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `entry_type` enum('received','export','balance') NOT NULL,
        `quantity` int(11) DEFAULT NULL,
        `location` varchar(100) DEFAULT NULL,
        `entry_date` date NOT NULL,
        `single_phase` int(11) DEFAULT NULL,
        `three_phase` int(11) DEFAULT NULL,
        `damaged` int(11) DEFAULT NULL,
        `added_by` int(11) NOT NULL,
        `added_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($conn, $create_table_sql);
}

// Fetch inventory data with filtering
$where_conditions = [];
$query_params = [];

// Date range filter
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $start_date = mysqli_real_escape_string($conn, $_GET['start_date']);
    $where_conditions[] = "entry_date >= '$start_date'";
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $end_date = mysqli_real_escape_string($conn, $_GET['end_date']);
    $where_conditions[] = "entry_date <= '$end_date'";
}

// Location filter
if (isset($_GET['location']) && !empty($_GET['location'])) {
    $location_filter = mysqli_real_escape_string($conn, $_GET['location']);
    $where_conditions[] = "location = '$location_filter'";
}

// Entry type filter
if (isset($_GET['entry_type']) && !empty($_GET['entry_type'])) {
    $entry_type_filter = mysqli_real_escape_string($conn, $_GET['entry_type']);
    $where_conditions[] = "entry_type = '$entry_type_filter'";
}

// Build the WHERE clause
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Get distinct locations for dropdown
$locations_query = "SELECT DISTINCT location FROM ciu_inventory WHERE location IS NOT NULL AND location != '' ORDER BY location";
$locations_result = mysqli_query($conn, $locations_query);
$locations = [];
while ($row = mysqli_fetch_assoc($locations_result)) {
    $locations[] = $row['location'];
}

// Get location data for the form dropdown
$location_dropdown_query = "SELECT location_name FROM location";
$location_dropdown_result = mysqli_query($conn, $location_dropdown_query);

// Get inventory data
$inventory_query = "SELECT i.*, u.username FROM ciu_inventory i
        LEFT JOIN users u ON i.added_by = u.userid
        $where_clause
        ORDER BY i.id DESC ";
$inventory_result = mysqli_query($conn, $inventory_query);




// Get the selected number of items per page from the request
$items_per_page = isset($_GET['items_per_page']) && in_array($_GET['items_per_page'], [5, 10, 25, 50, 100, 'all'])
    ? $_GET['items_per_page']
    : 5; // Default to 5 items per page

// Calculate the total number of pages
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($current_page - 1) * ($items_per_page === 'all' ? 0 : $items_per_page); // Offset for SQL query

// Modify the inventory query to include LIMIT and OFFSET
$limit_clause = $items_per_page === 'all' ? '' : "LIMIT $items_per_page OFFSET $offset";
$inventory_query = "SELECT i.*, u.username FROM ciu_inventory i
        LEFT JOIN users u ON i.added_by = u.userid
        $where_clause
        ORDER BY i.id DESC
        $limit_clause";
$inventory_result = mysqli_query($conn, $inventory_query);

// Get total number of records for pagination
$total_records_query = "SELECT COUNT(*) as total FROM ciu_inventory i $where_clause";
$total_records_result = mysqli_query($conn, $total_records_query);
$total_records = mysqli_fetch_assoc($total_records_result)['total'];
$total_pages = $items_per_page === 'all' ? 1 : ceil($total_records / $items_per_page); // If "All" is selected, only 1 page
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIU Inventory </title>
      <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 10px;
        }

        .page-header {
            margin-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 0.5rem;
        }

        .welcome-bar {
            background-color: var(--light-color);
            border-radius: 0.25rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            padding: 0.75rem 1rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-control {
            border-radius: 0.25rem;
            padding: 0.5rem 0.75rem;
            border: 1px solid #ced4da;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .form-label {
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .btn {
            border-radius: 0.25rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .table td, .table th {
            padding: 0.75rem;
            vertical-align: middle;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .alert {
            border-radius: 0.25rem;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
        }

        .bilingual {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }



        /* Responsive grid for form fields */
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -0.5rem;
            margin-left: -0.5rem;
        }

        .form-row > .form-group {
            flex: 1 0 250px;
            padding: 0 0.5rem;
        }

        /* Responsive table container */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Mobile specific styles */
        @media (max-width: 767.98px) {
            .page-container {
                padding: 0;
                margin: 0;
                max-width: 100%;
            }

            .card {
                border-radius: 0;
                margin-bottom: 0.5rem;
                box-shadow: none;
            }

            .card-header {
                padding: 0.75rem 0.5rem;
            }

            .card-body {
                padding: 0.75rem 0.5rem;
            }

            .form-row > .form-group {
                flex: 1 0 100%;
                padding: 0 0.25rem;
            }

            .table td, .table th {
                padding: 0.5rem;
                font-size: 0.875rem;
            }

            .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }

            .welcome-bar {
                flex-direction: column;
                align-items: flex-start;
                padding: 0.5rem;
                margin-bottom: 0.5rem;
            }

            .btn-group {
                display: flex;
                flex-direction: column;
                width: 100%;
            }

            .btn-group .btn {
                margin-bottom: 0.5rem;
                width: 100%;
            }

            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
            }

            .action-buttons .btn {
                width: 100%;
            }

            .inventory-card {
                border: 1px solid #dee2e6;
                border-radius: 0;
                margin-bottom: 0.5rem;
                padding: 0;
            }

            .inventory-card-header {
                background-color: #f8f9fa;
                padding: 0.5rem;
                border-bottom: 1px solid #dee2e6;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .inventory-card-body {
                padding: 0.5rem;
            }

            .inventory-card-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 0.5rem;
                border-bottom: 1px solid #eee;
                padding-bottom: 0.5rem;
            }

            .inventory-card-row:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }

            .inventory-card-label {
                font-weight: 500;
                font-size: 0.875rem;
            }

            .inventory-card-value {
                text-align: right;
                font-size: 0.875rem;
            }

            .mt-3 {
                margin-top: 0.75rem !important;
            }

            .mb-3 {
                margin-bottom: 0.75rem !important;
            }
        }

        /* Small button in table */
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }

        /* Action buttons alignment */
        .action-buttons {
            display: flex;
            gap: 0.25rem;
        }

        /* Badge styles */
        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            border-radius: 0.25rem;
            text-transform: uppercase;
        }





        /* Add this to your existing CSS */
.d-flex {
    display: flex;
}
.justify-content-between {
    justify-content: space-between;
}
.align-items-center {
    align-items: center;
}
.mb-3 {
    margin-bottom: 1rem;
}
.form-select-sm {
    width: auto;
}
.pagination {
    margin-bottom: 0;
}
    </style>


    <!-- Include necessary JS libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.0/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
</head>
<body class="pb-5">

   <div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-12">
            <div class="page-title p-3">
                <h1 class="fw-bold text-primary mb-0">CIU Inventory</h1>
            </div>
        </div>
</div>

        <!-- User info -->
        <div class="welcome-bar">
            <div>
                <i class="fas fa-user-circle me-2"></i>
                <span>Welcome: <strong><?php echo $_SESSION['username']; ?></strong></span>
            </div>
            <div class="mt-2 mt-md-0">
                <i class="fas fa-calendar-alt me-2"></i>
                <span><?php echo date('d M Y'); ?></span>
            </div>
        </div>

        <!-- Add New Inventory Form -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New CIU Inventory Record</h5>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addFormCollapse">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div class="card-body collapse show" id="addFormCollapse">
                <?php if(isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="entry_type" class="form-label">Entry Type</label>
                            <select name="entry_type" id="entry_type" class="form-select" required>
                                <option value="received">CIU Received</option>
                                <option value="export">CIU Export</option>
                                <option value="balance">CIU Balance</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="quantity" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="location" class="form-label">Location</label>
                            <select name="location" id="location_list" class="form-select" required>
                                <?php
                                if (mysqli_num_rows($location_dropdown_result) > 0) {
                                    while($row = mysqli_fetch_assoc($location_dropdown_result)) {
                                        echo '<option value="' . $row["location_name"] . '">' . $row["location_name"] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="entry_date" class="form-label">Date</label>
                            <input type="date" name="entry_date" id="entry_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="single_phase" class="form-label">Single Phase</label>
                            <input type="number" name="single_phase" id="single_phase" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="three_phase" class="form-label">Three Phase</label>
                            <input type="number" name="three_phase" id="three_phase" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="damaged" class="form-label">Damaged</label>
                            <input type="number" name="damaged" id="damaged" class="form-control">
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" name="add_inventory" class="btn btn-primary w-100 w-md-auto">
                            <i class="fas fa-save me-2"></i>Add Record
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Inventory Data</h5>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filterFormCollapse">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
            <div class="card-body collapse show" id="filterFormCollapse">
                <form method="get" action="" id="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="location_filter" class="form-label">Location</label>
                            <select name="location" id="location_filter" class="form-select">
                                <option value="">All Locations</option>
                                <?php foreach($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>"
                                        <?php echo (isset($_GET['location']) && $_GET['location'] == $loc) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="entry_type_filter" class="form-label">Entry Type</label>
                            <select name="entry_type" id="entry_type_filter" class="form-select">
                                <option value="">All Types</option>
                                <option value="received" <?php echo (isset($_GET['entry_type']) && $_GET['entry_type'] == 'received') ? 'selected' : ''; ?>>
                                    CIU Received
                                </option>
                                <option value="export" <?php echo (isset($_GET['entry_type']) && $_GET['entry_type'] == 'export') ? 'selected' : ''; ?>>
                                    CIU Export
                                </option>
                                <option value="balance" <?php echo (isset($_GET['entry_type']) && $_GET['entry_type'] == 'balance') ? 'selected' : ''; ?>>
                                    CIU Balance
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="row g-2">
                            <div class="col-12 col-md-auto">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Apply Filters
                                </button>
                            </div>
                            <div class="col-12 col-md-auto">
                                <button type="button" id="export-excel" class="btn btn-success w-100">
                                    <i class="fas fa-file-excel me-2"></i>Export to Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Inventory Data Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>CIU Inventory Data</h5>
                <p class="text-muted"><?php echo mysqli_num_rows($inventory_result); ?> Records</p>
            </div>
            <div class="card-body p-0">

              <!-- Add this above or below the table -->
              <div class="d-flex justify-content-between align-items-center mb-3">
                  <!-- Pagination Dropdown -->
                  <form method="get" action="" class="d-flex align-items-center">
                      <label for="items_per_page" class="me-2">Items per page:</label>
                      <select name="items_per_page" id="items_per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                          <option value="5" <?php echo $items_per_page == 5 ? 'selected' : ''; ?>>5</option>
                          <option value="10" <?php echo $items_per_page == 10 ? 'selected' : ''; ?>>10</option>
                          <option value="25" <?php echo $items_per_page == 25 ? 'selected' : ''; ?>>25</option>
                          <option value="50" <?php echo $items_per_page == 50 ? 'selected' : ''; ?>>50</option>
                          <option value="100" <?php echo $items_per_page == 100 ? 'selected' : ''; ?>>100</option>
                          <option value="all" <?php echo $items_per_page == 'all' ? 'selected' : ''; ?>>All</option>
                      </select>
                      <!-- Preserve other GET parameters (e.g., filters) -->
                      <?php foreach ($_GET as $key => $value): ?>
                          <?php if ($key !== 'items_per_page' && $key !== 'page'): ?>
                              <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                          <?php endif; ?>
                      <?php endforeach; ?>
                  </form>


              </div>

                <!-- Desktop view - visible on md screens and up -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered table-striped table-hover" id="inventory-table">
                        <thead>
                            <tr>
                                <th>Entry Type</th>
                                <th>Quantity</th>
                                <th>Location</th>
                                <th>Date</th>
                                <th>Single Phase</th>
                                <th>Three Phase</th>
                                <th>Damage</th>
                                <th>Added By</th>
                                <th>Added Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($inventory_result) > 0) {
                                while ($row = mysqli_fetch_assoc($inventory_result)) {
                                    $entry_type_display = '';
                                    $entry_type_class = '';

                                    switch ($row['entry_type']) {
                                        case 'received':
                                            $entry_type_display = 'CIU Received';
                                            $entry_type_class = 'text-success';
                                            break;
                                        case 'export':
                                            $entry_type_display = 'CIU Export';
                                            $entry_type_class = 'text-danger';
                                            break;
                                        case 'balance':
                                            $entry_type_display = 'CIU Balance';
                                            $entry_type_class = 'text-primary';
                                            break;
                                    }

                                    echo "<tr>";
                                    echo "<td class='" . $entry_type_class . "'>" . $entry_type_display . "</td>";
                                    echo "<td>" . ($row['quantity'] !== NULL ? $row['quantity'] : '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                                    echo "<td>" . date('d/m/Y', strtotime($row['entry_date'])) . "</td>";
                                    echo "<td>" . ($row['single_phase'] !== NULL ? $row['single_phase'] : '-') . "</td>";
                                    echo "<td>" . ($row['three_phase'] !== NULL ? $row['three_phase'] : '-') . "</td>";
                                    echo "<td>" . ($row['damaged'] !== NULL ? $row['damaged'] : '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                    echo "<td>" . date('d/m/Y H:i', strtotime($row['added_date'])) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='10' class='text-center'>No inventory data found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
<br>
<div class="row">
  <div class="col-12 offset-md-5">
  <!-- Pagination Links -->
  <nav aria-label="Page navigation">
      <ul class="pagination mb-0">
          <?php if ($current_page > 1): ?>
              <li class="page-item">
                  <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>" aria-label="Previous">
                      <span aria-hidden="true">&laquo;</span>
                  </a>
              </li>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                  <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
              </li>
          <?php endfor; ?>

          <?php if ($current_page < $total_pages): ?>
              <li class="page-item">
                  <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>" aria-label="Next">
                      <span aria-hidden="true">&raquo;</span>
                  </a>
              </li>
          <?php endif; ?>
      </ul>
  </nav>
</div>
</div>
<br>
                <!-- Mobile view - card-based layout for smaller screens -->
                <div class="d-md-none">
                    <?php
                    if (mysqli_num_rows($inventory_result) > 0) {
                        // Reset the result pointer
                        mysqli_data_seek($inventory_result, 0);

                        while ($row = mysqli_fetch_assoc($inventory_result)) {
                            $entry_type_display = '';
                            $entry_type_class = '';

                            switch ($row['entry_type']) {
                                case 'received':
                                    $entry_type_display = 'CIU Received';
                                    $entry_type_class = 'text-dark';
                                    break;
                                case 'export':
                                    $entry_type_display = 'CIU Export';
                                    $entry_type_class = 'text-danger';
                                    break;
                                case 'balance':
                                    $entry_type_display = 'CIU Balance';
                                    $entry_type_class = 'text-primary';
                                    break;
                            }
                            ?>
                            <div class="inventory-card">
                                <div class="inventory-card-header">
                                    <h6 class="<?php echo $entry_type_class; ?>"><?php echo $entry_type_display; ?></h6>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($row['entry_date'])); ?></small>
                                </div>
                                <div class="inventory-card-body">
                                    <div class="inventory-card-row">
                                        <div class="inventory-card-label">Location:</div>
                                        <div class="inventory-card-value"><?php echo htmlspecialchars($row['location']); ?></div>
                                    </div>
                                    <div class="inventory-card-row">
                                        <div class="inventory-card-label">Quantity:</div>
                                        <div class="inventory-card-value"><?php echo ($row['quantity'] !== NULL ? $row['quantity'] : '-'); ?></div>
                                    </div>
                                    <div class="inventory-card-row">
                                        <div class="inventory-card-label">Single Phase:</div>
                                        <div class="inventory-card-value"><?php echo ($row['single_phase'] !== NULL ? $row['single_phase'] : '-'); ?></div>
                                    </div>
                                    <div class="inventory-card-row">
                                        <div class="inventory-card-label">Three Phase:</div>
                                        <div class="inventory-card-value"><?php echo ($row['three_phase'] !== NULL ? $row['three_phase'] : '-'); ?></div>
                                    </div>
                                    <div class="inventory-card-row">
                                        <div class="inventory-card-label">Damaged:</div>
                                        <div class="inventory-card-value"><?php echo ($row['damaged'] !== NULL ? $row['damaged'] : '-'); ?></div>
                                    </div>
                                    <div class="inventory-card-row">
                                        <div class="inventory-card-label">Added By:</div>
                                        <div class="inventory-card-value"><?php echo htmlspecialchars($row['username']); ?></div>
                                    </div>
                                    <div class="inventory-card-row">
                                        <div class="inventory-card-label">Added Date:</div>
                                        <div class="inventory-card-value"><?php echo date('d/m/Y H:i', strtotime($row['added_date'])); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<div class='p-3 text-center'>No inventory data found</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    </div>
    <script>
        document.getElementById('items_per_page').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
    <script>


        // Set default date values if not set
        window.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(today.getDate() - 30);

            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const entryDateInput = document.getElementById('entry_date');

            // Set default date for entry form if not already filled
            if (!entryDateInput.value) {
                entryDateInput.valueAsDate = today;
            }

            // Only set filter dates if they're not already set from GET parameters
            if (!startDateInput.value && !window.location.search.includes('start_date')) {
                startDateInput.valueAsDate = thirtyDaysAgo;
            }

            if (!endDateInput.value && !window.location.search.includes('end_date')) {
                endDateInput.valueAsDate = today;
            }
        });

        // Export to Excel functionality
        document.getElementById('export-excel').addEventListener('click', function() {
            const table = document.getElementById('inventory-table');
            const wb = XLSX.utils.table_to_book(table, {sheet: "CIU Inventory"});
            const today = new Date().toISOString().slice(0, 10);
            XLSX.writeFile(wb, `CIU_Inventory_${today}.xlsx`);
        });

        // Initialize datepicker (if your Bootstrap version supports it)
        $(function() {
            // Toggle form sections
            $('.btn-toggle-form').click(function() {
                $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
            });
        });
    </script>
</body>
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>
</html>
