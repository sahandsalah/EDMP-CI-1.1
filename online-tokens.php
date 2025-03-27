<!-- Database Connection -->

<?php
include 'connection.php'; // Include your database connection file
include 'header.php';

// Session validation and security
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Manager' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Cashier')) {
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

// Add new token
if (isset($_POST['add_token'])) {
    $sts_no = mysqli_real_escape_string($conn, $_POST['sts_no']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $created_by = $_SESSION['userid']; // Current logged-in user
    $created_at = date('Y-m-d H:i:s'); // Current timestamp

    // Insert into main tokens history table
    $insert_query = "INSERT INTO tokens_history (sts_no, amount, phone, created_by, created_at)
                    VALUES ('$sts_no', '$amount', '$phone', '$created_by', '$created_at')";

    if (mysqli_query($conn, $insert_query)) {
        $token_id = mysqli_insert_id($conn);

        // Also insert into active tokens table
        $active_query = "INSERT INTO tokens_active (token_id, sts_no, amount, phone, created_by, created_at)
                        VALUES ('$token_id', '$sts_no', '$amount', '$phone', '$created_by', '$created_at')";

        if (mysqli_query($conn, $active_query)) {
            $success_message = "Token added successfully!";

            // Removed logActivity call
        } else {
            $error_message = "Error adding to active tokens: " . mysqli_error($conn);
        }
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }
}

// Mark token as paid (remove from active table)
if (isset($_GET['mark_paid']) && isset($_GET['id'])) {
    $token_id = mysqli_real_escape_string($conn, $_GET['id']);
    $user_id = $_SESSION['userid'];

    // Check if this user created the token
    $check_query = "SELECT * FROM tokens_active WHERE token_id = '$token_id' AND created_by = '$user_id'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // User is authorized to mark as paid
        $delete_query = "DELETE FROM tokens_active WHERE token_id = '$token_id'";

        if (mysqli_query($conn, $delete_query)) {
            $success_message = "Token marked as paid successfully!";

            // Removed logActivity call
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    } else {
        $error_message = "You can only mark tokens as paid that you created.";
    }
}

// Get active tokens
$active_tokens_query = "SELECT ta.*, u.username FROM tokens_active ta
                        LEFT JOIN users u ON ta.created_by = u.userid
                        ORDER BY ta.created_at DESC";
$active_tokens_result = mysqli_query($conn, $active_tokens_query);

// Get all tokens history
$history_tokens_query = "SELECT th.*, u.username FROM tokens_history th
                         LEFT JOIN users u ON th.created_by = u.userid
                         ORDER BY th.created_at DESC";
$history_tokens_result = mysqli_query($conn, $history_tokens_query);

?>
 <!-- Additional Mobile-Specific CSS -->
<style>
    @media (max-width: 767.98px) {
        .container-fluid {
            padding: 0 !important;
        }
        .card {
            border-radius: 0 !important;
            margin: 0 0 10px 0 !important;
        }
        .page-title {
            padding: 10px !important;
        }
        .card-header, .card-body {
            padding: 10px !important;
        }
        .table-responsive {
            margin: 0 !important;
        }
        .alert {
            margin: 0 0 10px 0 !important;
            border-radius: 0 !important;
        }
        .table td, .table th {
            padding: 8px 5px !important;
        }
        .btn-group .btn {
            padding: 0.25rem 0.5rem !important;
        }
    }

    .badge-active {
        background-color: #198754;
        color: white;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        border-radius: 0.25rem;
        display: inline-block;
    }

    .badge-paid {
        background-color: #6c757d;
        color: white;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        border-radius: 0.25rem;
        display: inline-block;
    }

    .nav-tabs .nav-link.active {
        font-weight: bold;
        border-bottom: 3px solid #0d6efd;
    }

    @media (max-width: 767.98px) {
    /* Container & Layout */
    .container-fluid {
        padding: 0 !important;
    }

    /* Card Styling */
    .card {
        border-radius: 0 !important;
        margin: 0 0 12px 0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
    }

    /* Headers & Titles */
    .page-title {
        padding: 12px 15px !important;
        margin-bottom: 0 !important;
    }

    .page-title h1 {
        font-size: 1.5rem !important;
        margin-bottom: 0 !important;
    }

    .card-header {
        padding: 12px 15px !important;
    }

    .card-body {
        padding: 15px !important;
    }

    /* Form Elements */
    .form-control, .btn {
        font-size: 0.95rem !important;
        height: auto !important;
        padding: 10px 12px !important;
    }

    .form-label {
        margin-bottom: 6px !important;
        font-size: 0.9rem !important;
    }

    /* Tables */
    .table-responsive {
        margin: 0 !important;
        border: none !important;
    }

    .table td, .table th {
        padding: 8px 10px !important;
        font-size: 0.9rem !important;
        vertical-align: middle !important;
    }

    /* Tab Navigation */
    .nav-tabs {
        padding: 0 10px !important;
    }

    .nav-tabs .nav-item {
        margin-bottom: 0 !important;
    }

    .nav-tabs .nav-link {
        padding: 10px 12px !important;
        font-size: 0.9rem !important;
    }

    /* Alerts */
    .alert {
        margin: 0 0 12px 0 !important;
        border-radius: 0 !important;
        padding: 12px 15px !important;
    }

    /* Actions & Buttons */
    .btn-group .btn {
        padding: 6px 10px !important;
        font-size: 0.85rem !important;
    }

    .btn-sm {
        padding: 4px 8px !important;
        font-size: 0.8rem !important;
    }

    /* Search Bar */
    .search-form {
        margin-bottom: 12px !important;
    }

    /* Spacing for better readability */
    .mb-3 {
        margin-bottom: 12px !important;
    }

    .py-4 {
        padding-top: 15px !important;
        padding-bottom: 15px !important;
    }
}

/* Keep your existing badge styles */
.badge-active {
    background-color: #198754;
    color: white;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    border-radius: 0.25rem;
    display: inline-block;
}

.badge-paid {
    background-color: #6c757d;
    color: white;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    border-radius: 0.25rem;
    display: inline-block;
}

.nav-tabs .nav-link.active {
    font-weight: bold;
    border-bottom: 3px solid #0d6efd;
}
</style>

<!-- Page Content -->
<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-12">
            <div class="page-title p-3">
                <h1 class="fw-bold text-primary">Online Tokens</h1>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if(isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show mx-0 my-2" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if(isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show mx-0 my-2" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row g-0">
        <!-- Add Token Form -->
        <div class="col-12">
            <div class="card shadow-sm mx-0 my-2">
                <div class="card-header bg-white">
                    <h5 class="card-title m-0">Add New Token</h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="sts_no" class="form-label">STS No.</label>
                            <input type="text" class="form-control" id="sts_no" name="sts_no" required>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Customer Phone No.</label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                        </div>
                        <button type="submit" name="add_token" class="btn btn-primary w-100">Add Token</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-0">
        <!-- Tokens Tabs and Tables -->
        <div class="col-12">
            <div class="card mx-0 my-2">
                <div class="card-header bg-white p-0">
                    <ul class="nav nav-tabs" id="tokenTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="active-tokens-tab" data-bs-toggle="tab"
                                    data-bs-target="#active-tokens" type="button" role="tab"
                                    aria-controls="active-tokens" aria-selected="true">
                                Active Tokens
                            </button>
                        </li>
                        <?php    if ($_SESSION['role'] == 'Manager' || $_SESSION['role'] == 'Admin') {
                               ?>
                               <li class="nav-item" role="presentation">
                                   <button class="nav-link" id="history-tokens-tab" data-bs-toggle="tab"
                                           data-bs-target="#history-tokens" type="button" role="tab"
                                           aria-controls="history-tokens" aria-selected="false">
                                       Token History
                                   </button>
                               </li>

                      <?php } else {

                      } ?>


                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content" id="tokenTabsContent">
                        <!-- Active Tokens Tab -->
                        <div class="tab-pane fade show active" id="active-tokens" role="tabpanel" aria-labelledby="active-tokens-tab">
                            <div class="p-3">
                                <div class="input-group search-form mb-3">
                                    <input type="text" class="form-control" id="search-active" placeholder="Search active tokens...">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped text-center m-0">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>STS No.</th>
                                            <th>Amount</th>
                                            <th>Phone</th>
                                            <th class="d-none d-md-table-cell">Created By</th>
                                            <th class="d-none d-md-table-cell">Date & Time</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($active_tokens_result) > 0): ?>
                                            <?php while($token = mysqli_fetch_assoc($active_tokens_result)): ?>
                                                <tr>
                                                    <td><?php echo $token['sts_no']; ?></td>
                                                    <td><?php echo number_format($token['amount'], 0); ?></td>
                                                    <td><?php echo $token['phone']; ?></td>
                                                    <td class="d-none d-md-table-cell"><?php echo $token['username']; ?></td>
                                                    <td class="d-none d-md-table-cell">
                                                        <?php echo date('M d, Y g:i A', strtotime($token['created_at'])); ?>
                                                        <div class="d-md-none small text-muted">
                                                            By: <?php echo $token['username']; ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if($token['created_by'] == $_SESSION['userid']): ?>
                                                            <a href="online-tokens.php?mark_paid=1&id=<?php echo $token['token_id']; ?>"
                                                            class="btn btn-sm btn-success"
                                                            onclick="return confirm('Mark this token as paid?')">
                                                                <i class="fas fa-check"></i> Mark Paid
                                                            </a>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-secondary" disabled>
                                                                <i class="fas fa-lock"></i> Not Owner
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">No active tokens found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Token History Tab -->
                        <div class="tab-pane fade" id="history-tokens" role="tabpanel" aria-labelledby="history-tokens-tab">
                            <div class="p-3">
                                <div class="input-group search-form mb-3">
                                    <input type="text" class="form-control" id="search-history" placeholder="Search token history...">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped text-center m-0">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>STS No.</th>
                                            <th>Amount</th>
                                            <th>Phone</th>
                                            <th class="d-none d-md-table-cell">Created By</th>
                                            <th>Date & Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($history_tokens_result) > 0): ?>
                                            <?php
                                            // Create an array of active token IDs for status checking
                                            $active_ids = array();
                                            mysqli_data_seek($active_tokens_result, 0);
                                            while($active = mysqli_fetch_assoc($active_tokens_result)) {
                                                $active_ids[] = $active['token_id'];
                                            }

                                            while($token = mysqli_fetch_assoc($history_tokens_result)):
                                                $is_active = in_array($token['id'], $active_ids);
                                            ?>
                                                <tr>
                                                    <td><?php echo $token['sts_no']; ?></td>
                                                    <td><?php echo number_format($token['amount'], 0); ?></td>
                                                    <td><?php echo $token['phone']; ?></td>
                                                    <td class="d-none d-md-table-cell"><?php echo $token['username']; ?></td>
                                                    <td>
                                                        <?php echo date('M d, Y g:i A', strtotime($token['created_at'])); ?>
                                                        <div class="d-md-none small text-muted">
                                                            By: <?php echo $token['username']; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if($is_active): ?>
                                                            <span class="text badge-active">Active</span>
                                                        <?php else: ?>
                                                            <span class="text badge-paid">Paid</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">No token history found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Search functionality for active tokens
    document.getElementById('search-active').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const table = document.querySelector('#active-tokens table');
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            let match = false;
            const cells = row.querySelectorAll('td');

            cells.forEach(cell => {
                if (cell.textContent.toLowerCase().includes(searchValue)) {
                    match = true;
                }
            });

            if (match) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Search functionality for history tokens
    document.getElementById('search-history').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const table = document.querySelector('#history-tokens table');
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            let match = false;
            const cells = row.querySelectorAll('td');

            cells.forEach(cell => {
                if (cell.textContent.toLowerCase().includes(searchValue)) {
                    match = true;
                }
            });

            if (match) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

<?php
// Close the database connection
mysqli_close($conn);
?>

<?php include 'footer.php'; ?>
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>
