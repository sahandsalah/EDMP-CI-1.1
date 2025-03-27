<?php
include 'connection.php'; // Include your database connection file
include 'header.php';
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
 // Fetch Total Meters
$total_meters_query = "SELECT COUNT(*) AS total_meters FROM prepaymentmode";
$total_meters_result = mysqli_query($conn, $total_meters_query);
$total_meters = mysqli_fetch_assoc($total_meters_result)['total_meters'];

// Fetch Total PrePaid Meters
$total_prepaid_query = "SELECT COUNT(*) AS total_prepaid FROM prepaymentmode WHERE Payment_Mode = 'PrePaid'";
$total_prepaid_result = mysqli_query($conn, $total_prepaid_query);
$total_prepaid = mysqli_fetch_assoc($total_prepaid_result)['total_prepaid'];

// Fetch Total PostPaid Meters
$total_postpaid_query = "SELECT COUNT(*) AS total_postpaid FROM prepaymentmode WHERE Payment_Mode = 'PostPaid'";
$total_postpaid_result = mysqli_query($conn, $total_postpaid_query);
$total_postpaid = mysqli_fetch_assoc($total_postpaid_result)['total_postpaid'];

// Fetch Total InProgress Meters
$total_inprogress_query = "SELECT COUNT(*) AS total_inprogress FROM prepaymentmode WHERE Payment_Mode = 'In Progress'";
$total_inprogress_result = mysqli_query($conn, $total_inprogress_query);
$total_inprogress = mysqli_fetch_assoc($total_inprogress_result)['total_inprogress'];

$total_active_query = "SELECT COUNT(*) AS total_active FROM tokens_active";
$total_active_result = mysqli_query($conn, $total_active_query);
$total_active = mysqli_fetch_assoc($total_active_result)['total_active'];
// Close the database connection
mysqli_close($conn);
?>

<!-- Dashboard Content -->
<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-12">
            <div class="page-title p-3">
            <h1 class="fw-bold text-primary">Dashboard</h1>
            <p class="text-muted">Welcome back, <?php echo $_SESSION['username']  ?></p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Total Meters -->
    <div class="col-md-6 col-lg-3 mb-4">
         <div class="card stat-card bg-dark text-white">
            <div class="card-body">
                <div class="stat-icon">
                    <i class="fas fa-tachometer-alt fa-2x"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $total_meters; ?></h3>
                    <p class="stat-text">Total Meters</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Total PrePaid Meters -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="stat-icon">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $total_prepaid; ?></h3>
                    <p class="stat-text">Total PrePaid Meters</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Total PostPaid Meters -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card stat-card bg-danger text-white">
            <div class="card-body">
                <div class="stat-icon">
                    <i class="fas fa-times-circle fa-2x"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $total_postpaid; ?></h3>
                    <p class="stat-text">Total PostPaid Meters</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Total InProgress Meters -->
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="stat-icon">
                    <i class="fas fa-spinner fa-2x"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $total_inprogress; ?></h3>
                    <p class="stat-text">Total InProgress Meters</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
  <div class="col-md-6 col-lg-3 mb-4">
      <div class="card stat-card bg-primary text-white">
          <div class="card-body">
              <div class="stat-icon">
                  <i class="fa-solid fa-money-bills"></i>
              </div>
              <div class="stat-content">
                  <h3 class="stat-number"><?php echo $total_active; ?></h3>
                  <p class="stat-text">Active Tokens in All Area</p>
              </div>
          </div>
      </div>
  </div>
</div>
</div>
<!-- Latest Activities Section (Remaining Code)
<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">Latest Activities</h5>
                <a href="#" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="activity-feed">
                    <div class="activity-item">
                        <div class="activity-icon bg-primary">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-text">New user <strong>Jessica Parker</strong> registered</p>
                            <p class="activity-time"><i class="far fa-clock"></i> 30 minutes ago</p>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon bg-success">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-text">Payment received from <strong>Robert Anderson</strong></p>
                            <p class="activity-time"><i class="far fa-clock"></i> 1 hour ago</p>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon bg-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-text">System alert: <strong>Disk space running low</strong></p>
                            <p class="activity-time"><i class="far fa-clock"></i> 2 hours ago</p>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon bg-info">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-text">System update <strong>v2.4.3</strong> completed</p>
                            <p class="activity-time"><i class="far fa-clock"></i> 3 hours ago</p>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon bg-danger">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-text">New support ticket <strong>#4578</strong> opened</p>
                            <p class="activity-time"><i class="far fa-clock"></i> 5 hours ago</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->

<?php include 'footer.php'; ?>
