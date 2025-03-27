<?php
include 'connection.php'; // Include your database connection file
include 'header.php';
if ($_SESSION['role'] !== 'Manager') {
  session_unset();
  session_destroy();
  header("Location: index.php");
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
 ?>

<!-- Payment Report Content -->
<div class="row">
    <div class="col-12">
        <div class="page-title">
            <h1>Payment Report</h1>
            <p class="text-muted">Search for payment details by Meter SN, STS NO, or MSN.</p>
        </div>
    </div>
</div>

<!-- Search Form -->
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Search Payment Report</h5>
            </div>
            <div class="card-body">
                <form action="payment-report.php" method="GET">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="search" placeholder="Enter MS No. or STS No." required>
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

    // Query to fetch payment report information
    $sql = "SELECT * FROM prepaymentmode
            WHERE Meter_Plant_No = '$search'
            OR STS_No = '$search'
            OR Customer_No = '$search'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        // Loop through all matching rows
        while ($row = mysqli_fetch_assoc($result)) {
            ?>
            <!-- Display Payment Report Information in a Card -->
            <div class="row mt-4">
                <div class="col-md-8 offset-md-2">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Payment Report Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">

                                  <!--  <div class="form-group">
                                        <label>Meter Type</label>
                                        <input type="text" class="form-control" value="<?php echo $row['Meter_Type']; ?>" readonly>
                                    </div> -->

                                    <div class="form-group">
                                        <h6>Area</h6>
                                        <p> <?php echo $row['Area']; ?></p>
                                    </div>
                                    <div class="form-group">
                                        <h6>Location & Phase</h6>
                                        <p><?php echo $row['Location & Phase']; ?></p>
                                    </div>
                                    <div class="form-group">
                                        <h6>Tariff</h6>
                                        <p><?php echo $row['Tariff']; ?> </p>
                                    </div>

                                  <br>
                                    <div class="input-group">
                        <!-- Status with Icon -->
                        <?php if ($row['Payment_Mode'] == 'PrePaid') { ?>
                            <h6><i class="fas fa-check-circle text-success"></i> Status: <?php echo $row['Payment_Mode']; ?></h6>
                        <?php } else if ($row['Payment_Mode'] == 'PostPaid') { ?>
                            <h6><i class="fas fa-times-circle text-danger"></i> Status: <?php echo $row['Payment_Mode']; ?></h6>
                        <?php } else { ?>
                            <h6><i class="fas fa-exclamation-circle text-warning"></i> Status: <?php echo $row['Payment_Mode']; ?></h6>
                        <?php } ?>
                    </div><br>
                                </div>
                                <div class="col-md-6">

                                  <div class="form-group">
                                      <h6>STS NO</h6>
                                      <p><?php echo $row['STS_No']; ?></p>
                                  </div>
                                  <div class="form-group">
                                      <h6>Meter No</h6>
                                      <p><?php echo $row['Meter_Plant_No']; ?></p>
                                  </div>
                                  <div class="form-group">
                                      <h6>Prepayment Date</h6>
                                      <p> <?php echo $row['Prepayment Date']; ?> </p>
                                  </div> <br>
                                    <div class="input-group">
                                        <!-- Purchased Credit with Icon -->
                                        <?php if ($row['Purchased Credit'] == 'Yes') { ?>
                                            <h6><i class="fas fa-check-circle text-success"></i> Purchased Credit: <?php echo $row['Purchased Credit']; ?></h6>
                                        <?php } else if ($row['Purchased Credit'] == 'No') { ?>
                                            <h6><i class="fas fa-times-circle text-danger"></i> Purchased Credit: <?php echo $row['Purchased Credit']; ?></h6>
                                        <?php } else { ?>
                                            <h6><i class="fas fa-exclamation-circle text-warning"></i> Purchased Credit: <?php echo $row['Purchased Credit']; ?></h6>
                                        <?php } ?>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        // No results found
        echo '<div class="row mt-4">
                <div class="col-md-8 offset-md-2">
                    <div class="alert alert-danger">No payment report found for the provided details.</div>
                </div>
              </div>';
    }

    // Close the database connection
    mysqli_close($conn);
}
?>

<?php include 'footer.php'; ?>
